<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClinicaRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Clinica;
use App\Models\User;
use App\Models\Doctor;
use App\Models\HorarioClinica;

class ConfiguracionController extends Controller
{
    /**
     * Muestra la vista de configuración.
     * CORRECCIÓN: El perfil del doctor siempre es el del usuario autenticado,
     * nunca el de otro doctor de la misma clínica.
     */
    public function index()
    {
        $user = Auth::user();
        $clinica = Clinica::find($user->id_clinica);
        abort_if(!$clinica, 403, 'No tienes una clínica asignada.');

        $doctorUser = null;
        $doctorPerfil = null;

        if ($user->rol === 'doctor') {
            $doctorUser = $user;
            // Busca el perfil del doctor del usuario autenticado, no de cualquier doctor
            $doctorPerfil = Doctor::where('id_usuario', $user->id_usuario)->first();
        }

        // Solo recepcionistas de la clínica del usuario autenticado
        $recepcionistas = User::where('id_clinica', $user->id_clinica)
            ->where('rol', 'recepcionista')
            ->where('is_active', true)
            ->get();

        $horarios = $this->obtenerOCrearHorarios($clinica->id_clinica);

        return view('configuracion.index', compact(
            'user',
            'clinica',
            'doctorUser',
            'doctorPerfil',
            'recepcionistas',
            'horarios'
        ));
    }

    /**
     * Actualiza la información de la clínica.
     */
    public function updateClinica(UpdateClinicaRequest $request)
    {
        $clinica = Clinica::find(Auth::user()->id_clinica);
        abort_if(!$clinica, 403, 'No tienes permiso para modificar esta clínica.');

        $clinica->update($request->validated());

        return back()->with('success', 'Datos de la clínica actualizados correctamente.');
    }

