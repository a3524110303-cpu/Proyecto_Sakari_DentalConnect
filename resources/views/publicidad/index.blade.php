@extends('layouts.app')

@section('titulo', 'Publicidad')

@section('contenido')
    <div class="header-section"
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 class="page-title" style="margin: 0;">Gestión de Publicidad</h2>
            <p style="color: var(--text-light); margin: 5px 0 0;">Sube promociones para que tus pacientes las vean en la sala de espera.
            </p>
        </div>
        <button onclick="document.getElementById('modal-upload').style.display='flex'" class="ghost-btn"
            style="background: var(--primary-color); color: white; border: none;">
            <i class="fa-solid fa-cloud-arrow-up"></i> Nueva Promoción
        </button>
    </div>

    @if($anuncios->isEmpty())
        <div style="text-align: center; padding: 50px; background: var(--input-bg); border-radius: 15px; color: var(--text-light); border: 2px dashed var(--light-bg);">
            <i class="fa-solid fa-images" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>No tienes promociones activas. ¡Sube la primera!</p>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px;">
            @foreach($anuncios as $ad)
                <div class="ad-card"
                    style="background: var(--white); padding: 15px; border-radius: 12px; box-shadow: var(--shadow); border: 1px solid var(--light-bg); transition: transform 0.2s;">
                    <div
                        style="height: 180px; background: var(--input-bg); border-radius: 8px; margin-bottom: 15px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                        @if($ad->imagen_path)
                            <img src="{{ route('storage.file', ['path' => $ad->imagen_path]) }}" alt="{{ $ad->titulo }}"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <i class="fa-solid fa-image" style="font-size: 2rem; color: var(--text-light);"></i>
                        @endif
                    </div>

                    <h4 style="margin: 0 0 5px; color: var(--text-dark);">{{ $ad->titulo }}</h4>
                    <div style="margin-bottom: 5px;">
                        <small style="color: var(--primary-color); font-size: 0.75rem;">
                            <i class="fa-solid fa-user-doctor"></i> {{ optional($ad->usuario)->nombre_completo ?? 'Desconocido' }}
                        </small>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 15px; height: 40px; overflow: hidden;">
                        {{ $ad->descripcion ?? 'Sin descripción' }}
                    </p>

                    <form action="{{ route('publicidad.destroy', $ad->id_publicidad) }}" method="POST"
                        onsubmit="return confirm('¿Seguro que quieres borrar esta promoción?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ghost-btn"
                            style="width: 100%; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                            <i class="fa-solid fa-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    <div id="modal-upload"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: var(--white); padding: 30px; border-radius: 15px; width: 400px; max-width: 90%; border: 1px solid var(--light-bg); box-shadow: var(--shadow);">
            <h3 style="margin-top: 0; color: var(--primary-color);">Nueva Promoción</h3>

            <form action="{{ route('publicidad.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-dark);">Título</label>
                    <input type="text" name="titulo" class="modern-input" required placeholder="Ej: 2x1 en Blanqueamiento"
                        style="width: 100%; padding: 10px; border: 1px solid var(--light-bg); border-radius: 8px; background: var(--input-bg); color: var(--text-dark);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-dark);">Descripción</label>
                    <textarea name="descripcion" rows="3" class="modern-input" placeholder="Detalles de la promo..."
                        style="width: 100%; padding: 10px; border: 1px solid var(--light-bg); border-radius: 8px; background: var(--input-bg); color: var(--text-dark);"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-dark);">Imagen (Banner)</label>
                    <input type="file" name="imagen" accept="image/*" required style="width: 100%; color: var(--text-dark);">
                    <small style="color: var(--text-light);">Recomendado: JPG o PNG, Máx 2MB</small>
                </div>

                <div style="text-align: right; display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="document.getElementById('modal-upload').style.display='none'"
                        style="padding: 10px 20px; background: var(--input-bg); border: none; border-radius: 8px; cursor: pointer; color: var(--text-dark);">Cancelar</button>
                    <button type="submit"
                        style="padding: 10px 20px; background: var(--primary-color); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">Publicar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.onclick = function (event) {
            let modal = document.getElementById('modal-upload');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
@endsection