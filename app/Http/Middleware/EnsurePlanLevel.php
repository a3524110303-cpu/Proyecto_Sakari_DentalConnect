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
        $clinica = $user->clinica;

        if (!$clinica || !$clinica->hasPlanAtLeast($requiredSlug)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Plan insuficiente para acceder a este recurso.'], 403);
            }

            return redirect()->route('landing')
                ->with('error', 'Tu plan actual no incluye este modulo. Actualiza tu suscripcion.');
        }

        return $next($request);
    }
}
