<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Helpers\StringHelper;


class StorePacienteRequest extends FormRequest
{
    /**
     * Solo los usuarios autenticados pueden registrar pacientes.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Aplica sanitización a los datos antes de la validación.
     * Defensa en profundidad: limpia emojis, rich text, espacios, y HTML
     * ANTES de que las reglas regex se apliquen.
     */
    protected function prepareForValidation()
    {
        // ── Sanitizar campos de nombre (solo letras + espacios) ──
        $nombre = StringHelper::capitalizeName($this->nombre);
        $apellidoPaterno = StringHelper::capitalizeName($this->apellido_paterno);
        $apellidoMaterno = StringHelper::capitalizeName($this->apellido_materno);
        $emergenciaNombre = StringHelper::capitalizeName($this->emergencia_nombre);
        $emergenciaPaterno = StringHelper::capitalizeName($this->emergencia_apellido_paterno);
        $emergenciaMaterno = StringHelper::capitalizeName($this->emergencia_apellido_materno);

        // ── Sanitizar campos de dirección ──
        $calle = StringHelper::sanitizeAddress($this->input('calle', ''));
        $colonia = StringHelper::sanitizeAddress($this->input('colonia', ''));
        $municipio = StringHelper::sanitizeAddress($this->input('municipio', ''));
        $numExterior = StringHelper::sanitizeAddressNumber($this->input('num_exterior', ''));
        $numInterior = StringHelper::sanitizeAddressNumber($this->input('num_interior', ''));

        // ── Sanitizar campos de salud ──
        $enfermedadesCronicas = StringHelper::sanitizeHealthText($this->input('enfermedades_cronicas', ''));
        $alergias = StringHelper::sanitizeHealthText($this->input('alergias', ''));

        // ── Sanitizar ocupación ──
        $ocupacion = StringHelper::sanitizeAddress($this->input('ocupacion', ''));

        // ── Sanitizar email (trim + lowercase) ──
        $email = mb_strtolower(trim((string) $this->input('email', '')), 'UTF-8');

        // ── Construir dirección compuesta ──
        $calleCapitalizada = StringHelper::capitalizeName($calle);
        $coloniaCapitalizada = StringHelper::capitalizeName($colonia);
        $municipioCapitalizado = StringHelper::capitalizeName($municipio);

        $partesDireccion = array_filter([
            $calleCapitalizada,
            $numExterior ? 'Ext. ' . $numExterior : null,
            $numInterior ? 'Int. ' . $numInterior : null,
            $coloniaCapitalizada,
            $municipioCapitalizado,
        ]);

        $this->merge([
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoPaterno,
            'apellido_materno' => $apellidoMaterno,
            'emergencia_nombre' => $emergenciaNombre,
            'emergencia_apellido_paterno' => $emergenciaPaterno,
            'emergencia_apellido_materno' => $emergenciaMaterno,
            'calle' => $calleCapitalizada,
            'colonia' => $coloniaCapitalizada,
            'municipio' => $municipioCapitalizado,
            'num_exterior' => $numExterior,
            'num_interior' => $numInterior,
            'enfermedades_cronicas' => $enfermedadesCronicas,
            'alergias' => $alergias,
            'ocupacion' => $ocupacion,
            'email' => $email,
            'direccion' => implode(', ', $partesDireccion),
        ]);
    }

    /**
     * Reglas de validación del formulario de creación de paciente.
     *
     * Campos de contacto_emergencia son todos opcionales.
     * alergias y enfermedades_cronicas se almacenan como texto libre; no se valida array.
     */
    public function rules(): array
    {
        return [
            // ── Datos básicos obligatorios ──
            'nombre'            => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'apellido_paterno'  => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'apellido_materno'  => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'telefono'          => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]+$/'],
            'email'             => [
                'required',
                'email',
                'max:150',
                'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/',
                Rule::unique('usuarios_sistema', 'email')
                    ->where(function ($query) {
                        return $query->where('id_clinica', Auth::user()->id_clinica);
                    }),
            ],
            'fecha_nacimiento'  => ['required', 'date', 'before:today'],
            'sexo'              => ['required', 'in:M,F,O'],

            // ── Datos médicos sensibles ──
            'tipo_sangre'       => ['nullable', 'string', 'max:5'],
            'peso'              => ['nullable', 'numeric', 'min:0.5', 'max:500', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],

            // ── Dirección ──
            'calle'             => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,#\-\/°]+$/'],
            'num_exterior'      => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9\s\-\/#]+$/'],
            'num_interior'      => ['nullable', 'string', 'max:20', 'regex:/^[a-zA-Z0-9\s\-\/#]+$/'],
            'colonia'           => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,#\-\/°]+$/'],
            'municipio'         => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,\-]+$/'],
            'direccion'         => ['nullable', 'string', 'max:255'],
            'ocupacion'         => ['required', 'string', 'max:100'],

