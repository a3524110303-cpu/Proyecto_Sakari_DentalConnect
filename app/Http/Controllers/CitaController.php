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
     * Almacena una nueva cita en la base de datos.
     *
     * Valida los datos recibidos, busca el precio del servicio y crea el registro de la cita.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_paciente' => 'required|exists:pacientes,id_paciente',
            'id_servicio' => 'required|exists:catalogo_servicios,id_servicio',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
            'duracion_minutos' => 'nullable|integer|in:15,30',
        ]);

        try {
            $user = Auth::user();
            $idClinica = $user->id_clinica;

            // Combinar fecha + hora en un solo datetime
            $fechaHora = Carbon::createFromFormat('Y-m-d H:i', $request->fecha . ' ' . $request->hora);
            $duracionMinutos = (int) ($request->input('duracion_minutos', 30));
            $finHora = $fechaHora->copy()->addMinutes($duracionMinutos);

            // Buscar el servicio para obtener el precio y nombre
            $servicio = Servicio::findOrFail($request->id_servicio);

            $idDoctor = $this->buscarDoctorDisponible($idClinica, $fechaHora, $finHora);

            if (!$idDoctor) {
                return redirect()->route('pacientes.index')
                    ->with('error', 'El horario seleccionado ya no está disponible para la duración elegida.')
                    ->withInput();
            }

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
            ]);

            return redirect()->route('pacientes.index')->with('success', '¡Cita agendada correctamente para el ' . $fechaHora->format('d/m/Y \a \l\a\s H:i') . '!');

        } catch (\Exception $e) {
            return redirect()->route('pacientes.index')->with('error', 'Error al agendar: ' . $e->getMessage())->withInput();
        }
    }

    public function buscarDoctorDisponible(int $idClinica, Carbon $inicio, Carbon $fin): ?int
    {
        $doctores = DB::table('doctores')
            ->join('usuarios_sistema', 'doctores.id_usuario', '=', 'usuarios_sistema.id_usuario')
            ->where('usuarios_sistema.id_clinica', $idClinica)
            ->pluck('doctores.id_doctor');

        foreach ($doctores as $idDoctor) {
            $tieneBloqueo = DB::table('horarios_bloqueados')
                ->where('id_doctor', $idDoctor)
                ->where('estatus_horario', 'activo')
                ->where('fecha_inicio', '<', $fin)
                ->where('fecha_fin', '>', $inicio)
                ->exists();

            if ($tieneBloqueo) {
                continue;
            }

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
