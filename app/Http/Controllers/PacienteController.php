<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePacienteRequest;
use App\Models\Paciente;
use App\Models\User;
use App\Models\Token;
use App\Models\Servicio;
use App\Helpers\StringHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon; // <--- SE MANTIENE PARA LA VALIDACIÓN DE EDAD

class PacienteController extends Controller
{
    private function construirDireccionCompuesta(Request $request): string
    {
        $partes = array_filter([
            trim((string) $request->input('calle')),
            $request->filled('num_exterior') ? 'Ext. ' . trim((string) $request->input('num_exterior')) : null,
            $request->filled('num_interior') ? 'Int. ' . trim((string) $request->input('num_interior')) : null,
            trim((string) $request->input('colonia')),
            trim((string) $request->input('municipio')),
        ]);

        return implode(', ', $partes);
    }

    public function index()
    {
        $idClinica = Auth::user()->id_clinica;
        $rol = Auth::user()->rol;

        // 1. Iniciamos la consulta para traer solo a los de esta clínica
        $query = Paciente::whereHas('usuario', function ($q) use ($idClinica) {
            $q->where('id_clinica', $idClinica)
              ->where('rol', 'paciente'); // <--- AÑADIR ESTE FILTRO PARA OCULTAR DOCTORES
        })->where('is_active', true);

        // 2. Si el usuario es doctor, individualizamos
        if ($rol === 'doctor') {
            $idDoctor = DB::table('doctores')
                ->where('id_usuario', Auth::user()->id_usuario)
                ->value('id_doctor');

            if ($idDoctor) {
                // Un doctor solo ve a los pacientes que él mismo registró
                // O aquellos que tienen al menos una cita asignada a él.
                $query->where(function($q) use ($idDoctor) {
                    $q->where('created_by_doctor_id', $idDoctor)
                      ->orWhereHas('citas', function($citaQ) use ($idDoctor) {
                          $citaQ->where('id_doctor', $idDoctor);
                      });
                });
            }
        }

        // Los demás roles (recepcionista, admin) pasan directo y ven a todos
        $pacientes = $query->orderBy('created_at', 'desc')->get();
        $servicios = Servicio::where('id_clinica', $idClinica)->orderBy('nombre_servicio')->get();
        
        return view('pacientes.index', compact('pacientes', 'servicios'));
    }

