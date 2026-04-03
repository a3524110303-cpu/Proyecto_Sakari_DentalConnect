@extends('layouts.app')

@section('titulo', 'Panel Principal')

{{-- =====================================================================
IDX-00 | VISTA DASHBOARD
Resumen de secciones:
- IDX-01: Encabezado y metricas rapidas
- IDX-02: Lista de citas pendientes
- IDX-03: Modal principal de detalle de cita
- IDX-04: Widgets internos (horario, seguimiento, pago)
- IDX-05: Tab odontograma
- IDX-06: Script principal (calendario, modal, odontograma, paginacion)
====================================================================== --}}

@section('contenido')
    {{-- IDX-01 | Encabezado y métricas rápidas --}}
    <h2 class="page-title">Panel Principal</h2>

    {{-- Panel de Notificaciones de Reagendado --}}
    <div id="panel-notificaciones-reagenda" style="display: none; background: #FFF7ED; border: 1px solid #FED7AA; border-radius: 12px; padding: 18px 22px; margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h4 style="margin: 0; color: #9A3412; font-weight: 800; font-size: 1rem;">
                <i class="fa-solid fa-bell" style="margin-right: 6px;"></i>
                Solicitudes de Reagendado
                <span id="badge-reagenda-count" style="background: #EA580C; color: white; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; margin-left: 6px;">0</span>
            </h4>
        </div>
        <div id="lista-notificaciones-reagenda" style="display: flex; flex-direction: column; gap: 10px;"></div>
    </div>

    <script>
        // ── Función reutilizable para refrescar el panel de notificaciones del dashboard ──
        function refrescarPanelReagendaDashboard() {
            const panel = document.getElementById('panel-notificaciones-reagenda');
            const lista = document.getElementById('lista-notificaciones-reagenda');
            const badge = document.getElementById('badge-reagenda-count');
            if (!panel || !lista || !badge) return;

            fetch('/api/notificaciones/reagenda')
                .then(r => r.json())
                .then(data => {
                    const payload = Array.isArray(data)
                        ? { success: true, data }
                        : data;

                    if (!payload.success || !Array.isArray(payload.data) || payload.data.length === 0) {
                        panel.style.display = 'none';
                        lista.innerHTML = '';
                        badge.textContent = '0';
                        return;
                    }

                    panel.style.display = 'block';
                    badge.textContent = payload.data.length;

                    lista.innerHTML = '';
                    payload.data.forEach(n => {
                        const div = document.createElement('div');
                        div.id = 'notif-' + n.id_notificacion;
                        div.style.cssText = 'background: white; border: 1px solid #FDE68A; border-radius: 10px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; gap: 12px;';
                        div.innerHTML = `
                            <div style="flex: 1;">
                                <p style="margin: 0; color: #78350F; font-size: 0.9rem; line-height: 1.4;">${n.mensaje}</p>
                                <small style="color: #A3A3A3; font-size: 0.75rem;">${new Date(n.created_at).toLocaleString('es-MX')}</small>
                            </div>
                            <button onclick="marcarLeida(${n.id_notificacion})"
                                style="background: #16A34A; color: white; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 700; cursor: pointer; font-size: 0.8rem; white-space: nowrap;">
                                <i class="fa-solid fa-check"></i> Leída
                            </button>
                        `;
                        lista.appendChild(div);
                    });
                })
                .catch(() => {});
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Si existe el panel global del layout, evitamos duplicar UX de notificaciones.
            if (document.getElementById('notif-bell')) {
                return;
            }

            // Carga inicial
            refrescarPanelReagendaDashboard();

            // ── Polling en vivo: revisa cada 15 segundos ──
            window._dashReagendaPoll = setInterval(refrescarPanelReagendaDashboard, 15000);

            // Pausar polling cuando la pestaña está oculta
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    clearInterval(window._dashReagendaPoll);
                } else {
                    refrescarPanelReagendaDashboard();
                    window._dashReagendaPoll = setInterval(refrescarPanelReagendaDashboard, 15000);
                }
            });
        });

        function marcarLeida(id) {
            fetch('/api/notificaciones/' + id + '/leer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const el = document.getElementById('notif-' + id);
                    if (el) el.remove();

                    const badge = document.getElementById('badge-reagenda-count');
                    const count = parseInt(badge.textContent) - 1;
                    badge.textContent = count;

                    if (count <= 0) {
                        document.getElementById('panel-notificaciones-reagenda').style.display = 'none';
                    }
                }
            });
        }
    </script>

    {{-- =====================================================================
    SECCIÓN DE MÉTRICAS RÁPIDAS
    ====================================================================== --}}
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">

        {{-- Tarjeta: Pacientes totales (Acceso Rápido a Pacientes) --}}
        <div onclick="window.location.href='{{ route('pacientes.index') }}'"
            style="cursor: pointer; background: white; border-radius: 15px; padding: 22px 25px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 18px; border-left: 5px solid var(--primary-color); transition: transform 0.2s;"
            onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
            <div
                style="background: #e0fbfc; border-radius: 12px; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fa-solid fa-users" style="color: var(--primary-color); font-size: 1.4em;"></i>
            </div>
            <div>
                <div style="font-size: 1.8em; font-weight: 800; color: #333; line-height: 1;">{{ $totalPacientes }}</div>
                <div style="color: #888; font-size: 0.85em; margin-top: 3px;">Pacientes activos</div>
            </div>
        </div>

        {{-- Tarjeta: Citas de hoy (Generar Citas) --}}
        <div onclick="window.location.href='{{ route('pacientes.index') }}'"
            style="cursor: pointer; background: white; border-radius: 15px; padding: 22px 25px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 18px; border-left: 5px solid #4CAF50; transition: transform 0.2s;"
            onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='none'">
            <div
                style="background: #e8f5e9; border-radius: 12px; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fa-solid fa-calendar-plus" style="color: #4CAF50; font-size: 1.4em;"></i>
            </div>
            <div>
                <div style="font-size: 1.8em; font-weight: 800; color: #333; line-height: 1;">{{ $citasHoyCount }}</div>
                <div style="color: #888; font-size: 0.85em; margin-top: 3px;">Generar / Ver Citas</div>
            </div>
        </div>



        {{-- Tarjeta: Ingresos del mes --}}
       <div
    style="background: white; border-radius: 15px; padding: 22px 25px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 18px; border-left: 5px solid #FF9800;">
    <div
        style="background: #fff3e0; border-radius: 12px; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <i class="fa-solid fa-dollar-sign" style="color: #FF9800; font-size: 1.4em;"></i>
    </div>
    <div>
        <div id="lbl-ingresos-mes" style="font-size: 1.8em; font-weight: 800; color: #333; line-height: 1;">
            ${{ number_format($ingresosMes, 0) }}
        </div>
        <div style="color: #888; font-size: 0.85em; margin-top: 3px;">Ingresos del mes</div>
    </div>
</div>

</div>
    {{-- IDX-02 | Tarjeta principal de citas pendientes --}}
    {{-- Tarjeta Principal: Citas --}}
