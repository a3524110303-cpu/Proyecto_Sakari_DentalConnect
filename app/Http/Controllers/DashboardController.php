<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\IngresoCaja;
use App\Models\Inventario;
use App\Models\Notificacion;
use App\Models\Servicio;
use App\Models\Odontograma;
use App\Models\SeguimientoClinico;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

    /**
    * Construye el dashboard principal de la clinica autenticada.
    *
    * Recolecta metricas rapidas y lista de citas pendientes para pintar
    * la vista inicial del panel operativo.
     */
    public function index()
    {
        $user = Auth::user();
        $idClinica = $user->id_clinica;

        $hoy = Carbon::today();
        $ahora = Carbon::now();

        // Citas futuras
        $citasFuturas = Cita::with(['paciente','servicio'])
            ->where('id_clinica',$idClinica)
            ->where('fecha_hora_inicio','>=',$ahora)
            ->whereIn('estado_cita', ['pendiente', 'confirmada'])
            ->orderBy('fecha_hora_inicio','asc')
            ->get();

        // Citas vencidas
        $citasVencidas = Cita::with(['paciente','servicio'])
            ->where('id_clinica',$idClinica)
            ->where('fecha_hora_inicio','<',$ahora)
            ->whereIn('estado_cita', ['pendiente', 'confirmada'])
            ->orderBy('fecha_hora_inicio','desc')
            ->get();

        $proximasCitas = $citasFuturas->concat($citasVencidas)->take(15);

        // Citas de hoy
        $citasHoyCount = Cita::where('id_clinica',$idClinica)
            ->whereDate('fecha_hora_inicio',$hoy)
            ->whereIn('estado_cita', ['pendiente', 'confirmada'])
            ->count();

        // Pacientes activos
        $totalPacientes = Paciente::whereHas('usuario',function($q) use ($idClinica){
            $q->where('id_clinica',$idClinica);
        })
        ->where('is_active',true)
        ->count();

        // Ingresos del mes
        $ingresosMes = IngresoCaja::where('id_clinica',$idClinica)
            ->whereMonth('fecha_ingreso',$hoy->month)
            ->whereYear('fecha_ingreso',$hoy->year)
            ->sum('monto');

        // Inventario bajo
        $itemsBajoStock = Inventario::where('id_clinica',$idClinica)
            ->where('stock','<',5)
            ->orderBy('stock','asc')
            ->take(5)
            ->get();

        // Notificaciones
        $notificacionesPendientes = Notificacion::where('id_usuario',$user->id_usuario)
            ->where('estado','pendiente')
            ->count();

        // Servicios
        $servicios = Servicio::where('id_clinica',$idClinica)
            ->orderBy('nombre_servicio')
            ->get();

        return view('dashboard',compact(
            'proximasCitas',
            'citasHoyCount',
            'totalPacientes',
            'ingresosMes',
            'itemsBajoStock',
            'notificacionesPendientes',
            'servicios'
        ));
    }



    /**
        * Retorna toda la data del modal de detalle de cita en formato JSON.
        *
        * Incluye informacion clinica del paciente, historial paginado,
        * resumen financiero y registros del odontograma.
     */
    public function obtenerDatosModal($idCita)
    {

        $cita = Cita::with(['paciente','servicio','ingresos'])->findOrFail($idCita);

        $p = $cita->paciente;

        $costoTotal = floatval($cita->costo_estimado ?? 0);
        $totalPagado = $cita->ingresos ? $cita->ingresos->sum('monto') : 0;
        $saldo = max(0,$costoTotal-$totalPagado);

        $pacienteData = null;

        if($p){

            $edad = $p->fecha_nacimiento ? Carbon::parse($p->fecha_nacimiento)->age : null;

            $sexoMap = [
                'M'=>'Masculino',
                'F'=>'Femenino',
                'O'=>'Otro'
            ];

            $pacienteData = [

                'id_paciente'=>$p->id_paciente,
                'nombres'=>$p->nombre,
                'paterno'=>$p->apellido_paterno,
                'materno'=>$p->apellido_materno,
                'edad'=>$edad ? $edad.' años':'N/A',
                'edad_numero'=>$edad,
                'sexo'=>$sexoMap[$p->sexo] ?? $p->sexo ?? 'N/A',
                'telefono'=>$p->telefono,
                'tipo_sangre'=>$p->tipo_sangre,
                'peso'=>$p->peso ? $p->peso.' kg':'N/A',
                'alergias'=>$p->alergias ?? 'Ninguna registrada',
                'enfermedades'=>$p->enfermedades_cronicas ?? 'Ninguna registrada'

            ];

        }

        $citasPaciente = Cita::with(['servicio', 'ingresos'])
            ->where('id_paciente', $p->id_paciente)
            ->orderBy('fecha_hora_inicio', 'desc')
            ->get();

        $filasTabla = [];
        foreach ($citasPaciente as $c) {
            $pagadoCita = $c->ingresos ? $c->ingresos->sum('monto') : 0;
            $filasTabla[] = [
                'id_cita' => $c->id_cita,
                'dia' => Carbon::parse($c->fecha_hora_inicio)->format('d/m/Y'),
                'hora' => Carbon::parse($c->fecha_hora_inicio)->format('h:i A') . ' – ' . Carbon::parse($c->fecha_hora_fin)->format('h:i A'),
                'seguimiento' => $c->motivo ?? ($c->servicio?->nombre_servicio ?? 'Consulta'),
                'abono' => number_format($pagadoCita, 2),
                'estado' => $c->estado_cita ?? 'Pendiente'
            ];
        }

        $finanzas = [

            'total'=>number_format($costoTotal,2),
            'pagado'=>number_format($totalPagado,2),
            'restante'=>number_format($saldo,2)

        ];

        $fechaCita = [

            'mes'=>(int)Carbon::parse($cita->fecha_hora_inicio)->format('m'),
            'anio'=>(int)Carbon::parse($cita->fecha_hora_inicio)->format('Y')

        ];

        $odontograma = Odontograma::where('id_paciente',$p?->id_paciente)
            ->orderBy('id_odontograma','desc')
            ->get();

        return response()->json([

            'success'=>true,
            'paciente'=>$pacienteData,
            'filas_tabla'=>$filasTabla,
            'finanzas'=>$finanzas,
            'fecha_cita'=>$fechaCita,
            'odontograma'=>$odontograma,
            'ingresos'=>$cita->ingresos

        ]);

    }



    /**
        * Actualiza estado, costo, agenda y notas de una cita.
        *
        * Si cambia la fecha/hora, valida disponibilidad real de doctores
        * antes de persistir para evitar doble asignacion.
     */
    public function actualizarCita(Request $request,$idCita)
    {

        $cita = Cita::findOrFail($idCita);
        $idClinica = Auth::user()->id_clinica;
        $esReagendaMovilPendiente =
            $cita->reagenda_estatus === 'pendiente'
            && !empty($cita->reagenda_solicitada_at)
            && !empty($cita->reagenda_fecha_solicitada);

        if($request->filled('estado_cita')){
            $cita->estado_cita=$request->estado_cita;
        }

        if($request->filled('costo_estimado')){
            $cita->costo_estimado=$request->costo_estimado;
        }

        if($request->filled('nueva_fecha')){

            if ($esReagendaMovilPendiente) {
                $fechaSolicitud = Carbon::parse($cita->reagenda_solicitada_at)->toDateString();
                $hoy = now()->toDateString();

                if ($fechaSolicitud !== $hoy) {
                    $cita->reagenda_estatus = 'expirada';
                    $cita->save();

                    return response()->json([
                        'success' => false,
                        'message' => 'La solicitud de reagenda expiro. Solo se puede aplicar el mismo dia en que el paciente la envio.'
                    ], 422);
                }
            }

            $hora=$request->filled('nueva_hora')
                ? $request->nueva_hora
                : (
                    $esReagendaMovilPendiente && !empty($cita->reagenda_hora_solicitada)
                        ? Carbon::parse($cita->reagenda_hora_solicitada)->format('H:i')
                        : Carbon::parse($cita->fecha_hora_inicio)->format('H:i')
                );

            if ($esReagendaMovilPendiente) {
                $horaSolicitada = !empty($cita->reagenda_hora_solicitada)
                    ? Carbon::parse($cita->reagenda_hora_solicitada)->format('H:i')
                    : null;

                if (
                    $request->nueva_fecha !== $cita->reagenda_fecha_solicitada
                    || ($horaSolicitada !== null && $hora !== $horaSolicitada)
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Para una reagenda solicitada desde la app, solo se puede aplicar la fecha y hora solicitadas por el paciente.'
                    ], 422);
                }
            }

            $duracionMinutos = (int) $request->input('nueva_duracion_minutos', 30);
            if (!in_array($duracionMinutos, [15, 30], true)) {
                $duracionMinutos = 30;
            }

            $nuevoInicio = Carbon::createFromFormat('Y-m-d H:i', $request->nueva_fecha.' '.$hora);
            $nuevoFin = $nuevoInicio->copy()->addMinutes($duracionMinutos);

            $idDoctorDisponible = $this->buscarDoctorDisponible($idClinica, $nuevoInicio, $nuevoFin, (int) $cita->id_cita);

            if (!$idDoctorDisponible) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay doctores disponibles para ese horario y duración.'
                ], 422);
            }

            $cita->id_doctor = $idDoctorDisponible;
            $cita->fecha_hora_inicio=$nuevoInicio;
            $cita->fecha_hora_fin=$nuevoFin;

            if ($esReagendaMovilPendiente) {
                $cita->reagenda_estatus = 'aplicada';
            }

        }

        $cita->save();

        if($request->filled('notas_seguimiento')){

            SeguimientoClinico::create([

                'id_cita'=>$cita->id_cita,
                'observaciones'=>$request->notas_seguimiento

            ]);

            $cita->motivo=$request->notas_seguimiento;
            $cita->save();

        }

        if($request->filled('monto_abono') && $request->monto_abono>0){

            IngresoCaja::create([

                'id_clinica'=>Auth::user()->id_clinica ?? 1,
                'id_cita'=>$cita->id_cita,
                'monto'=>$request->monto_abono,
                'fecha_ingreso'=>now(),
                'metodo'=>'efectivo',
                'descripcion'=>'Abono en cita: '.$cita->motivo

            ]);

        }

        return response()->json([

            'success'=>true,
            'message'=>'Cita actualizada correctamente'

        ]);

    }



    /**
        * Marca una cita como completada.
        *
        * Se usa desde el flujo rapido del dashboard para retirar la cita
        * de pendientes sin abrir formularios adicionales.
     */
    public function completarCita($idCita)
    {

        $cita=Cita::findOrFail($idCita);

        $cita->estado_cita='completada';
        $cita->save();

        return response()->json([

            'success'=>true,
            'message'=>'Cita marcada como completada.'

        ]);

    }



    /**
        * Calcula disponibilidad diaria del mes para el mini-calendario.
        *
        * Devuelve color y clickabilidad por dia considerando horarios,
        * capacidad por doctores, bloqueos y fechas pasadas.
     */
    public function obtenerDisponibilidadMes(Request $request)
    {

        $mes=$request->input('mes',Carbon::now()->month);
        $anio=$request->input('anio',Carbon::now()->year);
        $idClinica=Auth::user()->id_clinica;

        $diasDelMes=Carbon::createFromDate($anio,$mes,1)->daysInMonth;
        $eventos=[];

        $horariosClinica = \App\Models\HorarioClinica::where('id_clinica', $idClinica)->get()->keyBy('dia_semana');

        for($i=1;$i<=$diasDelMes;$i++){

            $fecha=Carbon::createFromDate($anio,$mes,$i);
            $diaSemana = $fecha->dayOfWeek;
            $horarioDia = $horariosClinica->get($diaSemana);
            $clinicaAbierta = $horarioDia && $horarioDia->activo;

            $estado='verde';
            $clickable=true;
            $horaInicioModal = '08:00';
            $horaFinModal = '20:00';

            if (!$clinicaAbierta) {
                $estado='rojo';
                $clickable=false;
            } else {
                $horaInicioModal = Carbon::parse($horarioDia->hora_inicio ?: '08:00:00')->format('H:i');
                $horaFinModal = Carbon::parse($horarioDia->hora_fin ?: '20:00:00')->format('H:i');

                $resumen = $this->calcularSlotsOcupadosPorCapacidad($idClinica, $fecha->toDateString(), $horaInicioModal, $horaFinModal, 15);
                $totalSlots = $resumen['total_slots'];
                $slotsOcupados = count($resumen['horas_ocupadas']);

                if ($totalSlots <= 0 || $slotsOcupados >= $totalSlots) {
                    $estado='rojo';
                    $clickable=false;
                } elseif ($slotsOcupados > 0) {
                    $estado='amarillo';
                }
            }

            if($fecha->isPast() && !$fecha->isToday()){
                $estado='gris';
                $clickable=false;
            }

            $eventos[$i]=[
                'estado'=>$estado,
                'clickable'=>$clickable,
                'hora_inicio'=>$horaInicioModal,
                'hora_fin'=>$horaFinModal
            ];

        }

        return response()->json($eventos);

    }



    /**
        * Devuelve slots ocupados de una fecha especifica.
        *
        * Este endpoint alimenta el widget de reagendado para bloquear
        * horas no disponibles al momento de seleccionar horario.
     */
    public function horasOcupadas(Request $request)
    {
        try {
            $fecha = $request->input('fecha');
            $user = Auth::user();
            
            if (!$fecha || !$user) {
                return response()->json([
                    'horas_ocupadas' => []
                ]);
            }
            
            $idClinica = $user->id_clinica;

            $horarioDia = \App\Models\HorarioClinica::where('id_clinica', $idClinica)
                ->where('dia_semana', Carbon::parse($fecha)->dayOfWeek)
                ->first();

            if (!$horarioDia || !$horarioDia->activo) {
                return response()->json([
                    'horas_ocupadas' => [],
                    'intervalo_minutos' => 15,
                    'duraciones_permitidas' => [15, 30]
                ]);
            }

            $horaInicio = Carbon::parse($horarioDia->hora_inicio ?: '08:00:00')->format('H:i');
            $horaFin = Carbon::parse($horarioDia->hora_fin ?: '20:00:00')->format('H:i');

            $resumen = $this->calcularSlotsOcupadosPorCapacidad($idClinica, $fecha, $horaInicio, $horaFin, 15);

            return response()->json([
                'horas_ocupadas' => $resumen['horas_ocupadas'],
                'intervalo_minutos' => 15,
                'duraciones_permitidas' => [15, 30]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'horas_ocupadas' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca un doctor libre en el intervalo solicitado.
     *
     * Excluye doctores bloqueados y evita traslapes con otras citas.
     */
    private function buscarDoctorDisponible(int $idClinica, Carbon $inicio, Carbon $fin, ?int $excludeCitaId = null): ?int
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

            $query = Cita::where('id_clinica', $idClinica)
                ->where('id_doctor', $idDoctor)
                ->whereIn('estado_cita', ['pendiente', 'confirmada'])
                ->where('fecha_hora_inicio', '<', $fin)
                ->where('fecha_hora_fin', '>', $inicio);

            if (!is_null($excludeCitaId)) {
                $query->where('id_cita', '!=', $excludeCitaId);
            }

            if (!$query->exists()) {
                return (int) $idDoctor;
            }
        }

        return null;
    }

    /**
     * Calcula slots ocupados por capacidad total de doctores.
     *
     * Si todos los doctores disponibles estan ocupados en un slot,
     * dicho horario se marca como no seleccionable en UI.
     */
    private function calcularSlotsOcupadosPorCapacidad(int $idClinica, string $fecha, string $horaInicio, string $horaFin, int $intervaloMinutos): array
    {
        $fechaObj = Carbon::parse($fecha);
        $inicioDia = Carbon::createFromFormat('Y-m-d H:i', $fechaObj->format('Y-m-d').' '.$horaInicio);
        $finDia = Carbon::createFromFormat('Y-m-d H:i', $fechaObj->format('Y-m-d').' '.$horaFin);

        if ($inicioDia >= $finDia) {
            return ['horas_ocupadas' => [], 'total_slots' => 0];
        }

        $doctorIds = DB::table('doctores')
            ->join('usuarios_sistema', 'doctores.id_usuario', '=', 'usuarios_sistema.id_usuario')
            ->where('usuarios_sistema.id_clinica', $idClinica)
            ->pluck('doctores.id_doctor')
            ->map(fn($id) => (int) $id)
            ->all();

        $doctoresTotal = count($doctorIds);
        if ($doctoresTotal <= 0) {
            $doctoresTotal = 1;
        }

        $citas = Cita::where('id_clinica', $idClinica)
            ->whereIn('estado_cita', ['pendiente', 'confirmada'])
            ->whereDate('fecha_hora_inicio', $fechaObj->toDateString())
            ->get(['id_doctor', 'fecha_hora_inicio', 'fecha_hora_fin']);

        $bloqueos = \App\Models\HorarioBloqueado::where('estatus_horario', 'activo')
            ->whereIn('id_doctor', $doctorIds)
            ->where('fecha_inicio', '<', $finDia)
            ->where('fecha_fin', '>', $inicioDia)
            ->get(['id_doctor', 'fecha_inicio', 'fecha_fin']);

        $horasOcupadas = [];
        $totalSlots = 0;

        $slotInicio = $inicioDia->copy();
        while ($slotInicio < $finDia) {
            $slotFin = $slotInicio->copy()->addMinutes($intervaloMinutos);
            $totalSlots++;

            $doctoresBloqueados = [];
            foreach ($bloqueos as $bloqueo) {
                $bloqueoInicio = Carbon::parse($bloqueo->fecha_inicio);
                $bloqueoFin = Carbon::parse($bloqueo->fecha_fin);
                if ($bloqueoInicio < $slotFin && $bloqueoFin > $slotInicio) {
                    $doctoresBloqueados[(int) $bloqueo->id_doctor] = true;
                }
            }

            $doctoresDisponibles = $doctoresTotal - count($doctoresBloqueados);
            if ($doctoresDisponibles <= 0) {
                $horasOcupadas[] = $slotInicio->format('H:i');
                $slotInicio->addMinutes($intervaloMinutos);
                continue;
            }

            $ocupacionSlot = 0;
            foreach ($citas as $cita) {
                if (isset($doctoresBloqueados[(int) $cita->id_doctor])) {
                    continue;
                }

                $citaInicio = Carbon::parse($cita->fecha_hora_inicio);
                $citaFin = Carbon::parse($cita->fecha_hora_fin);
                if ($citaInicio < $slotFin && $citaFin > $slotInicio) {
                    $ocupacionSlot++;
                }
            }

            if ($ocupacionSlot >= $doctoresDisponibles) {
                $horasOcupadas[] = $slotInicio->format('H:i');
            }

            $slotInicio->addMinutes($intervaloMinutos);
        }

        return [
            'horas_ocupadas' => array_values(array_unique($horasOcupadas)),
            'total_slots' => $totalSlots,
        ];
    }
    /**
     * Retorna notificaciones de reagenda no leídas del usuario autenticado.
     */
    public function notificacionesReagenda()
    {
        $user = Auth::user();

        $notificaciones = Notificacion::where('id_usuario', $user->id_usuario)
            ->where('tipo', 'reagenda')
            ->where('estado', 'no_leida')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $idsCita = $notificaciones
            ->pluck('id_cita')
            ->filter()
            ->unique()
            ->values();

        $citasById = Cita::with('paciente')
            ->whereIn('id_cita', $idsCita)
            ->get()
            ->keyBy('id_cita');

        $notificaciones->transform(function ($notif) use ($citasById) {
            $datos = $notif->datos;
            if (is_string($datos)) {
                $decoded = json_decode($datos, true);
                $datos = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($datos)) {
                $datos = [];
            }

            $cita = $notif->id_cita ? ($citasById[$notif->id_cita] ?? null) : null;

            if (empty($datos['paciente'])) {
                if ($cita && $cita->paciente) {
                    $datos['paciente'] = trim(
                        ($cita->paciente->nombre ?? '') . ' ' .
                        ($cita->paciente->apellido_paterno ?? '') . ' ' .
                        ($cita->paciente->apellido_materno ?? '')
                    );
                }

                if (empty($datos['paciente']) && !empty($notif->mensaje)) {
                    if (preg_match('/paciente\s+(.+?)\s+(solicita|ha)\s+/iu', $notif->mensaje, $m)) {
                        $datos['paciente'] = trim($m[1]);
                    }
                }
            }

            if (empty($datos['nueva_fecha']) || empty($datos['nueva_hora'])) {
                // Compatibilidad con payloads antiguos o claves alternativas.
                $fechaHoraRaw = $datos['nueva_fecha_hora']
                    ?? $datos['fecha_hora']
                    ?? $datos['fecha_sugerida']
                    ?? null;

                if (!empty($fechaHoraRaw)) {
                    try {
                        $fecha = Carbon::parse($fechaHoraRaw);
                        $datos['nueva_fecha'] = $datos['nueva_fecha'] ?? $fecha->format('Y-m-d');
                        $datos['nueva_hora'] = $datos['nueva_hora'] ?? $fecha->format('H:i');
                    } catch (\Throwable $e) {
                        // Intento manual para formato dd/mm/YYYY HH:mm(:ss)
                        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}:\d{2})(?::\d{2})?$/', (string) $fechaHoraRaw, $m)) {
                            $datos['nueva_fecha'] = $datos['nueva_fecha'] ?? ($m[3] . '-' . $m[2] . '-' . $m[1]);
                            $datos['nueva_hora'] = $datos['nueva_hora'] ?? $m[4];
                        }
                    }
                }

                if (empty($datos['nueva_fecha']) && !empty($datos['fecha'])) {
                    $datos['nueva_fecha'] = $datos['fecha'];
                }
                if (empty($datos['nueva_hora']) && !empty($datos['hora'])) {
                    $datos['nueva_hora'] = $datos['hora'];
                }
                if (empty($datos['nueva_fecha']) && !empty($datos['fecha_nueva'])) {
                    $datos['nueva_fecha'] = $datos['fecha_nueva'];
                }
                if (empty($datos['nueva_hora']) && !empty($datos['hora_nueva'])) {
                    $datos['nueva_hora'] = $datos['hora_nueva'];
                }

                // Fallback legacy: intentar extraer fecha/hora desde el mensaje.
                if ((!empty($notif->mensaje)) && (empty($datos['nueva_fecha']) || empty($datos['nueva_hora']))) {
                    $mensaje = (string) $notif->mensaje;

                    // Ejemplo: "... para el 2026-03-25 a las 14:30"
                    if (preg_match('/para\s+el\s+(\d{4}-\d{2}-\d{2})\s+a\s+las\s+(\d{1,2}:\d{2})/iu', $mensaje, $m)) {
                        $datos['nueva_fecha'] = $datos['nueva_fecha'] ?? $m[1];
                        $datos['nueva_hora'] = $datos['nueva_hora'] ?? $m[2];
                    }

                    // Ejemplo: "... para el 25/03/2026 a las 14:30"
                    if (empty($datos['nueva_fecha']) || empty($datos['nueva_hora'])) {
                        if (preg_match('/para\s+el\s+(\d{2})\/(\d{2})\/(\d{4})\s+a\s+las\s+(\d{1,2}:\d{2})/iu', $mensaje, $m)) {
                            $datos['nueva_fecha'] = $datos['nueva_fecha'] ?? ($m[3] . '-' . $m[2] . '-' . $m[1]);
                            $datos['nueva_hora'] = $datos['nueva_hora'] ?? $m[4];
                        }
                    }
                }

                if (($cita && $cita->fecha_hora_inicio) && (empty($datos['nueva_fecha']) || empty($datos['nueva_hora']))) {
                    try {
                        $inicio = Carbon::parse($cita->fecha_hora_inicio);
                        $datos['nueva_fecha'] = $datos['nueva_fecha'] ?? $inicio->format('Y-m-d');
                        $datos['nueva_hora'] = $datos['nueva_hora'] ?? $inicio->format('H:i');
                    } catch (\Throwable $e) {
                    }
                }
            }

            $datos['paciente'] = trim($datos['paciente'] ?? '') ?: 'Paciente';
            $datos['nueva_fecha'] = trim((string) ($datos['nueva_fecha'] ?? '')) ?: '-';
            $datos['nueva_hora'] = trim((string) ($datos['nueva_hora'] ?? '')) ?: '-';

            $notif->datos = $datos;
            return $notif;
        });

        return response()->json($notificaciones);
    }

    /**
     * Procesa una solicitud de reagenda (aceptar/rechazar) desde el dashboard.
     */
    public function procesarReagenda(Request $request, $id)
    {
        $request->validate([
            'accion' => 'required|string|in:aceptar,rechazar,cancelar,ignorar',
        ]);

        $notificacion = Notificacion::where('id_notificacion', $id)
            ->where('id_usuario', Auth::user()->id_usuario)
            ->first();

        if (!$notificacion) {
            return response()->json([
                'success' => true,
                'message' => 'La notificacion ya fue procesada o no existe.',
            ]);
        }

        if ($request->accion === 'aceptar') {
            $cita = Cita::find($notificacion->id_cita);
            if (!$cita) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cita asociada a la notificacion no existe.',
                ], 404);
            }

            $datos = $notificacion->datos;
            if (is_string($datos)) {
                $decoded = json_decode($datos, true);
                $datos = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($datos)) {
                $datos = [];
            }

            $fechaHoraRaw = $datos['nueva_fecha_hora']
                ?? $datos['fecha_hora']
                ?? $datos['fecha_sugerida']
                ?? null;

            if (empty($fechaHoraRaw)) {
                $fecha = $datos['nueva_fecha']
                    ?? $datos['fecha']
                    ?? $datos['fecha_nueva']
                    ?? null;
                $hora = $datos['nueva_hora']
                    ?? $datos['hora']
                    ?? $datos['hora_nueva']
                    ?? null;

                if (!empty($fecha) && !empty($hora)) {
                    $fechaHoraRaw = trim((string) $fecha) . ' ' . trim((string) $hora);
                }
            }

            if (empty($fechaHoraRaw)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud de reagenda no contiene fecha y hora validas.',
                ], 422);
            }

            try {
                $nuevaFecha = Carbon::parse($fechaHoraRaw);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo interpretar la fecha y hora solicitadas para reagendar.',
                ], 422);
            }

            $duracionMinutos = 30;
            if (!empty($cita->fecha_hora_inicio) && !empty($cita->fecha_hora_fin)) {
                try {
                    $inicioOriginal = Carbon::parse($cita->fecha_hora_inicio);
                    $finOriginal = Carbon::parse($cita->fecha_hora_fin);
                    $duracionDetectada = max(15, $inicioOriginal->diffInMinutes($finOriginal));
                    $duracionMinutos = in_array($duracionDetectada, [15, 30], true) ? $duracionDetectada : 30;
                } catch (\Throwable $e) {
                    $duracionMinutos = 30;
                }
            }

            $nuevaFechaFin = $nuevaFecha->copy()->addMinutes($duracionMinutos);

            $idDoctorDisponible = $this->buscarDoctorDisponible(
                (int) $cita->id_clinica,
                $nuevaFecha,
                $nuevaFechaFin
            );

            if (!$idDoctorDisponible) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay doctores disponibles para la fecha y hora solicitadas.',
                ], 422);
            }

            DB::transaction(function () use ($cita, $nuevaFecha, $nuevaFechaFin, $idDoctorDisponible, $notificacion) {
                $cita->estado_cita = 'completada';
                $cita->reagenda_estatus = 'aplicada';
                $cita->notas = "[REAGENDADA POR PACIENTE - CITA ORIGINAL COMPLETADA]\n" . ($cita->notas ?? '');
                $cita->save();

                Cita::create([
                    'id_clinica' => $cita->id_clinica,
                    'id_paciente' => $cita->id_paciente,
                    'id_doctor' => $idDoctorDisponible,
                    'id_servicio' => $cita->id_servicio,
                    'fecha_hora_inicio' => $nuevaFecha,
                    'fecha_hora_fin' => $nuevaFechaFin,
                    'estado_cita' => 'pendiente',
                    'costo_estimado' => $cita->costo_estimado,
                    'motivo' => $cita->motivo,
                    'notas' => "[NUEVA CITA POR REAGENDA DE PACIENTE]",
                    'reagenda_estatus' => 'aplicada',
                ]);

                $notificacion->estado = 'leido';
                $notificacion->save();
            });
        }

        if ($request->accion !== 'aceptar') {
            $notificacion->estado = 'leido';
            $notificacion->save();
        }

        return response()->json([
            'success' => true,
            'message' => $request->accion === 'aceptar'
                ? 'Solicitud de reagenda aceptada y aplicada correctamente.'
                : 'Solicitud de reagenda descartada correctamente.',
        ]);
    }

    /**
     * Marca una notificación como leída.
     */
    public function marcarNotificacionLeida($id)
    {
        $notificacion = Notificacion::where('id_notificacion', $id)
            ->where('id_usuario', Auth::user()->id_usuario)
            ->first();

        if (!$notificacion) {
            return response()->json([
                'success' => true,
                'message' => 'Notificacion ya marcada o no encontrada.',
            ]);
        }

        $notificacion->estado = 'leido';
        $notificacion->save();

        return response()->json(['success' => true, 'message' => 'Notificación marcada como leída.']);
    }

}