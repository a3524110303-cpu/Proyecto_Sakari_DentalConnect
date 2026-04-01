<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanLevel
{
    public function handle(Request $request, Closure $next, string $requiredSlug = 'basic'): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        $user = Auth::user();

        // 🔥 EXCEPCIÓN CRUCIAL: Si es el Super Administrador, tiene acceso total y gratuito a todo
        if ($user->rol === 'admin') {
            return $next($request);
        }

        $clinica = $user->clinica;

        if (!$clinica || !$clinica->hasPlanAtLeast($requiredSlug)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Plan insuficiente para acceder a este recurso.'], 403);
            }

            return redirect()->route('suscripciones.show')
                ->with('error', 'Tu plan no está activo. Por favor selecciona un plan para usar DentalConnect.');
        }

        return $next($request);
    }
}
