<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Publicidad;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PublicidadController extends Controller
{
    /**
     * Muestra los anuncios publicitarios de la clínica autenticada.
     * Solo se devuelven publicidades que pertenecen a la clínica del usuario actual.
     */
    public function index()
    {
        $idClinica = Auth::user()->id_clinica;

        if (! $idClinica) {
            return view('publicidad.index', ['anuncios' => collect()]);
        }

        $anuncios = Publicidad::with('usuario')
            ->where('id_clinica', $idClinica)
            ->orderByDesc('created_at')
            ->get();

        return view('publicidad.index', compact('anuncios'));
    }

    /**
     * Almacena una nueva promoción publicitaria asociada a la clínica actual.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $authUser  = Auth::user();
        $idClinica = $authUser->id_clinica;
        $authId    = (int) ($authUser->id_usuario ?? $authUser->id ?? 0);

        if (! $idClinica || $authId <= 0) {
            return redirect()->back()->with('error', 'No se pudo identificar la clínica o el usuario para publicar.');
        }

        try {
            $path = $request->file('imagen')->store('ads', 'public');

            Publicidad::create([
                'id_clinica'  => $idClinica,
                'id_usuario'  => $authId,
                'titulo'      => $request->titulo,
                'descripcion' => $request->descripcion,
                'imagen_path' => $path,
                'activo'      => 1,
            ]);

            return redirect()->back()->with('success', '¡Promoción publicada correctamente!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al publicar: ' . $e->getMessage());
        }
    }

    /**
     * Elimina una promoción publicitaria.
     * Solo se puede eliminar si la publicidad pertenece a la clínica del usuario actual.
     */
    public function destroy($id)
    {
        $idClinica = Auth::user()->id_clinica;

        $anuncio = Publicidad::where('id_publicidad', $id)
            ->where('id_clinica', $idClinica)
            ->firstOrFail();

        if ($anuncio->imagen_path) {
            Storage::disk('public')->delete($anuncio->imagen_path);
        }

        $anuncio->delete();

        return redirect()->back()->with('success', 'Promoción eliminada correctamente.');
    }
}
