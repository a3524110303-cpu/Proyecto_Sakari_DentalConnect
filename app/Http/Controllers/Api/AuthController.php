<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['success' => false, 'message' => 'Credenciales inválidas'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // RESTRICCIÓN DE SEGURIDAD:
        // La App Móvil es exclusiva para Pacientes.
        if ($user->rol !== 'paciente') {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Esta aplicación es exclusiva para pacientes.'
            ], 403);
        }

        // Generamos el token para el dispositivo móvil
        $token = $user->createToken('auth_token_paciente')->plainTextToken;

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id_usuario' => $user->id_usuario,
                'email' => $user->email,
                'nombre_completo' => $user->nombre_completo,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    // ==========================================
    // NUEVA FUNCIÓN PARA ACTIVAR CUENTA MÓVIL
    // ==========================================
    public function activarCuenta(Request $request)
    {
        // 1. Validar que la app envíe los datos correctamente
        $request->validate([
            'email' => 'required|email',
            'telefono' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        // 2. Buscar al usuario en el sistema por su email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ninguna cuenta con este correo electrónico.'
            ], 404);
        }

        if ($user->rol !== 'paciente') {
            return response()->json([
                'success' => false,
                'message' => 'Esta aplicación es exclusiva para pacientes.'
            ], 403);
        }

        // 3. Buscar al paciente ligado a ese usuario para verificar su teléfono
        $paciente = Paciente::where('id_usuario', $user->id_usuario)
                            ->where('telefono', $request->telefono)
                            ->first();

        if (!$paciente) {
            return response()->json([
                'success' => false,
                'message' => 'El número de teléfono no coincide con los registros de la clínica.'
            ], 400);
        }

        // 4. Si todo es correcto, actualizar la contraseña
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta activada exitosamente. Ya puedes iniciar sesión.'
        ], 200);
    }

    /**
     * Enviar correo de recuperación de contraseña desde la App Móvil
     */
    public function enviarCorreoRecuperacion(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Este correo no está registrado en el sistema.'
            ], 404);
        }

        // Crear token seguro
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Crear la URL que apunta a la vista web de tu equipo
        $url = url("/recuperar-password?token={$token}&email=" . urlencode($request->email));

        // Enviar el correo usando Mail::raw para evitar problemas de vistas
        Mail::raw("Hola {$user->nombre_completo},\n\nHas solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para crear una nueva:\n\n{$url}\n\nSi no fuiste tú, ignora este mensaje.", function ($msg) use ($request) {
            $msg->to($request->email)
                ->subject('Recuperación de Contraseña - DentalConnect');
        });

        return response()->json([
            'success' => true,
            'message' => 'Correo enviado exitosamente.',
            // Esta línea extra le mandará a Flutter el nombre del cartero real que está usando
            'cartero_usado' => config('mail.default'), 
        ], 200);
    }
}
