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

    <div style="display: flex; flex-direction: column; gap: 40px; max-width: 1500px; margin: 0 auto;">

        {{-- Contenedor superior para lado a lado --}}
        <div style="display: flex; flex-wrap: wrap; gap: 40px;">

            {{-- ═══════════════ DATOS DE LA CLÍNICA ═══════════════ --}}
            <div class="premium-card" style="flex: 1 1 500px;">
            <div class="card-header clinica-header">
                <h3><i class="fa-solid fa-hospital"></i> Datos de la Clínica</h3>
                <p>Información pública y ubicación de tu sucursal</p>
            </div>
            <div class="card-body">
                <form action="{{ route('configuracion.updateClinica') }}" method="POST">
                    @csrf
                    <div style="margin-bottom: 20px;">
                        <label>Nombre Comercial</label>
                        <input type="text" name="nombre_comercial" value="{{ $clinica->nombre_comercial }}" class="modern-input"
                            required oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
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

                    <div style="margin-bottom: 20px;">
                        <label>Calle y Número</label>
                        <input type="text" name="calle" id="campo_calle" value="{{ $clinica->calle }}" class="modern-input"
                            placeholder="Ej. Av. Reforma 123">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
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

                    <div style="margin-bottom: 20px;">
                        <label>Estado / Provincia</label>
                        <input type="text" name="estado" id="campo_estado" value="{{ $clinica->estado }}" class="modern-input"
                            oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                    </div>

                    {{-- Mapa de Google Maps --}}
                    <div style="border-top: 2px dashed #f1f5f9; padding-top: 20px; margin-top: 10px;">
                        <label style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span>Ubicación en Mapa</span>
                            <button type="button" onclick="buscarUbicacionEnMapa()" style="background: #eef6fb; color: #0284c7; padding: 6px 16px; font-size: 0.85rem; border-radius: 8px; border: 1px solid #bae6fd; cursor: pointer; font-weight: 700;">
                                <i class="fa-solid fa-map-location-dot"></i> Buscar por dirección
                            </button>
                        </label>
                        <div id="mapa-clinica" style="width: 100%; height: 300px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);"></div>
                        <p style="color: #64748b; font-size: 0.8rem; margin-top: 8px; font-style: italic;"><i class="fa-solid fa-circle-info"></i> Arrastra el pin rojo para ajustar con mayor precisión.</p>
                        <input type="hidden" name="latitud"  id="latitud"  value="{{ $clinica->latitud }}">
                        <input type="hidden" name="longitud" id="longitud" value="{{ $clinica->longitud }}">
                    </div>

                    <div style="margin-top: 25px; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <label>Porcentaje de Anticipo por Defecto</label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="number" step="0.01" min="0" max="100" name="config_anticipo_pct" value="{{ $clinica->config_anticipo_pct }}" class="modern-input" style="margin-bottom: 0;">
                            <span style="font-weight: 800; color: #475569; font-size: 1.2rem;">%</span>
                        </div>
                    </div>

                    <button type="submit" class="ghost-btn-premium" style="width: 100%; margin-top: 30px;">
                        Guardar Cambios Clínica
                    </button>
                </form>
            </div>
        </div>

        {{-- ═══════════════ PERFIL DEL DOCTOR ═══════════════ --}}
        @if($doctorUser)
            <div class="premium-card" style="flex: 1 1 500px;">
                <div class="card-header doctor-header">
                    <h3><i class="fa-solid fa-user-doctor"></i> Perfil del Doctor</h3>
                    <p>Detalles personales y credenciales</p>
                </div>
                <div class="card-body">

                    {{-- Foto de perfil del doctor --}}
                    <div style="text-align: center; margin-bottom: 35px;">
                        <div style="position: relative; display: inline-block;">
                            @if($doctorPerfil && $doctorPerfil->foto_perfil)
                                <img src="{{ route('storage.file', ['path' => $doctorPerfil->foto_perfil]) }}"
                                     alt="Foto del Doctor"
                                     style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; box-shadow: 0 8px 25px rgba(220, 38, 38, 0.25); cursor: pointer; border: 4px solid white; transition: transform 0.3s ease;"
                                     onclick="document.getElementById('input-foto-doctor').click()"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform='scale(1)'">
                            @else
                                <div style="width: 130px; height: 130px; border-radius: 50%; background: #fef2f2; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(220, 38, 38, 0.25); cursor: pointer; margin: 0 auto; border: 4px solid white; transition: transform 0.3s ease;"
                                     onclick="document.getElementById('input-foto-doctor').click()"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform='scale(1)'">
                                    <i class="fa-solid fa-camera" style="font-size: 3rem; color: #dc2626;"></i>
                                </div>
                            @endif
                            <div style="position: absolute; bottom: 0; right: 0; background: white; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15); pointer-events: none;">
                                <i class="fa-solid fa-pen" style="color: #64748b; font-size: 0.9rem;"></i>
                            </div>
                        </div>
                        <form action="{{ route('configuracion.fotoDoctor') }}" method="POST" enctype="multipart/form-data" id="form-foto-doctor">
                            @csrf
                            <input type="file" id="input-foto-doctor" name="foto_perfil" accept="image/jpeg,image/png,image/webp" style="display: none;"
                                   onchange="document.getElementById('form-foto-doctor').submit();">
                        </form>
                        <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 15px; font-weight: 500;">Haz clic en la imagen para cambiar tu foto</p>
                    </div>

                    <form action="{{ route('configuracion.updateUsuario') }}" method="POST">
                        @csrf
                        <input type="hidden" name="id_usuario" value="{{ $doctorUser->id_usuario }}">

                        <div style="margin-bottom: 20px;">
                            <label>Nombre Completo</label>
                            <input type="text" name="nombre_completo" value="{{ $doctorUser->nombre_completo }}"
                                class="modern-input"
                                oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')" required>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label>Correo Electrónico (Acceso)</label>
                            <input type="email" name="email" value="{{ $doctorUser->email }}" class="modern-input" required>
                        </div>

                        {{-- CAMPO AGREGADO --}}
                        <div style="margin-bottom: 20px;">
                            <label>Teléfono de Contacto Móvil</label>
                            <input type="text" name="telefono" value="{{ $doctorUser->telefono }}" class="modern-input" 
                                placeholder="Ej: +52 1 234 567 8900" maxlength="20">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label>Sobre mí</label>
                            <textarea name="sobre_mi" id="sobre_mi" class="modern-input" rows="5" 
                                placeholder="Cuéntale a tus pacientes de tu experiencia, especialidad..."
                                style="resize: vertical; font-family: inherit;">{{ old('sobre_mi', $doctorUser->sobre_mi) }}</textarea>
                        </div>

                        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 30px;">
                            <div style="margin-bottom: 20px;">
                                <label><i class="fa-solid fa-id-card-clip"></i> Cédula Profesional</label>
                                <input type="text" name="cedula_profesional" value="{{ $doctorPerfil->cedula_profesional ?? '' }}"
                                    class="modern-input" placeholder="Ej: 12345678" style="background: white;">
                            </div>
                            
                            <hr style="border: 0; border-top: 1px dashed #cbd5e1; margin: 20px 0;">
                            
                            <h4 style="margin: 0 0 15px 0; color: #334155; font-size: 0.95rem;">Seguridad y Acceso</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <label style="font-size: 0.8rem;">Nueva Contraseña</label>
                                    <input type="password" name="password" class="modern-input" placeholder="(Opcional)" autocomplete="new-password" style="background: white; padding: 10px;">
                                </div>
                                <div>
                                    <label style="font-size: 0.8rem;">Confirmar Contraseña</label>
                                    <input type="password" name="password_confirmation" class="modern-input" placeholder="Repite contraseña" autocomplete="new-password" style="background: white; padding: 10px;">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="ghost-btn-premium ghost-btn-premium-red" style="width: 100%;">
                            Actualizar Perfil Doctor
                        </button>
                    </form>
                </div>
            </div>
        @endif

        </div> {{-- Fin del contenedor superior lado a lado --}}

        {{-- ═══════════════ HORARIOS DE ATENCIÓN ═══════════════ --}}
        <div class="premium-card" style="width: 100%;">
            <div class="card-header clinica-header" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-bottom: 1px solid #f1f5f9; padding: 24px 30px;">
                <h3 style="color: #166534; margin: 0; font-size: 1.3rem; font-weight: 800;">
                    <i class="fa-solid fa-clock"></i> Horarios de Atención
                </h3>
                <p style="color: #15803d; margin: 6px 0 0 0; font-size: 0.85rem;">
                    Define los días y horas en que la clínica está abierta. Los días desactivados aparecerán como cerrados en el calendario.
                </p>
            </div>
            <div class="card-body">
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
        </div>

        {{-- ═══════════════ EQUIPO DE RECEPCIÓN ═══════════════ --}}
        @if(Auth::user()->rol == 'doctor' || Auth::user()->rol == 'admin')
            <div class="premium-card" style="width: 100%;">
                <div class="card-header clinica-header" style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border-bottom: 1px solid #f1f5f9; padding: 24px 30px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="color: #5b21b6; margin: 0; font-size: 1.3rem; font-weight: 800;">
                            <i class="fa-solid fa-users"></i> Equipo de Recepción
                        </h3>
                        <p style="color: #6d28d9; margin: 6px 0 0 0; font-size: 0.85rem;">
                            Gestiona los accesos administrativos
                        </p>
                    </div>
                    <button onclick="document.getElementById('modal-recep').style.display='flex'" class="ghost-btn-premium" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); box-shadow: 0 4px 15px rgba(109, 40, 217, 0.3); padding: 10px 20px; font-size: 0.9rem;">
                        + Agregar Recepcionista
                    </button>
                </div>
                <div class="card-body">

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
                                        class="ghost-btn" style="background: #e2e8f0; color: #334155; padding: 6px 12px; border:none; border-radius:6px; cursor:pointer;">
                                        <i class="fa-solid fa-pen"></i> Editar
                                    </button>
                                    
                                    <form action="{{ route('configuracion.destroyRecepcionista', $recep->id_usuario) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas dar de baja a esta recepcionista?');" style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ghost-btn" style="background: #ef4444; color: white; padding: 6px 12px; border:none; border-radius:6px; cursor:pointer;">
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
            </div>
        @endif

        {{-- ═══════════════ ACCESIBILIDAD Y PERSONALIZACIÓN ═══════════════ --}}
        <div class="premium-card" style="width: 100%;">
            <div class="card-header clinica-header" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-bottom: 1px solid #f1f5f9; padding: 24px 30px;">
                <h3 style="color: #92400e; margin: 0; font-size: 1.3rem; font-weight: 800;">
                    <i class="fa-solid fa-palette"></i> Accesibilidad y Personalización Visual
                </h3>
                <p style="color: #b45309; margin: 6px 0 0 0; font-size: 0.85rem;">
                    Configura el modo de visualización de tu sistema.
                </p>
            </div>
            <div class="card-body">
                <form action="{{ route('configuracion.updateApariencia') }}" method="POST">
                    @csrf
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        {{-- Modo Claro --}}
                        <label style="cursor:pointer; display:flex; flex-direction:column; align-items:center; padding:20px; border: 2px solid {{ ($clinica->tema_visual ?? 'claro') == 'claro' ? 'var(--primary-color)' : '#e2e8f0' }}; border-radius: 12px; background: #f8fafc; transition: all 0.3s;" onmouseover="this.style.borderColor='var(--primary-color)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#e2e8f0'">
                            <input type="radio" name="tema_visual" value="claro" {{ ($clinica->tema_visual ?? 'claro') == 'claro' ? 'checked' : '' }} style="margin-bottom: 15px;">
                            <i class="fa-solid fa-sun" style="font-size: 2rem; color: #f59e0b; margin-bottom: 10px;"></i>
                            <span style="font-weight: 600; color: #334155;">Modo Claro</span>
                            <span style="font-size: 0.8rem; color: #64748b; text-align:center;">Apariencia Original</span>
                        </label>

                        {{-- Modo Noche --}}
                        <label style="cursor:pointer; display:flex; flex-direction:column; align-items:center; padding:20px; border: 2px solid {{ ($clinica->tema_visual ?? 'claro') == 'oscuro' ? 'var(--primary-color)' : '#e2e8f0' }}; border-radius: 12px; background: #1e293b; transition: all 0.3s;" onmouseover="this.style.borderColor='var(--primary-color)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#e2e8f0'">
                            <input type="radio" name="tema_visual" value="oscuro" {{ ($clinica->tema_visual ?? 'claro') == 'oscuro' ? 'checked' : '' }} style="margin-bottom: 15px;">
                            <i class="fa-solid fa-moon" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 10px;"></i>
                            <span style="font-weight: 600; color: #f8fafc;">Modo Noche</span>
                            <span style="font-size: 0.8rem; color: #94a3b8; text-align:center;">Reduce fatiga visual</span>
                        </label>

                        {{-- Modo Invertido --}}
                        <label style="cursor:pointer; display:flex; flex-direction:column; align-items:center; padding:20px; border: 2px solid {{ ($clinica->tema_visual ?? 'claro') == 'invertido' ? 'var(--primary-color)' : '#e2e8f0' }}; border-radius: 12px; background: #000; transition: all 0.3s;" onmouseover="this.style.borderColor='var(--primary-color)'" onmouseout="if(!this.querySelector('input').checked) this.style.borderColor='#e2e8f0'">
                            <input type="radio" name="tema_visual" value="invertido" {{ ($clinica->tema_visual ?? 'claro') == 'invertido' ? 'checked' : '' }} style="margin-bottom: 15px; accent-color: yellow;">
                            <i class="fa-solid fa-circle-half-stroke" style="font-size: 2rem; color: #eab308; margin-bottom: 10px;"></i>
                            <span style="font-weight: 600; color: #fff;">Alto Contraste</span>
                            <span style="font-size: 0.8rem; color: #ccc; text-align:center;">Colores invertidos y brillantes</span>
                        </label>
                    </div>

                    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display:flex; align-items:center; gap: 20px;">
                        <div>
                            <label style="font-weight:600; color:#334155; display:block; margin-bottom:5px;">Color Principal de la Interfaz</label>
                            <p style="font-size:0.85rem; color:#64748b; margin:0;">Elige un color que represente a tu clínica.</p>
                        </div>
                        <input type="color" name="color_primario" value="{{ $clinica->color_primario ?? '#00b4d8' }}" style="width: 60px; height: 60px; padding: 0; border: none; border-radius: 8px; cursor: pointer; margin-left: auto;">
                    </div>

                    <button type="submit" class="ghost-btn-premium" style="width: 100%; margin-top: 30px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        Guardar Preferencias Visuales
                    </button>
                </form>
            </div>
        </div>
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
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            color: #1e293b;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
            box-sizing: border-box;
        }
        .modern-input:focus {
            outline: none;
            border-color: #38bdf8;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
        }
        label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #475569;
            margin-bottom: 6px;
            display: block;
        }
        .premium-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(148, 163, 184, 0.15);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .premium-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 40px rgba(148, 163, 184, 0.2);
        }
        .card-header {
            padding: 24px 30px;
            border-bottom: 1px solid #f1f5f9;
        }
        .card-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
        }
        .card-header p {
            margin: 6px 0 0 0;
            font-size: 0.85rem;
        }
        .clinica-header {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        .clinica-header h3 { color: #0369a1; }
        .clinica-header p { color: #0284c7; }
        .doctor-header {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        }
        .doctor-header h3 { color: #991b1b; }
        .doctor-header p { color: #b91c1c; }
        
        .card-body {
            padding: 30px;
        }
        
        .ghost-btn-premium {
            background: linear-gradient(135deg, var(--primary-color) 0%, #0284c7 100%);
            color: white;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(2, 132, 199, 0.3);
            text-align: center;
            display: inline-block;
        }
        .ghost-btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.4);
        }
        
        .ghost-btn-premium-red {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            box-shadow: 0 4px 15px rgba(185, 28, 28, 0.3);
        }
        .ghost-btn-premium-red:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(185, 28, 28, 0.4);
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
