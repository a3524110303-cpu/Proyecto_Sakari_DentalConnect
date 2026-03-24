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

            // 🔥 NUEVO: Validación PDF
            'cuidados_pdf' => 'nullable|mimes:pdf|max:2048'
        ]);

        try {
            $user = Auth::user();
            $idClinica = $user->id_clinica;

            // Combinar fecha + hora
            $fechaHora = Carbon::createFromFormat('Y-m-d H:i', $request->fecha . ' ' . $request->hora);

            // Servicio
            $servicio = Servicio::findOrFail($request->id_servicio);

            // Doctor
            $idDoctor = DB::table('doctores')
                ->join('usuarios_sistema', 'doctores.id_usuario', '=', 'usuarios_sistema.id_usuario')
                ->where('usuarios_sistema.id_clinica', $idClinica)
                ->value('doctores.id_doctor') ?? 1;

            // 🚫 Evitar duplicados
            $duplicado = Cita::where('id_paciente', $request->id_paciente)
                ->where('id_clinica', $idClinica)
                ->where('estado_cita', 'pendiente')
                ->where('fecha_hora_inicio', $fechaHora)
                ->exists();

            if ($duplicado) {
                return redirect()->route('pacientes.index')
                    ->with('error', 'Ya existe una cita pendiente para este paciente en la misma fecha y hora.')
                    ->withInput();
            }

            // 🔥 NUEVO: Guardar PDF
            $rutaPdf = null;

            if ($request->hasFile('cuidados_pdf')) {
                $rutaPdf = $request->file('cuidados_pdf')->store('cuidados', 'public');
            }

            // ✅ Crear cita
            Cita::create([
                'id_clinica' => $idClinica,
                'id_paciente' => $request->id_paciente,
                'id_doctor' => $idDoctor,
                'id_servicio' => $request->id_servicio,
                'fecha_hora_inicio' => $fechaHora,
                'fecha_hora_fin' => $fechaHora->copy()->addHour(),
                'estado_cita' => 'pendiente',
                'motivo' => $servicio->nombre_servicio,
                'costo_estimado' => $servicio->precio_base,

                // 🔥 NUEVO: guardar ruta del PDF
                'cuidados_pdf' => $rutaPdf,
            ]);

            return redirect()->route('pacientes.index')
                ->with('success', '¡Cita agendada correctamente para el ' . $fechaHora->format('d/m/Y \a \l\a\s H:i') . '!');

        } catch (\Exception $e) {
            return redirect()->route('pacientes.index')
                ->with('error', 'Error al agendar: ' . $e->getMessage())
                ->withInput();
        }
    }
}