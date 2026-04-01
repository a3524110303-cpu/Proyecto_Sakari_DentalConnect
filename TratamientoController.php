<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Servicio;
use Illuminate\Support\Facades\Auth;

class TratamientoController extends Controller
{
    /**
     * Lista los tratamientos de la clínica autenticada.
     * El ClinicaScope en Servicio ya aplica el filtro automáticamente,
     * pero lo hacemos explícito aquí como doble seguridad.
     */
    public function index()
    {
        $idClinica = Auth::user()->id_clinica;

        $tratamientos = Servicio::where('id_clinica', $idClinica)->get();

        return view('tratamientos.index', compact('tratamientos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'    => 'required|string|max:255',
            'precio'    => 'required|numeric|min:0',
            'categoria' => 'required|string|max:100',
        ]);

        $idClinica = Auth::user()->id_clinica;

        $existe = Servicio::where('nombre_servicio', $request->nombre)
            ->where('id_clinica', $idClinica)
            ->first();

        if ($existe) {
            return redirect()->back()->with('error', 'El tratamiento ya existe');
        }

        Servicio::create([
            'id_clinica'      => $idClinica,
            'nombre_servicio' => $request->nombre,
            'precio_base'     => $request->precio,
            'categoria'       => $request->categoria,
        ]);

        return redirect()->back()->with('success', 'Tratamiento creado correctamente');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre'    => 'required|string|max:255',
            'precio'    => 'required|numeric|min:0',
            'categoria' => 'required|string|max:100',
        ]);

        $idClinica = Auth::user()->id_clinica;

        // Solo puede editar tratamientos de SU clínica
        $tratamiento = Servicio::where('id_servicio', $id)
            ->where('id_clinica', $idClinica)
            ->firstOrFail();

        $existe = Servicio::where('nombre_servicio', $request->nombre)
            ->where('id_clinica', $idClinica)
            ->where('id_servicio', '!=', $id)
            ->first();

        if ($existe) {
            return redirect()->back()->with('error', 'El tratamiento ya existe');
        }

        $tratamiento->update([
            'nombre_servicio' => $request->nombre,
            'precio_base'     => $request->precio,
            'categoria'       => $request->categoria,
        ]);

        return redirect()->back()->with('success', 'Tratamiento actualizado correctamente');
    }

    public function destroy($id)
    {
        try {
            $idClinica = Auth::user()->id_clinica;

            // Solo puede eliminar tratamientos de SU clínica
            $tratamiento = Servicio::where('id_servicio', $id)
                ->where('id_clinica', $idClinica)
                ->first();

            if (! $tratamiento) {
                return redirect()->back()->with('error', 'Tratamiento no encontrado.');
            }

            $tratamiento->delete();

            return redirect()->back()->with('success', 'Tratamiento eliminado correctamente');

        } catch (\Exception $e) {
            return redirect()->back()->with(
                'error',
                'No se puede eliminar: el tratamiento está siendo usado en citas registradas.'
            );
        }
    }
}
