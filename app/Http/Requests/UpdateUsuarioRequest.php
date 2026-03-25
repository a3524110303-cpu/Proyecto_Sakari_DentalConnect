<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;


class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!Auth::check())
            return false;

        // Verificar que el usuario a editar pertenece a la misma clínica
        $idUsuario = $this->input('id_usuario');
        if (!$idUsuario)
            return false;

        $usuario = \App\Models\User::find($idUsuario);
        return $usuario && $usuario->id_clinica === Auth::user()->id_clinica;
    }

    protected function prepareForValidation()
    {
        if ($this->nombre_completo) {
            $this->merge([
                'nombre_completo' => StringHelper::capitalizeName($this->nombre_completo),
            ]);
        }
    }

    public function rules(): array
    {
        $idUsuario = (int) $this->input('id_usuario');

        return [
            'id_usuario' => 'required|exists:usuarios_sistema,id_usuario',
            'nombre_completo' => ['required', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuarios_sistema', 'email')->ignore($idUsuario, 'id_usuario'),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            
            // --- CAMBIO AQUÍ: Agregamos sobre_mi ---
            'sobre_mi' => 'nullable|string|max:1000', 
            
            // Datos del doctor (opcionales, se ignoran para recepcionistas)
            'cedula_profesional' => 'nullable|string|max:20',
            'horario_default' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'nombre_completo.regex' => 'El nombre completo solo puede contener letras y espacios.',
            'email.email' => 'El correo debe tener un formato válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'sobre_mi.max' => 'La descripción no puede exceder los 1000 caracteres.',
        ];
    }
}