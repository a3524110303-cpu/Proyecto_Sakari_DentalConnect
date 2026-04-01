<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Publicidad;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PublicidadController extends Controller
{
    /**
     * Muestra la lista de anuncios publicitarios ordenados por fecha.
     */
    public function index()
    {
        $idClinica = Auth::user()->id_clinica;
        
        // 1. Obtenemos un arreglo solo con los IDs de los doctores/recepcionistas de esta clínica
        $usuariosClinica = User::where('id_clinica', $idClinica)->pluck('id_usuario');

        // 2. Buscamos directamente los anuncios que pertenezcan a esos usuarios (método infalible)
        $anuncios = Publicidad::whereIn('id_usuario', $usuariosClinica)
                              ->orderBy('created_at', 'desc')
                              ->get();

        return view('publicidad.index', compact('anuncios'));
    }

    /**
     * Almacena una nueva promoción publicitaria.
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:100',
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        try {
            $path = null;
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('ads', 'public');
            }

            Publicidad::create([
                // Usamos explícitamente Auth::user()->id_usuario para evitar errores de ID nulo
                'id_usuario' => Auth::user()->id_usuario, 
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'imagen_path' => $path,
                'activo' => 1
            ]);

            return redirect()->back()->with('success', '¡Promoción publicada correctamente!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Elimina una promoción publicitaria.
     */
    public function destroy($id)
    {
        $idClinica = Auth::user()->id_clinica;
        $usuariosClinica = User::where('id_clinica', $idClinica)->pluck('id_usuario');

        // Nos aseguramos de que solo pueda borrar anuncios de su propia clínica
        $anuncio = Publicidad::whereIn('id_usuario', $usuariosClinica)->findOrFail($id);

        if ($anuncio->imagen_path) {
            Storage::disk('public')->delete($anuncio->imagen_path);
        }

        $anuncio->delete();

        return redirect()->back()->with('success', 'Promoción eliminada correctamente.');
    }
}