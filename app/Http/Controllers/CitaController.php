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

    /**
     * NUEVO: Obtiene las horas que ya tienen cita para un día específico
     * Esto servirá para poner los botones en GRIS y BLOQUEARLOS.
     */
    public function obtenerHorasOcupadas(Request $request)
    {
        $request->validate(['fecha' => 'required|date_format:Y-m-d']);
        
        $idClinica = Auth::user()->id_clinica;

        // Buscamos citas que no estén canceladas en esa fecha
        $citas = Cita::where('id_clinica', $idClinica)
            ->whereDate('fecha_hora_inicio', $request->fecha)
            ->whereIn('estado_cita', ['pendiente', 'confirmada'])
            ->get();

        // Formateamos las horas para que coincidan con los textos de tus botones (ej: 9:00 AM)
        $ocupadas = $citas->map(function ($cita) {
            return Carbon::parse($cita->fecha_hora_inicio)->format('g:i A');
        });

        return response()->json($ocupadas);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_paciente' => 'required|exists:pacientes,id_paciente',
            'id_servicio' => 'required|exists:catalogo_servicios,id_servicio',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required', // Quitamos el formato estricto H:i porque puede venir como "9:00 AM"
            'duracion_minutos' => 'nullable|integer|in:15,30',
            'cuidados_pdf' => 'nullable|file|mimes:pdf|max:2048',
            'tips_pdf' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        try {
            $user = Auth::user();
            $idClinica = $user->id_clinica;

            // 🕒 Convertir "2026-04-01" + "9:00 AM" a un objeto Carbon
            $fechaHora = Carbon::parse($request->fecha . ' ' . $request->hora);

            $duracionMinutos = (int) $request->input('duracion_minutos', 30);
            $finHora = $fechaHora->copy()->addMinutes($duracionMinutos);

            $servicio = Servicio::findOrFail($request->id_servicio);

            // 👨‍⚕️ Buscar doctor disponible
            $idDoctor = $this->buscarDoctorDisponible($idClinica, $fechaHora, $finHora);

            if (!$idDoctor) {
                return back()
                    ->with('error', 'No hay doctores disponibles en ese horario.')
                    ->withInput();
            }

            // 🚫 Evitar duplicado exacto del mismo paciente
            $duplicado = Cita::where('id_paciente', $request->id_paciente)
                ->where('id_clinica', $idClinica)
                ->where('estado_cita', 'pendiente')
                ->where('fecha_hora_inicio', $fechaHora)
                ->exists();

            if ($duplicado) {
                return back()
                    ->with('error', 'Este paciente ya tiene una cita agendada exactamente a esta hora.')
                    ->withInput();
            }

            // 📄 Guardar PDFs
            $rutaCuidados = $request->hasFile('cuidados_pdf') 
                ? $request->file('cuidados_pdf')->store('cuidados', 'public') 
                : null;

            $rutaTips = $request->hasFile('tips_pdf') 
                ? $request->file('tips_pdf')->store('tips', 'public') 
                : null;

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

    public function buscarDoctorDisponible(int $idClinica, Carbon $inicio, Carbon $fin): ?int
    {
        $doctores = DB::table('doctores')
            ->join('usuarios_sistema', 'doctores.id_usuario', '=', 'usuarios_sistema.id_usuario')
            ->where('usuarios_sistema.id_clinica', $idClinica)
            ->pluck('doctores.id_doctor');

        foreach ($doctores as $idDoctor) {
            // 🚫 Bloqueos manuales
            $tieneBloqueo = DB::table('horarios_bloqueados')
                ->where('id_doctor', $idDoctor)
                ->where('estatus_horario', 'activo')
                ->where('fecha_inicio', '<', $fin)
                ->where('fecha_fin', '>', $inicio)
                ->exists();

            if ($tieneBloqueo) continue;

            // 🚫 Citas existentes (Empalmes)
            $empalme = Cita::where('id_doctor', $idDoctor)
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