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
            $request->session()->regenerate();

            // CRÍTICO PARA SAAS MULTI-TENANT: Guardamos en sesión la clínica del usuario logueado
            $request->session()->put('id_clinica', Auth::user()->id_clinica);

            // Redirigir al panel de administración si es un administrador
            if (Auth::user()->rol === 'administrador') {
                return redirect()->route('admin.panel');
            }

            return redirect()->intended('dashboard');
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
