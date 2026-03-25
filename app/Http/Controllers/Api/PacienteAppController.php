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
use Illuminate\Support\Facades\Hash;

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
    public function citasProximas()
    {
        $paciente = Paciente::where('id_usuario', Auth::id())->first();

        if (!$paciente) {
            return response()->json(['success' => false, 'message' => 'Paciente no encontrado.'], 404);
        }

        // Solo pedimos el doctor, para evitar errores de relaciones complejas
        $citas = Cita::with([
            'doctor.usuario',
            'archivos:id_archivo,id_cita,url_archivo,tipo,descripcion',
        ])
            ->where('id_paciente', $paciente->id_paciente)
            // Filtramos las que ya pasaron
            ->whereNotIn('estado_cita', ['cancelada', 'completada'])
            ->orderBy('fecha_hora_inicio', 'asc')
            ->get();

        $citas->transform(function ($cita) {
            $cuidados = $cita->archivos
                ->first(fn ($a) => $a->tipo === 'pdf' && $a->descripcion === 'cuidados_pdf');
            $tips = $cita->archivos
                ->first(fn ($a) => $a->tipo === 'pdf' && $a->descripcion === 'tips_pdf');

            $cita->cuidados_pdf_url = $cuidados
                ? route('storage.file', ['path' => ltrim(str_replace('public/', '', $cuidados->url_archivo), '/')])
                : ($cita->cuidados_pdf_url ?? null);

            $cita->tips_pdf_url = $tips
                ? route('storage.file', ['path' => ltrim(str_replace('public/', '', $tips->url_archivo), '/')])
                : ($cita->tips_pdf_url ?? null);

            return $cita;
        });

        return response()->json([
            'success' => true,
            'data' => $citas
        ], 200);
    }

    /**
     * Retorna citas concluidas y pasadas del paciente.
     */
    public function citasPasadas(Request $request)
    {
        $paciente = Paciente::where('id_usuario', Auth::id())->first();

        if (!$paciente)
            return response()->json(['success' => false, 'data' => []], 404);

        $citas = Cita::with([
            'servicio:id_servicio,nombre_servicio',
            'doctor:id_doctor,id_usuario,cedula_profesional',
            'archivos:id_archivo,id_cita,url_archivo,tipo,descripcion',
        ])
            ->where('id_paciente', $paciente->id_paciente)
            ->where('fecha_hora_inicio', '<', now())
            ->whereIn('estado_cita', ['completada', 'cancelada'])
            ->orderBy('fecha_hora_inicio', 'desc')
            ->take(15) // Limitado por desempeño
            ->get();

        $citas->transform(function ($cita) {
            $cuidados = $cita->archivos
                ->first(fn ($a) => $a->tipo === 'pdf' && $a->descripcion === 'cuidados_pdf');
            $tips = $cita->archivos
                ->first(fn ($a) => $a->tipo === 'pdf' && $a->descripcion === 'tips_pdf');

            $cita->cuidados_pdf_url = $cuidados
                ? route('storage.file', ['path' => ltrim(str_replace('public/', '', $cuidados->url_archivo), '/')])
                : ($cita->cuidados_pdf_url ?? null);

            $cita->tips_pdf_url = $tips
                ? route('storage.file', ['path' => ltrim(str_replace('public/', '', $tips->url_archivo), '/')])
                : ($cita->tips_pdf_url ?? null);

            return $cita;
        });

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

        // Construir dirección legible
        $partesDireccion = array_filter([
            $clinica->calle, $clinica->ciudad, $clinica->municipio, $clinica->estado, $clinica->codigo_postal,
        ]);
        $clinica->direccion_completa = implode(', ', $partesDireccion);

        // URL de Google Maps
        if (!empty($clinica->latitud) && !empty($clinica->longitud)) {
            $clinica->map_url = "https://www.google.com/maps/search/?api=1&query={$clinica->latitud},{$clinica->longitud}";
        } else {
            $clinica->map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($clinica->direccion_completa);
        }

        // 🔥 NUEVO: Buscamos los 7 días en la base de datos y los metemos a la clínica 🔥
        $clinica->horarios = \Illuminate\Support\Facades\DB::table('horarios_clinica')
            ->where('id_clinica', $idClinica)
            ->orderBy('dia_semana', 'asc')
            ->get();

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
        // Leemos TODAS las promociones reales que hayas guardado en tu base de datos
        $promociones = \App\Models\Publicidad::all();

        // Le enviamos la lista completa a la aplicación de Flutter
        return response()->json([
            'success' => true,
            'data' => $promociones
        ], 200);
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
        // 1. Validamos que lleguen los datos
        $request->validate([
            'id_servicio' => 'required|exists:catalogo_servicios,id_servicio',
            'fecha' => 'required|date_format:Y-m-d',
            'hora' => 'required|date_format:H:i',
        ]);

        try {
            $user = Auth::user();
            
            // 2. Buscamos al paciente PRIMERO (Así es más seguro)
            $paciente = \App\Models\Paciente::where('id_usuario', $user->id_usuario)->first();
            if (!$paciente) {
                return response()->json(['success' => false, 'message' => 'Paciente no encontrado.'], 404);
            }

            // ✅ EXTRAEMOS LA CLÍNICA DEL PACIENTE (Esto evita el error)
            $idClinica = $paciente->id_clinica ?? ($user->id_clinica ?? 1); 

            // 3. Comprobar si ya tiene una cita ese mismo día
            $tieneCitaHoy = \App\Models\Cita::where('id_paciente', $paciente->id_paciente)
                ->whereDate('fecha_hora_inicio', $request->fecha)
                ->whereIn('estado_cita', ['pendiente', 'confirmada'])
                ->exists();

            if ($tieneCitaHoy) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Solo puedes agendar una cita por día. Por favor elige otra fecha.'
                ], 400); 
            }

            // 4. Preparamos fechas
            $fechaHora = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->fecha . ' ' . $request->hora);
            $finHora = $fechaHora->copy()->addMinutes(30);
            
            // Asegurarnos de que no esté agendando en el pasado (por si alguien es muy rápido con los dedos)
            if ($fechaHora->isPast()) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No puedes agendar una cita en una hora que ya pasó.'
                ], 400);
            }

            $servicio = \App\Models\Servicio::findOrFail($request->id_servicio);

            // 5. Buscamos doctor libre
            $citaController = new \App\Http\Controllers\CitaController();
            $reflection = new \ReflectionMethod($citaController, 'buscarDoctorDisponible');
            $reflection->setAccessible(true);
            $idDoctor = $reflection->invoke($citaController, $idClinica, $fechaHora, $finHora);

            if (!$idDoctor) {
                return response()->json([
                    'success' => false, 
                    'message' => 'El horario seleccionado ya no está disponible.'
                ], 400);
            }

            // 6. Creamos la cita
            $cita = \App\Models\Cita::create([
                'id_clinica' => $idClinica,
                'id_paciente' => $paciente->id_paciente,
                'id_doctor' => $idDoctor,
                'id_servicio' => $request->id_servicio,
                'fecha_hora_inicio' => $fechaHora,
                'fecha_hora_fin' => $finHora,
                'estado_cita' => 'pendiente',
                'motivo' => $servicio->nombre_servicio,
                'costo_estimado' => $servicio->precio_base ?? 0,
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Cita agendada correctamente',
                'data' => $cita
            ]);

        } catch (\Exception $e) {
            // ✅ SI ALGO FALLA, TE DIRÁ EXACTAMENTE QUÉ LÍNEA O VARIABLE FUE:
            return response()->json([
                'success' => false, 
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
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

        // 1. Averiguar qué día de la semana es la fecha que nos piden (1 = Lunes, 7 = Domingo)
        $fechaCarbon = \Carbon\Carbon::parse($fecha);
        $diaSemanaNumero = $fechaCarbon->dayOfWeekIso; 

        // 2. Mapear ese número con los textos que podrían estar en tu Base de Datos
        $mapaDias = [
            1 => ['lunes', '1'],
            2 => ['martes', '2'],
            3 => ['miercoles', 'miércoles', '3'],
            4 => ['jueves', '4'],
            5 => ['viernes', '5'],
            6 => ['sabado', 'sábado', '6'],
            7 => ['domingo', '7']
        ];

        // 3. Valores por defecto por si el SaaS no tiene nada configurado
        $horaInicioBd = '09:00:00';
        $horaFinBd = '18:00:00';

        // 4. Buscar en la Base de Datos a qué hora abren y cierran ESE DÍA ESPECÍFICO
        try {
            $horariosClinica = \Illuminate\Support\Facades\DB::table('horarios_clinica')
                ->where('id_clinica', $idClinica)
                ->get();

            foreach ($horariosClinica as $h) {
                // Soportar diferentes nombres de columna (dia_semana o dia)
                $diaBD = isset($h->dia_semana) ? $h->dia_semana : (isset($h->dia) ? $h->dia : null);
                
                if ($diaBD) {
                    $diaStr = strtolower(trim((string)$diaBD));
                    // Si el día de la BD coincide con el día de la fecha solicitada...
                    if (in_array($diaStr, $mapaDias[$diaSemanaNumero])) {
                        if (isset($h->hora_inicio) && $h->hora_inicio != null) {
                            $horaInicioBd = $h->hora_inicio;
                        }
                        if (isset($h->hora_fin) && $h->hora_fin != null) {
                            $horaFinBd = $h->hora_fin;
                        }
                        break; // Ya encontramos el horario de ese día, salimos del ciclo
                    }
                }
            }
        } catch (\Exception $e) {
            // Si hay un error, usará los valores por defecto (09:00 a 18:00)
        }

        // 🔥 AHORA SÍ: Usamos las horas dinámicas que vienen de tu SaaS 🔥
        $horariosLibres = [];
        $inicio = \Carbon\Carbon::parse($fecha . ' ' . $horaInicioBd);
        $finDia = \Carbon\Carbon::parse($fecha . ' ' . $horaFinBd); 
        
        $citaController = new \App\Http\Controllers\CitaController();
        $reflection = new \ReflectionMethod($citaController, 'buscarDoctorDisponible');
        $reflection->setAccessible(true);

        $ahora = \Carbon\Carbon::now();

        // Armar los bloques de media hora
        while ($inicio < $finDia) {
            $finHora = $inicio->copy()->addMinutes(30); 
            
            // REGLA: Si la fecha seleccionada es HOY, y la hora ya pasó, lo saltamos
            if ($fecha === $ahora->toDateString() && $inicio <= $ahora) {
                $inicio->addMinutes(30);
                continue; 
            }

            // Preguntamos si hay doctores libres en ese bloque
            $idDoctor = $reflection->invoke($citaController, $idClinica, $inicio, $finHora);
            
            if ($idDoctor) {
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

        // 1. BUSCAR FECHAS ESPECÍFICAS Y CITAS DEL PACIENTE
        try {
            // A) Vacaciones de la clínica
            $vacaciones = \Illuminate\Support\Facades\DB::table('horario_bloqueados')
                ->where('id_clinica', $idClinica)
                ->whereDate('fecha_inicio', '>=', now()->startOfMonth())
                ->pluck('fecha_inicio')
                ->map(function($date) {
                    return \Carbon\Carbon::parse($date)->format('Y-m-d');
                })->toArray();
            
            $fechasBloqueadas = $vacaciones;

            // ✅ B) NUEVO: Buscamos si el paciente actual ya tiene citas activas y bloqueamos ESOS días
            $paciente = \App\Models\Paciente::where('id_usuario', $user->id_usuario)->first();
            if ($paciente) {
                $misCitas = \Illuminate\Support\Facades\DB::table('citas')
                    ->where('id_paciente', $paciente->id_paciente)
                    ->whereIn('estado_cita', ['pendiente', 'confirmada'])
                    ->whereDate('fecha_hora_inicio', '>=', now()->toDateString())
                    ->pluck('fecha_hora_inicio')
                    ->map(function($date) {
                        return \Carbon\Carbon::parse($date)->format('Y-m-d');
                    })->toArray();

                // Fusionamos las vacaciones con los días que el paciente ya apartó
                $fechasBloqueadas = array_unique(array_merge($fechasBloqueadas, $misCitas));
            }

        } catch (\Exception $e) {
            // Ignoramos si hay error
        }

        // 2. BUSCAR DÍAS DE LA SEMANA
        try {
            $horarios = \Illuminate\Support\Facades\DB::table('horarios_clinica')
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
    public function reagendarCita(Request $request, $id)
    {
        $request->validate([
            'fecha'  => 'required|date',
            'hora'   => 'required|string',
        ]);

        $cita = Cita::with(['doctor.usuario', 'paciente'])->where('id_cita', $id)->first();

        if (!$cita) {
            return response()->json(['success' => false, 'message' => 'Cita no encontrada.'], 404);
        }

        if (strtolower($cita->estado_cita) === 'confirmada') {
            return response()->json([
                'success' => false, 
                'message' => 'No es posible reagendar una cita que ya ha sido confirmada.'
            ], 400);
        }

        if (!in_array($cita->estado_cita, ['pendiente', 'confirmada'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede solicitar reagenda para citas pendientes o confirmadas.'
            ], 422);
        }

        $fechaHoraSolicitada = Carbon::createFromFormat('Y-m-d H:i', $request->fecha . ' ' . $request->hora);
        if ($fechaHoraSolicitada->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes solicitar reagenda en una fecha u hora que ya paso.'
            ], 422);
        }

        // Revisar si ya tiene una petición pendiente para evitar spam.
        $peticionPrevia = Notificacion::where('id_cita', $cita->id_cita)
            ->where('estado', 'no_leida')
            ->where('tipo', 'reagenda')
            ->exists();
        if ($peticionPrevia) {
            return response()->json(['success' => false, 'message' => 'Ya tienes una solicitud de reagenda en proceso.'], 400);
        }

        $fechaCitaOriginal = Carbon::parse($cita->fecha_hora_inicio)->format('d/m/Y H:i');

        $cita->reagenda_solicitada_at = now();
        $cita->reagenda_fecha_solicitada = $request->fecha;
        $cita->reagenda_hora_solicitada = $request->hora;
        $cita->reagenda_motivo = $request->motivo;
        $cita->reagenda_estatus = 'pendiente';
        $notaReagenda = "SOLICITUD DE REAGENDA: El paciente solicita cambiar la cita para el "
            . $request->fecha . " a las " . $request->hora
            . ". Motivo: " . ($request->motivo ?? 'No especificado');
        $cita->notas = $notaReagenda . ($cita->notas ? "\n\n" . $cita->notas : '');
        $cita->save();

        $idUsuarioDoctor = optional($cita->doctor)->id_usuario;

        if ($idUsuarioDoctor) {
            $paciente = $cita->paciente;
            $nombrePaciente = $paciente
                ? trim(($paciente->nombre ?? '') . ' ' . ($paciente->apellido_paterno ?? ''))
                : 'Paciente';
            if ($nombrePaciente === '') {
                $nombrePaciente = 'Paciente';
            }

            Notificacion::create([
                'id_usuario' => $idUsuarioDoctor,
                'id_cita'    => $cita->id_cita,
                'tipo'       => 'reagenda',
                'mensaje'    => "El paciente {$nombrePaciente} solicita reagendar su cita para el {$request->fecha} a las {$request->hora}.",
                'datos'      => [
                    'paciente'         => $nombrePaciente,
                    'fecha_original'   => $fechaCitaOriginal,
                    'nueva_fecha'      => $request->fecha,
                    'nueva_hora'       => $request->hora,
                    'nueva_fecha_hora' => $request->fecha . ' ' . $request->hora . ':00',
                ],
                'estado'     => 'no_leida',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud de reagenda enviada. La clinica debe aplicarla el mismo dia de la solicitud.',
        ], 200);
    }

    public function confirmarCita($id)
    {
        try {
            $cita = Cita::find($id);

            if (!$cita) {
                return response()->json(['success' => false, 'message' => 'Cita no encontrada.'], 404);
            }

            // Cambiamos el estado a 'confirmada'
            $cita->estado_cita = 'confirmada';
            $cita->save(); // Guardamos en la base de datos

            return response()->json([
                'success' => true,
                'message' => 'Cita confirmada correctamente.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al confirmar la cita.'], 500);
        }
    }

    // --- FUNCIÓN 1: ACTUALIZAR DATOS BÁSICOS ---
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        
        // Buscar al paciente relacionado a este usuario
        $paciente = Paciente::withoutGlobalScopes()
            ->where('id_usuario', $user->id_usuario)
            ->first();

        if (!$paciente) {
            return response()->json([
                'success' => false, 
                'message' => 'Paciente no encontrado.'
            ], 404);
        }

        // Validar qué datos se pueden actualizar (Añade los que consideres necesarios)
        $request->validate([
            'telefono' => 'nullable|string|max:20',
            'calle' => 'nullable|string|max:100',
            'numero_exterior' => 'nullable|string|max:20',
            'colonia' => 'nullable|string|max:100',
            'ciudad' => 'nullable|string|max:100',
        ]);

        // Actualizamos los datos
        $paciente->update($request->only([
            'telefono', 'calle', 'numero_exterior', 'colonia', 'ciudad'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente.',
            'paciente' => $paciente
        ], 200);
    }

    // --- FUNCIÓN 2: ACTUALIZAR CONTRASEÑA ---
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);

        $user = auth()->user();

        // Verificar que la contraseña actual ingresada coincida con la de la BD
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false, 
                'message' => 'La contraseña actual es incorrecta.'
            ], 400);
        }

        // Guardar la nueva contraseña
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true, 
            'message' => 'Tu contraseña se ha actualizado con éxito.'
        ], 200);
    }

}
