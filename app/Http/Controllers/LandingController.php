<?php

namespace App\Http\Controllers;

use App\Models\PlanSaas;
use App\Models\Publicidad;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    /**
     * Renderiza la landing comercial del SaaS.
     *
     * Carga planes activos, anuncios de publicidad y, si hay sesion,
     * muestra tambien el estado de suscripcion de la clinica.
     */
    public function index()
    {
        $planes = PlanSaas::where('activo', true)->orderBy('nivel')->get();

        $suscripcionActiva = null;

        if (Auth::check() && Auth::user()->id_clinica) {
            $suscripcionActiva = Auth::user()->clinica
                ?->suscripcionActiva()
                ->with('plan')
                ->first();
        }

        return view('landing.index', compact('planes', 'suscripcionActiva'));
    }
}
