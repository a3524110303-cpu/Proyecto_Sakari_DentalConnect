@extends('layouts.app')

@section('titulo', 'Tratamientos')

@section('contenido')

<h2 class="page-title" style="margin-bottom: 30px;">
    Gestión de Tratamientos
</h2>

{{-- ÁREA DE NOTIFICACIONES (SUCCESS Y ERROR) --}}
@if(session('success'))
<div style="background:#dcfce7;color:#166534;padding:15px;border-radius:8px;margin-bottom:20px;border:1px solid #bbf7d0; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin-bottom:20px;border:1px solid #fecaca; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
    <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
</div>
@endif

{{-- BARRA SUPERIOR (SIN BÚSQUEDA) --}}
<div style="display:flex; justify-content: flex-end; align-items:center; margin-bottom:25px;">
    <button onclick="openModal('modal-new-treatment')" class="ghost-btn" style="border-radius:50px; background:var(--primary-color); color:white; border:none; padding:12px 25px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; box-shadow: var(--shadow);">
        <i class="fa-solid fa-plus"></i> Nuevo Tratamiento
    </button>
</div>

{{-- TABLA DE TRATAMIENTOS --}}
<div class="dashboard-table" style="background:var(--white); border-radius:15px; padding:20px; box-shadow:var(--shadow); border: 1px solid var(--light-bg);">
    <table style="width:100%; border-collapse:collapse; color: var(--text-dark);">
        <thead>
            <tr style="border-bottom:2px solid var(--light-bg);">
                <th style="text-align:left; padding:15px; color:var(--text-light);">Nombre</th>
                <th style="text-align:left; padding:15px; color:var(--text-light);">Categoría</th>
                <th style="text-align:left; padding:15px; color:var(--text-light);">Costo</th>
                <th style="text-align:right; padding:15px; color:var(--text-light);">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tratamientos as $servicio)
            <tr style="border-bottom:1px solid var(--light-bg);">
                <td style="padding:15px; font-weight:600;">{{ $servicio->nombre_servicio }}</td>
                <td style="padding:15px; color:var(--text-light); font-size:0.9em;">
                    <span style="background:var(--input-bg); padding:4px 10px; border-radius:10px;">
                        {{ $servicio->categoria ?? 'General' }}
                    </span>
                </td>
                <td style="padding:15px; color:var(--primary-color); font-weight:bold;">
                    ${{ number_format($servicio->precio_base, 2) }}
                </td>
                <td style="padding:15px; text-align:right;">
                    {{-- EDITAR --}}
                    <button onclick='editarTratamiento(@json($servicio))' style="background:none; border:none; cursor:pointer; color:#f59e0b; margin-right:10px; font-size: 1.1rem;">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    {{-- ELIMINAR CON SWEETALERT --}}
                    <form id="delete-form-{{ $servicio->id_servicio }}" action="{{ url('tratamientos/' . $servicio->id_servicio) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="confirmDelete({{ $servicio->id_servicio }})" style="background:none; border:none; cursor:pointer; color:#ef4444; font-size: 1.1rem;">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center; padding:30px; color:var(--text-light);">No hay tratamientos registrados</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- MODAL NUEVO (SIN BUGS DE ERROR INTERNO) --}}
<div id="modal-new-treatment" class="modal-overlay">
    <div class="modal-glass" style="max-width:500px;">
        <button class="close-modal" onclick="closeModal('modal-new-treatment')">&times;</button>
        <h3 style="margin-bottom: 20px; color: var(--text-dark);">Nuevo Tratamiento</h3>
        
        <form action="{{ route('tratamientos.store') }}" method="POST">
            @csrf
            <div style="margin-bottom:15px;">
                <input type="text" name="nombre" class="modern-input" placeholder="Nombre Servicio" value="{{ old('nombre') }}" required style="width:100%;">
            </div>
            <div style="margin-bottom:15px;">
                <input type="number" name="precio" step="0.01" class="modern-input" placeholder="Precio Base" value="{{ old('precio') }}" required style="width:100%;">
            </div>
            <div style="margin-bottom:15px;">
                <select name="categoria" class="modern-input" style="width:100%;">
                    <option value="General">General</option>
                    <option value="Ortodoncia">Ortodoncia</option>
                    <option value="Limpieza">Limpieza</option>
                    <option value="Cirugía">Cirugía</option>
                    <option value="Estética">Estética</option>
                    <option value="Endodoncia">Endodoncia</option>
                </select>
            </div>
            <button type="submit" class="ghost-btn" style="width:100%; background:var(--primary-color); color:white; border-radius: 10px; padding: 12px;">Guardar</button>
        </form>
    </div>
</div>

{{-- MODAL EDITAR --}}
<div id="modal-edit-treatment" class="modal-overlay">
    <div class="modal-glass" style="max-width:500px;">
        <button class="close-modal" onclick="closeModal('modal-edit-treatment')">&times;</button>
        <h3 style="color:#f59e0b; margin-bottom: 20px;">Editar Tratamiento</h3>
        <form id="form-edit" method="POST">
            @csrf
            @method('PUT')
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; color:var(--text-dark); font-size: 0.9em;">Nombre</label>
                <input type="text" id="edit-nombre" name="nombre" class="modern-input" required style="width:100%;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; color:var(--text-dark); font-size: 0.9em;">Precio</label>
                <input type="number" id="edit-precio" name="precio" step="0.01" class="modern-input" required style="width:100%;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; color:var(--text-dark); font-size: 0.9em;">Categoría</label>
                <select id="edit-categoria" name="categoria" class="modern-input" style="width:100%;">
                    <option value="General">General</option>
                    <option value="Ortodoncia">Ortodoncia</option>
                    <option value="Limpieza">Limpieza</option>
                    <option value="Cirugía">Cirugía</option>
                    <option value="Estética">Estética</option>
                    <option value="Endodoncia">Endodoncia</option>
                </select>
            </div>
            <button type="submit" class="ghost-btn" style="width:100%; background:#f59e0b; color:white; border-radius: 10px; padding: 12px;">Actualizar</button>
        </form>
    </div>
</div>

@endsection

@section('scripts')
{{-- SweetAlert2 para una experiencia de usuario Premium --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Confirmación Estética de Eliminación
function confirmDelete(id) {
    Swal.fire({
        title: '¿Eliminar tratamiento?',
        text: "Esta acción no se puede deshacer. Si el tratamiento tiene citas, no podrá ser borrado.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: {
            popup: 'animated fadeInDown'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    })
}

function editarTratamiento(servicio){
    document.getElementById('edit-nombre').value = servicio.nombre_servicio;
    document.getElementById('edit-precio').value = servicio.precio_base;
    document.getElementById('edit-categoria').value = servicio.categoria || 'General';

    const form = document.getElementById('form-edit');
    form.action = "{{ url('tratamientos') }}/" + servicio.id_servicio;

    openModal('modal-edit-treatment');
}

{{-- Control inteligente de Modales: No se abre si el error es de eliminación --}}
@if(session('error') && !str_contains(session('error'), 'eliminar'))
    openModal('modal-new-treatment');
@endif
</script>
@endsection