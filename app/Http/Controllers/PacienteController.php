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

class PacienteController extends Controller
{
    public function index()
    {
        $idClinica = Auth::user()->id_clinica;

        $pacientes = Paciente::whereHas('usuario', function ($query) {
            $query->where('id_clinica', Auth::user()->id_clinica);
        })
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $servicios = Servicio::where('id_clinica', $idClinica)->orderBy('nombre_servicio')->get();
        return view('pacientes.index', compact('pacientes', 'servicios'));
    }

    public function store(StorePacienteRequest $request)
    {
        $idClinica = Auth::user()->id_clinica;

        try {
            DB::beginTransaction();

            // ── 1. Contacto de Emergencia ──
            $idContactoEmergencia = null;

            // Solo creamos el contacto si al menos el nombre y el apellido paterno están presentes
            // Esto evita el error de "Integrity violation" en MySQL
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
                'password' => 'dental123',
                'rol' => 'paciente',
                'is_active' => true,
            ]);

            // ── 3. Crear el perfil del paciente ──
            $paciente = Paciente::create([
                'id_usuario' => $user->id_usuario,
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
                'direccion' => $request->direccion,
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

        // Validación más estricta para evitar nulos en cascada
        $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('usuarios_sistema', 'email')->ignore($paciente->id_usuario, 'id_usuario'),
            ],
            'emergencia_nombre' => 'nullable|string|max:100',
            'emergencia_apellido_paterno' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $paciente->update([
                'correo_electronico' => $request->email,
                'tipo_sangre' => $request->tipo_sangre,
                'telefono' => $request->telefono,
                'peso' => $request->peso,
                'direccion' => $request->direccion,
                'ocupacion' => $request->ocupacion,
                'enfermedades_cronicas' => $request->enfermedades_cronicas,
                'alergias' => $request->alergias,
            ]);

            $paciente->usuario()->update([
                'email' => $request->email,
            ]);

            // Contacto de emergencia: soporta actualización parcial cuando ya existe.
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