    public function store(StorePacienteRequest $request)
    {
        $clinica = Auth::user()->clinica;
        $suscripcion = $clinica->suscripciones()->where('estado', 'active')->first();
        
        if (!$suscripcion || !$suscripcion->plan) {
            return redirect()->back()
                ->with('error', "No tienes una suscripción activa para registrar pacientes.");
        }

        $plan = $suscripcion->plan;
        $totalPacientes = $clinica->pacientes()->count();

        if ($totalPacientes >= $plan->max_pacientes) {
            return redirect()->back()
                ->with('error', "Límite alcanzado: Tu plan {$plan->nombre} solo permite {$plan->max_pacientes} pacientes. Mejora tu suscripción.");
        }

        // ── VALIDACIÓN DE EDAD (Mínimo 2 años) ──
        // Calculamos la fecha de hace 2 años exactos
        $fechaLimite = Carbon::now()->subYears(2)->format('Y-m-d');

        $request->validate([
    'fecha_nacimiento' => "required|date|before_or_equal:$fechaLimite",
], [
    'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no es válida.',
]);

        $idClinica = Auth::user()->id_clinica;

        try {
            DB::beginTransaction();

            // ── 1. Contacto de Emergencia ──
            $idContactoEmergencia = null;

            if ($request->filled('emergencia_nombre') && $request->filled('emergencia_apellido_paterno')) {
                $idContactoEmergencia = DB::table('contacto_emergencia')->insertGetId([
                    'nombre' => $request->emergencia_nombre,
                    'apellido_paterno' => $request->emergencia_apellido_paterno,
                    'apellido_materno' => $request->emergencia_apellido_materno ?? '',
                    'numero_telefono' => $request->emergencia_telefono ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ── 2. Crear el usuario del sistema ──
            $nombreCompleto = trim(
                $request->nombre . ' ' .
                $request->apellido_paterno . ' ' .
                ($request->apellido_materno ?? '')
            );

            $user = User::create([
                'id_clinica' => $idClinica,
                'nombre_completo' => $nombreCompleto,
                'email' => $request->email,
                'password' => Hash::make('dental123'), // <--- CORREGIDO: Se usa Hash para que no de error al loguear
                'rol' => 'paciente',
                'is_active' => true,
            ]);

            // ── 3. Crear el perfil del paciente ──
            $direccionCompuesta = $this->construirDireccionCompuesta($request);

            // Obtener doctor si el registrador es doctor
            $idDoctorCreador = null;
            if (Auth::user()->rol === 'doctor') {
                $idDoctorCreador = DB::table('doctores')
                    ->where('id_usuario', Auth::user()->id_usuario)
                    ->value('id_doctor');
            }

            $paciente = Paciente::create([
                'id_usuario' => $user->id_usuario,
                'created_by_doctor_id' => $idDoctorCreador,
                'id_contacto_emergencia' => $idContactoEmergencia,
                'nombre' => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'sexo' => $request->sexo ?? 'O',
                'telefono' => $request->telefono,
                'correo_electronico' => $request->email,
                'tipo_sangre' => $request->tipo_sangre,
                'peso' => $request->peso,
                'direccion' => $direccionCompuesta,
                'calle' => $request->calle,
                'num_exterior' => $request->num_exterior,
                'num_interior' => $request->num_interior,
                'colonia' => $request->colonia,
                'municipio' => $request->municipio,
                'ocupacion' => $request->ocupacion,
                'enfermedades_cronicas' => $request->enfermedades_cronicas,
                'alergias' => $request->alergias,
                'is_active' => true,
            ]);

            // ── 4. Generar token de acceso ──
            $tokenStr = 'PAC-' . strtoupper(Str::random(6));
            Token::create([
                'id_usuario' => $user->id_usuario,
                'token' => $tokenStr,
                'tipo_token' => 'acceso_app',
                'estado' => 'activo',
                'fecha_creacion' => now(),
                'fecha_expiracion' => now()->addYear(),
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Paciente registrado correctamente. Se ha generado su acceso a la aplicación móvil.');

        } catch (\Exception $e) {
            DB::rollBack();

            // ── SEGURIDAD: No exponer mensajes de error de BD al usuario ──
            Log::error('Error al registrar paciente', [
                'user_id'   => Auth::id(),
                'clinica'   => $idClinica,
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Ocurrió un error al guardar el paciente. Si el problema persiste, contacta al administrador del sistema.')
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $idClinica = Auth::user()->id_clinica;
        $paciente = Paciente::where('id_paciente', $id)
            ->whereHas('usuario', function ($q) use ($idClinica) {
                $q->where('id_clinica', $idClinica);
            })->firstOrFail();

        // ── SANITIZACIÓN PRE-VALIDACIÓN ──
        $request->merge([
            'email'     => mb_strtolower(trim((string) $request->input('email', '')), 'UTF-8'),
            'calle'     => StringHelper::sanitizeAddress($request->input('calle', '')),
            'colonia'   => StringHelper::sanitizeAddress($request->input('colonia', '')),
            'municipio' => StringHelper::sanitizeAddress($request->input('municipio', '')),
            'num_exterior' => StringHelper::sanitizeAddressNumber($request->input('num_exterior', '')),
            'num_interior' => StringHelper::sanitizeAddressNumber($request->input('num_interior', '')),
            'ocupacion' => StringHelper::sanitizeAddress($request->input('ocupacion', '')),
            'emergencia_nombre'            => StringHelper::sanitizeText($request->input('emergencia_nombre', '')),
            'emergencia_apellido_paterno'  => StringHelper::sanitizeText($request->input('emergencia_apellido_paterno', '')),
            'emergencia_apellido_materno'  => StringHelper::sanitizeText($request->input('emergencia_apellido_materno', '')),
        ]);

        // Sanitizar campos médicos solo si el rol lo permite
        if (Auth::user()->rol !== 'recepcionista') {
            $request->merge([
                'enfermedades_cronicas' => StringHelper::sanitizeHealthText($request->input('enfermedades_cronicas', '')),
                'alergias'              => StringHelper::sanitizeHealthText($request->input('alergias', '')),
            ]);
        }

        // ── VALIDACIÓN COMPLETA ──
        $rules = [
            'email' => [
                'required',
                'email',
                'max:150',
                'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/',
                Rule::unique('usuarios_sistema', 'email')->ignore($paciente->id_usuario, 'id_usuario'),
            ],
            'telefono'     => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]+$/'],
            'calle'        => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,#\-\/°]+$/'],
            'num_exterior' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z0-9\s\-\/#]+$/'],
            'num_interior' => ['nullable', 'string', 'max:20', 'regex:/^[a-zA-Z0-9\s\-\/#]+$/'],
            'colonia'      => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,#\-\/°]+$/'],
            'municipio'    => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,\-]+$/'],
            'ocupacion'    => ['nullable', 'string', 'max:100'],
            'peso'         => ['nullable', 'numeric', 'min:0.5', 'max:500', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
            'emergencia_nombre'           => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'emergencia_apellido_paterno' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'emergencia_apellido_materno' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'emergencia_telefono'         => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]+$/'],
        ];

        // Agregar reglas de campos médicos solo si el rol lo permite
        if (Auth::user()->rol !== 'recepcionista') {
            $rules['enfermedades_cronicas'] = ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,;:()\-\/"\'\+\%°#]+$/'];
            $rules['alergias']              = ['nullable', 'string', 'max:500', 'regex:/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,;:()\-\/"\'\+\%°#]+$/'];
        }

        $request->validate($rules, [
            'email.email'           => 'El correo debe tener un formato válido con un dominio existente.',
            'email.unique'          => 'Este correo ya está registrado en el sistema.',
            'telefono.regex'        => 'El teléfono solo puede contener números.',
            'peso.numeric'          => 'El peso debe ser un valor numérico (ej: 32.50).',
            'peso.regex'            => 'El peso debe tener máximo 2 decimales.',
            'peso.max'              => 'El peso no puede exceder 500 kg.',
            'calle.regex'           => 'La calle contiene caracteres no permitidos.',
            'calle.max'             => 'La calle no puede exceder 100 caracteres.',
            'num_exterior.regex'    => 'El número exterior solo permite letras, números, -, / y #.',
            'num_interior.regex'    => 'El número interior solo permite letras, números, -, / y #.',
            'colonia.regex'         => 'La colonia contiene caracteres no permitidos.',
            'colonia.max'           => 'La colonia no puede exceder 100 caracteres.',
            'municipio.regex'       => 'El municipio contiene caracteres no permitidos.',
            'municipio.max'         => 'El municipio no puede exceder 100 caracteres.',
            'emergencia_nombre.regex'           => 'El nombre del contacto solo permite letras y espacios.',
            'emergencia_apellido_paterno.regex' => 'El apellido del contacto solo permite letras y espacios.',
            'emergencia_apellido_materno.regex' => 'El apellido del contacto solo permite letras y espacios.',
            'emergencia_telefono.regex'         => 'El teléfono del contacto solo puede contener números.',
            'enfermedades_cronicas.max'         => 'Las enfermedades crónicas no pueden exceder 500 caracteres.',
            'enfermedades_cronicas.regex'       => 'Las enfermedades crónicas contienen caracteres no permitidos.',
            'alergias.max'                      => 'Las alergias no pueden exceder 500 caracteres.',
            'alergias.regex'                    => 'Las alergias contienen caracteres no permitidos.',
        ]);

        try {
            DB::beginTransaction();

            $direccionCompuesta = $this->construirDireccionCompuesta($request);

            $datosActualizar = [
                'correo_electronico' => $request->email,
                'telefono' => $request->telefono,
                'direccion' => $direccionCompuesta,
                'calle' => $request->calle,
                'num_exterior' => $request->num_exterior,
                'num_interior' => $request->num_interior,
                'colonia' => $request->colonia,
                'municipio' => $request->municipio,
                'ocupacion' => $request->ocupacion,
            ];

            if (Auth::user()->rol !== 'recepcionista') {
                $datosActualizar['tipo_sangre'] = $request->tipo_sangre;
                $datosActualizar['peso'] = $request->peso;
                $datosActualizar['enfermedades_cronicas'] = $request->enfermedades_cronicas;
                $datosActualizar['alergias'] = $request->alergias;
            }

            $paciente->update($datosActualizar);

            $paciente->usuario()->update([
                'email' => $request->email,
            ]);

            $hayDatosEmergencia = $request->filled('emergencia_nombre')
                || $request->filled('emergencia_apellido_paterno')
                || $request->filled('emergencia_apellido_materno')
                || $request->filled('emergencia_telefono');

            if ($paciente->id_contacto_emergencia) {
                if ($hayDatosEmergencia) {
                    $contactoActual = DB::table('contacto_emergencia')
                        ->where('id_contacto_emergencia', $paciente->id_contacto_emergencia)
                        ->first();

                    if ($contactoActual) {
                        DB::table('contacto_emergencia')
                            ->where('id_contacto_emergencia', $paciente->id_contacto_emergencia)
                            ->update([
                                'nombre' => $request->emergencia_nombre ?: $contactoActual->nombre,
                                'apellido_paterno' => $request->emergencia_apellido_paterno ?: $contactoActual->apellido_paterno,
                                'apellido_materno' => $request->emergencia_apellido_materno ?? $contactoActual->apellido_materno,
                                'numero_telefono' => $request->emergencia_telefono ?: $contactoActual->numero_telefono,
                                'updated_at' => now(),
                            ]);
                    }
                }
            } elseif ($request->filled('emergencia_nombre') && $request->filled('emergencia_apellido_paterno')) {
                $idCe = DB::table('contacto_emergencia')->insertGetId([
                    'nombre' => $request->emergencia_nombre,
                    'apellido_paterno' => $request->emergencia_apellido_paterno,
                    'apellido_materno' => $request->emergencia_apellido_materno ?? '',
                    'numero_telefono' => $request->emergencia_telefono ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $paciente->update(['id_contacto_emergencia' => $idCe]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Paciente actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            // ── SEGURIDAD: No exponer mensajes de error de BD al usuario ──
            Log::error('Error al actualizar paciente', [
                'user_id'    => Auth::id(),
                'paciente'   => $id,
                'message'    => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Ocurrió un error al actualizar el paciente. Si el problema persiste, contacta al administrador del sistema.');
        }
    }

    public function destroy($id)
    {
        return redirect()->back()->with(
            'error',
            'Acción no permitida por cumplimiento normativo NOM-004-SSA3-2012 (Conservación de expedientes por 5 años).'
        );
    }
}