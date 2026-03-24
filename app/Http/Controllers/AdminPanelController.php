<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Publicidad;
use App\Models\SuscripcionClinica;

class AdminPanelController extends Controller
{
    public function index()
    {
        $clinicas = Clinica::query()
            ->withCount([
                'usuarios as total_usuarios',
                'usuarios as total_doctores' => fn ($query) => $query->where('rol', 'doctor'),
                'usuarios as total_recepcionistas' => fn ($query) => $query->where('rol', 'recepcionista'),
                'citas as total_citas',
            ])
            ->with(['suscripcionActiva.plan'])
            ->orderByDesc('created_at')
            ->get();

        $publicidadReciente = Publicidad::with('usuario')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $suscripcionesActivas = SuscripcionClinica::whereIn('estado', ['active', 'trialing'])->count();

        $mrr = SuscripcionClinica::query()
            ->join('saas_planes', 'saas_planes.id_plan', '=', 'suscripciones_clinica.id_plan')
            ->whereIn('suscripciones_clinica.estado', ['active', 'trialing'])
            ->sum('saas_planes.precio_mensual');

        return view('admin.panel', [
            'clinicas' => $clinicas,
            'publicidadReciente' => $publicidadReciente,
            'suscripcionesActivas' => $suscripcionesActivas,
            'mrr' => $mrr,
        ]);
    }
}
