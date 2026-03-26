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
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Alias web-friendly para enviar correo de recuperación.
     */
    public function forgotPassword(Request $request)
    {
        return $this->enviarCorreoRecuperacion($request);
    }

    /**
     * Restablece contraseña validando token temporal.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $row = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$row || !Hash::check($request->token, $row->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token de recuperación inválido o expirado.'
            ], 422);
        }

        $expiraMinutos = (int) config('auth.passwords.users.expire', 60);
        $creado = Carbon::parse($row->created_at);
        if ($creado->addMinutes($expiraMinutos)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'El enlace de recuperación ya expiró. Solicita uno nuevo.'
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No existe una cuenta asociada a ese correo.'
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.'
        ], 200);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Credenciales inválidas'], 401);
        }

        // RESTRICCIÓN DE SEGURIDAD:
        // La App Móvil es exclusiva para Pacientes.
        if ($user->rol !== 'paciente') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Esta aplicación es exclusiva para pacientes.'
            ], 403);
        }

        if (isset($user->is_active) && !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta está inactiva. Contacta a la clínica.'
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
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    // ==========================================
    // NUEVA FUNCIÓN PARA ACTIVAR CUENTA MÓVIL
    // ==========================================
    public function activarCuenta(Request $request)
{

    $request->merge([
        'email' => trim($request->email),
        'telefono' => trim($request->telefono),
    ]);

    $request->validate([
        'email' => 'required|email',
        'telefono' => 'required|string',
        'password' => 'required|string|min:6',
    ]);

    // 1. Buscar al usuario ignorando cualquier filtro oculto de clínica (Global Scopes)
    $user = User::withoutGlobalScopes()
                ->where('email', $request->email)
                //->where('rol', 'paciente')
                ->first();

    if (!$user) {
    return response()->json([
        'success' => false,
        'message' => 'No encontré: "' . $request->email . '" en la BD. Revisa mayúsculas, espacios o si la base de datos remota es la correcta.'
    ], 404);
}

    // 2. Verificar que el teléfono ingresado coincida con el registrado en la tabla pacientes,
    // también ignorando filtros globales.
    $paciente = Paciente::withoutGlobalScopes()
                        ->where('id_usuario', $user->id_usuario)
                        ->where('telefono', $request->telefono)
                        ->first();

    if (!$paciente) {
        return response()->json([
            'success' => false,
            'message' => 'El número de teléfono no coincide con nuestros registros para este correo.'
        ], 404);
    }

    // 3. Si todo es correcto, actualizar la contraseña
    $user->password = Hash::make($request->password);
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Cuenta activada exitosamente. Ya puedes iniciar sesión en la aplicación.'
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
