<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\SignoVital;
use App\Models\EvolucionTratamiento;
use App\Models\Archivo;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PacienteHistorialController extends Controller
{
    /**
     * Retorna los últimos signos vitales del paciente.
     */
    public function signosVitales($idPaciente)
    {
        $registros = SignoVital::where('paciente_id', $idPaciente)
            ->orderBy('fecha_registro', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $registros,
        ]);
    }

    /**
     * Retorna las evoluciones de tratamiento del paciente.
     */
    public function evoluciones($idPaciente)
    {
        $registros = EvolucionTratamiento::where('id_paciente', $idPaciente)
            ->orderBy('fecha_evolucion', 'desc')
            ->take(10)
            ->get();

        foreach ($registros as $reg) {
            $archivo = Archivo::where('id_paciente', $idPaciente)
                ->where('descripcion', 'Evolucion_' . $reg->id_evolucion)
                ->first();

            if ($archivo) {
                $ruta = ltrim(str_replace('public/', '', (string) $archivo->url_archivo), '/');
                $reg->imagenes = [
                    [
                        'ruta_imagen' => $ruta,
                        'url_imagen' => route('storage.file', ['path' => $ruta]),
                    ]
                ];
            } else {
                $reg->imagenes = [];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $registros,
        ]);
    }

    /**
     * Guarda una nueva evolución clínica y permite adjuntar una imagen.
     */
    public function storeEvolucion(\Illuminate\Http\Request $request, $idPaciente)
    {
        try {
            $request->validate([
                'descripcion_avance' => 'required|string|max:500',
                'plan_tratamiento' => 'nullable|string',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // máximo 5MB
            ]);

            $evolucion = new EvolucionTratamiento();
            // Assigning a default service ID if it's required, although we don't know for sure.
            // But let's see if it errors out first.
            $evolucion->id_paciente = $idPaciente;
            $evolucion->fecha_evolucion = now();
            $evolucion->descripcion_avance = $request->descripcion_avance;
            $evolucion->plan_tratamiento = $request->plan_tratamiento;
            $evolucion->save();

            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('evoluciones', $filename, 'public');

                Archivo::create([
                    'id_paciente' => $idPaciente,
                    'tipo' => 'imagen',
                    'url_archivo' => $path,
                    'descripcion' => 'Evolucion_' . $evolucion->id_evolucion,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Evolución guardada correctamente.',
                'data' => $evolucion
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error 500: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()
            ], 500);
        }
    }

    /**
     * Sube y actualiza la foto de progreso del paciente.
     */
    public function subirFotoProgreso(Request $request, $idPaciente)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = 'paciente_' . $idPaciente . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('pacientes/fotos', $filename, 'public');

            // Buscar si ya tiene una foto, si sí, actualizar, si no, crear.
            $archivo = Archivo::where('id_paciente', $idPaciente)
                ->where('tipo', 'imagen')
                ->where('descripcion', 'Foto de perfil/progreso del paciente')
                ->first();

            if ($archivo) {
                // Eliminar archivo anterior si existe
                if (Storage::disk('public')->exists($archivo->url_archivo)) {
                    Storage::disk('public')->delete($archivo->url_archivo);
                }
                $archivo->url_archivo = $path;
                $archivo->save();
            } else {
                $archivo = Archivo::create([
                    'id_paciente' => $idPaciente,
                    'tipo' => 'imagen',
                    'url_archivo' => $path,
                    'descripcion' => 'Foto de perfil/progreso del paciente',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Foto actualizada correctamente.',
                'url' => route('storage.file', ['path' => ltrim($path, '/')])
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No se recibió ninguna imagen.']);
    }

    /**
     * Retorna el historial de citas del paciente.
     */
    public function historialCitas($idPaciente)
    {
        $citas = Cita::with(['servicio', 'doctor'])
            ->where('id_paciente', $idPaciente)
            ->orderBy('fecha_hora_inicio', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $citas,
        ]);
    }
}