<div style="background: white; padding: 25px; border-radius: 15px; box-shadow: var(--shadow);">
    <h3 style="margin-bottom: 20px; color: #333; font-weight: 700;">Próximas Citas Pendientes</h3>
    
    <div class="appointment-list" id="appointment-list" style="display: flex; flex-direction: column; gap: 15px;">
        @forelse($proximasCitas as $cita)
            @php
                $fechaCita = \Carbon\Carbon::parse($cita->fecha_hora_inicio);
                $esVencida = $fechaCita->isPast();

                // Colores neutros y profesionales
                $borderColor = $esVencida ? '#FCD34D' : '#E5E7EB';
                $bgColor = $esVencida ? '#FFFBEB' : '#FFFFFF';
                $hoverColor = $esVencida ? '#F59E0B' : 'var(--primary-color)';
            @endphp

            <div class="appointment-card" 
                 id="cita-card-{{ $cita->id_cita }}" 
                 onclick="cargarModalCita({{ $cita->id_cita }})"
                 style="position: relative; border: 1px solid {{ $borderColor }}; background: {{ $bgColor }}; padding: 18px 22px; border-radius: 12px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: space-between; gap: 15px;"
                 onmouseover="this.style.boxShadow='0 6px 15px rgba(0,0,0,0.05)'; this.style.borderColor='{{ $hoverColor }}'"
                 onmouseout="this.style.boxShadow='none'; this.style.borderColor='{{ $borderColor }}'">

                {{-- Overlay de éxito --}}
                <div class="check-overlay" id="overlay-{{ $cita->id_cita }}"
                    style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.95); border-radius: 12px; z-index: 10; flex-direction: column; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-circle-check" style="color: #22C55E; font-size: 2.5em;"></i>
                    <span style="font-weight: 800; color: #15803D; margin-top: 5px;">¡Cita completada!</span>
                </div>

                <div style="display: flex; align-items: center; gap: 18px; flex: 1;">
                    {{-- Bloque fecha --}}
                    <div style="background: {{ $esVencida ? '#FEF3C7' : '#E0F2FE' }}; padding: 10px; border-radius: 10px; text-align: center; min-width: 65px;">
                        <span style="display: block; font-weight: 800; color: {{ $esVencida ? '#B45309' : '#0369A1' }}; font-size: 1.3em;">
                            {{ $fechaCita->format('d') }}
                        </span>
                        <small style="color: #666; font-weight: 700; text-transform: uppercase; font-size: 0.75em;">
                            {{ $fechaCita->translatedFormat('M') }}
                        </small>
                    </div>

                    {{-- Información del Paciente --}}
                    <div style="overflow: hidden;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <h4 style="margin: 0; font-size: 1.15em; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $cita->paciente->nombre }} {{ $cita->paciente->apellido_paterno }}
                            </h4>
                            @if($esVencida)
                                <span style="background: #FEF3C7; color: #B45309; font-size: 0.65em; font-weight: 800; padding: 2px 8px; border-radius: 10px; border: 1px solid #FCD34D;">VENCIDA</span>
                            @endif
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; color: #6b7280; font-size: 0.9em;">
                            <span><i class="fa-regular fa-clock"></i> {{ $fechaCita->format('h:i A') }}</span>
                            <span style="color: #d1d5db;">|</span>
                            <span style="font-weight: 600; color: #4b5563;">{{ $cita->servicio->nombre_servicio ?? 'Consulta General' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Acciones: Solo botón de completar --}}
                <div onclick="event.stopPropagation();">
                    <button id="btn-completar-{{ $cita->id_cita }}" onclick="completarCita({{ $cita->id_cita }})" 
                            style="background: #22C55E; color: white; border: none; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: transform 0.2s;">
                        <i class="fa-regular fa-circle-check"></i> Completar
                    </button>
                </div>
            </div>
        @empty
            <div style="text-align: center; color: #9ca3af; padding: 40px;">
                <i class="fa-regular fa-calendar-xmark" style="font-size: 3em; margin-bottom: 10px; opacity: 0.5;"></i>
                <p>No hay citas próximas agendadas.</p>
            </div>
        @endforelse
    </div>
</div>

    {{-- IDX-03 | Modal principal de detalle de cita --}}
    <div class="modal-overlay" id="modal-detalle-cita">
        <div class="modal-glass modal-xl"
            style="background: #F8FDFF; padding: 0; max-width: 1750px; width: 98vw; height: 95vh; display: flex; overflow: hidden; border-radius: 20px; border: 1px solid #dceeef;">

            <div
                style="width: 30%; background: #E0FBFC; padding: 30px; display: flex; flex-direction: column; border-right: 2px solid #bcebf5; overflow-y: auto;">

                <h2 style="margin-top: 0; color: #000; margin-bottom: 20px; font-weight: 800;">Calendario</h2>

                <div style="background: white; padding: 20px; border-radius: 16px; width: 100%; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: auto; box-sizing: border-box;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: #F8F9FA; padding: 8px; border-radius: 10px;">
        <button class="ghost-btn" style="padding: 5px 10px; background: transparent; color: #666; min-width: 30px; cursor: pointer;" onclick="cambiarMes(-1)">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <span id="cal-mes-anio" style="font-weight: 700; color: #00D1FF; font-size: 0.95em;">Cargando...</span>
        <button class="ghost-btn" style="padding: 5px 10px; background: transparent; color: #666; min-width: 30px; cursor: pointer;" onclick="cambiarMes(1)">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>

    <div id="calendar-main-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; text-align: center; font-size: 0.85em;">
        <span style="color:#aaa; font-weight:600; font-size: 0.8em; margin-bottom: 5px;">D</span>
        <span style="color:#aaa; font-weight:600; font-size: 0.8em; margin-bottom: 5px;">L</span>
        <span style="color:#aaa; font-weight:600; font-size: 0.8em; margin-bottom: 5px;">M</span>
        <span style="color:#aaa; font-weight:600; font-size: 0.8em; margin-bottom: 5px;">M</span>
        <span style="color:#aaa; font-weight:600; font-size: 0.8em; margin-bottom: 5px;">J</span>
        <span style="color:#aaa; font-weight:600; font-size: 0.8em; margin-bottom: 5px;">V</span>
        <span style="color:#aaa; font-weight:600; font-size: 0.8em; margin-bottom: 5px;">S</span>

        <div id="functional-calendar-days" style="display: contents;"></div>
    </div>

    <div style="margin-top: 20px; display: flex; gap: 12px; justify-content: center; font-size: 0.7em; color: #666;">
        <div style="display:flex; align-items:center;"><span style="width:8px;height:8px;background:#32D74B;border-radius:50%;margin-right:4px;"></span>Libre</div>
        <div style="display:flex; align-items:center;"><span style="width:8px;height:8px;background:#FFC107;border-radius:50%;margin-right:4px;"></span>Ocupado</div>
        <div style="display:flex; align-items:center;"><span style="width:8px;height:8px;background:#EF4444;border-radius:50%;margin-right:4px;"></span>Lleno</div>
    </div>
