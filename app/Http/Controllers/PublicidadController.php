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
     * Obtiene IDs de usuarios de la clínica actual con fallback al usuario autenticado.
     */
    private function resolveUsuariosClinicaIds(): array
    {
        $authUser = Auth::user();
        if (!$authUser) {
            return [];
        }

        $ids = User::query()
            ->where('id_clinica', $authUser->id_clinica)
            ->pluck('id_usuario')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $authId = (int) ($authUser->id_usuario ?? $authUser->id ?? 0);
        if ($authId > 0 && !in_array($authId, $ids, true)) {
            $ids[] = $authId;
        }

        return $ids;
    }

    /**
     * Muestra la lista de anuncios publicitarios ordenados por fecha.
     */
    public function index()
    {
        $usuariosClinica = $this->resolveUsuariosClinicaIds();
        $authId = (int) (Auth::user()->id_usuario ?? Auth::user()->id ?? 0);

        $anuncios = Publicidad::with('usuario')
            ->where(function ($query) use ($usuariosClinica, $authId) {
                if (!empty($usuariosClinica)) {
                    $query->whereIn('id_usuario', $usuariosClinica);
                }

                if ($authId > 0) {
                    $query->orWhere('id_usuario', $authId);
                }
            })
            ->orderByDesc('created_at')
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
            $authUser = Auth::user();
            $authId = (int) ($authUser->id_usuario ?? $authUser->id ?? 0);
            if ($authId <= 0) {
                return redirect()->back()->with('error', 'No se pudo identificar el usuario para publicar.');
            }

            $path = null;
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('ads', 'public');
            }

            Publicidad::create([
                'id_usuario' => $authId,
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
        $usuariosClinica = $this->resolveUsuariosClinicaIds();
        $authId = (int) (Auth::user()->id_usuario ?? Auth::user()->id ?? 0);

        $anuncio = Publicidad::where('id_publicidad', $id)
            ->where(function ($query) use ($usuariosClinica, $authId) {
                if (!empty($usuariosClinica)) {
                    $query->whereIn('id_usuario', $usuariosClinica);
                }

                if ($authId > 0) {
                    $query->orWhere('id_usuario', $authId);
                }
            })
            ->firstOrFail();

        if ($anuncio->imagen_path) {
            Storage::disk('public')->delete($anuncio->imagen_path);
        }

        $anuncio->delete();

        return redirect()->back()->with('success', 'Promoción eliminada correctamente.');
    }
}