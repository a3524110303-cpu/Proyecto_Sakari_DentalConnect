<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DentalConnect SaaS</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap');

        :root {
            --bg: #f6fbff;
            --surface: #ffffff;
            --primary: #0f6d9c;
            --accent: #14b8a6;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #d7e8f2;
            --shadow: 0 20px 50px rgba(5, 41, 61, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Manrope', sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 8% 8%, #c9f8f1 0, transparent 28%),
                radial-gradient(circle at 90% 15%, #dbeafe 0, transparent 35%),
                var(--bg);
        }

        .container {
            width: min(1120px, 92vw);
            margin: 0 auto;
        }

        header {
            padding: 20px 0;
        }

        .nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            border-radius: 18px;
            padding: 12px 16px;
        }

        .brand {
            font-weight: 800;
            color: var(--primary);
            font-size: 1.2rem;
            text-decoration: none;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 12px;
            padding: 10px 14px;
            border: 1px solid transparent;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #14b8a6, #0f6d9c);
            color: #fff;
            box-shadow: 0 10px 20px rgba(15, 109, 156, 0.25);
        }

        .btn-outline {
            border-color: var(--line);
            color: var(--primary);
            background: #fff;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 22px;
            margin: 22px 0 35px;
        }

        .hero-card,
        .status-card,
        .plan-card,
        .ad-card {
            background: var(--surface);
            border: 1px solid #ecf3f8;
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .hero-card {
            padding: 32px;
        }

        h1 {
            margin: 0 0 12px;
            font-size: clamp(1.8rem, 3.2vw, 2.6rem);
            line-height: 1.1;
        }

        .hero p {
            color: var(--muted);
            margin: 0 0 18px;
        }

        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .badge {
            background: #eef7fb;
            color: var(--primary);
            padding: 8px 10px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .status-card {
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
        }

        .status-title {
            color: var(--muted);
            font-size: 0.9rem;
            margin: 0;
        }

        .status-value {
            font-size: 1.45rem;
            font-weight: 800;
            margin: 0;
        }

        .section-title {
            margin: 0 0 14px;
            font-size: 1.4rem;
        }

        .plans {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 34px;
        }

        .plan-card {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
            overflow: hidden;
        }

        .plan-card.featured {
            border: 1px solid #14b8a6;
            transform: translateY(-5px);
        }

        .plan-name {
            margin: 0;
            font-size: 1.2rem;
            color: var(--primary);
        }

        .plan-price {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 800;
        }

        .plan-price small {
            font-size: 0.8rem;
            color: var(--muted);
            font-weight: 600;
        }

        .plan-list {
            margin: 0;
            padding-left: 18px;
            color: #334155;
            display: grid;
            gap: 6px;
            font-size: 0.92rem;
        }

        .ads {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 30px;
        }

        .ad-card {
            padding: 12px;
        }

        .ad-cover {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 12px;
            background: #edf5fb;
        }

        .ad-title {
            margin: 10px 0 6px;
            font-size: 0.98rem;
            font-weight: 700;
        }

        .ad-desc {
            margin: 0;
            font-size: 0.88rem;
            color: var(--muted);
            min-height: 40px;
        }

        .alert {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 12px;
            font-weight: 700;
        }

        .alert-success {
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fff1f2;
            color: #9f1239;
            border: 1px solid #fecdd3;
        }

        @media (max-width: 900px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .plans,
            .ads {
                grid-template-columns: 1fr;
            }

            .plan-card.featured {
                transform: none;
            }
        }
    </style>
</head>

<body>
    <header class="container">
        <div class="nav">
            <a class="brand" href="{{ route('landing') }}"><i class="fa-solid fa-tooth"></i> DentalConnect SaaS</a>
            <div class="nav-actions">
                @auth
                    <a class="btn btn-outline" href="{{ route('dashboard') }}"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                    <a class="btn btn-outline" href="{{ route('suscripciones.show') }}"><i class="fa-solid fa-crown"></i> Mi Suscripcion</a>
                @else
                    <a class="btn btn-outline" href="{{ route('login') }}">Iniciar sesion</a>
                    <a class="btn btn-primary" href="{{ route('register') }}">Crear clinica</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <section class="hero">
            <article class="hero-card">
                <h1>Gestiona tu clinica dental con un SaaS listo para crecer</h1>
                <p>Centraliza pacientes, citas, tratamientos y captacion en una sola plataforma. Activa el plan que se adapte a tu etapa y escala a Premium o Ultra cuando necesites nuevos modulos.</p>
                <div class="badges">
                    <span class="badge">Agenda inteligente</span>
                    <span class="badge">Historial clínico</span>
                    <span class="badge">Pagos con Stripe</span>
                    <span class="badge">Panel administrativo</span>
                </div>
            </article>

            <aside class="status-card">
                <p class="status-title">Estado de suscripcion</p>
                @if($suscripcionActiva)
                    <p class="status-value">{{ $suscripcionActiva->plan->nombre ?? 'Plan activo' }}</p>
                    <p class="status-title">Activo hasta {{ optional($suscripcionActiva->periodo_fin)->format('d/m/Y') ?? 'sin fecha' }}</p>
                @else
                    <p class="status-value">Sin plan activo</p>
                    <p class="status-title">Activa un plan para desbloquear modulos avanzados.</p>
                @endif
            </aside>
        </section>

        <section>
            <h2 class="section-title">Planes y suscripciones</h2>
            <div class="plans">
                @foreach($planes as $plan)
                    <article class="plan-card {{ $plan->slug === 'premium' ? 'featured' : '' }}">
                        <h3 class="plan-name">{{ $plan->nombre }}</h3>
                        <p class="plan-price">${{ number_format($plan->precio_mensual, 0) }} <small>/ mes</small></p>

                        <ul class="plan-list">
                            @foreach($plan->features ?? [] as $feature)
                                <li>{{ $feature }}</li>
                            @endforeach
                        </ul>

                        @auth
                            <form method="POST" action="{{ route('suscripciones.checkout', $plan->slug) }}">
                                @csrf
                                <button class="btn btn-primary" type="submit" style="width:100%; justify-content:center;">Comprar {{ $plan->nombre }}</button>
                            </form>
                        @else
                            <a class="btn btn-primary" style="justify-content:center;" href="{{ route('register') }}">Crear cuenta y comprar</a>
                        @endauth
                    </article>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="section-title">Publicidad destacada</h2>
            <div class="ads">
                @forelse($publicidad as $ad)
                    <article class="ad-card">
                        @if($ad->imagen_path)
                            <img class="ad-cover" src="{{ route('storage.file', ['path' => $ad->imagen_path]) }}" alt="{{ $ad->titulo }}">
                        @else
                            <div class="ad-cover" style="display:grid;place-items:center;color:#64748b;">
                                <i class="fa-regular fa-image" style="font-size:2rem;"></i>
                            </div>
                        @endif
                        <h3 class="ad-title">{{ $ad->titulo }}</h3>
                        <p class="ad-desc">{{ \Illuminate\Support\Str::limit($ad->descripcion, 100) }}</p>
                    </article>
                @empty
                    <article class="ad-card">
                        <p class="ad-title">No hay campañas activas</p>
                        <p class="ad-desc">Cuando publiques anuncios desde el panel, apareceran aqui.</p>
                    </article>
                @endforelse
            </div>
        </section>
    </main>
</body>

</html>
