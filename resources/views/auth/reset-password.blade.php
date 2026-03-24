<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña</title>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

        * {
            box-sizing: border-box;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            font-family: 'Montserrat', sans-serif;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        h1 {
            font-weight: bold;
            margin: 0;
        }

        p {
            font-size: 14px;
            font-weight: 100;
            line-height: 20px;
            letter-spacing: 0.5px;
            margin: 20px 0 30px;
        }

        a {
            color: #333;
            font-size: 14px;
            text-decoration: none;
            margin: 15px 0;
        }

        button {
            border-radius: 20px;
            border: 1px solid #00b4d8;
            background-color: #00b4d8;
            color: #FFFFFF;
            font-size: 12px;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in;
            cursor: pointer;
            margin-top: 10px;
        }

        button:active {
            transform: scale(0.95);
        }

        button:focus {
            outline: none;
        }

        button:disabled {
            background-color: #ccc;
            border-color: #ccc;
            cursor: not-allowed;
        }

        form {
            background-color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 50px;
            height: 100%;
            text-align: center;
        }

        input {
            background-color: #eee;
            border: none;
            padding: 12px 15px;
            margin: 8px 0;
            width: 100%;
            border-radius: 5px;
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
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            margin: 0;
            cursor: pointer;
            color: #888;
            font-size: 15px;
            line-height: 1;
            letter-spacing: 0;
            text-transform: none;
        }

        .toggle-password:hover {
            color: #00b4d8;
        }

        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25), 0 10px 10px rgba(0, 0, 0, 0.22);
            position: relative;
            overflow: hidden;
            width: 850px;
            max-width: 100%;
            min-height: 600px;
            z-index: 10;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }

        .overlay {
            background: #00b4d8;
            background: linear-gradient(to right, #90e0ef, #0077b6);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 0 0;
            color: #FFFFFF;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
            right: 0;
        }

        .alert {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            font-size: 12px;
            width: 100%;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
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
    </style>
</head>

<body>
    <div class="container" id="container">
        <div class="form-container">
            <form id="resetPasswordForm" onsubmit="return false;">
                <h1>Ingresa tu nueva contraseña</h1>
                <p>Asegúrate de que sea segura y coincida en ambos campos.</p>

                <div id="alertBox" class="alert" style="display:none; text-align:center;"></div>

                <div class="password-wrapper">
                    <input type="password" id="newPassword" placeholder="Nueva contraseña" minlength="8" required />
                    <button type="button" class="toggle-password" data-target="newPassword" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <span
                    style="font-size:10px; color:#888; margin-top:3px; line-height:1.4; display:block; text-align:left; width:100%;">
                    <i class="fas fa-shield-alt" style="color:#00b4d8;"></i>
                    Mínimo 8 caracteres, 1 mayúscula, 1 carácter especial (@#$!), sin secuencias (123).
                </span>
                <div class="password-wrapper">
                    <input type="password" id="confirmPassword" placeholder="Confirmar contraseña" minlength="8"
                        required />
                    <button type="button" class="toggle-password" data-target="confirmPassword" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <button type="submit" id="submitBtn">Actualizar Contraseña</button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel">
                    <h1>¡Estás a un paso!</h1>
                    <p>Ingresa tu nueva contraseña y podrás volver a acceder a DentalConnect.</p>
                </div>
            </div>
        </div>
    </div>

    <canvas id="canvas1"></canvas>

    <script>
        // Animación de Dientes
        const canvas = document.getElementById("canvas1");
        const ctx = canvas.getContext("2d");
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
        init(); animate();

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

        // Lógica de Fetch y URLSearchParams
        document.getElementById('resetPasswordForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const submitBtn = document.getElementById('submitBtn');
            const alertBox = document.getElementById('alertBox');

            // Validaciones de seguridad de contraseña (mismas reglas que el registro)
            if (newPassword.length < 8) {
                alertBox.textContent = 'La contraseña debe tener mínimo 8 caracteres.';
                alertBox.className = 'alert alert-danger';
                alertBox.style.display = 'block';
                return;
            }

            if (!/[A-Z]/.test(newPassword)) {
                alertBox.textContent = 'La contraseña debe contener al menos una letra mayúscula.';
                alertBox.className = 'alert alert-danger';
                alertBox.style.display = 'block';
                return;
            }

            if (!/[\W_]/.test(newPassword)) {
                alertBox.textContent = 'La contraseña debe contener al menos un carácter especial (ej. @, #, $, !).';
                alertBox.className = 'alert alert-danger';
                alertBox.style.display = 'block';
                return;
            }

            if (/123/.test(newPassword)) {
                alertBox.textContent = 'La contraseña no puede contener secuencias numéricas como 123.';
                alertBox.className = 'alert alert-danger';
                alertBox.style.display = 'block';
                return;
            }

            if (newPassword !== confirmPassword) {
                alertBox.textContent = 'Las contraseñas no coinciden.';
                alertBox.className = 'alert alert-danger';
                alertBox.style.display = 'block';
                return;
            }

            // Exraer token y email de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            const email = urlParams.get('email');

            if (!token || !email) {
                alertBox.textContent = 'Enlace inválido o incompleto. Solicita uno nuevo.';
                alertBox.className = 'alert alert-danger';
                alertBox.style.display = 'block';
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Actualizando...';
            alertBox.style.display = 'none';
            alertBox.className = 'alert';

            try {
                // Actualizado para apuntar a tu API en producción en Railway
                const response = await fetch('{{ url('/api/auth/reset-password') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, token, password: newPassword, password_confirmation: confirmPassword })
                });

                const data = await response.json();

                if (response.ok) {
                    alertBox.textContent = '¡Contraseña actualizada con éxito! Redirigiendo...';
                    alertBox.classList.add('alert-success');
                    alertBox.style.display = 'block';

                    setTimeout(() => {
                        window.location.href = '{{ route('login') }}';
                    }, 2500);
                } else {
                    alertBox.textContent = data.message || 'Error al actualizar la contraseña.';
                    alertBox.classList.add('alert-danger');
                    alertBox.style.display = 'block';
                }
            } catch (error) {
                alertBox.textContent = 'Problema de conexión con el servidor.';
                alertBox.classList.add('alert-danger');
                alertBox.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Actualizar Contraseña';
            }
        });
    </script>
</body>

</html>