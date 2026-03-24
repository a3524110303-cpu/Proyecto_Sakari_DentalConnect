@extends('layouts.app')

@section('titulo', 'Mi Suscripcion')

@section('contenido')
    <h2 class="page-title">Mi Suscripcion</h2>

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
                    <td>{{ strtoupper($suscripcion->estado) }}</td>
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
                    <th>Renovacion automatica</th>
                    <td>{{ $suscripcion->auto_renovar ? 'Si' : 'No' }}</td>
                </tr>
            </table>
        @else
            <p style="margin:0 0 12px;color:#475569;">Aun no tienes una suscripcion activa.</p>
            <a href="{{ route('landing') }}" class="ghost-btn">Ir a planes</a>
        @endif
    </div>
@endsection
