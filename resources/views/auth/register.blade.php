<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Clínica - DentalConnect</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css?family=Montserrat:400,600,800');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 20px;
            /* El background-gradient se movió al canvas1 */
        }

        #canvas1 {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: linear-gradient(135deg, #f6f5f7 0%, #eef7fb 100%);
            pointer-events: none;
        }

        .page-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 800;
            color: #0077b6;
        }

        .page-header p {
            font-size: 13px;
            color: #888;
            margin-top: 4px;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 14px 38px rgba(0, 119, 182, 0.13), 0 4px 12px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 700px;
            padding: 36px 40px;
        }

        .section-title {
            font-size: 10px;
            font-weight: 700;
            color: #00b4d8;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin: 20px 0 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e9f6fb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-grid.single {
            grid-template-columns: 1fr;
        }

        .form-grid.triple {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 600;
            color: #555;
            margin-bottom: 4px;
        }

        .form-group input {
            background-color: #f4f8fa;
            border: 1.5px solid #dde8ef;
            border-radius: 8px;
            padding: 10px 13px;
            font-size: 13px;
            font-family: 'Montserrat', sans-serif;
            transition: border-color 0.2s;
            width: 100%;
        }

        .form-group input:focus {
            outline: none;
            border-color: #00b4d8;
            background-color: #eef9fd;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 10px;
            background: none;
            border: none;
            padding: 0;
            margin: 0;
            cursor: pointer;
            color: #888;
            font-size: 14px;
            line-height: 1;
        }

        .toggle-password:hover {
            color: #00b4d8;
        }

        .form-group input.is-invalid {
            border-color: #e63946;
            background-color: #fff5f5;
        }

        .error-text {
            font-size: 10px;
            color: #e63946;
            margin-top: 3px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 16px;
        }

        .alert-danger {
            background: #fff5f5;
            border-left: 4px solid #e63946;
            color: #c1121f;
        }

        .alert-danger ul {
            margin: 6px 0 0 16px;
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(90deg, #00b4d8, #0077b6);
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            margin-top: 24px;
            transition: transform 0.1s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(0, 119, 182, 0.25);
        }

        .btn-submit:hover {
            box-shadow: 0 6px 18px rgba(0, 119, 182, 0.35);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .back-link {
            text-align: center;
            margin-top: 18px;
            font-size: 12px;
            color: #888;
        }

        .back-link a {
            color: #00b4d8;
            font-weight: 600;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {

            .form-grid,
            .form-grid.triple {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 24px 18px;
            }
        }

        #page-transition {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: linear-gradient(to right, #90e0ef, #0077b6);
            z-index: 9999;
            transform: translateX(0);
            /* Empieza cubriendo la pantalla */
            transition: transform 0.6s ease-in-out;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <!-- Cortina de Transición a Pantalla Completa -->
    <div id="page-transition"></div>
    <!-- Animación de fondo 3D - Dientes Flotantes -->
    <canvas id="canvas1"></canvas>

    <div class="page-header">
        <h1><i class="fas fa-tooth" style="color:#00b4d8;"></i> DentalConnect</h1>
        <p>Registra tu clínica dental y comienza a administrarla</p>
    </div>

    <div class="card">

        @if($errors->any())
            <div class="alert alert-danger">
                <strong>Por favor corrige los siguientes errores:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST" novalidate>
            @csrf

            {{-- ===================== DATOS DEL DOCTOR ===================== --}}
            <div class="section-title">Datos del Doctor</div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre">Nombre(s) <span style="color:red">*</span></label>
                    <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}"
                        placeholder="Ej. Marco Antonio" class="{{ $errors->has('nombre') ? 'is-invalid' : '' }}"
                        oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')" required>
                    @error('nombre')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="apellido_paterno">Apellido Paterno <span style="color:red">*</span></label>
                    <input type="text" id="apellido_paterno" name="apellido_paterno"
                        value="{{ old('apellido_paterno') }}" placeholder="Ej. Ramírez"
                        class="{{ $errors->has('apellido_paterno') ? 'is-invalid' : '' }}"
                        oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')" required>
                    @error('apellido_paterno')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="apellido_materno">Apellido Materno</label>
                    <input type="text" id="apellido_materno" name="apellido_materno"
                        value="{{ old('apellido_materno') }}" placeholder="Ej. González"
                        class="{{ $errors->has('apellido_materno') ? 'is-invalid' : '' }}"
                        oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                    @error('apellido_materno')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico <span style="color:red">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        placeholder="doctor@clinica.com" class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                        required>
                    @error('email')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="password">Contraseña <span style="color:red">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Contraseña segura"
                            minlength="8" class="{{ $errors->has('password') ? 'is-invalid' : '' }}" required>
                        <button type="button" class="toggle-password" data-target="password" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span style="font-size:10px; color:#888; margin-top:3px; line-height:1.4;">
                        <i class="fas fa-shield-alt" style="color:#00b4d8;"></i>
                        Mínimo 8 caracteres, 1 mayúscula, 1 carácter especial (@#$!), sin secuencias (123).
                    </span>
                    @error('password')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirmar Contraseña <span style="color:red">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            placeholder="Repite tu contraseña" minlength="8" required>
                        <button type="button" class="toggle-password" data-target="password_confirmation" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span id="error-match" class="error-text" style="display:none;">Las contraseñas no coinciden.</span>
                </div>
            </div>

            {{-- ===================== DATOS DE LA CLÍNICA ===================== --}}
            <div class="section-title" style="margin-top: 24px;">Datos de la Clínica</div>

            <div class="form-grid single">
                <div class="form-group">
                    <label for="nombre_clinica">Nombre Comercial <span style="color:red">*</span></label>
                    <input type="text" id="nombre_clinica" name="nombre_clinica" value="{{ old('nombre_clinica') }}"
                        placeholder="Ej. Clínica Dental Sonrisas"
                        class="{{ $errors->has('nombre_clinica') ? 'is-invalid' : '' }}" required
                        oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                    @error('nombre_clinica')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="telefono_clinica">Teléfono</label>
                    <input type="tel" id="telefono_clinica" name="telefono_clinica"
                        value="{{ old('telefono_clinica') }}" placeholder="Ej. 5512345678" maxlength="12"
                        class="{{ $errors->has('telefono_clinica') ? 'is-invalid' : '' }}"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    @error('telefono_clinica')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="localidad">Ciudad / Localidad</label>
                    <input type="text" id="localidad" name="localidad" value="{{ old('localidad') }}"
                        placeholder="Ej. Ciudad de México" class="{{ $errors->has('localidad') ? 'is-invalid' : '' }}"
                        oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                    @error('localidad')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="estado_clinica">Estado</label>
                    <input type="text" id="estado_clinica" name="estado_clinica" value="{{ old('estado_clinica') }}"
                        placeholder="Ej. Puebla" class="{{ $errors->has('estado_clinica') ? 'is-invalid' : '' }}"
                        oninput="this.value=this.value.replace(/[^A-Za-zÀ-ÿÑñ ]/g,'').replace(/  +/g,' ')">
                    @error('estado_clinica')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="codigo_postal">Código Postal</label>
                    <input type="text" id="codigo_postal" name="codigo_postal" value="{{ old('codigo_postal') }}"
                        placeholder="Ej. 72000" maxlength="5"
                        class="{{ $errors->has('codigo_postal') ? 'is-invalid' : '' }}"
                        oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    @error('codigo_postal')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-clinic-medical"></i> Crear Clínica y Cuenta
            </button>
        </form>
        <div class="back-link">
            ¿Ya tienes cuenta? <a href="{{ route('login') }}">Iniciar sesión aquí</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('password_confirmation');
            const form = document.querySelector('form');

            // ===== Title Case automático al salir del campo =====
            function toTitleCase(str) {
                return str.toLowerCase().replace(/(?:^|\s)\S/g, function (match) {
                    return match.toUpperCase();
                });
            }

            ['nombre', 'apellido_paterno', 'apellido_materno', 'nombre_clinica', 'localidad', 'estado_clinica'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('blur', function () {
                        if (this.value.trim()) {
                            this.value = toTitleCase(this.value.trim());
                        }
                    });
                }
            });

            // ===== Validación de coincidencia de contraseñas =====
            const errorMatch = document.getElementById('error-match');

            function validatePasswordMatch() {
                if (passwordInput.value && confirmInput.value) {
                    if (passwordInput.value !== confirmInput.value) {
                        confirmInput.classList.add('is-invalid');
                        errorMatch.style.display = 'block';
                    } else {
                        confirmInput.classList.remove('is-invalid');
                        errorMatch.style.display = 'none';
                    }
                } else {
                    confirmInput.classList.remove('is-invalid');
                    errorMatch.style.display = 'none';
                }
            }

            confirmInput.addEventListener('blur', validatePasswordMatch);
            confirmInput.addEventListener('keyup', validatePasswordMatch);

            form.addEventListener('submit', function (e) {
                if (passwordInput.value !== confirmInput.value) {
                    e.preventDefault();
                    validatePasswordMatch();
                    confirmInput.focus();
                }
            });

            // Toggle mostrar/ocultar contraseña
            document.querySelectorAll('.toggle-password').forEach(btn => {
                btn.addEventListener('click', function () {
                    const input = document.getElementById(this.dataset.target);
                    const icon = this.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.replace('fa-eye', 'fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.replace('fa-eye-slash', 'fa-eye');
                    }
                });
            });
        });

        // Script para la animación de dientes flotantes 3D
        const canvas = document.getElementById('canvas1');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        let particlesArray;

        class Tooth {
            constructor(x, y, directionX, directionY, size, color) {
                this.x = x; this.y = y; this.directionX = directionX; this.directionY = directionY;
                this.size = size; this.color = color;
                this.spinSpeed = (Math.random() - 0.5) * 0.02; this.angle = Math.random() * 360;
            }
            draw() {
                ctx.save(); ctx.translate(this.x, this.y); ctx.rotate(this.angle); ctx.scale(this.size / 10, this.size / 10);
                ctx.beginPath(); ctx.fillStyle = this.color;
                ctx.moveTo(-10, -10); ctx.quadraticCurveTo(-5, -15, 0, -10); ctx.quadraticCurveTo(5, -15, 10, -10);
                ctx.quadraticCurveTo(12, 0, 10, 10); ctx.lineTo(5, 20); ctx.lineTo(0, 10); ctx.lineTo(-5, 20); ctx.lineTo(-10, 10); ctx.quadraticCurveTo(-12, 0, -10, -10);
                ctx.closePath(); ctx.fill(); ctx.restore();
            }
            update() {
                if (this.x > canvas.width || this.x < 0) this.directionX = -this.directionX;
                if (this.y > canvas.height || this.y < 0) this.directionY = -this.directionY;
                this.x += this.directionX; this.y += this.directionY; this.angle += this.spinSpeed; this.draw();
            }
        }

        function init() {
            particlesArray = [];
            for (let i = 0; i < 20; i++) {
                let size = (Math.random() * 5) + 3;
                let x = Math.random() * innerWidth; let y = Math.random() * innerHeight;
                let color = 'rgba(0, 180, 216, 0.3)';
                particlesArray.push(new Tooth(x, y, (Math.random() - 0.5), (Math.random() - 0.5), size, color));
            }
        }

        function animate() {
            requestAnimationFrame(animate); ctx.clearRect(0, 0, innerWidth, innerHeight);
            particlesArray.forEach(p => p.update());
        }

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            init();
        });

        init(); animate();

        // Lógica de transición animada de página completa (de Register a Login)
        document.addEventListener('DOMContentLoaded', () => {
            const transitionEl = document.getElementById('page-transition');
            if (transitionEl) {
                // Al inicio, la cortina está cubriendo (0). La deslizamos hacia la izquierda (-100%)
                setTimeout(() => {
                    transitionEl.style.transform = 'translateX(-100%)';
                }, 50);
            }

            const loginLinks = document.querySelectorAll('a[href="{{ route('login') }}"]');
            loginLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const href = link.href;
                    if (transitionEl) {
                        transitionEl.style.transition = 'transform 0.6s ease-in-out';
                        transitionEl.style.transform = 'translateX(0)'; // La traemos de regreso
                        setTimeout(() => {
                            window.location.href = href;
                        }, 550);
                    } else {
                        window.location.href = href;
                    }
                });
            });
        });
    </script>
</body>

</html>