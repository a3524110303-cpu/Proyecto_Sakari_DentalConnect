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

    /**
     * Agendar una nueva cita desde la aplicación móvil.
     */
    public function agendarCita(Request $request)
    {
        $request->validate([
            'id_servicio' => 'required|exists:catalogo_servicios,id_servicio',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
        ]);

        try {
            $user = Auth::user();
            $idClinica = $user->id_clinica;
            
            // Obtenemos el ID del paciente logueado
            $paciente = Paciente::where('id_usuario', $user->id_usuario)->first();
            if (!$paciente) {
                return response()->json(['success' => false, 'message' => 'Paciente no encontrado.'], 404);
            }

            // Construir las fechas
            $fechaHora = Carbon::createFromFormat('Y-m-d H:i', $request->fecha . ' ' . $request->hora);
            $finHora = $fechaHora->copy()->addMinutes(30); // Duración fija de 30 mins por ahora

            $servicio = \App\Models\Servicio::findOrFail($request->id_servicio);

            // Importamos el controlador de citas para reusar su lógica de buscar doctor disponible
            $citaController = new \App\Http\Controllers\CitaController();
            
            // Usamos Reflection para acceder al método privado (o pídele a tu equipo que lo haga public en CitaController)
            $reflection = new \ReflectionMethod($citaController, 'buscarDoctorDisponible');
            $reflection->setAccessible(true);
            $idDoctor = $reflection->invoke($citaController, $idClinica, $fechaHora, $finHora);

            if (!$idDoctor) {
                return response()->json([
                    'success' => false, 
                    'message' => 'El horario seleccionado ya no está disponible.'
                ], 400);
            }

            // Crear la cita
            $cita = Cita::create([
                'id_clinica' => $idClinica,
                'id_paciente' => $paciente->id_paciente,
                'id_doctor' => $idDoctor,
                'id_servicio' => $request->id_servicio,
                'fecha_hora_inicio' => $fechaHora,
                'fecha_hora_fin' => $finHora,
                'estado_cita' => 'pendiente',
                'motivo' => $servicio->nombre_servicio,
                'costo_estimado' => $servicio->precio_base,
            ]);

            return response()->json([
                'success' => true, 
                'message' => '¡Cita agendada correctamente!',
                'data' => $cita
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al agendar: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Retorna los horarios disponibles de un día específico (ej. 09:00 AM)
     */
    public function horasDisponiblesDia(Request $request)
{
    $fecha = $request->query('fecha');
    $paciente = Paciente::where('id_usuario', Auth::id())->first();
    $idClinica = $paciente ? $paciente->id_clinica : 1;

    // 1. Buscamos a qué horas ya hay citas ese día (Pendientes o Confirmadas)
    $citasOcupadas = Cita::where('id_clinica', $idClinica)
        ->whereDate('fecha_hora_inicio', $fecha)
        ->whereIn('estado_cita', ['pendiente', 'confirmada'])
        ->get()
        ->map(function ($cita) {
            // Extraemos solo la hora en formato 24h (Ej: "14:30")
            return \Carbon\Carbon::parse($cita->fecha_hora_inicio)->format('H:i');
        })->toArray();

    // 2. Generamos el horario laboral normal (Ej: 09:00 a 18:00)
    $horarios = [];
    $inicio = \Carbon\Carbon::parse($fecha . ' 09:00:00');
    $fin = \Carbon\Carbon::parse($fecha . ' 18:00:00');

    while ($inicio < $fin) {
        $horaStr24 = $inicio->format('H:i'); // Hora para comparar
        $horaStr12 = $inicio->format('h:i A'); // Hora bonita para el móvil

        // ✅ MAGIA: Solo mandamos la hora al móvil si NO está en $citasOcupadas
        if (!in_array($horaStr24, $citasOcupadas)) {
            $horarios[] = $horaStr12;
        }
        $inicio->addMinutes(30); // O el tiempo que dure cada consulta
    }

    return response()->json(['success' => true, 'data' => $horarios]);
}

    /**
     * Retorna los tratamientos en los que el paciente está actualmente
     */
    public function tratamientosActivos(Request $request)
    {
        $paciente = Paciente::where('id_usuario', Auth::id())->first();
        if (!$paciente) return response()->json(['success' => false, 'data' => []], 404);

        $citas = Cita::with('servicio')
            ->where('id_paciente', $paciente->id_paciente)
            ->where('fecha_hora_inicio', '>=', now()->subDays(60))
            ->orderBy('fecha_hora_inicio', 'desc')
            ->get()
            ->unique('id_servicio');

        $activos = [];
        foreach ($citas as $cita) {
            if ($cita->servicio) {
                $activos[] = [
                    'nombre' => $cita->servicio->nombre_servicio,
                    'fecha_inicio' => \Carbon\Carbon::parse($cita->fecha_hora_inicio)->format('M Y'),
                    // ✅ ENVIAMOS EL ESTADO REAL (confirmada, atendida, etc.)
                    'estado' => $cita->estado_cita 
                ];
            }
        }
        return response()->json(['success' => true, 'data' => array_values($activos)]);
    }
    
    public function diasBloqueados(Request $request)
    {
        $paciente = Paciente::where('id_usuario', Auth::id())->first();
        $idClinica = $paciente ? $paciente->id_clinica : 1;

        // Extraemos las fechas de tu tabla HorarioBloqueado del mes actual en adelante
        $diasBloqueados = HorarioBloqueado::where('id_clinica', $idClinica)
            ->whereDate('fecha_inicio', '>=', now()->startOfMonth())
            ->pluck('fecha_inicio')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })->toArray();

        return response()->json(['success' => true, 'data' => $diasBloqueados]);
    }

}