    /**
     * Actualiza la información de un usuario verificando que pertenezca
     * ESTRICTAMENTE a la misma clínica del autenticado.
     */
    public function updateUsuario(UpdateUsuarioRequest $request)
    {
        $validated = $request->validated();

        // Verifica que el usuario a editar sea de LA MISMA clínica
        $usuario = User::where('id_clinica', Auth::user()->id_clinica)
            ->findOrFail($validated['id_usuario']);

        $data = [
            'nombre_completo' => $validated['nombre_completo'],
            'email' => $validated['email'],
            'sobre_mi' => $validated['sobre_mi'] ?? null,
            'telefono' => $validated['telefono'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        DB::transaction(function () use ($usuario, $data, $validated) {
            $usuario->update($data);

            if ($usuario->rol === 'doctor') {
                Doctor::updateOrCreate(
                    ['id_usuario' => $usuario->id_usuario],
                    [
                        'cedula_profesional' => $validated['cedula_profesional'] ?? null,
                        'horario_default' => $validated['horario_default'] ?? null,
                    ]
                );
            }
        });

        return back()->with('success', 'Perfil de usuario actualizado correctamente.');
    }

    /**
     * Crea una nueva cuenta de recepcionista verificando límites del plan.
     */
    public function storeRecepcionista(Request $request)
    {
        $clinica = Auth::user()->clinica;
        $suscripcion = $clinica->suscripciones()->where('estado', 'active')->first();

        if (!$suscripcion || !$suscripcion->plan) {
            return redirect()->back()
                ->with('error', 'No tienes una suscripción activa para agregar usuarios.');
        }

        $plan = $suscripcion->plan;
        $totalUsuarios = User::where('id_clinica', $clinica->id_clinica)->count();

        if ($totalUsuarios >= $plan->max_doctores) {
            return redirect()->back()
                ->with('error', "Límite alcanzado: Tu plan {$plan->nombre} solo permite {$plan->max_doctores} usuarios en total. Mejora tu suscripción.");
        }

        $request->validate([
            'nombre_completo' => 'required|string|max:100',
            'email' => 'required|email|unique:usuarios_sistema,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'id_clinica' => Auth::user()->id_clinica,
            'nombre_completo' => $request->nombre_completo,
            'email' => $request->email,
            'password' => $request->password,
            'rol' => 'recepcionista',
            'is_active' => true,
        ]);

        return back()->with('success', 'Recepcionista agregada correctamente.');
    }

    /**
     * Actualiza los horarios de atención de la clínica (7 días).
     */
    public function updateHorarios(Request $request)
    {
        $user = Auth::user();
        $clinica = Clinica::find($user->id_clinica);
        abort_if(!$clinica, 403, 'No tienes una clínica asignada.');

        $dias = $request->input('dias', []);

        foreach ([1, 2, 3, 4, 5, 6, 0] as $dia) {
            $activo = isset($dias[$dia]['activo']) ? 1 : 0;
            $horaInicio = $dias[$dia]['hora_inicio'] ?? null;
            $horaFin = $dias[$dia]['hora_fin'] ?? null;

            if (!$activo) {
                $horaInicio = null;
                $horaFin = null;
            }

            HorarioClinica::updateOrCreate(
                [
                    'id_clinica' => $clinica->id_clinica,
                    'dia_semana' => $dia,
                ],
                [
                    'activo' => $activo,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                ]
            );
        }

        return back()->with('success', 'Horarios de atención actualizados correctamente.');
    }

    /**
     * Da de baja (desactiva) a un recepcionista verificando que pertenezca a la clínica.
     */
    public function destroyRecepcionista($id)
    {
        $usuario = User::where('id_clinica', Auth::user()->id_clinica)
            ->where('id_usuario', $id)
            ->where('rol', 'recepcionista')
            ->firstOrFail();

        $usuario->is_active = false;
        $usuario->save();

        return back()->with('success', 'Recepcionista dada de baja correctamente.');
    }

    /**
     * Obtiene o crea horarios por defecto para la clínica.
     */
    private function obtenerOCrearHorarios(int $idClinica): \Illuminate\Database\Eloquent\Collection
    {
        $existentes = HorarioClinica::where('id_clinica', $idClinica)->count();

        if ($existentes < 7) {
            $defaults = [
                1 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'],
                2 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'],
                3 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'],
                4 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'],
                5 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'],
                6 => ['activo' => false, 'hora_inicio' => null, 'hora_fin' => null],
                0 => ['activo' => false, 'hora_inicio' => null, 'hora_fin' => null],
            ];

            foreach ($defaults as $dia => $vals) {
                HorarioClinica::firstOrCreate(
                    ['id_clinica' => $idClinica, 'dia_semana' => $dia],
                    $vals
                );
            }
        }

        return HorarioClinica::where('id_clinica', $idClinica)
            ->orderByRaw('FIELD(dia_semana, 1, 2, 3, 4, 5, 6, 0)')
            ->get();
    }

    /**
     * Sube o actualiza la foto de perfil del doctor AUTENTICADO.
     * CORRECCIÓN CRÍTICA: Solo actualiza la foto del doctor que
     * corresponde al usuario que hace la petición, sin posibilidad
     * de que se mezcle con otros doctores de la misma clínica.
     */
    public function subirFotoDoctor(Request $request)
    {
        $request->validate([
            'foto_perfil' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $user = Auth::user();

        if ($user->rol !== 'doctor') {
            return back()->with('error', 'Solo los doctores pueden actualizar su foto de perfil.');
        }

        // Busca el perfil del doctor vinculado EXCLUSIVAMENTE al usuario autenticado
        $doctor = Doctor::where('id_usuario', $user->id_usuario)->first();

        if (!$doctor) {
            return back()->with('error', 'No se encontró tu perfil de doctor.');
        }

        // Borrar foto anterior si existe
        if ($doctor->foto_perfil && Storage::disk('public')->exists($doctor->foto_perfil)) {
            Storage::disk('public')->delete($doctor->foto_perfil);
        }

        $ruta = $request->file('foto_perfil')->store('fotos_doctores', 'public');
        $doctor->foto_perfil = $ruta;
        $doctor->save();

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }

    /**
     * Actualiza las preferencias de accesibilidad y tema visual para la clínica.
     */
    public function updateApariencia(Request $request)
    {
        $clinica = Clinica::find(Auth::user()->id_clinica);
        abort_if(!$clinica, 403, 'No tienes permiso para modificar esta clínica.');

        $request->validate([
            'tema_visual' => 'required|in:claro,oscuro,invertido',
            'color_primario' => 'required|string|max:25',
            'color_secundario' => 'nullable|string|max:25',
            'color_acento' => 'nullable|string|max:25',
        ]);

        $clinica->tema_visual = $request->tema_visual;
        $clinica->color_primario = $request->color_primario;

        if ($clinica->hasAnyPlan(['premium', 'ultra'])) {
            $clinica->color_secundario = $request->color_secundario;
            $clinica->color_acento = $request->color_acento;
        } else {
            // Si el plan no califica, limpiamos o ignoramos las variables extendidas.
            // Para asegurar consistencia, las llevamos a null.
            $clinica->color_secundario = null;
            $clinica->color_acento = null;
        }

        $clinica->save();

        return back()->with('success', 'Ajustes de apariencia guardados correctamente.');
    }
}