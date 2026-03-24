<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cita;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CitaController extends Controller
{
    public function index()
    {
        $idClinica = Auth::user()->id_clinica;

        $citas = Cita::with(['paciente', 'servicio'])
            ->where('id_clinica', $idClinica)
            ->get();

        if (request()->wantsJson()) {
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

            // duración opcional
            'duracion_minutos' => 'nullable|integer|in:15,30',

            // PDFs opcionales
            'cuidados_pdf' => 'nullable|file|mimes:pdf|max:2048',
            'tips_pdf' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        try {
            $user = Auth::user();
            $idClinica = $user->id_clinica;

            // 🕒 Crear fecha inicio y fin
            $fechaHora = Carbon::createFromFormat(
                'Y-m-d H:i',
                $request->fecha . ' ' . $request->hora
            );

            $duracionMinutos = (int) $request->input('duracion_minutos', 30);
            $finHora = $fechaHora->copy()->addMinutes($duracionMinutos);

            // 📦 Servicio
            $servicio = Servicio::findOrFail($request->id_servicio);

            // 👨‍⚕️ Buscar doctor disponible
            $idDoctor = $this->buscarDoctorDisponible($idClinica, $fechaHora, $finHora);

            if (!$idDoctor) {
                return back()
                    ->with('error', 'No hay doctores disponibles en ese horario.')
                    ->withInput();
            }

            // 🚫 Evitar duplicado exacto
            $duplicado = Cita::where('id_paciente', $request->id_paciente)
                ->where('id_clinica', $idClinica)
                ->where('estado_cita', 'pendiente')
                ->where('fecha_hora_inicio', $fechaHora)
                ->exists();

            if ($duplicado) {
                return back()
                    ->with('error', 'Ya existe una cita en ese horario.')
                    ->withInput();
            }

            // 📄 Guardar PDFs
            $rutaCuidados = null;
            $rutaTips = null;

            if ($request->hasFile('cuidados_pdf')) {
                $rutaCuidados = $request->file('cuidados_pdf')
                    ->store('cuidados', 'public');
            }

            if ($request->hasFile('tips_pdf')) {
                $rutaTips = $request->file('tips_pdf')
                    ->store('tips', 'public');
            }

            // ✅ Crear cita
            Cita::create([
                'id_clinica' => $idClinica,
                'id_paciente' => $request->id_paciente,
                'id_doctor' => $idDoctor,
                'id_servicio' => $request->id_servicio,
                'fecha_hora_inicio' => $fechaHora,
                'fecha_hora_fin' => $finHora,
                'estado_cita' => 'pendiente',
                'motivo' => $servicio->nombre_servicio,
                'costo_estimado' => $servicio->precio_base,
                'cuidados_pdf' => $rutaCuidados,
                'tips_pdf' => $rutaTips,
            ]);

            return redirect()->route('pacientes.index')
                ->with('success', '¡Cita agendada correctamente!');

        } catch (\Exception $e) {
            return back()
                ->with('error', 'Error al agendar: ' . $e->getMessage())
                ->withInput();
        }
    }

    // 👨‍⚕️ Doctor disponible
    public function buscarDoctorDisponible(int $idClinica, Carbon $inicio, Carbon $fin): ?int
    {
        $doctores = DB::table('doctores')
            ->join('usuarios_sistema', 'doctores.id_usuario', '=', 'usuarios_sistema.id_usuario')
            ->where('usuarios_sistema.id_clinica', $idClinica)
            ->pluck('doctores.id_doctor');

        foreach ($doctores as $idDoctor) {

            // 🚫 Bloqueos
            $tieneBloqueo = DB::table('horarios_bloqueados')
                ->where('id_doctor', $idDoctor)
                ->where('estatus_horario', 'activo')
                ->where('fecha_inicio', '<', $fin)
                ->where('fecha_fin', '>', $inicio)
                ->exists();

            if ($tieneBloqueo) continue;

            // 🚫 Empalmes
            $empalme = Cita::where('id_clinica', $idClinica)
                ->where('id_doctor', $idDoctor)
                ->whereIn('estado_cita', ['pendiente', 'confirmada'])
                ->where('fecha_hora_inicio', '<', $fin)
                ->where('fecha_hora_fin', '>', $inicio)
                ->exists();

            if (!$empalme) {
                return (int) $idDoctor;
            }
        }

        return null;
    }
}