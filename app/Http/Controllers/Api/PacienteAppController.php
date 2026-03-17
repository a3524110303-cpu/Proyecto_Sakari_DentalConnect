<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Paciente;
use App\Models\Cita;
use App\Models\HorarioBloqueado;
use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\Publicidad;
use Carbon\Carbon;

class PacienteAppController extends Controller
{
    /**
     * Obtiene todos los detalles médicos básicos y perfil del paciente.
     * Carga alergias y enfermedades de los campos TEXT libres 
     * SIN consumir los catálogos/relaciones pesadas.
     */
    public function perfil(Request $request)
    {
        // En base a la estructura, $request->user() nos da el modelo Auth
        // y este tiene el id correspondido
        $usuario = clone $request->user();

        $paciente = Paciente::where('id_usuario', $usuario->id_usuario)
            ->where('is_active', true)
            ->first();

        if (!$paciente) {
            return response()->json([
                'success' => false,
                'message' => 'El perfil de paciente no existe o está inactivo.'
            ], 404);
        }

        // Devolvemos la info limpia
        return response()->json([
            'success' => true,
            'paciente' => $paciente,
            'usuario_sistema' => [
                'email' => $usuario->email,
                'nombre_completo' => $usuario->nombre_completo,
            ]
        ]);
    }

    /**
     * Retorna citas del paciente ordenadas a futuro.
     */
    public function citasProximas(Request $request)
    {
        $paciente = Paciente::where('id_usuario', Auth::id())->first();

        if (!$paciente)
            return response()->json(['success' => false, 'data' => []], 404);

        $citas = Cita::with(['servicio:id_servicio,nombre_servicio', 'doctor:id_doctor,id_usuario,cedula_profesional'])
            ->where('id_paciente', $paciente->id_paciente)
            ->where('fecha_hora_inicio', '>=', now())
            ->whereIn('estado_cita', ['pendiente', 'confirmada'])
            ->orderBy('fecha_hora_inicio', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $citas]);
    }

    /**
     * Retorna citas concluidas y pasadas del paciente.
     */
    public function citasPasadas(Request $request)
    {
        $paciente = Paciente::where('id_usuario', Auth::id())->first();

        if (!$paciente)
            return response()->json(['success' => false, 'data' => []], 404);

        $citas = Cita::with(['servicio:id_servicio,nombre_servicio', 'doctor:id_doctor,id_usuario,cedula_profesional'])
            ->where('id_paciente', $paciente->id_paciente)
            ->where('fecha_hora_inicio', '<', now())
            ->whereIn('estado_cita', ['completada', 'cancelada'])
            ->orderBy('fecha_hora_inicio', 'desc')
            ->take(15) // Limitado por desempeño
            ->get();

        return response()->json(['success' => true, 'data' => $citas]);
    }

    /**
     * Muestra estado de cuenta genérico.
     */
    public function estadoCuenta(Request $request)
    {
        $paciente = Paciente::where('id_usuario', Auth::id())->first();

        if (!$paciente)
            return response()->json(['success' => false], 404);

        // Sumatoria directa SQL para evitar mapeos N+1 extensos
        $cargos = Cita::where('id_paciente', $paciente->id_paciente)
            ->sum('costo_estimado');

        // Cruzar y sumar en ingresos 
        // Ya que la tabla ingresos_caja tiene 'id_cita'.
        $abonos = \App\Models\IngresoCaja::whereIn('id_cita', function ($query) use ($paciente) {
            $query->select('id_cita')
                ->from('citas')
                ->where('id_paciente', $paciente->id_paciente);
        })->sum('monto');

        $saldo = $cargos - $abonos;

        return response()->json([
            'success' => true,
            'data' => [
                'total_cargos' => $cargos,
                'total_abonado' => $abonos,
                'saldo_pendiente' => max(0, $saldo)
            ]
        ]);
    }

    /**
     * Horarios disponibles del mes
     */
    public function horariosDisponibles(Request $request)
    {
        $fecha = Carbon::parse($request->input('fecha', now()));

        $idClinica = Auth::user()->id_clinica ?? 1;

        $citasDiasMes = Cita::where('id_clinica', $idClinica)
            ->whereMonth('fecha_hora_inicio', $fecha->month)
            ->whereYear('fecha_hora_inicio', $fecha->year)
            ->where('estado_cita', '!=', 'cancelada')
            ->selectRaw('DAY(fecha_hora_inicio) as dia, count(*) as c')
            ->groupBy('dia')
            ->pluck('c', 'dia')
            ->toArray();

        return response()->json([
            'success' => true,
            'fecha_solicitada' => $fecha->format('Y-m'),
            'disponibilidad_mes' => $citasDiasMes
        ]);
    }

    /**
     * Retorna datos de la clínica
     */
    public function clinicasYDoctores()
    {
        // En este SaaS, el paciente pertenece a un Auth User y ese a una clínica
        $idClinica = Auth::user()->id_clinica;

        $clinica = Clinica::find($idClinica);

        // Cargar usuarios con rol doctor en esa clinica
        $doctores = \App\Models\User::where('id_clinica', $idClinica)
            ->where('rol', 'doctor')
            ->where('is_active', true)
            ->select('id_usuario', 'nombre_completo', 'email')
            ->get();

        return response()->json([
            'success' => true,
            'clinica' => $clinica,
            'doctores' => $doctores
        ]);
    }

    /**
     * Carteleras/Promociones activas
     */
    public function publicidad()
    {
        $idClinica = Auth::user()->id_clinica ?? 1;

        $promociones = Publicidad::whereHas('usuario', function ($q) use ($idClinica) {
            $q->where('id_clinica', $idClinica);
        })
            ->where('activo', 1)
            ->where(function ($q) {
                $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
            })
            ->get();

        return response()->json(['success' => true, 'publicidad' => $promociones]);
    }
    /**
     * Retorna el catálogo de servicios/tratamientos de la clínica.
     */
    public function tratamientos(Request $request)
    {
        $idClinica = Auth::user()->id_clinica ?? 1;

        // Buscamos los servicios de la clínica del paciente
        $servicios = \App\Models\Servicio::where('id_clinica', $idClinica)
            ->select('id_servicio', 'nombre_servicio', 'precio_base', 'categoria')
            ->get();

        return response()->json($servicios);
    }
}
