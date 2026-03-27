@extends('layouts.app')

@section('titulo', 'Configuración')

@section('contenido')
    <div class="header-section" style="margin-bottom: 30px;">
        <h2 class="page-title">Configuración de la Clínica {{ $clinica->nombre_comercial }}</h2>
        <p style="color: #666;">Gestiona la información de tu consultorio y de tu equipo</p>
    </div>

    @if(session('success'))
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div style="background: #f8d7da; color: #842029; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-circle-xmark"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: #f8d7da; color: #842029; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">

        {{-- ═══════════════ DATOS DE LA CLÍNICA ═══════════════ --}}
        <div class="config-card"
            style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h3 style="color: #0077b6; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-top: 0;">
                <i class="fa-solid fa-hospital"></i> Datos de la Clínica
            </h3>
            <form action="{{ route('configuracion.updateClinica') }}" method="POST">
                @csrf
                <div style="margin-bottom: 15px;">
                    <label>Nombre Comercial</label>
                    <input type="text" name="nombre_comercial" value="{{ $clinica->nombre_comercial }}" class="modern-input"
                        required oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label>Teléfono</label>
                        <input type="text" name="numero_telefono" value="{{ $clinica->numero_telefono }}"
                            class="modern-input" maxlength="12" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    </div>
                    <div>
                        <label>Código Postal</label>
                        <input type="text" name="codigo_postal" value="{{ $clinica->codigo_postal }}"
                            class="modern-input" maxlength="5" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Calle y Número</label>
                    <input type="text" name="calle" id="campo_calle" value="{{ $clinica->calle }}" class="modern-input"
                        placeholder="Ej. Av. Reforma 123">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label>Ciudad</label>
                        <input type="text" name="ciudad" id="campo_ciudad" value="{{ $clinica->ciudad }}" class="modern-input"
                            oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                    </div>
                    <div>
                        <label>Municipio / Alcaldía</label>
                        <input type="text" name="municipio" id="campo_municipio" value="{{ $clinica->municipio }}" class="modern-input"
                            oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                    </div>
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Estado / Provincia</label>
                    <input type="text" name="estado" id="campo_estado" value="{{ $clinica->estado }}" class="modern-input"
                        oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                </div>

                {{-- Mapa de Google Maps --}}
                <div style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 5px;">
                    <label style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span>Ubicación en Mapa</span>
                        <button type="button" onclick="buscarUbicacionEnMapa()" style="background: #eef6fb; color: #0077b6; padding: 5px 14px; font-size: 0.8rem; border-radius: 6px; border: 1px solid #cce5f5; cursor: pointer;">
                            <i class="fa-solid fa-map-location-dot"></i> Buscar por dirección
                        </button>
                    </label>
                    <div id="mapa-clinica" style="width: 100%; height: 280px; background: #f0f0f0; border-radius: 8px;"></div>
                    <p style="color: #999; font-size: 0.78rem; margin-top: 6px;">Arrastra el pin para mayor precisión.</p>
                    <input type="hidden" name="latitud"  id="latitud"  value="{{ $clinica->latitud }}">
                    <input type="hidden" name="longitud" id="longitud" value="{{ $clinica->longitud }}">
                </div>

                <div style="margin-top: 15px;">
                    <label>Porcentaje de Anticipo</label>
                    <input type="number" step="0.01" min="0" max="100" name="config_anticipo_pct" value="{{ $clinica->config_anticipo_pct }}" class="modern-input">
                </div>

                <button type="submit" class="ghost-btn"
                    style="margin-top: 20px; background: #00b4d8; color: white; width: 100%;">
                    Guardar Cambios Clínica
                </button>
            </form>
        </div>

        {{-- ═══════════════ PERFIL DEL DOCTOR ═══════════════ --}}
        @if($doctorUser)
            <div class="config-card"
                style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <h3 style="color: #0077b6; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-top: 0;">
                    <i class="fa-solid fa-user-doctor"></i> Perfil del Doctor
                </h3>

                {{-- Foto de perfil del doctor --}}
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="position: relative; display: inline-block;">
                        @if($doctorPerfil && $doctorPerfil->foto_perfil)
                            <img src="{{ route('storage.file', ['path' => $doctorPerfil->foto_perfil]) }}"
                                 alt="Foto del Doctor"
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 15px rgba(0,119,182,0.25); cursor: pointer;"
                                 onclick="document.getElementById('input-foto-doctor').click()">
                        @else
                            <div style="width: 100px; height: 100px; border-radius: 50%; background: #e0fbfc; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,119,182,0.25); cursor: pointer; margin: 0 auto;"
                                 onclick="document.getElementById('input-foto-doctor').click()">
                                <i class="fa-solid fa-camera" style="font-size: 2rem; color: #0077b6;"></i>
                            </div>
                        @endif
                    </div>
                    <form action="{{ route('configuracion.fotoDoctor') }}" method="POST" enctype="multipart/form-data" id="form-foto-doctor">
                        @csrf
                        <input type="file" id="input-foto-doctor" name="foto_perfil" accept="image/jpeg,image/png,image/webp" style="display: none;"
                               onchange="document.getElementById('form-foto-doctor').submit();">
                    </form>
                    <p style="color: #888; font-size: 0.75rem; margin-top: 8px;">Haz clic para subir o cambiar la foto</p>
                </div>

                <form action="{{ route('configuracion.updateUsuario') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id_usuario" value="{{ $doctorUser->id_usuario }}">

                    <div style="margin-bottom: 15px;">
                        <label>Nombre Completo</label>
                        <input type="text" name="nombre_completo" value="{{ $doctorUser->nombre_completo }}"
                            class="modern-input"
                            oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')" required>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label>Correo Electrónico (Acceso)</label>
                        <input type="email" name="email" value="{{ $doctorUser->email }}" class="modern-input" required>
                    </div>

                    {{-- CAMPO AGREGADO --}}
                    <div style="margin-top: 15px;">
                        <label>Sobre mí</label>
                        <textarea name="sobre_mi" id="sobre_mi" class="modern-input" rows="4" 
                            placeholder="Cuéntale a tus pacientes sobre tu experiencia..."
                            style="resize: none; font-family: inherit;">{{ old('sobre_mi', $doctorUser->sobre_mi) }}</textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label>Cédula Profesional</label>
                            <input type="text" name="cedula_profesional" value="{{ $doctorPerfil->cedula_profesional ?? '' }}"
                                class="modern-input" placeholder="Ej: 12345678">
                        </div>
                        <div>
                            <label>Cambiar Contraseña</label>
                            <input type="password" name="password" class="modern-input" placeholder="Opcional" autocomplete="new-password">
                        </div>
                    </div>

                    <div style="margin-top: 15px;">
                        <label>Confirmar Nueva Contraseña</label>
                        <input type="password" name="password_confirmation" class="modern-input"
                            placeholder="Solo si cambias contraseña" autocomplete="new-password">
                        </div>

                    <button type="submit" class="ghost-btn"
                        style="margin-top: 20px; background: #0077b6; color: white; width: 100%;">
                        Actualizar Perfil Doctor
                    </button>
                </form>
            </div>
        @endif

        {{-- ═══════════════ HORARIOS DE ATENCIÓN ═══════════════ --}}
        <div class="config-card"
            style="grid-column: 1 / -1; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h3 style="color: #0077b6; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-top: 0;">
                <i class="fa-solid fa-clock"></i> Horarios de Atención
            </h3>
            <p style="color: #888; font-size: 0.85rem; margin-top: 0;">
                Define los días y horas en que la clínica está abierta. Los días desactivados aparecerán como cerrados en el calendario.
            </p>

            <form action="{{ route('configuracion.updateHorarios') }}" method="POST">
                @csrf
                <div class="horarios-table-wrapper">
                    <table class="horarios-table">
                        <thead>
                            <tr>
                                <th style="width: 140px;">Día</th>
                                <th style="width: 80px; text-align: center;">Activo</th>
                                <th>Hora Inicio</th>
                                <th>Hora Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($horarios as $horario)
                                @php
                                    $diasNombres = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 0 => 'Domingo'];
                                    $diasIcons = [1 => '🟢', 2 => '🟢', 3 => '🟢', 4 => '🟢', 5 => '🟢', 6 => '🟡', 0 => '🔴'];
                                    $dia = $horario->dia_semana;
                                @endphp
                                <tr class="horario-row {{ $horario->activo ? '' : 'row-inactive' }}" id="row-dia-{{ $dia }}">
                                    <td data-label="Día">
                                        <span style="font-weight: 600; color: #333;">
                                            {{ $diasIcons[$dia] }} {{ $diasNombres[$dia] }}
                                        </span>
                                    </td>
                                    <td data-label="Activo" style="text-align: center;">
                                        <label class="switch-toggle">
                                            <input type="checkbox"
                                                   name="dias[{{ $dia }}][activo]"
                                                   value="1"
                                                   {{ $horario->activo ? 'checked' : '' }}
                                                   onchange="toggleDia({{ $dia }}, this.checked)">
                                            <span class="switch-slider"></span>
                                        </label>
                                    </td>
                                    <td data-label="Hora Inicio">
                                        <input type="time"
                                               name="dias[{{ $dia }}][hora_inicio]"
                                               value="{{ $horario->hora_inicio ? \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') : '' }}"
                                               class="modern-input horario-input"
                                               id="inicio-{{ $dia }}"
                                               {{ $horario->activo ? '' : 'disabled' }}>
                                    </td>
                                    <td data-label="Hora Fin">
                                        <input type="time"
                                               name="dias[{{ $dia }}][hora_fin]"
                                               value="{{ $horario->hora_fin ? \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') : '' }}"
                                               class="modern-input horario-input"
                                               id="fin-{{ $dia }}"
                                               {{ $horario->activo ? '' : 'disabled' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" onclick="aplicarHorarioSemana()" class="ghost-btn"
                        style="background: #6c757d;">
                        <i class="fa-solid fa-copy"></i> Aplicar Lun-Vie igual
                    </button>
                    <button type="submit" class="ghost-btn" style="background: #00b4d8;">
                        <i class="fa-solid fa-save"></i> Guardar Horarios
                    </button>
                </div>
            </form>
        </div>

        {{-- ═══════════════ EQUIPO DE RECEPCIÓN ═══════════════ --}}
        @if(Auth::user()->rol == 'doctor' || Auth::user()->rol == 'admin')
            <div class="config-card"
                style="grid-column: 1 / -1; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 20px;">
                    <h3 style="color: #0077b6; margin: 0;">
                        <i class="fa-solid fa-users"></i> Equipo de Recepción
                    </h3>
                    <button onclick="document.getElementById('modal-recep').style.display='flex'" class="ghost-btn"
                        style="padding: 5px 15px; font-size: 0.8rem;">
                        + Agregar Recepcionista
                    </button>
                </div>

                @if($recepcionistas->count() > 0)
                    <div style="display: grid; gap: 15px;">
                        @foreach($recepcionistas as $recep)
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; background: #f9f9f9; padding: 15px; border-radius: 8px;">
                                <div>
                                    <strong style="display: block; color: #333;">{{ $recep->nombre_completo }}</strong>
                                    <span style="color: #666; font-size: 0.9rem;">{{ $recep->email }}</span>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button
                                        onclick="editarRecep('{{ $recep->id_usuario }}', '{{ $recep->nombre_completo }}', '{{ $recep->email }}')"
                                        class="ghost-btn" style="background: #e0e0e0; color: #333; padding: 5px 10px;">
                                        <i class="fa-solid fa-pen"></i> Editar
                                    </button>
                                    
                                    <form action="{{ route('configuracion.destroyRecepcionista', $recep->id_usuario) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas dar de baja a esta recepcionista?');" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ghost-btn" style="background: #ff4d4d; color: white; padding: 5px 10px;">
                                            <i class="fa-solid fa-user-minus"></i> Baja
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="text-align: center; color: #999;">No hay recepcionistas registradas.</p>
                @endif
            </div>
        @endif

    </div>

    {{-- ═══════════════ MODAL: Nueva Recepcionista ═══════════════ --}}
    <div id="modal-recep"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 15px; width: 400px;">
            <h3 style="margin-top: 0; color: #00b4d8;">Nueva Recepcionista</h3>
            <form action="{{ route('configuracion.storeRecepcionista') }}" method="POST">
                @csrf
                <label style="display:block; margin-bottom:5px;">Nombre</label>
                <input type="text" name="nombre_completo" class="modern-input" required
                    style="width:100%; margin-bottom:10px;"
                    oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">

                <label style="display:block; margin-bottom:5px;">Email</label>
                <input type="email" name="email" class="modern-input" required style="width:100%; margin-bottom:10px;">

                <label style="display:block; margin-bottom:5px;">Contraseña</label>
                <input type="password" name="password" class="modern-input" required
                    style="width:100%; margin-bottom:10px;" autocomplete="new-password">

                <label style="display:block; margin-bottom:5px;">Confirmar Contraseña</label>
                <input type="password" name="password_confirmation" class="modern-input" required
                    style="width:100%; margin-bottom:20px;" autocomplete="new-password">

                <div style="text-align: right;">
                    <button type="button" onclick="document.getElementById('modal-recep').style.display='none'"
                        style="padding: 10px 20px; border: none; background: #eee; cursor: pointer; border-radius: 5px;">Cancelar</button>
                    <button type="submit"
                        style="padding: 10px 20px; border: none; background: #00b4d8; color: white; cursor: pointer; border-radius: 5px;">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════ MODAL: Editar Recepcionista ═══════════════ --}}
    <div id="modal-edit-recep"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 15px; width: 400px;">
            <h3 style="margin-top: 0; color: #0077b6;">Editar Recepcionista</h3>
            <form action="{{ route('configuracion.updateUsuario') }}" method="POST">
                @csrf
                <input type="hidden" name="id_usuario" id="edit_id">

                <label style="display:block; margin-bottom:5px;">Nombre</label>
                <input type="text" name="nombre_completo" id="edit_nombre" class="modern-input" required
                    style="width:100%; margin-bottom:10px;"
                    oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">

                <label style="display:block; margin-bottom:5px;">Email</label>
                <input type="email" name="email" id="edit_email" class="modern-input" required
                    style="width:100%; margin-bottom:10px;">

                <label style="display:block; margin-bottom:5px;">Nueva Contraseña (Opcional)</label>
                <input type="password" name="password" class="modern-input" style="width:100%; margin-bottom:10px;"
                    placeholder="Dejar vacío para no cambiar">

                <label style="display:block; margin-bottom:5px;">Confirmar Nueva Contraseña</label>
                <input type="password" name="password_confirmation" class="modern-input"
                    style="width:100%; margin-bottom:20px;" placeholder="Solo si cambias contraseña">

                <div style="text-align: right;">
                    <button type="button" onclick="document.getElementById('modal-edit-recep').style.display='none'"
                        style="padding: 10px 20px; border: none; background: #eee; cursor: pointer; border-radius: 5px;">Cancelar</button>
                    <button type="submit"
                        style="padding: 10px 20px; border: none; background: #0077b6; color: white; cursor: pointer; border-radius: 5px;">Actualizar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ── Recepcionistas ──
        function editarRecep(id, nombre, email) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_email').value = email;
            document.getElementById('modal-edit-recep').style.display = 'flex';
        }

        // Cerrar modales al hacer clic fuera
        window.onclick = function (event) {
            if (event.target == document.getElementById('modal-recep')) {
                document.getElementById('modal-recep').style.display = "none";
            }
            if (event.target == document.getElementById('modal-edit-recep')) {
                document.getElementById('modal-edit-recep').style.display = "none";
            }
        }

        // ── Horarios ──
        function toggleDia(dia, activo) {
            const row = document.getElementById('row-dia-' + dia);
            const inicio = document.getElementById('inicio-' + dia);
            const fin = document.getElementById('fin-' + dia);

            if (activo) {
                row.classList.remove('row-inactive');
                inicio.disabled = false;
                fin.disabled = false;
            } else {
                row.classList.add('row-inactive');
                inicio.disabled = true;
                fin.disabled = true;
                inicio.value = '';
                fin.value = '';
            }
        }

        // Copiar horario del lunes al resto de días laborales (Mar-Vie)
        function aplicarHorarioSemana() {
            const lunesInicio = document.getElementById('inicio-1').value;
            const lunesFin = document.getElementById('fin-1').value;

            if (!lunesInicio || !lunesFin) {
                alert('Primero configura el horario del Lunes.');
                return;
            }

            for (let dia = 2; dia <= 5; dia++) {
                const row = document.getElementById('row-dia-' + dia);
                const inicio = document.getElementById('inicio-' + dia);
                const fin = document.getElementById('fin-' + dia);
                const checkbox = row.querySelector('input[type="checkbox"]');

                checkbox.checked = true;
                row.classList.remove('row-inactive');
                inicio.disabled = false;
                fin.disabled = false;
                inicio.value = lunesInicio;
                fin.value = lunesFin;
            }
        }

        // ── Google Maps ──
        let map, marker, geocoder;

        function initMap() {
            const latVal = document.getElementById('latitud')  ? document.getElementById('latitud').value  : '';
            const lngVal = document.getElementById('longitud') ? document.getElementById('longitud').value : '';

            const center    = (latVal && lngVal)
                ? { lat: parseFloat(latVal), lng: parseFloat(lngVal) }
                : { lat: 19.4326, lng: -99.1332 }; // CDMX por defecto
            const zoomLevel = (latVal && lngVal) ? 16 : 12;

            map = new google.maps.Map(document.getElementById('mapa-clinica'), {
                zoom: zoomLevel,
                center: center,
                mapTypeControl: false,
            });

            geocoder = new google.maps.Geocoder();

            marker = new google.maps.Marker({
                map: map,
                position: center,
                draggable: true,
                animation: google.maps.Animation.DROP,
            });

            // Al arrastrar el pin se actualizan los inputs ocultos
            marker.addListener('dragend', function (event) {
                document.getElementById('latitud').value  = event.latLng.lat();
                document.getElementById('longitud').value = event.latLng.lng();
            });
        }

        function buscarUbicacionEnMapa() {
            const calle    = (document.getElementById('campo_calle')     || {}).value || '';
            const ciudad   = (document.getElementById('campo_ciudad')    || {}).value || '';
            const municipio= (document.getElementById('campo_municipio') || {}).value || '';
            const estado   = (document.getElementById('campo_estado')    || {}).value || '';
            const cp       = document.querySelector('input[name="codigo_postal"]')     ? document.querySelector('input[name="codigo_postal"]').value : '';

            const partes = [calle, ciudad, municipio, estado, 'México', cp].filter(Boolean);
            if (!partes.length) { alert('Ingresa al menos la calle y ciudad.'); return; }

            geocoder.geocode({ address: partes.join(', ') }, function (results, status) {
                if (status === 'OK') {
                    const loc = results[0].geometry.location;
                    map.setCenter(loc); map.setZoom(16); marker.setPosition(loc);
                    document.getElementById('latitud').value  = loc.lat();
                    document.getElementById('longitud').value = loc.lng();
                } else {
                    alert('No pudimos ubicar la dirección. Arrastra el pin manualmente.');
                }
            });
        }
    </script>

    {{-- Google Maps JS API (se carga último para no bloquear el render) --}}
    @if(config('services.google.maps_key'))
        <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_key') }}&callback=initMap">
        </script>
    @endif

    <style>
        .modern-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
        }

        label {
            font-weight: 500;
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }

        /* ── Tabla de Horarios ── */
        .horarios-table-wrapper {
            overflow-x: auto;
        }

        .horarios-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 6px;
        }

        .horarios-table thead th {
            padding: 10px 12px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #888;
            font-weight: 600;
            border-bottom: none;
        }

        .horarios-table tbody td {
            padding: 10px 12px;
            border-bottom: none;
        }

        .horario-row {
            background: #f8fafb;
            border-radius: 8px;
            transition: background 0.2s, opacity 0.3s;
        }

        .horario-row td:first-child { border-radius: 8px 0 0 8px; }
        .horario-row td:last-child  { border-radius: 0 8px 8px 0; }

        .horario-row:hover { background: #eef6fb; }

        .row-inactive {
            opacity: 0.45;
            background: #f5f5f5;
        }

        .horario-input {
            max-width: 150px;
            padding: 8px 10px;
            font-size: 0.9rem;
        }

        /* ── Toggle Switch ── */
        .switch-toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .switch-toggle input { opacity: 0; width: 0; height: 0; }

        .switch-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 24px;
        }

        .switch-slider:before {
            content: "";
            position: absolute;
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }

        .switch-toggle input:checked + .switch-slider {
            background-color: #00b4d8;
        }

        .switch-toggle input:checked + .switch-slider:before {
            transform: translateX(20px);
        }

        /* ── Responsividad (Mobile y Split PC) ── */
        @media (max-width: 1024px) {
            .horarios-table thead {
                display: none;
            }
            .horarios-table tbody, .horarios-table tr, .horarios-table td {
                display: block;
                width: 100%;
            }
            .horario-row {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 10px;
                background: white;
            }
            .horario-row td {
                text-align: right !important;
                padding: 10px 5px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                border-bottom: 1px solid #f0f0f0;
            }
            .horario-row td:last-child {
                border-bottom: none;
            }
            .horario-row td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #888;
                font-size: 0.85rem;
                text-transform: uppercase;
                text-align: left;
            }
            .horario-input {
                max-width: 60%;
            }
            /* Reset border radius for mobile block */
            .horario-row td:first-child { border-radius: 0; }
            .horario-row td:last-child  { border-radius: 0; }
        }
    </style>
@endsection
