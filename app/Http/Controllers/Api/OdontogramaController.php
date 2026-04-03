<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\Request;
use App\Models\Odontograma;
use Illuminate\Support\Facades\Auth;

/**
 * API del Odontograma mapeada a las columnas reales de la tabla `odontograma`.
 *
 * Columnas reales: numero_diente | cara_diente | estado_diente | observaciones | fecha_registro
 */
class OdontogramaController extends Controller
{
    /**
     * Verifica que el paciente pertenece a la clínica del usuario autenticado.
     */
    private function verificarPacienteClinica(int $idPaciente): void
    {
        $idClinica = Auth::user()->id_clinica;

        $existe = Paciente::whereHas('usuario', function ($q) use ($idClinica) {
            $q->where('id_clinica', $idClinica);
        })->where('id_paciente', $idPaciente)->exists();

        if (! $existe) {
            abort(403, 'No tienes acceso a los datos de este paciente.');
        }
    }

    /**
     * Obtiene todo el historial dental de un paciente.
     */
    public function index($id_paciente)
    {
        $this->verificarPacienteClinica((int) $id_paciente);

        $registros = Odontograma::where('id_paciente', $id_paciente)
            ->orderBy('id_odontograma', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $registros,
        ]);
    }

    /**
     * Guarda un nuevo registro cuando el doctor marca un diente.
     */
    public function store(Request $request, $id_paciente = null)
    {
        if (!is_null($id_paciente)) {
            $request->merge(['id_paciente' => $id_paciente]);
        }

        $validated = $request->validate([
            'id_paciente' => 'required|integer|exists:pacientes,id_paciente',
            'id_cita' => 'nullable|integer|exists:citas,id_cita',
            'numero_diente' => 'required|string|max:5',
            'cara_diente' => 'nullable|string|max:50',
            'estado_diente' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $this->verificarPacienteClinica((int) $validated['id_paciente']);

        $validated['fecha_registro'] = now();

        $registro = Odontograma::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Registro dental guardado correctamente.',
            'data' => $registro,
        ], 201);
    }

    /**
     * Elimina un registro del odontograma.
     * Solo se permite si el paciente pertenece a la clínica del usuario.
     */
    public function destroy($id_odontograma)
    {
        $registro = Odontograma::find($id_odontograma);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        $this->verificarPacienteClinica((int) $registro->id_paciente);

        $registro->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado correctamente.',
        ]);
    }
}
