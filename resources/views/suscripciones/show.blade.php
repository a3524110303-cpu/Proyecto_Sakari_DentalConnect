@extends('layouts.app')

@section('titulo', 'Mi Suscripción')

@section('contenido')
    <h2 class="page-title">Mi Suscripción</h2>

    @if(session('success'))
        <div style="background:#ecfdf5;border:1px solid #bbf7d0;padding:12px;border-radius:10px;margin-bottom:14px;color:#166534;font-weight:700;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background:#fff1f2;border:1px solid #fecdd3;padding:12px;border-radius:10px;margin-bottom:14px;color:#9f1239;font-weight:700;">
            {{ session('error') }}
        </div>
    @endif

    <div class="dashboard-table" style="max-width:900px;">
        @if($suscripcion)
            <table>
                <tr>
                    <th>Plan</th>
                    <td>{{ $suscripcion->plan->nombre ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Estado</th>
                    <td>
                        @if($suscripcion->estado === 'active')
                            <span style="color: #166534; font-weight: bold;">ACTIVA</span>
                        @else
                            <span style="color: #9f1239; font-weight: bold;">{{ strtoupper($suscripcion->estado) }}</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Monto mensual</th>
                    <td>${{ number_format((float) $suscripcion->monto_periodo, 2) }} {{ strtoupper($suscripcion->moneda) }}</td>
                </tr>
                <tr>
                    <th>Periodo inicio</th>
                    <td>{{ optional($suscripcion->periodo_inicio)->format('d/m/Y H:i') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Periodo fin</th>
                    <td>{{ optional($suscripcion->periodo_fin)->format('d/m/Y H:i') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Renovación automática</th>
                    <td>{{ $suscripcion->auto_renovar ? 'Si' : 'No' }}</td>
                </tr>
            </table>

            {{-- PANEL DE RENOVACIÓN PARA SUSCRIPCIONES INACTIVAS --}}
            @if($suscripcion->estado !== 'active' && $suscripcion->estado !== 'trialing')
                <div style="margin-top: 24px; padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <h3 style="margin-top: 0; color: #1e293b; font-size: 1.125rem;">Tu suscripción ha expirado</h3>
                    <p style="color: #475569; margin-bottom: 16px;">Para seguir disfrutando de los beneficios de DentalConnect, renueva tu plan actual o elige uno nuevo.</p>
                    
                    <div style="display: flex; gap: 12px; align-items: center;">
                        @if(isset($suscripcion->plan) && $suscripcion->plan->slug)
                        <form action="{{ route('suscripciones.checkout', $suscripcion->plan->slug) }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" style="background-color: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer;">
                                Renovar {{ $suscripcion->plan->nombre }}
                            </button>
                        </form>
                        @endif
                        <a href="{{ route('landing') }}" style="color: #2563eb; text-decoration: none; font-weight: bold; padding: 10px 20px; border: 1px solid #2563eb; border-radius: 6px;">Ver otros planes</a>
                    </div>
                </div>
            @endif

        @else
            <p style="margin:0 0 12px;color:#475569;">Aún no tienes una suscripción activa.</p>
            <a href="{{ route('landing') }}" class="ghost-btn">Ir a planes</a>
        @endif
    </div>
@endsection
