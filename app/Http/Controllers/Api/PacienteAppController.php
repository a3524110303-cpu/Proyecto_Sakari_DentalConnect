<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CitaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Paciente;
use App\Models\Cita;
use App\Models\HorarioBloqueado;
use App\Models\Clinica;
use App\Models\Doctor;
use App\Models\Notificacion;
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
        $idClinica = Auth::user()->id_clinica;
        $clinica   = Clinica::find($idClinica);

        // Construir dirección legible para la app móvil
        $partesDireccion = array_filter([
            $clinica->calle,
            $clinica->ciudad,
            $clinica->municipio,
            $clinica->estado,
            $clinica->codigo_postal,
        ]);
        $clinica->direccion_completa = implode(', ', $partesDireccion);

        // URL de Google Maps: coordenadas exactas si las tiene, texto si no
        if (!empty($clinica->latitud) && !empty($clinica->longitud)) {
            $clinica->map_url = "https://www.google.com/maps/search/?api=1&query={$clinica->latitud},{$clinica->longitud}";
        } else {
            $clinica->map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($clinica->direccion_completa);
        }

        $doctores = \App\Models\User::where('id_clinica', $idClinica)
            ->where('rol', 'doctor')
            ->where('is_active', true)
            ->select('id_usuario', 'nombre_completo', 'email')
            ->get();

        return response()->json([
            'success'  => true,
            'clinica'  => $clinica,
            'doctores' => $doctores,
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

            // Llamada directa al método público de CitaController
            $citaController = new CitaController();
            $idDoctor = $citaController->buscarDoctorDisponible($idClinica, $fechaHora, $finHora);

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
        $user = Auth::user();
        $idClinica = $user ? $user->id_clinica : 1;

        $horariosLibres = [];
        // Define la hora en la que abre y cierra tu clínica
        $inicio = \Carbon\Carbon::parse($fecha . ' 09:00:00');
        $finDia = \Carbon\Carbon::parse($fecha . ' 18:00:00'); 
        
        // Llamada directa al método público de CitaController
        $citaController = new CitaController();

        while ($inicio < $finDia) {
            $finHora = $inicio->copy()->addMinutes(30); // Tramos de 30 mins

            // Preguntamos: ¿Hay algún doctor disponible para este bloque?
            $idDoctor = $citaController->buscarDoctorDisponible($idClinica, $inicio, $finHora);
            
            if ($idDoctor) {
                // Si sí hay un doctor libre, la hora se manda a la app móvil
                $horariosLibres[] = $inicio->format('h:i A');
            }
            
            $inicio->addMinutes(30);
        }

        return response()->json(['success' => true, 'data' => $horariosLibres]);
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
        $user = Auth::user();
        $idClinica = $user ? $user->id_clinica : 1;

        $fechasBloqueadas = [];
        $diasSemanaCerrados = [];

        // 1. BUSCAR FECHAS ESPECÍFICAS (Vacaciones, Feriados)
        try {
            $fechasBloqueadas = \Illuminate\Support\Facades\DB::table('horarios_bloqueados')
                ->where('id_clinica', $idClinica)
                ->whereDate('fecha_inicio', '>=', now()->startOfMonth())
                ->pluck('fecha_inicio')
                ->map(function($date) {
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                })->toArray();
        } catch (\Exception $e) {
            // Ignoramos si la tabla de vacaciones está vacía o no existe
        }

        // 2. BUSCAR DÍAS DE LA SEMANA
        try {
            $horarios = \Illuminate\Support\Facades\DB::table('horario_clinicas')
                ->where('id_clinica', $idClinica)
                ->get();

            $diasAbiertos = [];
            $mapaDias = [
                'lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'miércoles' => 3,
                'jueves' => 4, 'viernes' => 5, 'sabado' => 6, 'sábado' => 6, 'domingo' => 7,
                '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7
            ];

            foreach ($horarios as $h) {
                $estaAbierto = true;
                
                // Verificamos si el día está marcado como cerrado en la BD
                if (isset($h->activo) && $h->activo == 0) $estaAbierto = false;
                if (isset($h->estado) && $h->estado === 'inactivo') $estaAbierto = false;
                if (isset($h->hora_inicio) && $h->hora_inicio == '00:00:00') $estaAbierto = false;

                if ($estaAbierto) {
                    // Soportar si la columna se llama dia_semana o dia
                    $dia = isset($h->dia_semana) ? $h->dia_semana : (isset($h->dia) ? $h->dia : null);
                    
                    if ($dia) {
                        $diaStr = strtolower(trim((string)$dia));
                        if (isset($mapaDias[$diaStr])) {
                            $diasAbiertos[] = $mapaDias[$diaStr];
                        }
                    }
                }
            }

            if (!empty($diasAbiertos)) {
                // MATEMÁTICA: Los días cerrados son la diferencia entre los 7 días y los abiertos
                $diasSemanaCerrados = array_values(array_diff([1, 2, 3, 4, 5, 6, 7], $diasAbiertos));
            } else {
                // Si no hay nada en la BD, bloqueamos sábado y domingo por seguridad
                $diasSemanaCerrados = [6, 7];
            }

        } catch (\Exception $e) {
            $diasSemanaCerrados = [6, 7];
        }

        return response()->json([
            'success' => true,
            'data' => [
                // Usamos array_values para asegurar que se mande como arreglo limpio a Flutter
                'fechas_bloqueadas' => array_values($fechasBloqueadas),
                'dias_semana_cerrados' => array_values($diasSemanaCerrados)
            ]
        ]);
    }

    /**
     * Recibe la solicitud de reagenda desde la app móvil.
     * Guarda una nota en la cita y crea una notificación para el doctor.
     */
    public function solicitarReagenda(Request $request, $id)
    {
        $request->validate([
            'fecha'  => 'required|date_format:Y-m-d',
            'hora'   => 'required|date_format:H:i',
            'motivo' => 'nullable|string|max:500',
        ]);

        $cita = Cita::with(['doctor.usuario', 'paciente'])->where('id_cita', $id)->first();

        if (!$cita) {
            return response()->json(['success' => false, 'message' => 'Cita no encontrada.'], 404);
        }

        // Verificar que la cita pertenezca al paciente autenticado
        $paciente = Paciente::where('id_usuario', Auth::id())->first();
        if (!$paciente || $cita->id_paciente !== $paciente->id_paciente) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para modificar esta cita.'], 403);
        }

        // 1. Añadir nota de reagenda al historial de la cita
        $notaReagenda = "⚠️ SOLICITUD DE REAGENDA: El paciente solicita cambiar la cita para el "
            . $request->fecha . " a las " . $request->hora
            . ". Motivo: " . ($request->motivo ?? 'No especificado');
        $cita->notas = $notaReagenda . ($cita->notas ? "\n\n" . $cita->notas : '');
        $cita->save();

        // 2. Crear notificación para el doctor asignado
        $idUsuarioDoctor = optional($cita->doctor)->id_usuario;
        if ($idUsuarioDoctor) {
            $fechaCitaOriginal = Carbon::parse($cita->fecha_hora_inicio)->format('d/m/Y H:i');
            $nombrePaciente    = optional($paciente)->nombre . ' ' . optional($paciente)->apellido_paterno;

            Notificacion::create([
                'id_usuario' => $idUsuarioDoctor,
                'tipo'       => 'reagenda',
                'mensaje'    => "El paciente {$nombrePaciente} ha solicitado reagendar su cita del "
                    . "{$fechaCitaOriginal} para el {$request->fecha} a las {$request->hora}.",
                'estado'     => 'no_leida',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de reagenda enviada a la clínica con éxito.',
        ], 200);
    }

}