            // ── Información de salud ──
            'enfermedades_cronicas' => ['required', 'string', 'min:3', 'max:500', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,;:()\-\/"\'\+\%°#]+$/'],
            'alergias'             => ['required', 'string', 'min:3', 'max:500', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,;:()\-\/"\'\+\%°#]+$/'],

            // ── Contacto de emergencia ──
            'emergencia_nombre'            => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'emergencia_apellido_paterno'  => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'emergencia_apellido_materno'  => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'emergencia_telefono'          => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]+$/'],
        ];
    }

    /**
     * Mensajes de error personalizados y amigables.
     */
    public function messages(): array
    {
        return [
            // ── Nombres ──
            'nombre.required'     => 'El nombre del paciente es obligatorio.',
            'nombre.min'          => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max'          => 'El nombre no puede exceder 100 caracteres.',
            'nombre.regex'        => 'El nombre solo puede contener letras y espacios (sin números, emojis ni símbolos).',

            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'apellido_paterno.min'      => 'El apellido paterno debe tener al menos 2 caracteres.',
            'apellido_paterno.max'      => 'El apellido paterno no puede exceder 100 caracteres.',
            'apellido_paterno.regex'    => 'El apellido paterno solo puede contener letras y espacios.',

            'apellido_materno.min'   => 'El apellido materno debe tener al menos 2 caracteres.',
            'apellido_materno.max'   => 'El apellido materno no puede exceder 100 caracteres.',
            'apellido_materno.regex' => 'El apellido materno solo puede contener letras y espacios.',

            // ── Teléfono ──
            'telefono.required' => 'El teléfono de contacto es obligatorio.',
            'telefono.regex'    => 'El teléfono solo puede contener números y opcionalmente un + al inicio.',

            // ── Email ──
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'El correo electrónico debe tener un formato válido con un dominio existente (ej: nombre@gmail.com).',
            'email.regex'    => 'El correo debe tener un formato válido con un dominio real (ej: usuario@gmail.com).',
            'email.unique'   => 'Este correo ya está registrado en el sistema.',

            // ── Fecha & Sexo ──
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.before'   => 'La fecha de nacimiento debe ser anterior a hoy.',
            'sexo.required'             => 'El sexo del paciente es obligatorio.',

            // ── Peso (ahora acepta decimales) ──
            'peso.numeric' => 'El peso debe ser un valor numérico (se permiten hasta 2 decimales, ej: 32.50).',
            'peso.min'     => 'El peso mínimo es 0.5 kg.',
            'peso.max'     => 'El peso no puede exceder los 500 kg.',
            'peso.regex'   => 'El peso debe ser un número válido con máximo 2 decimales (ej: 32.50).',

            // ── Dirección ──
            'calle.required'        => 'La calle es obligatoria para el expediente clínico.',
            'calle.max'             => 'La calle no puede exceder 100 caracteres.',
            'calle.regex'           => 'La calle contiene caracteres no permitidos (no se aceptan emojis ni símbolos especiales).',

            'num_exterior.required' => 'El número exterior es obligatorio para el expediente clínico.',
            'num_exterior.max'      => 'El número exterior no puede exceder 20 caracteres.',
            'num_exterior.regex'    => 'El número exterior solo puede contener letras, números, -, / y #.',

            'num_interior.max'      => 'El número interior no puede exceder 20 caracteres.',
            'num_interior.regex'    => 'El número interior solo puede contener letras, números, -, / y #.',

            'colonia.required'      => 'La colonia es obligatoria para el expediente clínico.',
            'colonia.max'           => 'La colonia no puede exceder 100 caracteres.',
            'colonia.regex'         => 'La colonia contiene caracteres no permitidos.',

            'municipio.required'    => 'El municipio es obligatorio para el expediente clínico.',
            'municipio.max'         => 'El municipio no puede exceder 100 caracteres.',
            'municipio.regex'       => 'El municipio contiene caracteres no permitidos.',

            // ── Información de Salud ──
            'enfermedades_cronicas.required' => 'Las enfermedades crónicas son obligatorias. Si no tiene, escriba "Ninguna".',
            'enfermedades_cronicas.min'      => 'Las enfermedades crónicas deben tener al menos 3 caracteres.',
            'enfermedades_cronicas.max'      => 'Las enfermedades crónicas no pueden exceder 500 caracteres.',
            'enfermedades_cronicas.regex'    => 'Las enfermedades crónicas contienen caracteres no permitidos (no se aceptan emojis).',

            'alergias.required' => 'Las alergias son obligatorias. Si no tiene, escriba "Ninguna".',
            'alergias.min'      => 'Las alergias deben tener al menos 3 caracteres.',
            'alergias.max'      => 'Las alergias no pueden exceder 500 caracteres.',
            'alergias.regex'    => 'Las alergias contienen caracteres no permitidos (no se aceptan emojis).',

            // ── Contacto de Emergencia ──
            'emergencia_nombre.required'    => 'El nombre del contacto de emergencia es obligatorio.',
            'emergencia_nombre.min'         => 'El nombre del contacto de emergencia debe tener al menos 2 caracteres.',
            'emergencia_nombre.regex'       => 'El nombre del contacto de emergencia solo puede contener letras y espacios.',

            'emergencia_apellido_paterno.regex' => 'El apellido paterno del contacto solo puede contener letras y espacios.',
            'emergencia_apellido_materno.regex' => 'El apellido materno del contacto solo puede contener letras y espacios.',

            'emergencia_telefono.required' => 'El teléfono del contacto de emergencia es obligatorio.',
            'emergencia_telefono.regex'    => 'El teléfono del contacto de emergencia solo puede contener números.',
        ];
    }
}
