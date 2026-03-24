<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClinicaRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // Datos del doctor principal de la clínica
        $doctorUser = User::where('id_clinica', $user->id_clinica)
            ->where('rol', 'doctor')
            ->first();

        $doctorPerfil = $doctorUser
            ? Doctor::where('id_usuario', $doctorUser->id_usuario)->first()
            : null;

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
     * Usa UpdateClinicaRequest para todas las validaciones.
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
     * La autorización de tenant está garantizada por UpdateUsuarioRequest::authorize().
     */
    public function updateUsuario(UpdateUsuarioRequest $request)
    {
        $validated = $request->validated();
        $usuario = User::findOrFail($validated['id_usuario']);

        $data = [
            'nombre_completo' => $validated['nombre_completo'],
            'email' => $validated['email'],
        ];

        // Actualizar contraseña solo si se proporcionó una nueva
        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];  // El cast 'hashed' del modelo User lo hashea automáticamente
        }

        $usuario->update($data);

        // Si es doctor, actualizar datos profesionales
        if ($usuario->rol === 'doctor') {
            Doctor::updateOrCreate(
                ['id_usuario' => $usuario->id_usuario],
                [
                    'cedula_profesional' => $validated['cedula_profesional'] ?? null,
                    'horario_default' => $validated['horario_default'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Perfil de usuario actualizado correctamente.');
    }

    /**
     * Crea una nueva cuenta de recepcionista para la clínica del usuario autenticado.
     */
    public function storeRecepcionista(Request $request)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:100',
            'email' => 'required|email|unique:usuarios_sistema,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'id_clinica' => Auth::user()->id_clinica,
            'nombre_completo' => $request->nombre_completo,
            'email' => $request->email,
            'password' => $request->password,  // El cast 'hashed' del modelo User lo hashea automáticamente
            'rol' => 'recepcionista',
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

        // Orden esperado: 1=Lunes … 6=Sábado, 0=Domingo
        foreach ([1, 2, 3, 4, 5, 6, 0] as $dia) {
            $activo = isset($dias[$dia]['activo']) ? 1 : 0;
            $horaInicio = $dias[$dia]['hora_inicio'] ?? null;
            $horaFin = $dias[$dia]['hora_fin'] ?? null;

            // Si está inactivo, limpiar horas
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
     * Obtiene los 7 registros de horario de una clínica, creándolos con valores
     * por defecto si no existen (Lun-Vie 09:00-18:00, Sáb-Dom cerrados).
     */
    private function obtenerOCrearHorarios(int $idClinica): \Illuminate\Database\Eloquent\Collection
    {
        $existentes = HorarioClinica::where('id_clinica', $idClinica)->count();

        if ($existentes < 7) {
            $defaults = [
                1 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'], // Lunes
                2 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'], // Martes
                3 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'], // Miércoles
                4 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'], // Jueves
                5 => ['activo' => true, 'hora_inicio' => '09:00', 'hora_fin' => '18:00'], // Viernes
                6 => ['activo' => false, 'hora_inicio' => null, 'hora_fin' => null],     // Sábado
                0 => ['activo' => false, 'hora_inicio' => null, 'hora_fin' => null],     // Domingo
            ];

            foreach ($defaults as $dia => $vals) {
                HorarioClinica::firstOrCreate(
                    ['id_clinica' => $idClinica, 'dia_semana' => $dia],
                    $vals
                );
            }
        }

        // Retornar ordenados: Lun(1)…Sáb(6), Dom(0)
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
            'foto_perfil' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB máximo
        ]);

        $user = Auth::user();
        // Buscar al doctor dueño de la clínica, sin importar si soy admin o recepcionista
        $doctorUser = User::where('id_clinica', $user->id_clinica)->where('rol', 'doctor')->first();
        $doctor = $doctorUser ? Doctor::where('id_usuario', $doctorUser->id_usuario)->first() : null;

        if (!$doctor) {
            return back()->with('error', 'No se encontró el perfil del doctor para esta clínica.');
        }

        // Eliminar foto anterior si existe
        if ($doctor->foto_perfil && \Storage::disk('public')->exists($doctor->foto_perfil)) {
            \Storage::disk('public')->delete($doctor->foto_perfil);
        }

        $ruta = $request->file('foto_perfil')->store('fotos_doctores', 'public');
        $doctor->update(['foto_perfil' => $ruta]);

        return back()->with('success', 'Foto de perfil actualizada correctamente.');
    }
}