</div>
                <div style="width: 100%; display: flex; flex-direction: column; gap: 12px; margin-top: 25px;">
                    <button class="ghost-btn" onclick="openWidget('widget-seguimiento')"
                        style="background: white; color: black; border: 2px solid #00D1FF; justify-content: center; font-weight: 700; border-radius: 10px; padding: 12px; cursor: pointer;">Seguimiento</button>
                    <button type="button" class="ghost-btn" onclick="openWidget('widget-pago')"
                        style="background: white; color: black; border: 2px solid #00D1FF; justify-content: center; font-weight: 700; border-radius: 10px; padding: 12px; cursor: pointer;">Pago
                        de hoy</button>

                    <button type="button" class="ghost-btn" onclick="switchTab('tab-odontograma')"
                        style="background: white; color: black; border: 2px solid #00D1FF; justify-content: center; font-weight: 700; border-radius: 10px; padding: 12px; cursor: pointer;">
                        Odontograma
                    </button>

                    <button class="ghost-btn" id="btn-actualizar-cita"
                        style="background: #00D1FF; color: white; border: none; font-weight: 800; justify-content: center;
                                                                                                                                                                                                                                    margin-top: 10px; padding: 14px; box-shadow: 0 5px 15px rgba(0, 209, 255, 0.3); border-radius: 10px;">
                        GUARDAR CAMBIOS
                    </button>

                </div>
            </div>

            <!-- Formulario Principal -->
            <form id="form-actualizar-cita" method="POST" onsubmit="return false;"
                style="width: 70%; padding: 40px; position: relative; overflow-y: auto; display: flex; flex-direction: column;">
                @csrf

                <button type="button" class="close-modal" onclick="closeModal('modal-detalle-cita')"
                    style="position: absolute; top: 25px; right: 25px; font-size: 1.5rem; background: #f0f0f0; width: 40px; height: 40px; border-radius: 50%; color: #555; border: none; cursor: pointer; z-index: 5;">&times;</button>

                <!-- OVERLAY PARA WIDGETS INTERNOS -->
                <div id="internal-widget-overlay"
                    style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 50; border-radius: 20px;">
                </div>

                <!-- TAB 1: RESUMEN (Visible por defecto) -->
                <div id="tab-resumen" class="tab-content active"
                    style="min-height: 100%; display: flex; flex-direction: column; flex: 1 0 auto; padding-bottom: 20px;">
                    <h1 style="font-size: 2.2rem; font-weight: 800; margin: 0 0 30px 0; color: #000; flex-shrink: 0;">
                        Detalles del Paciente
                    </h1>

                    <div
                        style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 30px; border: 1px solid #eee; flex-shrink: 0;">
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                            <div><small style="font-weight:700; color:#555;">Nombre(s):</small>
                                <div id="lbl-nombre" style="font-size:1.1em;">...</div>
                            </div>
                            <div><small style="font-weight:700; color:#555;">Apellido Paterno:</small>
                                <div id="lbl-paterno" style="font-size:1.1em;">...</div>
                            </div>
                            <div><small style="font-weight:700; color:#555;">Apellido Materno:</small>
                                <div id="lbl-materno" style="font-size:1.1em;">...</div>
                            </div>
                            <div><small style="font-weight:700; color:#555;">Edad:</small>
                                <div id="lbl-edad" style="font-size:1.1em;">...</div>
                            </div>

                            <div><small style="font-weight:700; color:#555;">Sexo:</small>
                                <div id="lbl-sexo" style="font-size:1.1em;">...</div>
                            </div>
                            <div><small style="font-weight:700; color:#555;">Teléfono:</small>
                                <div id="lbl-telefono" style="font-size:1.1em;">...</div>
                            </div>
                            <div><small style="font-weight:700; color:#555;">Tipo Sangre:</small>
                                <div id="lbl-sangre" style="font-weight:800; color: var(--primary-color);">...</div>
                            </div>
                            <div><small style="font-weight:700; color:#555;">Peso:</small>
                                <div id="lbl-peso">...</div>
                            </div>

                            <div style="grid-column: span 2;">
                                <small style="font-weight:700; color:#ef4444;"><i
                                        class="fa-solid fa-triangle-exclamation"></i>
                                    Alergias:</small>
                                <div id="lbl-alergias" style="color:#ef4444;">...</div>
                            </div>
                            <div style="grid-column: span 2;">
                                <small style="font-weight:700; color:#ef4444;"><i class="fa-solid fa-notes-medical"></i>
                                    Enfermedades Crónicas:</small>
                                <div id="lbl-enfermedades" style="color:#ef4444;">...</div>
                            </div>
                        </div>
                    </div>

                    <div
                        style="border: 2px solid #00D1FF; border-radius: 8px; overflow: hidden; margin-bottom: auto; flex-shrink: 0;">
                        <table style="width: 100%; border-collapse: collapse; text-align: center;">
                            <thead style="background: #CCFBFD;">
                                <tr>
                                    <th
                                        style="padding: 15px; border-right: 2px solid #00D1FF; color:#000; font-weight: 700;">
                                        Día</th>
                                    <th
                                        style="padding: 15px; border-right: 2px solid #00D1FF; color:#000; font-weight: 700;">
                                        Hora</th>
                                    <th
                                        style="padding: 15px; border-right: 2px solid #00D1FF; color:#000; font-weight: 700;">
                                        Seguimiento</th>
                                    <th
                                        style="padding: 15px; border-right: 2px solid #00D1FF; color:#000; font-weight: 700;">
                                        Abono</th>
                                    <th style="padding: 15px; color:#000; font-weight: 700;">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="cita-tabla-body" style="background: white;">
                                {{-- JS renderiza aquí todas las filas del historial --}}
                            </tbody>
                        </table>

                        <!-- Controles de paginación -->
                        <div id="paginacion-controles"
                            style="display: flex; justify-content: center; align-items: center; gap: 15px; padding: 15px; background: #f8f9fa; border-top: 2px solid #00D1FF;">
                            <button type="button" id="btn-pag-anterior" onclick="cambiarPagina(-1)"
                                style="background: var(--primary-color); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                <i class="fa-solid fa-chevron-left"></i> Anterior
                            </button>
                            <span id="info-paginacion" style="font-weight: 600; color: #333;">Página 1 de 1</span>
                            <button type="button" id="btn-pag-siguiente" onclick="cambiarPagina(1)"
                                style="background: var(--primary-color); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                                Siguiente <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div
                        style="display: flex; gap: 20px; align-items: center; margin-top: 30px; justify-content: flex-end; flex-wrap: wrap; flex-shrink: 0;">

                        {{-- TOTAL DE LA CITA COMO ETIQUETA ESTATICA --}}
                        <div
                            style="font-size: 1.5rem; font-weight: 700; color: #000; display: flex; align-items: center; white-space: nowrap;">
                            Total:
                            <span
                                style="background: #FFFFFF; padding: 8px 20px; border-radius: 8px; border: 2px solid #00D1FF; margin-left: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); min-width: 120px; text-align: center;">
                                <span id="lbl-total">0.00</span>
                            </span>
                        </div>

                        <div
                            style="font-size: 1.5rem; font-weight: 700; color: #000; display: flex; align-items: center; white-space: nowrap;">
                            <span style="font-weight: 700; font-size: 1em;">Restante:</span>
                            <span style="margin-left: 10px; font-weight: 700;">
                                $<span id="lbl-restante">0.00</span>
                            </span>
                        </div>
                    </div>

                    <!-- Datos Ocultos para cálculos Matemáticos Crudos -->
                    <input type="hidden" id="raw-costo-total" value="0">
                    <input type="hidden" id="raw-total-abonado" value="0">
                </div>

                <!-- IDX-04A | Widget de horario (reprogramacion) -->
                <!-- WIDGET 2: HORARIO (Aparece sobre Resumen/Odontograma) -->
                <div id="widget-horario" class="inner-widget"
                    style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                                                                   background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); 
                                                                   border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 25px 50px rgba(0,0,0,0.15); 
                                                                   padding: 40px; border-radius: 24px; z-index: 100; width: 90%; max-width: 550px;">

                    <h2 style="color: var(--primary-color); font-weight: 800; font-size: 2rem; margin-bottom: 5px;">
                        <i class="fa-regular fa-calendar-check"></i> Reprogramar Cita
                    </h2>
                    <p style="color: #555; margin-bottom: 25px; font-size: 1.05rem;">
                        Selecciona la fecha y un horario de atención disponible.
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 700; color: #333;">Fecha seleccionada</label>
                            <input type="date" name="nueva_fecha" id="input-nueva-fecha"
                                style="padding: 14px; border: 2px solid rgba(0, 209, 255, 0.2); border-radius: 12px; font-size: 1.1rem; 
                                                                               background: rgba(255, 255, 255, 0.9); outline: none; color: #333; font-weight: 600;"
                                onchange="recalcularHorariosWidget()">
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 700; color: #333;">Duración de la cita</label>
                            <select name="nueva_duracion_minutos" id="input-nueva-duracion"
                                style="padding: 12px; border: 2px solid rgba(0, 209, 255, 0.2); border-radius: 12px; font-size: 1rem; background: rgba(255, 255, 255, 0.9); color: #333; font-weight: 600;"
                                onchange="recalcularHorariosWidget()">
                                <option value="15">15 minutos</option>
                                <option value="30" selected>30 minutos</option>
                            </select>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-weight: 700; color: #333;">Horarios Disponibles</label>
                            <input type="hidden" name="nueva_hora" id="input-nueva-hora">

                            <div id="contenedor-horarios"
                                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px; 
                                                                                max-height: 220px; overflow-y: auto; padding-right: 5px; padding-bottom: 5px;">
                                <div
                                    style="grid-column: 1 / -1; color: #888; text-align: center; padding: 20px; font-style: italic;">
                                    Selecciona una fecha primero...
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="button" onclick="cancelarHorario()"
                            style="flex: 1; background: rgba(0,0,0,0.05); color: #555; padding: 14px; border: none; border-radius: 12px; font-weight: 700; cursor: pointer;">
                            Cancelar
                        </button>
                        <button type="button" onclick="confirmarHorario()"
                            style="flex: 2; background: var(--primary-color); color: white; padding: 14px; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 209, 255, 0.3);">
                            Confirmar Horario
                        </button>
                    </div>
                </div>

                <!-- IDX-04B | Widget de seguimiento clinico -->
                <!-- WIDGET 3: SEGUIMIENTO -->
                <div id="widget-seguimiento" class="inner-widget"
                    style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 100; width: 80%; max-width: 600px;">
                    <h2 style="color: var(--primary-color); font-weight: 800; font-size: 2rem; margin-bottom: 10px;">
                        Seguimiento Clínico</h2>
                    <p style="color: #666; margin-bottom: 30px; font-size: 1.1rem;">Tratamiento que se realizara la proxima
                        cita.</p>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="font-weight: 700; color: #444;">Notas / Observaciones</label>
                        <textarea name="notas_seguimiento"
                            style="padding: 15px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; resize: none; min-height: 150px;"
                            placeholder="Escribe aquí los detalles del tratamiento..."></textarea>
                    </div>
                    <button type="button"
                        style="background: #eee; color: #555; margin-top: 30px; padding: 12px; width: 100%; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; text-align: center;"
                        onclick="closeWidgets()">Confirmar / Volver</button>
                </div>

                <!-- IDX-04C | Widget de pago del dia -->
                <!-- WIDGET 4: PAGO -->
                <div id="widget-pago" class="inner-widget"
                    style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); z-index: 100; width: 80%; max-width: 400px;">
                    <h2 style="color: var(--primary-color); font-weight: 800; font-size: 2rem; margin-bottom: 10px;">
                        Registrar Pago</h2>
                    <p style="color: #666; margin-bottom: 30px; font-size: 1.1rem;">Ingresa el monto abonado hoy por el
                        paciente.</p>

                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px;">
                        <label style="font-weight: 700; color: #444;">Monto a Abonar en esta Cita ($)</label>
                        <input type="number" name="monto_abono" id="input-monto-abono"
                            style="padding: 15px; border: 1px solid #ccc; border-radius: 8px; font-size: 1.5rem; font-weight: bold;"
                            step="0.01" min="0" placeholder="0.00"
                            oninput="if(this.value < 0) { this.value = 0; } calcularVueltoReal();">
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="font-weight: 700; color: #444;">Método de Pago</label>
                        <select name="metodo_pago" id="input-metodo-pago"
                            style="padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; color: #333;">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <button type="button"
                        style="background: #eee; color: #555; margin-top: 30px; padding: 12px; width: 100%; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; text-align: center;"
                        onclick="closeWidgets()">Confirmar / Volver</button>
                </div>

                <!-- IDX-05 | Tab Odontograma -->
                <!-- TAB O: ODONTOGRAMA -->
                <div id="tab-odontograma" class="tab-content" style="display: none; height: 100%;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <div>
                            <h2 style="color: var(--primary-color); font-weight: 800; font-size: 2rem; margin: 0;">
                                Odontograma Digital</h2>
                            <p style="color: #666; margin-top: 5px; font-size: 1.1rem;">Selecciona una herramienta y haz
                                clic en las piezas dentales.</p>
                        </div>
                        <button type="button" class="ghost-btn" onclick="switchTab('tab-resumen')"
                            style="background: #f0f0f0; border: 1px solid #ccc; color: #333; font-weight: 700; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                            <i class="fa-solid fa-arrow-left"></i> Volver a Detalles
                        </button>
                    </div>

                    <svg style="display: none;">
                        <defs>
                            <symbol id="tooth-incisor" viewBox="0 0 80 120">
                                <path
                                    d="M20 15 Q40 0 60 15 L65 45 Q60 75 50 85 L45 110 Q40 118 35 110 L30 85 Q20 75 15 45 Z"
                                    fill="#f8f1e4" stroke="#222" stroke-width="2" />
                            </symbol>
                            <symbol id="tooth-canine" viewBox="0 0 80 130">
                                <path
                                    d="M20 25 Q40 0 60 25 L65 50 Q55 80 50 90 L45 120 Q40 128 35 120 L30 90 Q20 80 15 50 Z"
                                    fill="#f8f1e4" stroke="#222" stroke-width="2" />
                            </symbol>
                            <symbol id="tooth-premolar" viewBox="0 0 90 130">
                                <path
                                    d="M20 30 Q45 5 70 30 L75 60 Q70 80 60 90 L55 115 Q45 125 35 115 L30 90 Q20 80 15 60 Z"
                                    fill="#f8f1e4" stroke="#222" stroke-width="2" />
                            </symbol>
                            <symbol id="tooth-molar-upper" viewBox="0 0 110 140">
                                <path d="M20 40 Q55 5 90 40 L85 70 Q80 90 65 100 Q55 105 45 100 Q30 90 25 70 Z"
                                    fill="#f8f1e4" stroke="#222" stroke-width="2" />
                                <path d="M40 100 L30 130 Q45 135 50 110 Z" fill="#f8f1e4" stroke="#222" stroke-width="2" />
                                <path d="M70 100 L80 130 Q65 135 60 110 Z" fill="#f8f1e4" stroke="#222" stroke-width="2" />
                                <path d="M55 100 L50 135 Q60 138 58 110 Z" fill="#f8f1e4" stroke="#222" stroke-width="2" />
                            </symbol>
                            <symbol id="tooth-molar-lower" viewBox="0 0 110 140">
                                <path d="M20 40 Q55 5 90 40 L85 75 Q75 95 55 105 Q35 95 25 75 Z" fill="#f8f1e4"
                                    stroke="#222" stroke-width="2" />
                                <path d="M45 105 L35 135 Q50 140 55 115 Z" fill="#f8f1e4" stroke="#222" stroke-width="2" />
                                <path d="M65 105 L75 135 Q60 140 55 115 Z" fill="#f8f1e4" stroke="#222" stroke-width="2" />
                            </symbol>
                        </defs>
                    </svg>

                    <div
                        style="background: #f8f9fa; padding: 15px; border-radius: 12px; display: flex; gap: 20px; align-items: flex-end; margin-bottom: 20px; border: 1px solid #ddd;">
                        <div style="flex: 1;">
                            <label style="font-weight: 700; color: #444; display: block; margin-bottom: 5px;">Tratamiento a
                                aplicar:</label>
                            <select id="select-servicio"
                                style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc;">
                                <option value="">-- Seleccionar --</option>
                                @foreach($servicios as $srv)
                                    <option value="{{ $srv->id_servicio }}">{{ $srv->nombre_servicio }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                <input type="radio" name="tipo_registro" value="hallazgo" id="tipo-hallazgo" checked>
                                <span style="color: blue; font-weight: 700;">Hallazgo (Azul)</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                <input type="radio" name="tipo_registro" value="tratamiento" id="tipo-tratamiento">
                                <span style="color: red; font-weight: 700;">Plan/Realizado (Rojo)</span>
                            </label>
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                                <input type="radio" name="tipo_registro" value="borrar" id="tipo-borrar">
                                <span style="color: #444; font-weight: 700;">Borrar marca</span>
                            </label>
                        </div>
                    </div>

                    <style>
                        .odontograma-lienzo {
                            display: flex;
                            flex-direction: column;
                            gap: 30px;
                            padding: 20px;
                            background: #fff;
                            border-radius: 12px;
                            border: 2px dashed #ccc;
                            flex: 1;
                            overflow-x: auto;
                            min-height: 400px;
                        }

                        .fila-dientes {
                            display: flex;
                            justify-content: space-between;
                            gap: 5px;
                            min-width: 600px;
                        }

                        .fila-dientes.centrada {
                            justify-content: center;
                            gap: 15px;
                        }

                        .diente-wrapper {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            width: 50px;
                        }

                        .fila-dientes.temporales .diente-wrapper {
                            width: 38px;
                        }

                        .numero-diente {
                            color: #5bc0be;
                            font-size: 13px;
                            font-weight: bold;
                            margin: 4px 0;
                        }

                        .caras-interactivas {
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            overflow: hidden;
                            border: 1px solid #999;
                            cursor: pointer;
                        }

                        .odontograma-svg {
                            width: 100%;
                            height: 100%;
                        }

                        .cara-diente {
                            fill: #ffffff;
                            stroke: #999;
                            stroke-width: 2;
                            transition: fill 0.2s;
                            cursor: pointer;
                        }

                        .cara-diente:hover {
                            fill: #f0f0f0;
                        }

                        .anatomia {
                            width: 100%;
                            height: 55px;
                            display: flex;
                            justify-content: center;
                            align-items: flex-end;
                        }

                        .anatomia svg {
                            height: 100%;
                            width: auto;
                        }

                        .diente-wrapper.superior .anatomia svg {
                            transform: scale(1, -1);
                        }
                    </style>

                    <div id="odontograma-lienzo" class="odontograma-lienzo">
                        <div id="fila-perm-sup" class="fila-dientes superior"></div>
                        <div id="fila-temp-sup" class="fila-dientes centrada temporales superior"></div>
                        <div id="fila-temp-inf" class="fila-dientes centrada temporales inferior"></div>
                        <div id="fila-perm-inf" class="fila-dientes inferior"></div>
                    </div>

                  <input type="hidden" id="odontograma-paciente-id" value="">
<input type="hidden" id="odontograma-paciente-edad" value="0">

</div>
</form>
</div>
</div>

@endsection

@section('scripts')
<script>
    // =====================================================================
    // IDX-06 | Script principal del dashboard
    // Mapa rapido de bloques JS:
    // - IDX-JS-01: Estado global
    // - IDX-JS-02: Modal de cita (carga y render)
    // - IDX-JS-03: Calendario mensual
    // - IDX-JS-04: Horarios y reagendado
    // - IDX-JS-05: Odontograma interactivo
    // - IDX-JS-06: Guardado de cambios (AJAX)
    // - IDX-JS-07: Utilidades UI (tabs/widgets/saldo)
    // - IDX-JS-08: Completar cita
    // - IDX-JS-09: Paginacion historial
    // =====================================================================

    // IDX-JS-01 | Estado global de paginacion
    // --- VARIABLES GLOBALES DE PAGINACIÓN ---
    window.todasLasFilas = [];
    window.paginaActual = 1;
    window.filasPorPagina = 4; // Ajustamos a 4 para que se vea bien en el modal

    // IDX-JS-02 | Carga integral de datos en modal de cita
    // Flujo principal del modal: carga datos de cita/paciente y prepara widgets.
    function cargarModalCita(idCita){
        console.log("Abrir cita:", idCita);

    openModal('modal-detalle-cita');

    const form = document.getElementById('form-actualizar-cita');

    if(form){
        form.action = `/citas/${idCita}/actualizar`;
        
        // FIX: Limpiar valores previos para evitar fugas de estado a otras citas
        document.getElementById('input-nueva-fecha').value = '';
        document.getElementById('input-nueva-hora').value = '';
        
        const inputMonto = document.getElementById('input-monto-abono');
        if(inputMonto) inputMonto.value = '';
        
        const textareaNotas = document.querySelector('textarea[name="notas_seguimiento"]');
        if(textareaNotas) textareaNotas.value = '';
    }

    document.getElementById('lbl-nombre').innerText = 'Cargando...';

    fetch(`/api/citas/${idCita}/modal-detalles`)
    .then(res => res.json())
    .then(data => {

        // Datos del Paciente
        document.getElementById('lbl-nombre').innerText = data.paciente.nombres;
        document.getElementById('lbl-paterno').innerText = data.paciente.paterno;
        document.getElementById('lbl-materno').innerText = data.paciente.materno;
        document.getElementById('lbl-edad').innerText = data.paciente.edad;
        document.getElementById('lbl-sexo').innerText = data.paciente.sexo;
        document.getElementById('lbl-telefono').innerText = data.paciente.telefono;
        
        document.getElementById('lbl-sangre').innerText = data.paciente.tipo_sangre || 'N/A';
        document.getElementById('lbl-peso').innerText = data.paciente.peso || 'N/A';
        document.getElementById('lbl-alergias').innerText = data.paciente.alergias || 'Ninguna';
        document.getElementById('lbl-enfermedades').innerText = data.paciente.enfermedades || 'Ninguna';

        // Tabla de Historial (Paginada)
        const tbody = document.getElementById('cita-tabla-body');
        if(tbody && data.filas_tabla) {
            window.todasLasFilas = [];
            
            data.filas_tabla.forEach(f => {
                const tr = document.createElement('tr');
                tr.dataset.citaId = f.id_cita;
                
                // Color según estado
                let colorEstado = '#f59e0b'; // Pendiente/En Proceso
                if(f.estado.toLowerCase() === 'completada') colorEstado = '#10b981';
                if(f.estado.toLowerCase() === 'cancelada') colorEstado = '#ef4444';

                tr.innerHTML = `
                    <td style="padding: 15px; border-right: 2px solid #eee; font-weight: 600;">${f.dia}</td>
                    <td style="padding: 15px; border-right: 2px solid #eee; font-weight: 600;">${f.hora}</td>
                    <td style="padding: 15px; border-right: 2px solid #eee; font-weight: 600;">${f.seguimiento}</td>
                    <td style="padding: 15px; border-right: 2px solid #eee; font-weight: 600; color: #10b981;">+$${f.abono}</td>
                    <td style="padding: 15px; font-weight: 600; color: ${colorEstado};">${f.estado}</td>
                `;
                window.todasLasFilas.push(tr);
            });

            window.paginaActual = 1;
            renderizarPagina();
        }

        // Finanzas
        if(data.finanzas) {
            document.getElementById('lbl-total').innerText = data.finanzas.total;
            document.getElementById('lbl-restante').innerText = data.finanzas.restante;
            document.getElementById('raw-costo-total').value = data.finanzas.total.replace(/,/g, '');
            document.getElementById('raw-total-abonado').value = data.finanzas.pagado.replace(/,/g, '');
        }

        // Odontograma
        if(document.getElementById('odontograma-paciente-id')) {
            document.getElementById('odontograma-paciente-id').value = data.paciente.id_paciente;
        }
        if(document.getElementById('odontograma-paciente-edad')) {
            document.getElementById('odontograma-paciente-edad').value = data.paciente.edad_numero;
            document.dispatchEvent(new CustomEvent('odontograma:edadCargada', { detail: { edad: data.paciente.edad_numero } }));
        }
        if (typeof window.pintarOdontogramaDesdeRegistros === 'function' && Array.isArray(data.odontograma)) {
            window.pintarOdontogramaDesdeRegistros(data.odontograma);
        }

        // Calendario
        if (data.fecha_cita){
            fechaCitaActual = null;
            try {
                if (data.filas_tabla && data.filas_tabla.length > 0 && data.filas_tabla[0].dia){
                    const parts = data.filas_tabla[0].dia.split('/');
                    if(parts.length === 3) {
                        fechaCitaActual = `${parts[2]}-${parts[1]}-${parts[0]}`;
                    }
                }
                else if (data.fila_tabla && data.fila_tabla.fecha_cita){
                    const parts = data.fila_tabla.dia.split('/');
                    if (parts.length === 3){
                        fechaCitaActual = `${parts[2]}-${parts[1]}-${parts[0]}`;
                    }
                }
            } catch (e) {
                console.warn("Aviso: No se pudo establecer la fecha de la cita anterior.");
            }
            // Calculamos exactamente un mes después de la cita actual (o de hoy)
            let fechaParaAgendar = new Date(); 
            if (fechaCitaActual) {
                // Forzamos la zona horaria para evitar desfaces
                fechaParaAgendar = new Date(fechaCitaActual + 'T12:00:00'); 
            }
            
            // Magia de JS: suma 1 mes. Si es Diciembre, cambia a Enero del prox año automáticamente
            fechaParaAgendar.setMonth(fechaParaAgendar.getMonth() + 1);

            calMesActual = fechaParaAgendar.getMonth() + 1;
            calAnioActual = fechaParaAgendar.getFullYear();
            
            cargarCalendarioFuncional(calMesActual, calAnioActual);
        }

    })
    .catch(err=>{
        console.error("Error cargando cita:",err);
    });

}

// IDX-JS-03 | Calendario mensual de disponibilidad
// ==========================================
// CALENDARIO
// ==========================================

let horasOcupadas = [];
let calMesActual = new Date().getMonth() + 1;
let calAnioActual = new Date().getFullYear();
let fechaCitaActual = null;
let contextoHorarioWidget = {
    fecha: null,
    horaInicio: '09:00',
    horaFin: '18:00',
    horasOcupadas: [],
    intervaloMinutos: 15
};

const monthNames = [
"Enero","Febrero","Marzo","Abril","Mayo","Junio",
"Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"
];

// Dibuja el calendario mensual de disponibilidad con semaforizacion por dia.
function cargarCalendarioFuncional(mes, anio){

    const titulo = document.getElementById('cal-mes-anio');
    const grid = document.getElementById('functional-calendar-days');

    if(!grid) return;

    if(titulo){
        titulo.innerText = `${monthNames[mes-1]} ${anio}`;
    }

    grid.innerHTML =
        '<div style="grid-column: span 7; text-align:center; padding:20px;">\
        <i class="fa-solid fa-spinner fa-spin"></i></div>';

    fetch(`/api/calendario/disponibilidad?mes=${mes}&anio=${anio}`)
    .then(res => res.ok ? res.json() : Promise.reject())
    .then(disponibilidad => {

        grid.innerHTML = '';

        const hoy = new Date();
        hoy.setHours(0,0,0,0);

        let minFechaPermitida = hoy;

        if(fechaCitaActual){

            const unDiaAntes = new Date(fechaCitaActual);
            unDiaAntes.setDate(unDiaAntes.getDate()-1);
            unDiaAntes.setHours(0,0,0,0);

            minFechaPermitida = unDiaAntes > hoy ? unDiaAntes : hoy;
        }

        const primerDiaSemana = new Date(anio, mes-1, 1).getDay();

        for(let i=0;i<primerDiaSemana;i++){
            const spacer = document.createElement('div');
            spacer.style.height="35px";
            grid.appendChild(spacer);
        }

        Object.entries(disponibilidad).forEach(([dia,data])=>{

            const div = document.createElement('div');
            div.innerText = dia;

            div.style.cssText = `
                display:flex;
                align-items:center;
                justify-content:center;
                height:35px;
                border-radius:8px;
                font-weight:600;
                font-size:0.9em;
                transition:0.2s;
            `;

            let tooltip = `Día ${dia}`;

            if(data.horas_disponibles !== undefined){
                tooltip += `\nDisponibles: ${data.horas_disponibles}/8`;
            }

            div.title = tooltip;

            const fecha = new Date(anio,mes-1,parseInt(dia));
            fecha.setHours(0,0,0,0);

            const esBloqueado = fecha < minFechaPermitida;

            if(esBloqueado){

                div.style.background="#d1d5db";
                div.style.color="#9ca3af";
                div.style.cursor="not-allowed";
                div.style.opacity="0.5";

            }else{

                switch(data.estado){

                    case 'verde':
                        div.style.background="#32D74B";
                        div.style.color="white";
                    break;

                    case 'amarillo':
                        div.style.background="#FFC107";
                        div.style.color="#333";
                    break;

                    case 'rojo':
                        div.style.background="#EF4444";
                        div.style.color="white";
                    break;

                    default:
                        div.style.background="#f0f0f0";
                        div.style.color="#ccc";
                }

            }

            if(!esBloqueado && data.clickable){

                div.style.cursor="pointer";

                div.onclick=()=>{
                    abrirModalAgendar(dia,mes,anio,data.hora_inicio,data.hora_fin);
                };

                div.onmouseover=()=>div.style.transform="scale(1.1)";
                div.onmouseout=()=>div.style.transform="scale(1)";

            }

            grid.appendChild(div);

        });

    })
    .catch(()=>{
        grid.innerHTML = "<div>Error cargando calendario</div>";
    });

}



// Navegacion de mes en el calendario del modal.
function cambiarMes(delta){

    calMesActual += delta;

    if(calMesActual>12){
        calMesActual=1;
        calAnioActual++;
    }

    if(calMesActual<1){
        calMesActual=12;
        calAnioActual--;
    }

    cargarCalendarioFuncional(calMesActual,calAnioActual);

}



// IDX-JS-04 | Motor de horarios y reprogramacion
// ==========================================
// HORARIOS
// ==========================================

// Genera botones de horarios segun jornada, ocupacion y duracion elegida.
function generarHorariosDisponibles(
    fechaSeleccionada,
    horaInicioStr='09:00',
    horaFinStr='18:00',
    horasOcupadas=[],
    intervaloMinutos=15,
    duracionMinutos=30
){

    const contenedor = document.getElementById('contenedor-horarios');
    const inputHora = document.getElementById('input-nueva-hora');

    if(!contenedor) return;

    inputHora.value='';

    let horariosClinica=[];

    let [hInicio,mInicio]=horaInicioStr.split(':').map(Number);
    let [hFin,mFin]=horaFinStr.split(':').map(Number);

    let currentDate=new Date();
    currentDate.setHours(hInicio,mInicio,0,0);

    let endDate=new Date();
    endDate.setHours(hFin,mFin,0,0);

    while(currentDate<endDate){

        let h=String(currentDate.getHours()).padStart(2,'0');
        let m=String(currentDate.getMinutes()).padStart(2,'0');

        horariosClinica.push(`${h}:${m}`);

        currentDate.setMinutes(currentDate.getMinutes()+intervaloMinutos);

    }

    const horasOcupadasSet = new Set(horasOcupadas);
    const bloquesNecesarios = Math.max(1, Math.ceil(duracionMinutos / intervaloMinutos));

    contenedor.innerHTML='';

    horariosClinica.forEach(hora=>{

        const btn=document.createElement('button');

        btn.type='button';
        btn.className='slot-horario';
        btn.dataset.hora=hora;

        btn.innerText=hora;

        const indiceActual = horariosClinica.indexOf(hora);
        let puedeIniciar = true;

        for (let i = 0; i < bloquesNecesarios; i++) {
            const slot = horariosClinica[indiceActual + i];
            if (!slot || horasOcupadasSet.has(slot)) {
                puedeIniciar = false;
                break;
            }
        }

        if(!puedeIniciar){

            btn.disabled=true;
            btn.style.background="#ef4444";
            btn.style.color="white";

        }else{

            btn.onclick=()=>{

                document.querySelectorAll('.slot-horario').forEach(b=>{
                    b.style.background="white";
                    b.style.color="#333";
                });

                btn.style.background="var(--primary-color)";
                btn.style.color="white";

                inputHora.value=hora;

            };

        }

        contenedor.appendChild(btn);

    });

}

// Recalcula slots del widget cuando cambia fecha, ocupacion o duracion.
function recalcularHorariosWidget() {
    const inputFecha = document.getElementById('input-nueva-fecha');
    const nuevaFecha = inputFecha ? inputFecha.value : null;
    if (!nuevaFecha) return;

    if (contextoHorarioWidget.fecha !== nuevaFecha) {
        fetch(`/api/calendario/horas-ocupadas?fecha=${nuevaFecha}`)
            .then(res => res.ok ? res.json() : { horas_ocupadas: [], intervalo_minutos: 15 })
            .then(data => {
                contextoHorarioWidget.fecha = nuevaFecha;
                contextoHorarioWidget.horasOcupadas = (data.horas_ocupadas || []).map(hora => hora.substring(0, 5));
                contextoHorarioWidget.intervaloMinutos = parseInt(data.intervalo_minutos || 15, 10);
                recalcularHorariosWidget();
            })
            .catch(() => {
                contextoHorarioWidget.fecha = nuevaFecha;
                contextoHorarioWidget.horasOcupadas = [];
                contextoHorarioWidget.intervaloMinutos = 15;
                recalcularHorariosWidget();
            });
        return;
    }

    const duracionSelect = document.getElementById('input-nueva-duracion');
    const duracionMinutos = parseInt(duracionSelect ? duracionSelect.value : '30', 10);

    generarHorariosDisponibles(
        contextoHorarioWidget.fecha,
        contextoHorarioWidget.horaInicio,
        contextoHorarioWidget.horaFin,
        contextoHorarioWidget.horasOcupadas,
        contextoHorarioWidget.intervaloMinutos,
        duracionMinutos
    );
}



    // Abre widget de horario para un dia especifico y consulta horas ocupadas.
    function abrirModalAgendar(dia,mes,anio,horaInicio,horaFin){

        const fechaString =
        `${anio}-${String(mes).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;

        document.getElementById('input-nueva-fecha').value=fechaString;

        fetch(`/api/calendario/horas-ocupadas?fecha=${fechaString}`)
        .then(res=>res.ok?res.json():{horas_ocupadas:[]})
        .then(data=>{

            // 1. Forzamos el formato 'HH:mm' recortando los segundos de lo que mande el servidor
            horasOcupadas = (data.horas_ocupadas || []).map(hora => hora.substring(0, 5));
            const intervaloMinutos = parseInt(data.intervalo_minutos || 15, 10);
            
            // Validar si es el día de hoy para deshabilitar también las horas que ya pasaron
            const esHoy = fechaString === new Date().toISOString().split('T')[0];
            if (esHoy) {
                const now = new Date();
                // Si llamamos generarHorariosDisponibles, podemos mandarle las "horas ocupadas"
                // Pero es más fácil inyectar las horas vencidas aquí
                const [startH, startM] = (horaInicio || '08:00').split(':').map(Number);
                const inicio = new Date();
                inicio.setHours(startH, startM, 0, 0);
                const corte = new Date(now);
                while (inicio < corte) {
                    const hStr = String(inicio.getHours()).padStart(2, '0');
                    const mStr = String(inicio.getMinutes()).padStart(2, '0');
                    const slot = `${hStr}:${mStr}`;
                    if (!horasOcupadas.includes(slot)) horasOcupadas.push(slot);
                    inicio.setMinutes(inicio.getMinutes() + intervaloMinutos);
                }
            }

            contextoHorarioWidget = {
                fecha: fechaString,
                horaInicio: horaInicio || '09:00',
                horaFin: horaFin || '18:00',
                horasOcupadas: horasOcupadas,
                intervaloMinutos: intervaloMinutos
            };

            recalcularHorariosWidget();

            openWidget('widget-horario');

        })
        .catch(err => {
            console.error('Error cargando horas:', err);
            contextoHorarioWidget = {
                fecha: fechaString,
                horaInicio: horaInicio || '09:00',
                horaFin: horaFin || '18:00',
                horasOcupadas: [],
                intervaloMinutos: 15
            };
            recalcularHorariosWidget();
            openWidget('widget-horario');
        });

    }



// ==========================================
// CONFIRMAR HORARIO
// ==========================================

// Valida que exista hora seleccionada antes de cerrar el widget.
function confirmarHorario(){

    const hora =
    document.getElementById('input-nueva-hora').value;

    if(!hora){

        alert("Selecciona una hora");

        return;

    }

    closeWidgets();

}



        // IDX-JS-05 | Odontograma interactivo (render, pintar, guardar, borrar)
        // ==========================================
        // LÓGICA INTERACTIVA ODONTOGRAMA (Dinámica)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function () {
            // Definición de las piezas según el sistema FDI
            const dientesPermSup = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];
            const dientesTempSup = [55, 54, 53, 52, 51, 61, 62, 63, 64, 65];
            const dientesTempInf = [85, 84, 83, 82, 81, 71, 72, 73, 74, 75];
            const dientesPermInf = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];

            const svgCaras = `
                                                                                                                                                            <svg viewBox="0 0 100 100" class="odontograma-svg">
                                                                                                                                                                <polygon class="cara-diente" data-cara="vestibular" points="0,0 100,0 75,25 25,25" />
                                                                                                                                                                <polygon class="cara-diente" data-cara="distal" points="100,0 100,100 75,75 75,25" />
                                                                                                                                                                <polygon class="cara-diente" data-cara="palatina" points="0,100 100,100 75,75 25,75" />
                                                                                                                                                                <polygon class="cara-diente" data-cara="mesial" points="0,0 0,100 25,75 25,25" />
                                                                                                                                                                <circle class="cara-diente" data-cara="oclusal" cx="50" cy="50" r="25" />
                                                                                                                                                            </svg>
                                                                                                                                                        `;
            // Mapea numero FDI de diente al SVG anatomico correspondiente.
            function obtenerIdAnatomia(numero) {
                const numStr = numero.toString();
                const ultimoDigito = parseInt(numStr[numStr.length - 1]);
                const cuadrante = parseInt(numStr[0]);
                const esSuperior = cuadrante === 1 || cuadrante === 2 || cuadrante === 5 || cuadrante === 6;

                if (numero < 50) { // Permanentes
                    if (ultimoDigito === 1 || ultimoDigito === 2) return '#tooth-incisor';
                    if (ultimoDigito === 3) return '#tooth-canine';
                    if (ultimoDigito === 4 || ultimoDigito === 5) return '#tooth-premolar';
                    if (ultimoDigito >= 6) return esSuperior ? '#tooth-molar-upper' : '#tooth-molar-lower';
                } else { // Temporales
                    if (ultimoDigito === 1 || ultimoDigito === 2) return '#tooth-incisor';
                    if (ultimoDigito === 3) return '#tooth-canine';
                    if (ultimoDigito >= 4) return esSuperior ? '#tooth-molar-upper' : '#tooth-molar-lower';
                }
                return '#tooth-incisor'; // Fallback
            }

            // Renderiza una fila completa de dientes (permanentes o temporales).
            function renderizarFila(contenedorId, arrayDientes, orientacion) {
                const contenedor = document.getElementById(contenedorId);
                contenedor.innerHTML = '';
                const esInferior = orientacion === 'inferior';

                arrayDientes.forEach(numero => {
                    const wrapper = document.createElement('div');
                    wrapper.className = `diente-wrapper diente ${orientacion}`;
                    wrapper.dataset.diente = numero;

                    const svgId = obtenerIdAnatomia(numero);
                    const divAnatomia = `
                                                                                                                                                <div class="anatomia">
                                                                                                                                                    <svg><use href="${svgId}"></use></svg>
                                                                                                                                                </div>`;
                    const divNumero = `<div class="numero-diente">${numero}</div>`;
                    const divCaras = `<div class="caras-interactivas">${svgCaras}</div>`;

                    if (esInferior) {
                        wrapper.innerHTML = divNumero + divCaras + divAnatomia;
                    } else {
                        wrapper.innerHTML = divAnatomia + divCaras + divNumero;
                    }
                    contenedor.appendChild(wrapper);
                });
            }

            renderizarFila('fila-perm-sup', dientesPermSup, 'superior');
            renderizarFila('fila-temp-sup', dientesTempSup, 'superior');
            renderizarFila('fila-temp-inf', dientesTempInf, 'inferior');
            renderizarFila('fila-perm-inf', dientesPermInf, 'inferior');

            // Llave unica para identificar una cara concreta de un diente.
            function claveCara(numeroDiente, nombreCara) {
                return `${numeroDiente}-${nombreCara}`;
            }

            // Limpia color/metadata para repintar odontograma desde servidor.
            function limpiarMarcasOdontograma() {
                document.querySelectorAll('.cara-diente').forEach(cara => {
                    cara.style.fill = '#ffffff';
                    delete cara.dataset.odontogramaId;
                });
            }

            // Pinta el odontograma con los ultimos registros de cada cara dental.
            function pintarOdontogramaDesdeRegistros(registros) {
                limpiarMarcasOdontograma();

                const ultimaMarcaPorCara = {};
                (registros || []).forEach(registro => {
                    const key = claveCara(String(registro.numero_diente), registro.cara_diente);
                    if (!ultimaMarcaPorCara[key]) {
                        ultimaMarcaPorCara[key] = registro;
                    }
                });

                Object.values(ultimaMarcaPorCara).forEach(registro => {
                    const selector = `.diente[data-diente="${registro.numero_diente}"] .cara-diente[data-cara="${registro.cara_diente}"]`;
                    const cara = document.querySelector(selector);
                    if (!cara) return;

                    cara.style.fill = registro.estado_diente === 'hallazgo' ? 'blue' : 'red';
                    cara.dataset.odontogramaId = registro.id_odontograma;
                });
            }

            // Refresca en caliente el odontograma tras crear/borrar una marca.
            function recargarOdontogramaPaciente(idPaciente) {
                return fetch(`/api/pacientes/${idPaciente}/odontograma`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            pintarOdontogramaDesdeRegistros(data.data || []);
                        }
                    })
                    .catch(error => {
                        console.error('Error recargando odontograma:', error);
                    });
            }

            window.pintarOdontogramaDesdeRegistros = pintarOdontogramaDesdeRegistros;

            // Mostrar/Ocultar filas según la edad informada por AJAX
            document.addEventListener('odontograma:edadCargada', function (e) {
                const edad = parseInt(e.detail.edad);
                const permSup = document.getElementById('fila-perm-sup');
                const permInf = document.getElementById('fila-perm-inf');
                const tempSup = document.getElementById('fila-temp-sup');
                const tempInf = document.getElementById('fila-temp-inf');

                if (edad <= 5) {
                    permSup.style.display = 'none'; permInf.style.display = 'none';
                    tempSup.style.display = 'flex'; tempInf.style.display = 'flex';
                } else if (edad >= 6 && edad <= 12) {
                    permSup.style.display = 'flex'; permInf.style.display = 'flex';
                    tempSup.style.display = 'flex'; tempInf.style.display = 'flex';
                } else {
                    permSup.style.display = 'flex'; permInf.style.display = 'flex';
                    tempSup.style.display = 'none'; tempInf.style.display = 'none';
                }
            });

            // Usamos delegación de eventos ya que los SVG se generan dinámicamente
            document.getElementById('odontograma-lienzo').addEventListener('click', function (e) {
                if (e.target.classList.contains('cara-diente')) {
                    e.preventDefault();
                    const cara = e.target;
                    const dienteWrapper = cara.closest('.diente');
                    const numeroDiente = dienteWrapper.getAttribute('data-diente');
                    const nombreCara = cara.getAttribute('data-cara');
                    const selectElement = document.getElementById('select-servicio');
                    const tipoRegistro = document.querySelector('input[name="tipo_registro"]:checked').value;

                    const idPaciente = document.getElementById('odontograma-paciente-id').value;
                    if (!idPaciente) {
                        alert('No se pudo identificar el paciente del odontograma.');
                        cara.style.fill = 'white';
                        return;
                    }

                    if (tipoRegistro === 'borrar') {
                        const idOdontograma = cara.dataset.odontogramaId;

                        if (!idOdontograma) {
                            alert('Esa cara del diente no tiene una marca registrada para borrar.');
                            return;
                        }

                        const confirmar = confirm('¿Deseas borrar esta marca del odontograma?');
                        if (!confirmar) return;

                        fetch(`/api/odontograma/${idOdontograma}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    recargarOdontogramaPaciente(idPaciente);
                                } else {
                                    alert('No se pudo borrar la marca: ' + (data.message || 'Desconocido'));
                                }
                            })
                            .catch(error => {
                                console.error('Error al borrar marca:', error);
                                alert('Ocurrió un error al borrar la marca.');
                            });
                        return;
                    }

                    if (!selectElement || !selectElement.value) {
                        alert("Por favor, selecciona un tratamiento primero.");
                        return;
                    }

                    const idServicio = selectElement.value;
                    const color = (tipoRegistro === 'hallazgo') ? 'blue' : 'red';

                    cara.style.fill = color;

                    fetch(`/api/pacientes/${idPaciente}/odontograma`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id_paciente: idPaciente,
                            numero_diente: numeroDiente,
                            cara_diente: nombreCara,
                            id_servicio: idServicio,
                            estado_diente: tipoRegistro,
                            observaciones: 'Creado desde Modal Web Dinámico'
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                if (data.data && data.data.id_odontograma) {
                                    cara.dataset.odontogramaId = data.data.id_odontograma;
                                }
                                console.log('Odontograma actualizado en BD', data);
                            } else {
                                alert("Error al guardar: " + (data.message || "Desconocido"));
                                cara.style.fill = 'white';
                            }
                        })
                        .catch(error => {
                            console.error('Error FETCH:', error);
                            cara.style.fill = 'white';
                        });
                }
            });
        });

        // IDX-JS-06 | Envio AJAX de formulario principal
        // ==========================================
        // AJAX FORM SUBMISSION
        // ==========================================
        // Trigger submit when clicking the external button
        document.getElementById('btn-actualizar-cita').addEventListener('click', function () {
            // VERIFICACIÓN DE FECHA (Petición del usuario)
            if (window.fechaCitaActual) {
                const hoyStr = new Date().toISOString().split('T')[0];
                if (window.fechaCitaActual !== hoyStr) {
                    const confirmacion = confirm("Estás a punto de modificar datos de una cita días previos a la fecha programada o diferente al día de hoy. ¿Estás seguro de continuar?");
                    if (!confirmacion) return;
                }
            }
            document.getElementById('form-actualizar-cita').requestSubmit();
        });

        document.getElementById('form-actualizar-cita').addEventListener('submit', function (e) {
            e.preventDefault();

            const form = e.target;
            const formData = new FormData(form);
            const actionUrl = form.action;

            // Visual feedback (optional)
            const btn = document.getElementById('btn-actualizar-cita'); // ID assigned to "GUARDAR CAMBIOS"
            const originalText = btn.innerText;
            btn.innerText = 'Guardando...';
            btn.disabled = true;

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
// --- RECARGAR TODO EL MODAL DESDE EL SERVIDOR ---
                        // Esto asegura que si se creó una nueva cita de seguimiento, aparezca en la lista
                        const pathSegments = actionUrl.split('/');
                        const currentIdCita = pathSegments[pathSegments.length - 2];
                        
                        cargarModalCita(currentIdCita);

                        // --- ACTUALIZAR TARJETA DE INGRESOS DEL MES EN TIEMPO REAL ---
                        const montoAbonado = parseFloat(document.querySelector('input[name="monto_abono"]').value) || 0;
                        if (montoAbonado > 0) {
                            const lblIngresos = document.getElementById('lbl-ingresos-mes');
                            if (lblIngresos) {
                                // Extraer el valor actual (quitar $ y comas)
                                const valorActual = parseFloat(lblIngresos.innerText.replace(/[$,]/g, '')) || 0;
                                const nuevoTotal = valorActual + montoAbonado;
                                // Formatear con separadores de miles
                                lblIngresos.innerText = '$' + nuevoTotal.toLocaleString('es-MX', { maximumFractionDigits: 0 });
                                // Animación breve para resaltar el cambio
                                lblIngresos.style.transition = 'color 0.4s';
                                lblIngresos.style.color = '#FF9800';
                                setTimeout(() => lblIngresos.style.color = '#333', 1500);
                            }
                        }

                        // Show success
                        alert('¡Actualizado correctamente!');

                        // Limpiar inputs
                        document.querySelector('input[name="monto_abono"]').value = '';
                        const metodoPagoSelect = document.getElementById('input-metodo-pago');
                        if (metodoPagoSelect) { metodoPagoSelect.value = 'efectivo'; }
                        document.querySelector('textarea[name="notas_seguimiento"]').value = '';
                        document.getElementById('input-nueva-fecha').value = '';
                        document.getElementById('input-nueva-hora').value = '';
                        closeWidgets();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error inesperado.');
                })
                .finally(() => {
                    btn.innerText = originalText;
                    btn.disabled = false;
                });
        });


    // IDX-JS-07 | Utilidades de interfaz (tabs, widgets, calculos)

        // Cambia entre tabs principales del modal (resumen/odontograma).
        function switchTab(tabId) {
            // Ocultar todos los tabs que NO sean inner-widgets
            document.querySelectorAll('.tab-content').forEach(tab => {
                if (!tab.classList.contains('inner-widget')) {
                    tab.style.display = 'none';
                }
            });

            // Mostrar el seleccionado
            const tab = document.getElementById(tabId);
            if (tab) {
                tab.style.display = (tabId === 'tab-resumen') ? 'flex' : 'block';
                if (tabId === 'tab-resumen') tab.style.flexDirection = 'column';
            }
        }

        // --- MANEJO DE WIDGETS EMERGENTES ---
        // Abre un widget interno flotante dentro del modal.
        function openWidget(widgetId) {
            document.getElementById('internal-widget-overlay').style.display = 'block';
            document.querySelectorAll('.inner-widget').forEach(w => w.style.display = 'none'); // Cierra otros posibles widgets abiertos
            document.getElementById(widgetId).style.display = 'block';

            // Focus automatically si es el de pago
            if (widgetId === 'widget-pago') {
                setTimeout(() => document.getElementById('input-monto-abono').focus(), 100);
            }
        }

        // Cierra todos los widgets internos y oculta overlay.
        function closeWidgets() {
            document.getElementById('internal-widget-overlay').style.display = 'none';
            document.querySelectorAll('.inner-widget').forEach(w => w.style.display = 'none');
        }

        function cancelarHorario() {
            document.getElementById('input-nueva-fecha').value = '';
            document.getElementById('input-nueva-hora').value = '';
            closeWidgets();
        }

        // Cierra los widgets si das clic afuera (en el overlay)
        document.getElementById('internal-widget-overlay').addEventListener('click', closeWidgets);

        // --- CALCULO MATEMATICO EN TIEMPO REAL ---
        // Recalcula saldo restante en tiempo real segun abono ingresado.
        function calcularVueltoReal() {
            const costoTotalBase = parseFloat(document.getElementById('raw-costo-total').value) || 0;
            const abonoAnterior = parseFloat(document.getElementById('raw-total-abonado').value) || 0;
            const abonoActual = parseFloat(document.getElementById('input-monto-abono').value) || 0;

            const totalAbonadoAcumulado = abonoAnterior + abonoActual;
            let restanteVirtual = costoTotalBase - totalAbonadoAcumulado;

            if (restanteVirtual < 0) restanteVirtual = 0;

            document.getElementById('lbl-restante').innerText = restanteVirtual.toFixed(2);
        }

        // IDX-JS-08 | Cambio de estado de cita a completada
        // ==========================================
        // MARCAR CITA COMO COMPLETADA
        // ==========================================
        // Marca cita como completada desde la tarjeta rapida del dashboard.
        function completarCita(idCita) {
            const btn = document.getElementById('btn-completar-' + idCita);

            // Evitar doble clic
            if (btn) { btn.disabled = true; btn.style.opacity = '0.5'; }

            // ── Llamada AJAX ───────────────────────────────────────────
            fetch('/api/citas/' + idCita + '/completar', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert('Error: ' + (data.message || 'No se pudo completar la cita.'));
                        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                        return;
                    }

                    // ── ACTUALIZAR EL ESTADO EN window.citasData (si existe) ────────────
                    if (window.citasData) {
                        const cita = window.citasData.find(c => c.id === idCita);
                        if (cita) {
                            cita.estado = 'Completada';
                        }
                    }

                    // ── ACTUALIZAR LA TABLA SI ESTÁ ABIERTO EL MODAL ──────────────────
                    if (window.todasLasFilas && window.todasLasFilas.length > 0) {
                        // Buscar en todasLasFilas por idCita
                        const indice = window.todasLasFilas.findIndex(fila => {
                            // Verificar si tiene reference al id
                            return fila.dataset?.citaId === String(idCita) || (fila._citaId && fila._citaId === idCita);
                        });

                        // Si no encontramos, reload para actualizar
                        if (indice === -1) {
                            renderizarPagina();
                        } else {
                            // Actualizar la fila en memoria y re-renderizar
                            renderizarPagina();
                        }
                    }

                    // Animar el overlay de completado
                    const overlay = document.getElementById('overlay-' + idCita);
                    if (overlay) {
                        overlay.style.display = 'flex';
                        const checkPath = document.getElementById('check-path-' + idCita);
                        if (checkPath) {
                            setTimeout(() => {
                                checkPath.style.strokeDashoffset = '0';
                            }, 100);
                        }
                    }

                    // Refresca la página para actualizar la lista de citas pendientes
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                })
                .catch(err => {
                    console.error('Error al completar cita:', err);
                    if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
                    alert('Error de conexión. Inténtalo de nuevo.');
                });
        }

        // IDX-JS-09 | Paginacion del historial de citas del paciente
        // ==========================================
        // FUNCIONES DE PAGINACIÓN
        // ==========================================
        // Renderiza pagina actual de historial de citas en tabla paginada.
        function renderizarPagina() {
            const tbody = document.getElementById('cita-tabla-body');
            if (!tbody || !window.todasLasFilas) return;

            tbody.innerHTML = '';

            const totalPaginas = Math.ceil(window.todasLasFilas.length / window.filasPorPagina);
            const inicio = (window.paginaActual - 1) * window.filasPorPagina;
            const fin = inicio + window.filasPorPagina;

            const filasPagina = window.todasLasFilas.slice(inicio, fin);

            filasPagina.forEach(fila => {
                tbody.appendChild(fila.cloneNode(true));
            });

            // Actualizar info de paginación
            document.getElementById('info-paginacion').innerText = `Página ${window.paginaActual} de ${totalPaginas}`;

            // Actualizar estado de botones
            document.getElementById('btn-pag-anterior').disabled = window.paginaActual === 1;
            document.getElementById('btn-pag-siguiente').disabled = window.paginaActual >= totalPaginas;

            // Cambiar estilo de botones deshabilitados
            const btnAnterior = document.getElementById('btn-pag-anterior');
            const btnSiguiente = document.getElementById('btn-pag-siguiente');

            btnAnterior.style.opacity = window.paginaActual === 1 ? '0.5' : '1';
            btnAnterior.style.cursor = window.paginaActual === 1 ? 'not-allowed' : 'pointer';
            btnSiguiente.style.opacity = window.paginaActual >= totalPaginas ? '0.5' : '1';
            btnSiguiente.style.cursor = window.paginaActual >= totalPaginas ? 'not-allowed' : 'pointer';

            // Mostrar/ocultar controles si solo hay una página
            if (totalPaginas <= 0) {
                document.getElementById('paginacion-controles').style.display = 'none';
            } else {
                document.getElementById('paginacion-controles').style.display = 'flex';
            }
        }

        // Navega entre paginas del historial manteniendo limites validos.
        function cambiarPagina(direccion) {
            if (!window.todasLasFilas) return;

            const totalPaginas = Math.ceil(window.todasLasFilas.length / window.filasPorPagina);
            window.paginaActual += direccion;

            // Validar límites
            if (window.paginaActual < 1) window.paginaActual = 1;
            if (window.paginaActual > totalPaginas) window.paginaActual = totalPaginas;

            renderizarPagina();
        }
    </script>
@endsection