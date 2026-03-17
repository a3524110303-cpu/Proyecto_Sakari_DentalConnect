<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Paciente;

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
        // Validamos que el correo venga en la petición y que exista en la tabla de usuarios
        $request->validate([
            'email' => 'required|email|exists:usuarios_sistema,email',
        ], [
            'email.exists' => 'Este correo no está registrado en el sistema.'
        ]);

        $email = $request->email;
        // Generamos un token seguro
        $token = \Illuminate\Support\Str::random(60);

        // Guardamos el token en la tabla nativa de Laravel
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => \Illuminate\Support\Facades\Hash::make($token), 'created_at' => now()]
        );

        // Creamos la URL a la que el paciente le dará clic para cambiar su clave
        // (Esto lo mandará a la pantalla de resetear de tu página web administrativa)
        $url = url("/reset-password/{$token}?email=" . urlencode($email));

        try {
            \Illuminate\Support\Facades\Mail::raw("Hola,\n\nHemos recibido una solicitud para cambiar tu contraseña en DentalConnect.\n\nHaz clic en el siguiente enlace para crear una nueva:\n$url\n\nSi no solicitaste este cambio, puedes ignorar este mensaje en tu bandeja.", function ($msg) use ($email) {
                $msg->to($email)->subject('Recuperación de Contraseña - DentalConnect');
            });

            return response()->json(['success' => true, 'message' => 'Correo enviado exitosamente.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error enviando el correo. Verifica tu configuración de Mail en el servidor.'], 500);
        }
    }

}
