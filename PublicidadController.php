<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Publicidad;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PublicidadController extends Controller
{
    /**
     * Muestra SOLO los anuncios de la clínica del usuario autenticado.
     * Filtrado estricto: obtenemos los id_usuario de los miembros de la clínica
     * y luego filtramos la publicidad por esos usuarios.
     */
    public function index()
    {
        $idClinica = Auth::user()->id_clinica;

        // Obtenemos los IDs de todos los usuarios que pertenecen a ESTA clínica
        $idsUsuariosClinica = User::where('id_clinica', $idClinica)
            ->pluck('id_usuario')
            ->toArray();

        $anuncios = Publicidad::whereIn('id_usuario', $idsUsuariosClinica)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('publicidad.index', compact('anuncios'));
    }

    /**
     * Almacena una nueva promoción asociada al usuario autenticado.
     * La seguridad de clínica está garantizada porque el id_usuario
     * pertenece siempre al usuario autenticado.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo'  => 'required|string|max:100',
            'imagen'  => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $path = null;
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('ads', 'public');
            }

            Publicidad::create([
                'id_usuario'  => Auth::id(),
                'titulo'      => $request->titulo,
                'descripcion' => $request->descripcion,
                'imagen_path' => $path,
                'activo'      => 1,
            ]);

            return redirect()->back()->with('success', '¡Promoción publicada correctamente!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Elimina una promoción verificando que pertenezca a la clínica del usuario.
     */
    public function destroy($id)
    {
        $idClinica = Auth::user()->id_clinica;

        // Obtenemos los IDs de usuarios de esta clínica para verificar pertenencia
        $idsUsuariosClinica = User::where('id_clinica', $idClinica)
            ->pluck('id_usuario')
            ->toArray();

        // Solo permite eliminar anuncios de SU clínica
        $anuncio = Publicidad::whereIn('id_usuario', $idsUsuariosClinica)
            ->findOrFail($id);

        if ($anuncio->imagen_path) {
            Storage::disk('public')->delete($anuncio->imagen_path);
        }

        $anuncio->delete();

        return redirect()->back()->with('success', 'Promoción eliminada correctamente.');
    }
}
