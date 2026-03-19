<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Helpers\StringHelper;


class UpdateClinicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo usuarios autenticados con clínica asociada
        return Auth::check() && Auth::user()->id_clinica !== null;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'nombre_comercial' => StringHelper::capitalizeName($this->nombre_comercial),
            'calle'            => $this->calle,
            'ciudad'           => StringHelper::capitalizeName($this->ciudad),
            'municipio'        => StringHelper::capitalizeName($this->municipio),
            'estado'           => StringHelper::capitalizeName($this->estado),
            'pais'             => StringHelper::capitalizeName($this->pais ?? 'México'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre_comercial'    => ['required', 'string', 'max:150', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'numero_telefono'     => 'nullable|string|max:20|regex:/^[0-9]+$/',
            'calle'               => ['nullable', 'string', 'max:150'],
            'ciudad'              => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'municipio'           => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'estado'              => ['nullable', 'string', 'max:50',  'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u'],
            'pais'                => ['nullable', 'string', 'max:50'],
            'codigo_postal'       => ['nullable', 'string', 'max:10', 'regex:/^[0-9]+$/'],
            'latitud'             => ['nullable', 'numeric', 'between:-90,90'],
            'longitud'            => ['nullable', 'numeric', 'between:-180,180'],
            'config_anticipo_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_comercial.required' => 'El nombre de la clínica es obligatorio.',
            'numero_telefono.regex' => 'El teléfono solo puede contener números.',
            'localidad.regex' => 'La localidad solo puede contener letras y espacios.',
            'estado.regex' => 'El estado solo puede contener letras y espacios.',
            // Mensajes para nuevos campos
            'codigo_postal.regex' => 'El código postal solo puede contener números.',
            'codigo_postal.max' => 'El código postal no puede tener más de 10 caracteres.',
            'config_anticipo_pct.numeric' => 'El porcentaje de anticipo debe ser un número.',
            'config_anticipo_pct.min' => 'El porcentaje de anticipo no puede ser menor a 0.',
            'config_anticipo_pct.max' => 'El porcentaje de anticipo no puede ser mayor a 100.',
        ];
    }
}
