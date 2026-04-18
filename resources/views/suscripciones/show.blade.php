@extends('layouts.app')

@section('titulo', 'Mi Suscripción')

@section('contenido')

@php
    use Carbon\Carbon;
    $formatFecha = fn($dt) => $dt ? Carbon::parse($dt)->locale('es')->isoFormat('D MMM YYYY · H:mm') . ' h' : null;
@endphp

<style>
    .sus-card {
        background: var(--white);
        border-radius: 18px;
        border: 1px solid var(--light-bg);
        box-shadow: var(--shadow);
        max-width: 820px;
        overflow: hidden;
    }
    .sus-header {
        background: linear-gradient(135deg, #0096c7 0%, #023e8a 100%);
        padding: 28px 32px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }
    .sus-header .plan-badge {
        background: rgba(255,255,255,0.18);
        border: 1.5px solid rgba(255,255,255,0.35);
        border-radius: 12px;
        padding: 6px 18px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .sus-header h3 { font-size: 1.45rem; font-weight: 800; margin: 0; }
    .sus-header p  { margin: 4px 0 0; font-size: 0.88rem; opacity: 0.85; }

    .sus-estado-badge {
        margin-left: auto;
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .sus-estado-active  { background: #d1fae5; color: #065f46; }
    .sus-estado-other   { background: #fee2e2; color: #991b1b; }

    .sus-body {
        padding: 28px 32px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px 32px;
    }
    .sus-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .sus-field .label {
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: var(--text-light);
    }
    .sus-field .value {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-dark);
    }
    .sus-field .value.date {
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .sus-field .value.date i { color: var(--primary-color); }
    .date-na {
        color: var(--text-light);
        font-size: 0.88rem;
        font-style: italic;
        font-weight: 500;
    }

    .sus-divider {
        grid-column: 1 / -1;
        border: none;
        border-top: 1px solid var(--light-bg);
        margin: 4px 0;
    }

    .sus-renovar-panel {
        grid-column: 1 / -1;
        background: var(--input-bg);
        border: 1px dashed #fde68a;
        border-radius: 12px;
        padding: 18px 22px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .sus-sync-notice {
        grid-column: 1 / -1;
        background: rgba(59, 130, 246, 0.05);
        border: 1px dashed rgba(59, 130, 246, 0.4);
        border-radius: 12px;
        padding: 14px 20px;
        font-size: 0.85rem;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    @media (max-width: 620px) {
        .sus-body { grid-template-columns: 1fr; padding: 22px 20px; }
        .sus-header { padding: 22px 20px; }
        .sus-estado-badge { margin-left: 0; }
    }
</style>

<h2 class="page-title">Mi Suscripción</h2>

@if(session('success'))
    <div style="background:#ecfdf5;border:1px solid #bbf7d0;padding:12px 16px;border-radius:10px;margin-bottom:16px;color:#166534;font-weight:700;max-width:820px;">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div style="background:#fff1f2;border:1px solid #fecdd3;padding:12px 16px;border-radius:10px;margin-bottom:16px;color:#9f1239;font-weight:700;max-width:820px;">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
    </div>
@endif

@if($suscripcion)
    @php
        $esActiva   = in_array($suscripcion->estado, ['active', 'trialing']);
        $inicio     = $formatFecha($suscripcion->periodo_inicio);
        $fin        = $formatFecha($suscripcion->periodo_fin);
        $sinFechas  = is_null($suscripcion->periodo_inicio) && is_null($suscripcion->periodo_fin);
        $tieneStripe = !empty($suscripcion->stripe_subscription_id);

        $diasRestantes = null;
        if ($suscripcion->periodo_fin) {
            $diasRestantes = (int) now()->diffInDays(\Carbon\Carbon::parse($suscripcion->periodo_fin), false);
        }
    @endphp

    <div class="sus-card">

        {{-- Encabezado con gradiente --}}
        <div class="sus-header">
            <div>
                <span class="plan-badge">{{ $suscripcion->plan->nombre ?? 'Sin Plan' }}</span>
                <h3 style="margin-top:10px;">Plan {{ $suscripcion->plan->nombre ?? '' }}</h3>
                <p>${{ number_format((float) $suscripcion->monto_periodo, 2) }} {{ strtoupper($suscripcion->moneda ?? 'MXN') }} / mes</p>
            </div>
            <span class="sus-estado-badge {{ $esActiva ? 'sus-estado-active' : 'sus-estado-other' }}">
                <i class="fa-solid fa-{{ $esActiva ? 'circle-check' : 'circle-xmark' }}"></i>
                {{ $esActiva ? 'Activa' : strtoupper($suscripcion->estado) }}
            </span>
        </div>

        {{-- Cuerpo con grid --}}
        <div class="sus-body">

            {{-- Aviso de sincronización si las fechas son nulas --}}
            @if($sinFechas && $tieneStripe)
                <div class="sus-sync-notice">
                    <i class="fa-solid fa-rotate" style="flex-shrink:0;"></i>
                    <span>Las fechas del período no están disponibles aún. Haz clic en <strong>Sincronizar</strong> para obtenerlas de Stripe.</span>
                    <a href="{{ route('suscripciones.show') }}" style="font-weight:700; color:#1d4ed8; white-space:nowrap; text-decoration:none;">
                        <i class="fa-solid fa-rotate-right"></i> Sincronizar
                    </a>
                </div>
            @elseif($sinFechas)
                <div class="sus-sync-notice">
                    <i class="fa-solid fa-circle-info" style="flex-shrink:0;"></i>
                    <span>Las fechas del período aún no han sido asignadas a esta suscripción.</span>
                </div>
            @endif

            {{-- Periodo inicio --}}
            <div class="sus-field">
                <span class="label"><i class="fa-regular fa-calendar-check" style="margin-right:4px;"></i> Inicio del período</span>
                <span class="value date">
                    @if($inicio)
                        <i class="fa-solid fa-calendar-day"></i> {{ $inicio }}
                    @else
                        <span class="date-na"><i class="fa-regular fa-clock"></i> Pendiente de sincronización</span>
                    @endif
                </span>
            </div>

            {{-- Periodo fin --}}
            <div class="sus-field">
                <span class="label"><i class="fa-regular fa-calendar-xmark" style="margin-right:4px;"></i> Fin del período</span>
                <span class="value date">
                    @if($fin)
                        <i class="fa-solid fa-calendar-day"></i> {{ $fin }}
                        @if(!is_null($diasRestantes))
                            <span style="font-size:0.75rem; font-weight:700; padding:2px 10px; border-radius:20px;
                                background:{{ $diasRestantes <= 7 ? '#fef3c7' : '#e0f2fe' }};
                                color:{{ $diasRestantes <= 7 ? '#b45309' : '#0369a1' }};">
                                {{ $diasRestantes > 0 ? "en $diasRestantes días" : ($diasRestantes === 0 ? 'vence hoy' : 'vencida') }}
                            </span>
                        @endif
                    @else
                        <span class="date-na"><i class="fa-regular fa-clock"></i> Pendiente de sincronización</span>
                    @endif
                </span>
            </div>

            <hr class="sus-divider">

            {{-- Renovación automática --}}
            <div class="sus-field">
                <span class="label"><i class="fa-solid fa-rotate" style="margin-right:4px;"></i> Renovación automática</span>
                <span class="value" style="color: {{ $suscripcion->auto_renovar ? '#065f46' : '#991b1b' }};">
                    <i class="fa-solid fa-{{ $suscripcion->auto_renovar ? 'toggle-on' : 'toggle-off' }}"></i>
                    {{ $suscripcion->auto_renovar ? 'Activada' : 'Desactivada' }}
                </span>
            </div>

            {{-- ID de suscripción Stripe --}}
            @if($tieneStripe)
                <div class="sus-field">
                    <span class="label"><i class="fa-brands fa-stripe" style="margin-right:4px;"></i> ID Stripe</span>
                    <span class="value" style="font-size:0.78rem; color:#64748b; font-weight:500; word-break:break-all;">
                        {{ $suscripcion->stripe_subscription_id }}
                    </span>
                </div>
            @endif

            {{-- Panel de renovación si la suscripción está vencida --}}
            @if(!$esActiva)
                <div class="sus-renovar-panel">
                    <div>
                        <strong style="color:var(--text-dark);"><i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i> Tu suscripción ha expirado</strong>
                        <p style="margin:4px 0 0; color:var(--text-light); font-size:0.85rem;">Renueva tu plan para seguir usando todas las funciones de DentalConnect.</p>
                    </div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        @if(isset($suscripcion->plan) && $suscripcion->plan->slug)
                            <form action="{{ route('suscripciones.checkout', $suscripcion->plan->slug) }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" style="background:#0096c7;color:#fff;border:none;padding:10px 22px;border-radius:10px;font-weight:700;cursor:pointer;">
                                    <i class="fa-solid fa-rotate-right"></i> Renovar plan
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('landing') }}" style="color:#0077b6;font-weight:700;padding:10px 22px;border:2px solid #0077b6;border-radius:10px;text-decoration:none;display:inline-block;">
                            Ver planes
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>

@else
    <div style="background:var(--white);border:1px solid var(--light-bg);border-radius:18px;box-shadow:var(--shadow);max-width:820px;padding:48px;text-align:center;">
        <i class="fa-solid fa-crown" style="font-size:3rem;color:var(--light-bg);margin-bottom:16px;display:block;"></i>
        <h3 style="color:var(--text-dark);margin-bottom:8px;">Sin suscripción activa</h3>
        <p style="color:var(--text-light);margin-bottom:24px;">Aún no tienes una suscripción activa. Elige un plan para acceder a todas las funciones.</p>
        <a href="{{ route('landing') }}" class="ghost-btn">Ver planes disponibles</a>
    </div>
@endif

@endsection
