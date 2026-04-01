<?php

namespace App\Http\Controllers;

use App\Helpers\StringHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    // Muestra la vista de Registro (página independiente)
    public function showRegister()
    {
        return view('auth.register');
    }

    // Procesa el REGISTRO de nuevo usuario (Doctor + Clínica en transacción)
    public function register(Request $request)
    {
        // Capitalizar automáticamente la primera letra de cada palabra (Title Case)
        $request->merge([
            'nombre'           => StringHelper::capitalizeName($request->nombre),
            'apellido_paterno' => StringHelper::capitalizeName($request->apellido_paterno),
            'apellido_materno' => StringHelper::capitalizeName($request->apellido_materno),
            'nombre_clinica'   => StringHelper::capitalizeName($request->nombre_clinica),
            'calle'            => StringHelper::capitalizeName($request->calle),
            'ciudad'           => StringHelper::capitalizeName($request->ciudad),
            'municipio'        => StringHelper::capitalizeName($request->municipio),
            'estado_clinica'   => StringHelper::capitalizeName($request->estado_clinica),
            'pais'             => StringHelper::capitalizeName($request->pais ?? 'México'),
        ]);

        $messages = [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.regex' => 'El nombre solo puede contener letras y espacios simples (sin caracteres especiales).',
            'nombre.max' => 'El nombre no puede exceder 50 caracteres.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'apellido_paterno.regex' => 'El apellido paterno solo puede contener letras (sin caracteres especiales ni espacios).',
            'apellido_paterno.max' => 'El apellido paterno no puede exceder 50 caracteres.',
            'apellido_materno.regex' => 'El apellido materno solo puede contener letras (sin caracteres especiales ni espacios).',
            'apellido_materno.max' => 'El apellido materno no puede exceder 50 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex' => 'La contraseña debe contener al menos una letra mayúscula y un carácter especial (ej. @, #, $, !).',
            'password.not_regex' => 'La contraseña no puede contener secuencias numéricas como 123.',
            'nombre_clinica.required' => 'El nombre de la clínica es obligatorio.',
            'telefono_clinica.regex' => 'El teléfono solo puede contener números (sin espacios, letras ni caracteres especiales).',
            'telefono_clinica.max' => 'El teléfono no puede exceder 12 dígitos.',
            'codigo_postal.regex' => 'El código postal solo puede contener números (sin espacios, letras ni caracteres especiales).',
            'codigo_postal.max' => 'El código postal no puede exceder 5 dígitos.',
        ];

        $validator = Validator::make($request->all(), [
            // Letras, acentos y ñ, incluyendo espacios simples, sin caracteres especiales
            'nombre'           => ['required', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            // Apellidos: letras y espacios (para apellidos compuestos como "De La Cruz")
            'apellido_paterno' => ['required', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'apellido_materno' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'email'            => 'required|email|max:100|unique:usuarios_sistema,email',
            'password'         => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[\W_]).+$/',
                'not_regex:/123/',
            ],
            'nombre_clinica'   => ['required', 'string', 'max:150', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'telefono_clinica' => ['nullable', 'regex:/^[0-9]{1,12}$/', 'max:12'],
            'calle'            => ['nullable', 'string', 'max:150'],
            'ciudad'           => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'municipio'        => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'estado_clinica'   => ['nullable', 'string', 'max:50',  'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'codigo_postal'    => ['nullable', 'regex:/^[0-9]{5}$/'],
        ], $messages);

        if ($validator->fails()) {
            return redirect()->route('register')
                ->withErrors($validator)
                ->withInput();
        }

        DB::transaction(function () use ($request) {
            // 1. Buscar o Crear la Clínica
            $clinicaExistente = DB::table('clinicas')
                ->where('nombre_comercial', $request->nombre_clinica)
                ->first();

            if ($clinicaExistente) {
                $clinicaId = $clinicaExistente->id_clinica;
            } else {
                $clinicaId = DB::table('clinicas')->insertGetId([
                    'nombre_comercial'   => $request->nombre_clinica,
                    'numero_telefono'    => $request->telefono_clinica,
                    'calle'              => $request->calle,
                    'ciudad'             => $request->ciudad,
                    'municipio'          => $request->municipio,
                    'estado'             => $request->estado_clinica,
                    'pais'               => $request->pais ?? 'México',
                    'codigo_postal'      => $request->codigo_postal,
                    'config_anticipo_pct'=> 0.00,
                    'created_at'         => now()->toDateTimeString(), // Formato exacto para MySQL
                    'updated_at'         => now()->toDateTimeString(), // Formato exacto para MySQL
                ]);
            }

            // 2. Crear el Usuario (Usando insertGetId para saltar eventos y correos)
            $nombreCompleto = trim($request->nombre . ' ' . $request->apellido_paterno . ' ' . ($request->apellido_materno ?? ''));

            $userId = DB::table('usuarios_sistema')->insertGetId([
                'id_clinica'      => $clinicaId,
                'nombre_completo' => $nombreCompleto,
                'email'           => $request->email,
                'password'        => bcrypt($request->password), // Encriptamos la contraseña manualmente
                'rol'             => 'doctor',
                'created_at'      => now()->toDateTimeString(),
                'updated_at'      => now()->toDateTimeString(),
            ]);

            // 3. Crear el perfil de Doctor vinculado
            DB::table('doctores')->insert([
                'id_usuario' => $userId,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ]);
        });

        return redirect()->route('login')
            ->with('success', '¡Clínica y cuenta creadas exitosamente! Ya puedes iniciar sesión.');
    }
}
