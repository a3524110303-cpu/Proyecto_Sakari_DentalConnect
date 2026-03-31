{{--
Layout Principal de la Aplicación (Dashboard)

Este archivo define la estructura base de toda la interfaz administrativa.
Incluye:
- Barra de navegación lateral (Sidebar) con efectos de vidrio (Glassmorphism).
- Contenedor principal para el contenido dinámico (@yield('contenido')).
- Scripts globales para modales, tabs y lógica del odontograma.
- Estilos CSS para el tema visual.
--}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Connect - @yield('titulo')</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #00b4d8;
            --secondary-color: #0077b6;
            --accent-color: #90e0ef;
            --light-bg: #e0fbfc;
            --white: #ffffff;
            --text-dark: #333;
            --text-light: #555;
            --shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            --input-bg: #f0f0f0;
            --border-radius: 8px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            display: flex;
            overflow: hidden;
        }

        /* --- Vertical Glass Nav --- */
        .dashboard-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 80px;
            padding: 20px 10px;
            display: flex;
            justify-content: center;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-right: 1px solid rgba(255, 255, 255, 0.5);
            z-index: 1000;
            transition: width 0.4s ease;
        }

        .dashboard-sidebar:hover {
            width: 250px;
        }

        .navbar {
            display: flex;
            flex-direction: column;
            gap: 15px;
            list-style: none;
            padding: 0;
            width: 100%;
            align-items: center;
        }

        .nav-item {
            position: relative;
            height: 50px;
            width: 50px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            transition: all 0.3s;
            overflow: hidden;
            color: var(--text-light);
        }

        .dashboard-sidebar:hover .nav-item {
            width: 90%;
            padding-left: 10px;
        }

        .nav-item:hover {
            background: rgba(0, 180, 216, 0.1);
            color: var(--primary-color);
        }

        .nav-content {
            display: flex;
            align-items: center;
            width: 100%;
            height: 100%;
            text-decoration: none;
            color: inherit;
        }

        .icon {
            font-size: 1.2rem;
            min-width: 50px;
            display: flex;
            justify-content: center;
        }

        .text {
            white-space: nowrap;
            opacity: 0;
            transform: translateX(-10px);
            transition: 0.3s;
            font-weight: 500;
        }

        .dashboard-sidebar:hover .text {
            opacity: 1;
            transform: translateX(0);
        }

        .nav-item.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 180, 216, 0.4);
        }

        /* --- Main Content --- */
        main {
            margin-left: 80px;
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            padding: 40px;
            transition: margin-left 0.4s ease;
        }

        .dashboard-sidebar:hover~main {
            margin-left: 250px;
        }

        h2.page-title {
            color: #000;
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 2rem;
        }

        .section {
            display: none;
            animation: fadeIn 0.4s ease-out;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* --- Tables & Cards --- */
        .dashboard-table {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        th {
            color: var(--secondary-color);
            font-weight: 600;
        }

        /* --- Appointment List --- */
        .appointment-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .appointment-card {
            background: white;
            border-radius: 8px;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .app-time {
            font-weight: 600;
            color: #444;
            width: 100px;
        }

        .app-patient {
            font-weight: 500;
            color: #000;
            flex: 2;
        }

        .app-treatment {
            color: #666;
            flex: 2;
        }

        .status-check {
            width: 40px;
            height: 40px;
            background: #32D74B;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            border: none;
            transition: 0.2s;
        }

        .status-check:hover {
            background: #28c140;
        }

        .status-badge {
            padding: 8px 20px;
            background: #e0faff;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* --- Buttons --- */
        .ghost-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .ghost-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .ghost-btn.delete-btn {
            background: #ef4444;
        }

        .search-bar {
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 20px;
            outline: none;
            width: 250px;
            font-size: 0.9rem;
        }

        /* --- Cards & Config --- */
        .config-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            flex: 1;
            min-width: 300px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--light-bg);
            margin-bottom: 15px;
        }

        .profile-avatar-sm {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .ad-card {
            background: white;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .ad-image-placeholder {
            height: 120px;
            background: #f0f0f0;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 2rem;
        }

        /* --- Modals --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(5px);
            z-index: 2000;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 500px;
            max-width: 90%;
            position: relative;
            transform: scale(0.9);
            transition: transform 0.3s;
            animation: slideUp 0.3s forwards;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-lg {
            width: 700px;
        }

        .modal-xl {
            width: 900px;
        }

        @keyframes slideUp {
            to {
                transform: scale(1);
            }
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            color: #888;
        }

        .modal-glass h3 {
            text-align: center;
            color: var(--secondary-color);
            margin-bottom: 30px;
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* --- Forms --- */
        .modern-input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 0;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            background: var(--input-bg);
            font-size: 0.95rem;
            outline: none;
            box-sizing: border-box;
            color: #555;
            transition: all 0.2s;
        }

        .modern-input:focus {
            background: white;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 20px;
            margin-bottom: 20px;
        }

        .full-width {
            grid-column: span 2;
        }

        /* --- Odontograma Styles --- */
        .odontograma-container {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: var(--shadow);
        }

        .arcada {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .diente-wrapper {
            text-align: center;
            width: 45px;
        }

        .numero-diente {
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
        }

        .diente-geo {
            position: relative;
            width: 35px;
            height: 35px;
            margin: 0 auto;
            cursor: pointer;
            border: 1px solid #ccc;
            background: #eee;
        }

        .zona {
            position: absolute;
            background-color: var(--white);
            border: 1px solid #333;
        }

        .zona:hover {
            opacity: 0.8;
        }

        .zona.vestibular {
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            clip-path: polygon(0 0, 100% 0, 80% 100%, 20% 100%);
        }

        .zona.lingual {
            bottom: 0;
            left: 0;
            width: 100%;
            height: 8px;
            clip-path: polygon(20% 0, 80% 0, 100% 100%, 0 100%);
        }

        .zona.mesial {
            top: 8px;
            left: 0;
            width: 8px;
            height: 18px;
            clip-path: polygon(0 0, 100% 20%, 100% 80%, 0 100%);
        }

        .zona.distal {
            top: 8px;
            right: 0;
            width: 8px;
            height: 18px;
            clip-path: polygon(0 20%, 100% 0, 100% 100%, 0 80%);
        }

        .zona.oclusal {
            top: 8px;
            left: 8px;
            width: 18px;
            height: 18px;
            background-color: #eee;
        }

        .controles {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .btn-tool {
            padding: 6px 12px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            color: white;
            font-weight: 500;
        }

        .estado-caries {
            background-color: #ef4444 !important;
        }

        .estado-realizado {
            background-color: #3b82f6 !important;
        }

        /* --- Tabs --- */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-btn {
            background: #f0f0f0;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            transition: all 0.3s;
        }

        .tab-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            font-size: 0.95rem;
        }

        .patient-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .notif-bell-wrapper {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 1100;
        }

        .notif-bell-btn {
            position: relative;
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 999px;
            background: #fff;
            color: #4b5563;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            cursor: pointer;
        }

        .notif-bell-btn:hover {
            color: #2563eb;
        }

        .notif-bell-count {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #dc2626;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .notif-panel {
            display: none;
            position: absolute;
            right: 0;
            margin-top: 10px;
            width: 340px;
            max-height: 420px;
            overflow: hidden;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 18px 34px rgba(0, 0, 0, 0.16);
        }

        .notif-panel.open {
            display: block;
        }

        .notif-header {
            padding: 12px 14px;
            background: #eff6ff;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
        }

        .notif-list {
            max-height: 360px;
            overflow-y: auto;
        }

        .notif-item {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .notif-item-title {
            color: #111827;
            font-size: 15px;
            font-weight: 700;
            margin: 0;
        }

        .notif-item-sub {
            color: #6b7280;
            font-size: 13px;
            margin-top: 6px;
        }

        .notif-item-date {
            color: #2563eb;
            font-weight: 700;
        }

        .notif-actions {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .notif-btn {
            border: 0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 10px;
            cursor: pointer;
        }

        .notif-btn.ok {
            background: #22c55e;
            color: #fff;
        }

        .notif-btn.no {
            background: #e5e7eb;
            color: #111827;
        }

        .notif-empty {
            padding: 18px;
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <nav class="dashboard-sidebar">
        <ul class="navbar">
            @if(auth()->check() && auth()->user()->rol !== 'administrador')
                <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="nav-content">
                        <span class="icon"><i class="fa-solid fa-house"></i></span>
                        <span class="text">Inicio</span>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('pacientes.index') ? 'active' : '' }}">
                    <a href="{{ route('pacientes.index') }}" class="nav-content">
                        <span class="icon"><i class="fa-solid fa-users"></i></span>
                        <span class="text">Pacientes</span>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('tratamientos.index') ? 'active' : '' }}">
                    <a href="{{ route('tratamientos.index') }}" class="nav-content">
                        <span class="icon"><i class="fa-solid fa-notes-medical"></i></span>
                        <span class="text">Tratamientos</span>
                    </a>
                </li>

                @if(auth()->user()->clinica->hasPlanAtLeast('premium'))
                    <li class="nav-item {{ request()->routeIs('publicidad.index') ? 'active' : '' }}">
                        <a href="{{ route('publicidad.index') }}" class="nav-content">
                            <span class="icon"><i class="fa-solid fa-bullhorn"></i></span>
                            <span class="text">Publicidad</span>
                        </a>
                    </li>
                @else
                    <li class="nav-item">
                        <a href="{{ route('suscripciones.show') }}" class="nav-content" style="color: #94a3b8;">
                            <span class="icon"><i class="fa-solid fa-lock"></i></span>
                            <span class="text">Publicidad <span style="font-size: 0.7rem; background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">Premium 🔒</span></span>
                        </a>
                    </li>
                @endif

                <li class="nav-item {{ request()->routeIs('configuracion.index') ? 'active' : '' }}">
                    <a href="{{ route('configuracion.index') }}" class="nav-content">
                        <span class="icon"><i class="fa-solid fa-gear"></i></span>
                        <span class="text">Configuración</span>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('suscripciones.show') ? 'active' : '' }}">
                    <a href="{{ route('suscripciones.show') }}" class="nav-content">
                        <span class="icon"><i class="fa-solid fa-crown"></i></span>
                        <span class="text">Suscripción</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->rol === 'administrador')
                <li class="nav-item {{ request()->routeIs('admin.panel') ? 'active' : '' }}">
                    <a href="{{ route('admin.panel') }}" class="nav-content">
                        <span class="icon"><i class="fa-solid fa-shield-halved"></i></span>
                        <span class="text">Admin SaaS</span>
                    </a>
                </li>
            @endif

            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST" id="logout-form">
                    @csrf
                </form>
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="nav-content">
                    <span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span>
                    <span class="text">Salir</span>
                </a>
            </li>
        </ul>
    </nav>

    <main>
        <div id="notif-bell" class="notif-bell-wrapper">
            <button id="notif-bell-btn" class="notif-bell-btn" type="button" aria-label="Notificaciones">
                <i class="fas fa-bell"></i>
                <span id="notif-bell-count" class="notif-bell-count">0</span>
            </button>

            <div id="notif-panel" class="notif-panel">
                <div class="notif-header">Peticiones de Reagenda</div>
                <div id="notif-list" class="notif-list">
                    <div class="notif-empty">Todo al día.</div>
                </div>
            </div>
        </div>

        @yield('contenido')
    </main>

    @yield('modales')

    <script>
        const notifUi = {
            wrapper: null,
            panel: null,
            list: null,
            count: null,
        };

        function renderNotificaciones(items) {
            if (!notifUi.list) return;

            if (!items || items.length === 0) {
                notifUi.list.innerHTML = '<div class="notif-empty">Todo al día.</div>';
                notifUi.count.style.display = 'none';
                return;
            }

            notifUi.count.textContent = String(items.length);
            notifUi.count.style.display = 'inline-flex';

            notifUi.list.innerHTML = items.map(function (notif) {
                let datosObj = {};
                if (notif && notif.datos) {
                    try {
                        datosObj = typeof notif.datos === 'string' ? JSON.parse(notif.datos) : notif.datos;
                    } catch (e) {
                        datosObj = typeof notif.datos === 'object' ? notif.datos : {};
                    }
                }
                const paciente = datosObj.paciente ? datosObj.paciente : 'Paciente';

                let nuevaFecha = datosObj.nueva_fecha || datosObj.fecha || datosObj.fecha_nueva || '-';
                let nuevaHora = datosObj.nueva_hora || datosObj.hora || datosObj.hora_nueva || '-';

                if ((nuevaFecha === '-' || nuevaHora === '-') && (datosObj.nueva_fecha_hora || datosObj.fecha_hora || datosObj.fecha_sugerida)) {
                    const fechaHoraRaw = datosObj.nueva_fecha_hora || datosObj.fecha_hora || datosObj.fecha_sugerida;
                    const dt = new Date(fechaHoraRaw);
                    if (!isNaN(dt.getTime())) {
                        if (nuevaFecha === '-') nuevaFecha = dt.toISOString().slice(0, 10);
                        if (nuevaHora === '-') nuevaHora = dt.toTimeString().slice(0, 5);
                    }
                }

                return '<div class="notif-item">'
                    + '<p class="notif-item-title">' + paciente + '</p>'
                    + '<div class="notif-item-sub">Sugiere cambiar al: <span class="notif-item-date">' + nuevaFecha + ' ' + nuevaHora + '</span></div>'
                    + '<div class="notif-actions">'
                    + '<button class="notif-btn ok" type="button" onclick="procesarReagendaNotificacion(' + notif.id_notificacion + ', \'aceptar\')"><i class="fas fa-check"></i> Aceptar</button>'
                    + '<button class="notif-btn no" type="button" onclick="procesarReagendaNotificacion(' + notif.id_notificacion + ', \'rechazar\')"><i class="fas fa-times"></i> Ignorar</button>'
                    + '</div>'
                    + '</div>';
            }).join('');
        }

        function cargarNotificacionesReagenda() {
            fetch('/api/notificaciones/reagenda', { credentials: 'same-origin' })
                .then(function (res) { 
                    if (!res.ok) throw new Error('API Error');
                    return res.json(); 
                })
                .then(function (data) {
                    const items = Array.isArray(data) ? data : [];
                    renderNotificaciones(items);
                })
                .catch(function (error) {
                    console.error('Error fetching notifications:', error);
                    renderNotificaciones([]);
                });
        }

        function procesarReagendaNotificacion(id, accion) {
            fetch('/api/notificaciones/' + id + '/procesar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ accion: accion }),
                credentials: 'same-origin'
            })
            .then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (body) {
                    return { ok: res.ok, body: body };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    const msg = result.body && result.body.message
                        ? result.body.message
                        : 'No se pudo procesar la solicitud de reagenda.';
                    alert(msg);
                    return;
                }

                cargarNotificacionesReagenda();
                if (accion === 'aceptar') {
                    window.location.reload();
                }
            })
            .catch(function () {
                alert('Error de red al procesar la solicitud. Intenta de nuevo.');
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            notifUi.wrapper = document.getElementById('notif-bell');
            notifUi.panel = document.getElementById('notif-panel');
            notifUi.list = document.getElementById('notif-list');
            notifUi.count = document.getElementById('notif-bell-count');

            const btn = document.getElementById('notif-bell-btn');
            if (btn && notifUi.panel) {
                btn.addEventListener('click', function () {
                    notifUi.panel.classList.toggle('open');
                });
            }

            document.addEventListener('click', function (event) {
                if (!notifUi.wrapper || !notifUi.panel) return;
                if (!notifUi.wrapper.contains(event.target)) {
                    notifUi.panel.classList.remove('open');
                }
            });

            cargarNotificacionesReagenda();
        });

        // --- Modal System ---
        // Abre un modal por id reutilizable en todas las secciones del panel.
        function openModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('active');
            modal.style.display = 'flex';
            modal.style.opacity = '1';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
        }
        // Cierra un modal por id reutilizable en todas las secciones del panel.
        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('active');
            modal.style.display = '';
            modal.style.opacity = '';
            modal.style.justifyContent = '';
            modal.style.alignItems = '';
        }
        window.onclick = function (event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('active');
            }
        }

        // --- Tabs Logic ---
        // Alterna tabs en el modal activo (comportamiento global compartido).
        function switchTab(tabId) {
            // Encuentra el contenedor padre (modal) de este botón
            const parent = document.querySelector('.modal-overlay.active .modal-glass') || document;

            // Ocultar tabs dentro de este contexto
            parent.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            parent.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

            // Activar seleccionada
            const targetContent = document.getElementById(tabId);
            if (targetContent) targetContent.classList.add('active');

            // Estilo al botón (simple hack para identificar el clickeado si no pasamos 'this')
            // Recomendable pasar 'this' en el onclick en el HTML
            if (event.target) event.target.classList.add('active');
        }

        // --- Odontograma Logic (Global) ---
        let herramientaActual = 'caries';
        // Cambia herramienta activa de pintura en odontograma simple.
        function setHerramienta(h) { herramientaActual = h; }

        // Crea el HTML de un diente con sus cinco zonas interactivas.
        function renderizarDiente(numero) {
            return `
                <div class="diente-wrapper">
                    <div class="numero-diente">${numero}</div>
                    <div class="diente-geo" id="diente-${numero}">
                        <div class="zona vestibular" onclick="marcarZona(this, ${numero}, 'vestibular')"></div>
                        <div class="zona distal" onclick="marcarZona(this, ${numero}, 'distal')"></div>
                        <div class="zona mesial" onclick="marcarZona(this, ${numero}, 'mesial')"></div>
                        <div class="zona lingual" onclick="marcarZona(this, ${numero}, 'lingual')"></div>
                        <div class="zona oclusal" onclick="marcarZona(this, ${numero}, 'oclusal')"></div>
                    </div>
                </div>`;
        }

        // Aplica visualmente el estado seleccionado sobre una cara del diente.
        function marcarZona(elemento, idDiente, zona) {
            elemento.classList.remove('estado-caries', 'estado-realizado');
            if (herramientaActual === 'caries') elemento.classList.add('estado-caries');
            else if (herramientaActual === 'realizado') elemento.classList.add('estado-realizado');
        }

        // Inicializar Odontogramas cuando se abren los modales
        // Inicializa una plantilla de odontograma solo una vez por contenedor.
        function initOdontogram(containerId) {
            const contenedor = document.getElementById(containerId);
            if (!contenedor || contenedor.innerHTML !== '') return;

            const dientesQ1 = [18, 17, 16, 15, 14, 13, 12, 11];
            const dientesQ2 = [21, 22, 23, 24, 25, 26, 27, 28];

            const divQ1 = document.createElement('div'); divQ1.className = 'arcada';
            dientesQ1.forEach(num => divQ1.innerHTML += renderizarDiente(num));

            const divQ2 = document.createElement('div'); divQ2.className = 'arcada';
            dientesQ2.forEach(num => divQ2.innerHTML += renderizarDiente(num));

            contenedor.appendChild(divQ1);
            contenedor.appendChild(divQ2);
        }
    </script>

    @yield('scripts')
</body>

</html>