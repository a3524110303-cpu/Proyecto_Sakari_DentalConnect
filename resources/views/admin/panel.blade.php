@extends('layouts.app')

@section('titulo', 'Panel Admin SaaS')

@section('contenido')
    <h2 class="page-title">Panel Administrativo SaaS</h2>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:20px;">
        <div style="background:#fff;border-radius:14px;padding:18px;border-left:4px solid #0f6d9c;box-shadow:var(--shadow);">
            <div style="color:#64748b;font-size:0.85rem;">Clinicas registradas</div>
            <div style="font-size:1.7rem;font-weight:800;">{{ $clinicas->count() }}</div>
        </div>
        <div style="background:#fff;border-radius:14px;padding:18px;border-left:4px solid #14b8a6;box-shadow:var(--shadow);">
            <div style="color:#64748b;font-size:0.85rem;">Suscripciones activas</div>
            <div style="font-size:1.7rem;font-weight:800;">{{ $suscripcionesActivas }}</div>
        </div>
        <div style="background:#fff;border-radius:14px;padding:18px;border-left:4px solid #f59e0b;box-shadow:var(--shadow);">
            <div style="color:#64748b;font-size:0.85rem;">MRR estimado</div>
            <div style="font-size:1.7rem;font-weight:800;">${{ number_format($mrr, 2) }}</div>
        </div>
    </div>

    <div class="dashboard-table" style="margin-bottom:24px;">
        <h3 style="margin:0 0 14px;">Clientes (Clinicas)</h3>
        <table>
            <thead>
                <tr>
                    <th>Clinica</th>
                    <th>Telefono</th>
                    <th>Estado</th>
                    <th>Plan actual</th>
                    <th>Usuarios</th>
                    <th>Doctores</th>
                    <th>Recepcion</th>
                    <th>Citas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clinicas as $clinica)
                    @php
                        $sub = $clinica->suscripcionActiva;
                    @endphp
                    <tr>
                        <td>{{ $clinica->nombre_comercial }}</td>
                        <td>{{ $clinica->numero_telefono ?? 'N/A' }}</td>
                        <td>{{ ucfirst($sub->estado ?? 'sin plan') }}</td>
                        <td>{{ $sub?->plan?->nombre ?? 'Sin suscripcion' }}</td>
                        <td>{{ $clinica->total_usuarios }}</td>
                        <td>{{ $clinica->total_doctores }}</td>
                        <td>{{ $clinica->total_recepcionistas }}</td>
                        <td>{{ $clinica->total_citas }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">No hay clinicas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dashboard-table">
        <h3 style="margin:0 0 14px;">Publicidad reciente</h3>
        <table>
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Autor</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($publicidadReciente as $ad)
                    <tr>
                        <td>{{ $ad->titulo }}</td>
                        <td>{{ $ad->usuario->nombre_completo ?? 'N/A' }}</td>
                        <td>{{ $ad->activo ? 'Activa' : 'Inactiva' }}</td>
                        <td>{{ optional($ad->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No hay publicidad registrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
