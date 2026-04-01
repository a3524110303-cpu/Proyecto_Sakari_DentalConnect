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
     * Muestra la vista de configuración con datos del usuario autenticado y su clínica.
     */
    public function index()
    {
        $user = Auth::user();
        $clinica = Clinica::find($user->id_clinica);
        abort_if(!$clinica, 403, 'No tienes una clínica asignada.');

        // 1. Mostrar el perfil del doctor autenticado (No el primero de la clínica)
        $doctorUser = null;
        $doctorPerfil = null;

        if ($user->rol === 'doctor') {
            $doctorUser = $user;
            $doctorPerfil = Doctor::where('id_usuario', $user->id_usuario)->first();
        }

        // Lista de recepcionistas de la misma clínica
        $recepcionistas = User::where('id_clinica', $user->id_clinica)
            ->where('rol', 'recepcionista')
            ->where('is_active', true)
            ->get();

        // Horarios de atención (crear registros por defecto si no existen)
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
     * Actualiza la información de un usuario (doctor o recepcionista).
     */
    public function updateUsuario(UpdateUsuarioRequest $request)
    {
        $validated = $request->validated();
        
        // Buscamos al usuario asegurando que pertenezca a la clínica del autenticado
        $usuario = User::where('id_clinica', Auth::user()->id_clinica)
                       ->findOrFail($validated['id_usuario']);

        $data = [
            'nombre_completo' => $validated['nombre_completo'],
            'email'           => $validated['email'],
            'sobre_mi'        => $validated['sobre_mi'] ?? null, // <-- AQUÍ SE GUARDA TU TEXTO
        ];

        // Actualizar contraseña solo si se proporcionó una nueva
        if (!empty($validated['password'])) {
            $data['password'] = $validated['password']; 
        }

        // Usamos una transacción para asegurar que si falla la parte del Doctor, no se rompa el User
        DB::transaction(function () use ($usuario, $data, $validated) {
            $usuario->update($data);

            if ($usuario->rol === 'doctor') {
                Doctor::updateOrCreate(
                    ['id_usuario' => $usuario->id_usuario],
                    [
                        'cedula_profesional' => $validated['cedula_profesional'] ?? null,
                        'horario_default'    => $validated['horario_default'] ?? null,
                    ]
                );
            }
        });

        return back()->with('success', 'Perfil de usuario actualizado correctamente.');
    }

    /**
     * Crea una nueva cuenta de recepcionista.
     */
    public function storeRecepcionista(Request $request)
    {
        $clinica = Auth::user()->clinica;
        $suscripcion = $clinica->suscripciones()->where('estado', 'active')->first();

        if (!$suscripcion || !$suscripcion->plan) {
            return redirect()->back()
                ->with('error', "No tienes una suscripción activa para agregar usuarios.");
        }

        $plan = $suscripcion->plan;

        // Contamos cuántos usuarios (doctores + recepcionistas) tiene la clínica
        $totalUsuarios = \App\Models\User::where('id_clinica', $clinica->id_clinica)->count();

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
     * Da de baja (desactiva) a un recepcionista
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
     * Obtiene o crea horarios por defecto.
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
     * Sube o actualiza la foto de perfil del doctor.
     */
    public function subirFotoDoctor(Request $request)
    {
        $request->validate([
            'foto_perfil' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $user = Auth::user();
        $doctorUser = User::where('id_clinica', $user->id_clinica)->where('rol', 'doctor')->first();
        $doctor = $doctorUser ? Doctor::where('id_usuario', $doctorUser->id_usuario)->first() : null;

        if (!$doctor) {
            return back()->with('error', 'No se encontró el perfil del doctor.');
        }

        if ($doctor->foto_perfil && Storage::disk('public')->exists($doctor->foto_perfil)) {
            Storage::disk('public')->delete($doctor->foto_perfil);
        }

        $ruta = $request->file('foto_perfil')->store('fotos_doctores', 'public');
        $doctor->update(['foto_perfil' => $ruta]);

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }
}