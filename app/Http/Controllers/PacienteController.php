<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePacienteRequest;
use App\Models\Paciente;
use App\Models\User;
use App\Models\Token;
use App\Models\Servicio;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
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
            $q->where('id_clinica', $idClinica);
        })->where('is_active', true);

        // 2. Si el usuario es doctor, individualizamos
        if ($rol === 'doctor') {
            $idDoctor = DB::table('doctores')
                ->where('id_usuario', Auth::user()->id_usuario)
                ->value('id_doctor');

            if ($idDoctor) {
                // Verificamos cuántos doctores activos hay en la clínica
                $totalDoctores = DB::table('usuarios_sistema')
                    ->where('id_clinica', $idClinica)
                    ->where('rol', 'doctor')
                    ->where('is_active', true)
                    ->count();

                if ($totalDoctores > 1) {
                    // Si hay más de un doctor: El Doctor B no ve los registrados por el Doctor A.
                    // Solo verá los que él mismo registró y los que registró la Recepcionista/Admin (NULL)
                    $query->where(function($q) use ($idDoctor) {
                        $q->where('created_by_doctor_id', $idDoctor)
                          ->orWhereNull('created_by_doctor_id');
                    });
                }
                // Si es el único doctor en la clínica, ve absolutamente todo.
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
            return redirect()->back()
                ->with('error', 'Error al guardar el paciente: ' . $e->getMessage())
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

        $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('usuarios_sistema', 'email')->ignore($paciente->id_usuario, 'id_usuario'),
            ],
            'calle' => 'required|string|max:100',
            'num_exterior' => 'required|string|max:20',
            'num_interior' => 'nullable|string|max:20',
            'colonia' => 'required|string|max:100',
            'municipio' => 'required|string|max:100',
            'emergencia_nombre' => 'nullable|string|max:100',
            'emergencia_apellido_paterno' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $direccionCompuesta = $this->construirDireccionCompuesta($request);

            $paciente->update([
                'correo_electronico' => $request->email,
                'tipo_sangre' => $request->tipo_sangre,
                'telefono' => $request->telefono,
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
            ]);

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
            return redirect()->back()->with('error', 'Error al actualizar: ' . $e->getMessage());
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