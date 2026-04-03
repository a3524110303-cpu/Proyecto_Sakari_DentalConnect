<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Muestra la vista de Login
    public function showLogin()
    {
        return view('auth.login');
    }

    // Procesa el LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // 🚨 VALIDACIÓN DE SEGURIDAD: Bloquear acceso a pacientes en la plataforma web
            if (Auth::user()->rol === 'paciente') {
                Auth::logout(); // Cerramos la sesión que se acaba de crear
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Acceso denegado. Los pacientes solo pueden acceder a través de la aplicación móvil.',
                ]);
            }

            $request->session()->regenerate();

            // CRÍTICO PARA SAAS MULTI-TENANT: Guardamos en sesión la clínica del usuario logueado
            $request->session()->put('id_clinica', Auth::user()->id_clinica);

            // REDIRECCIÓN INTELIGENTE:
            if (Auth::user()->rol === 'admin' || Auth::user()->rol === 'administrador') {
                return redirect()->route('admin.panel');
            }

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ]);
    }

    // Procesa el LOGOUT
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
