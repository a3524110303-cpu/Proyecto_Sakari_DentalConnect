<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña — DentalConnect</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Montserrat', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #f6f5f7 0%, #eef7fb 100%);
            padding: 20px;
            overflow-x: hidden;
        }

        /* ── Fondo animado ── */
        #canvas1 {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: 0;
            pointer-events: none;
        }

        /* ── Tarjeta principal ── */
        .card {
            position: relative;
            z-index: 10;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 880px;
            display: flex;
            overflow: hidden;
            min-height: 520px;
        }

        /* ── Panel izquierdo: formulario ── */
        .form-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px 45px;
            text-align: center;
        }

        .form-panel h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: 10px;
        }

        .form-panel p.subtitle {
            font-size: 0.88rem;
            color: #666;
            line-height: 1.6;
            margin-bottom: 28px;
            max-width: 300px;
        }

        /* ── Inputs ── */
        .input-group {
            width: 100%;
            margin-bottom: 12px;
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            width: 100%;
            background: #f0f4f8;
            border: 2px solid transparent;
            border-radius: 12px;
            padding: 14px 44px 14px 18px;
            font-size: 0.9rem;
            font-family: 'Montserrat', sans-serif;
            color: #333;
            transition: border-color 0.2s, background 0.2s;
            outline: none;
        }

        .password-wrapper input:focus {
            background: #fff;
            border-color: #00b4d8;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #aaa;
            font-size: 15px;
            padding: 0;
            transition: color 0.2s;
        }

        .toggle-password:hover { color: #00b4d8; }

        .password-hint {
            font-size: 0.72rem;
            color: #999;
            text-align: left;
            margin: 5px 0 4px 4px;
            line-height: 1.4;
        }

        .password-hint i { color: #00b4d8; margin-right: 3px; }

        /* ── Botón submit ── */
        .btn-submit {
            width: 100%;
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #00b4d8, #0077b6);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 6px 20px rgba(0,180,216,0.3);
        }

        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,180,216,0.4); }
        .btn-submit:active { transform: scale(0.97); }
        .btn-submit:disabled { background: #ccc; box-shadow: none; cursor: not-allowed; transform: none; }

        /* ── Alertas ── */
        .alert {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.82rem;
            margin-bottom: 14px;
            text-align: left;
            display: none;
        }

        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-danger  { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* ── Panel derecho: decorativo ── */
        .deco-panel {
            width: 42%;
            background: linear-gradient(160deg, #0096c7 0%, #0077b6 60%, #023e8a 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 40px;
            text-align: center;
            color: #fff;
        }

        .deco-panel .deco-icon {
            font-size: 3.5rem;
            margin-bottom: 22px;
            opacity: 0.9;
        }

        .deco-panel h2 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .deco-panel p {
            font-size: 0.9rem;
            opacity: 0.87;
            line-height: 1.6;
        }

        /* ── RESPONSIVE: Mobile (<640px) ── */
        @media (max-width: 640px) {
            body {
                padding: 16px;
                align-items: flex-start;
                padding-top: 40px;
            }

            .card {
                flex-direction: column;
                min-height: unset;
                border-radius: 20px;
            }

            /* El panel decorativo se mueve arriba como un banner compacto */
            .deco-panel {
                width: 100%;
                padding: 28px 24px 24px;
                border-radius: 0;
            }

            .deco-panel .deco-icon { font-size: 2.5rem; margin-bottom: 10px; }
            .deco-panel h2 { font-size: 1.4rem; margin-bottom: 8px; }
            .deco-panel p { font-size: 0.82rem; }

            .form-panel {
                padding: 30px 24px 36px;
            }

            .form-panel h1 { font-size: 1.25rem; }
        }

        /* ── RESPONSIVE: Tablet (641-900px) ── */
        @media (min-width: 641px) and (max-width: 900px) {
            .deco-panel { width: 38%; padding: 40px 28px; }
            .form-panel { padding: 40px 32px; }
            .form-panel h1 { font-size: 1.35rem; }
        }
    </style>
</head>

<body>
    <canvas id="canvas1"></canvas>

    <div class="card">

        {{-- Panel derecho decorativo (en mobile va arriba) --}}
        <div class="deco-panel">
            <div class="deco-icon">
                <i class="fas fa-lock-open"></i>
            </div>
            <h2>¡Estás a un paso!</h2>
            <p>Ingresa tu nueva contraseña y podrás volver a acceder a DentalConnect de forma segura.</p>
        </div>

        {{-- Panel izquierdo: formulario --}}
        <div class="form-panel">
            <h1>Nueva contraseña</h1>
            <p class="subtitle">Asegúrate de que sea segura y coincida en ambos campos.</p>

            <div id="alertBox" class="alert"></div>

            <form id="resetPasswordForm" onsubmit="return false;" style="width:100%;">
                <div class="input-group">
                    <div class="password-wrapper">
                        <input type="password" id="newPassword" placeholder="Nueva contraseña" minlength="8" required autocomplete="new-password" />
                        <button type="button" class="toggle-password" data-target="newPassword" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="password-hint">
                        <i class="fas fa-shield-alt"></i>
                        Mín. 8 caracteres, 1 mayúscula, 1 carácter especial (@#$!), sin secuencias (123).
                    </p>
                </div>

                <div class="input-group">
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword" placeholder="Confirmar contraseña" minlength="8" required autocomplete="new-password" />
                        <button type="button" class="toggle-password" data-target="confirmPassword" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="btn-submit">
                    <i class="fas fa-key" style="margin-right:8px;"></i>Actualizar Contraseña
                </button>
            </form>
        </div>

    </div>

    <script>
        // ── Animación de dientes en el fondo ──
        const canvas = document.getElementById("canvas1");
        const ctx = canvas.getContext("2d");

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        class Tooth {
            constructor(x, y, dx, dy, size) {
                this.x = x; this.y = y; this.dx = dx; this.dy = dy;
                this.size = size;
                this.angle = Math.random() * Math.PI * 2;
                this.spin = (Math.random() - 0.5) * 0.02;
            }
            draw() {
                ctx.save();
                ctx.translate(this.x, this.y);
                ctx.rotate(this.angle);
                ctx.scale(this.size / 10, this.size / 10);
                ctx.beginPath();
                ctx.fillStyle = 'rgba(0,180,216,0.22)';
                ctx.moveTo(-10,-10); ctx.quadraticCurveTo(-5,-15,0,-10); ctx.quadraticCurveTo(5,-15,10,-10);
                ctx.quadraticCurveTo(12,0,10,10); ctx.lineTo(5,20); ctx.lineTo(0,10); ctx.lineTo(-5,20);
                ctx.lineTo(-10,10); ctx.quadraticCurveTo(-12,0,-10,-10);
                ctx.closePath(); ctx.fill(); ctx.restore();
            }
            update() {
                if (this.x > canvas.width || this.x < 0) this.dx = -this.dx;
                if (this.y > canvas.height || this.y < 0) this.dy = -this.dy;
                this.x += this.dx; this.y += this.dy; this.angle += this.spin;
                this.draw();
            }
        }

        const teeth = Array.from({length: 18}, () => new Tooth(
            Math.random() * window.innerWidth,
            Math.random() * window.innerHeight,
            (Math.random() - 0.5) * 0.6,
            (Math.random() - 0.5) * 0.6,
            (Math.random() * 5) + 3
        ));

        function animate() {
            requestAnimationFrame(animate);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            teeth.forEach(t => t.update());
        }
        animate();

        // ── Toggle mostrar/ocultar contraseña ──
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function () {
                const input = document.getElementById(this.dataset.target);
                const icon = this.querySelector('i');
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            });
        });

        // ── Lógica de submit ──
        function showAlert(msg, type) {
            const box = document.getElementById('alertBox');
            box.textContent = msg;
            box.className = 'alert alert-' + type;
            box.style.display = 'block';
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        document.getElementById('resetPasswordForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const newPassword     = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const submitBtn       = document.getElementById('submitBtn');

            // Validaciones
            if (newPassword.length < 8) return showAlert('La contraseña debe tener mínimo 8 caracteres.', 'danger');
            if (!/[A-Z]/.test(newPassword)) return showAlert('Debe contener al menos una letra mayúscula.', 'danger');
            if (!/[\W_]/.test(newPassword)) return showAlert('Debe contener al menos un carácter especial (@, #, $, !).', 'danger');
            if (/123/.test(newPassword)) return showAlert('No puede contener secuencias numéricas como 123.', 'danger');
            if (newPassword !== confirmPassword) return showAlert('Las contraseñas no coinciden.', 'danger');

            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            const email = urlParams.get('email');

            if (!token || !email) return showAlert('Enlace inválido o incompleto. Solicita uno nuevo.', 'danger');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i>Actualizando...';
            document.getElementById('alertBox').style.display = 'none';

            try {
                const response = await fetch('{{ url('/api/auth/reset-password') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, token, password: newPassword, password_confirmation: confirmPassword })
                });

                const isJson = response.headers.get('content-type')?.includes('application/json');
                const data = isJson ? await response.json() : null;

                if (response.ok) {
                    showAlert('¡Contraseña actualizada con éxito! Redirigiendo...', 'success');
                    setTimeout(() => { window.location.href = '{{ route('login') }}'; }, 2500);
                } else {
                    showAlert(data?.message || 'Error al actualizar la contraseña.', 'danger');
                }
            } catch (error) {
                console.error("Error en reset-password:", error);
                showAlert('Problema de conexión con el servidor. Intenta de nuevo.', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-key" style="margin-right:8px;"></i>Actualizar Contraseña';
            }
        });
    </script>
</body>

</html>