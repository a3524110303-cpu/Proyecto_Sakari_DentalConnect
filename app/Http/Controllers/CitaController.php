<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Archivo;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CitaController extends Controller
{
    public function index()
    {
        $idClinica = Auth::user()->id_clinica;

        $citas = Cita::with(['paciente', 'servicio', 'archivos'])
            ->where('id_clinica', $idClinica)
            ->get();

        if (request()->wantsJson()) {
            $citas->transform(function ($cita) {
                $normalizarPath = function (?string $path): ?string {
                    if (!$path) {
                        return null;
                    }
                    return ltrim(str_replace('public/', '', $path), '/');
                };

                $cuidadosPath = $normalizarPath($cita->cuidados_pdf);
                if (!$cuidadosPath) {
                    $archivoCuidados = $cita->archivos
                        ->first(fn ($a) => $a->tipo === 'pdf' && $a->descripcion === 'cuidados_pdf');
                    $cuidadosPath = $normalizarPath($archivoCuidados->url_archivo ?? null);
                }

                $tipsPath = $normalizarPath($cita->tips_pdf);
                if (!$tipsPath) {
                    $archivoTips = $cita->archivos
                        ->first(fn ($a) => $a->tipo === 'pdf' && $a->descripcion === 'tips_pdf');
                    $tipsPath = $normalizarPath($archivoTips->url_archivo ?? null);
                }

                $cita->cuidados_pdf_url = $cuidadosPath
                    ? route('storage.file', ['path' => $cuidadosPath])
                    : null;
                $cita->tips_pdf_url = $tipsPath
                    ? route('storage.file', ['path' => $tipsPath])
                    : null;

                return $cita;
            });

            return response()->json($citas);
        }

        return view('citas.index', compact('citas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_paciente' => 'required|exists:pacientes,id_paciente',
            'id_servicio' => 'required|exists:catalogo_servicios,id_servicio',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
            'duracion_minutos' => 'nullable|integer|in:15,30',
            'cuidados_pdf' => 'nullable|file|mimes:pdf|max:2048',
            'tips_pdf' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        try {
            $user = Auth::user();
            $idClinica = $this->resolveClinicaId($user, $request->input('id_paciente'));
            if (!$idClinica) {
                return back()
                    ->with('error', 'No se pudo determinar la clinica para agendar la cita.')
                    ->withInput();
            }

            // 🕒 Crear fecha inicio y fin
            $fechaHora = Carbon::createFromFormat(
                'Y-m-d H:i',
                $request->fecha . ' ' . $request->hora
            );

            $duracionMinutos = (int) $request->input('duracion_minutos', 30);
            $finHora = $fechaHora->copy()->addMinutes($duracionMinutos);

            // 🚫 VALIDACIÓN 1: no permitir horas pasadas
            if ($fechaHora < Carbon::now()) {
                return back()
                    ->with('error', 'No puedes agendar en una hora pasada.')
                    ->withInput();
            }

            // 📦 Servicio
            $servicio = Servicio::findOrFail($request->id_servicio);

            // 🚫 VALIDACIÓN 2: evitar empalmes en TODA la clínica
            $yaOcupada = Cita::where('id_clinica', $idClinica)
                ->whereIn('estado_cita', ['pendiente', 'confirmada'])
                ->where('fecha_hora_inicio', '<', $finHora)
                ->where('fecha_hora_fin', '>', $fechaHora)
                ->exists();

            if ($yaOcupada) {
                return back()
                    ->with('error', 'Ese horario ya está ocupado.')
                    ->withInput();
            }

            // 👨‍⚕️ Buscar doctor disponible
            $idDoctor = $this->buscarDoctorDisponible($idClinica, $fechaHora, $finHora);

            if (!$idDoctor) {
                return back()
                    ->with('error', 'No hay doctores disponibles en ese horario.')
                    ->withInput();
            }

            // 🚫 VALIDACIÓN 3: duplicado exacto del paciente
            $duplicado = Cita::where('id_paciente', $request->id_paciente)
                ->where('id_clinica', $idClinica)
                ->where('estado_cita', 'pendiente')
                ->where('fecha_hora_inicio', $fechaHora)
                ->exists();

            if ($duplicado) {
                return back()
                    ->with('error', 'El paciente ya tiene una cita en ese horario.')
                    ->withInput();
            }

            // 📄 Guardar PDFs
            $rutaCuidados = null;
            $rutaTips = null;
            $diskArchivos = env('CITAS_FILESYSTEM_DISK', 'public');

            if ($request->hasFile('cuidados_pdf')) {
                $rutaCuidados = $request->file('cuidados_pdf')->store('cuidados', $diskArchivos);
            }

            if ($request->hasFile('tips_pdf')) {
                $rutaTips = $request->file('tips_pdf')->store('tips', $diskArchivos);
            }

            // ✅ Crear cita
            $dataCita = [
                'id_clinica' => $idClinica,
                'id_paciente' => $request->id_paciente,
                'id_doctor' => $idDoctor,
                'id_servicio' => $request->id_servicio,
                'fecha_hora_inicio' => $fechaHora,
                'fecha_hora_fin' => $finHora,
                'estado_cita' => 'pendiente',
                'motivo' => $servicio->nombre_servicio,
                'costo_estimado' => $servicio->precio_base,
            ];

            // Compatibilidad: si en producción aún no han corrido migraciones,
            // evitamos insertar columnas que no existen para no romper el flujo.
            if (Schema::hasColumn('citas', 'cuidados_pdf')) {
                $dataCita['cuidados_pdf'] = $rutaCuidados;
            }

            if (Schema::hasColumn('citas', 'tips_pdf')) {
                $dataCita['tips_pdf'] = $rutaTips;
            }

            $cita = Cita::create($dataCita);

            // Fuente de verdad de adjuntos: tabla archivos.
            if ($rutaCuidados) {
                Archivo::create([
                    'id_paciente' => $request->id_paciente,
                    'id_cita' => $cita->id_cita,
                    'url_archivo' => $rutaCuidados,
                    'tipo' => 'pdf',
                    'descripcion' => 'cuidados_pdf',
                ]);
            }

            if ($rutaTips) {
                Archivo::create([
                    'id_paciente' => $request->id_paciente,
                    'id_cita' => $cita->id_cita,
                    'url_archivo' => $rutaTips,
                    'tipo' => 'pdf',
                    'descripcion' => 'tips_pdf',
                ]);
            }

            return redirect()->route('pacientes.index')
                ->with('success', '¡Cita agendada correctamente!');

        } catch (\Throwable $e) {
            return back()
                ->with('error', 'Error al agendar: ' . $e->getMessage())
                ->withInput();
        }
    }

    // 👨‍⚕️ Buscar doctor disponible (MODIFICADO PARA BLOQUEO ESTRICTO POR CLÍNICA)
    public function buscarDoctorDisponible(int $idClinica, Carbon $inicio, Carbon $fin): ?int
    {
        // 🔥 REGLA DE ORO: Si ya hay UNA cita (pendiente o confirmada) en toda la clínica 
        // en este horario, bloqueamos la hora por completo, sin importar cuántos doctores haya.
        $citaEnClinica = Cita::where('id_clinica', $idClinica)
            ->whereIn('estado_cita', ['pendiente', 'confirmada'])
            ->where('fecha_hora_inicio', '<', $fin)
            ->where('fecha_hora_fin', '>', $inicio)
            ->exists();

        if ($citaEnClinica) {
            // El horario ya está ocupado físicamente en la clínica, negamos el espacio.
            return null; 
        }

        // Si la clínica está totalmente libre, buscamos un doctor que no tenga 
        // un "bloqueo manual" (como horario de comida o vacaciones)
        $doctores = DB::table('doctores')
            ->join('usuarios_sistema', 'doctores.id_usuario', '=', 'usuarios_sistema.id_usuario')
            ->where('usuarios_sistema.id_clinica', $idClinica)
            ->pluck('doctores.id_doctor');

        foreach ($doctores as $idDoctor) {
            // 🚫 Validar bloqueos manuales del doctor
            $tieneBloqueo = DB::table('horarios_bloqueados')
                ->where('id_doctor', $idDoctor)
                ->where('estatus_horario', 'activo')
                ->where('fecha_inicio', '<', $fin)
                ->where('fecha_fin', '>', $inicio)
                ->exists();

            if (!$tieneBloqueo) {
                // Asignamos al primer doctor que esté disponible
                return (int) $idDoctor; 
            }
        }

        return null;
    }

    // 🔥 ENDPOINT PARA FRONTEND (horas ocupadas)
  public function horasOcupadas(Request $request)
{
    $fecha = $request->input('fecha');
    $user = Auth::user();
    $idClinica = $this->resolveClinicaId($user);

    if (empty($fecha) || !$idClinica) {
        return response()->json([
            'ocupadas' => [],
            'horas_ocupadas' => [],
        ]);
    }

    $citas = Cita::whereDate('fecha_hora_inicio', $fecha)
        ->where('id_clinica', $idClinica)
        ->whereIn('estado_cita', ['pendiente', 'confirmada'])
        ->get();

    $horasOcupadas = [];

    foreach ($citas as $cita) {

        $inicio = Carbon::parse($cita->fecha_hora_inicio);
        $fin = Carbon::parse($cita->fecha_hora_fin);

        while ($inicio < $fin) {
            $horasOcupadas[] = $inicio->format('H:i');
            $inicio->addMinutes(15); // 🔥 clave
        }
    }

    return response()->json([
        'ocupadas' => $horasOcupadas,
        'horas_ocupadas' => $horasOcupadas,
    ]);
}

    private function resolveClinicaId($user, ?int $idPaciente = null): ?int
    {
        if ($user && !empty($user->id_clinica)) {
            return (int) $user->id_clinica;
        }

        if ($idPaciente) {
            $paciente = Paciente::find($idPaciente);
            if ($paciente && !empty($paciente->id_clinica)) {
                return (int) $paciente->id_clinica;
            }
        }

        if ($user) {
            $idUsuario = $user->id_usuario ?? $user->id ?? null;
            if ($idUsuario) {
                $paciente = Paciente::where('id_usuario', $idUsuario)->first();
                if ($paciente && !empty($paciente->id_clinica)) {
                    return (int) $paciente->id_clinica;
                }
            }
        }

        return null;
    }
}