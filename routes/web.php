<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\TratamientoController;
use App\Http\Controllers\PublicidadController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SuscripcionController;
use App\Http\Controllers\AdminPanelController;
use App\Http\Controllers\Api\OdontogramaController;
use App\Http\Controllers\Api\PacienteHistorialController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 🔓 Rutas Públicas
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Recuperación de contraseña
Route::get('/olvide-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::get('/recuperar-password', function () {
    return view('auth.reset-password');
})->name('password.reset');

// Landing pública
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Webhook Stripe
Route::post('/stripe/webhook', [SuscripcionController::class, 'webhook'])->name('stripe.webhook');


// 🔐 Rutas Privadas
Route::middleware(['auth', \App\Http\Middleware\PreventBackHistory::class])->group(function () {

    // ==========================================
    // 🟢 ZONA LIBRE (Solo requiere iniciar sesión)
    // ==========================================
    // Aquí dejamos las rutas de cobro para que puedan pagar
    Route::get('/suscripciones', [SuscripcionController::class, 'show'])->name('suscripciones.show');
    Route::post('/suscripciones/checkout/{planSlug}', [SuscripcionController::class, 'checkout'])->name('suscripciones.checkout');
    Route::get('/suscripciones/success', [SuscripcionController::class, 'success'])->name('suscripciones.success');
    Route::get('/suscripciones/cancel', [SuscripcionController::class, 'cancel'])->name('suscripciones.cancel');


    // ==========================================
    // 🔵 ZONA BÁSICA (Nivel 1 o superior)
    // ==========================================
    Route::middleware([\App\Http\Middleware\EnsurePlanLevel::class.':basic'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Pacientes
        Route::resource('pacientes', PacienteController::class);

        // Tratamientos
        Route::resource('tratamientos', TratamientoController::class)
            ->parameters(['tratamientos' => 'id'])
            ->except(['create', 'edit', 'show']);

        Route::post('/citas/{id}/actualizar', [DashboardController::class, 'actualizarCita'])->name('citas.actualizar');

        // Configuración
        Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::post('/configuracion/clinica', [ConfiguracionController::class, 'updateClinica'])->name('configuracion.updateClinica');
        Route::post('/configuracion/usuario', [ConfiguracionController::class, 'updateUsuario'])->name('configuracion.updateUsuario');
        Route::post('/configuracion/recepcionista', [ConfiguracionController::class, 'storeRecepcionista'])->name('configuracion.storeRecepcionista');
        Route::post('/configuracion/horarios', [ConfiguracionController::class, 'updateHorarios'])->name('configuracion.updateHorarios');
        Route::delete('/configuracion/recepcionista/{id}', [ConfiguracionController::class, 'destroyRecepcionista'])->name('configuracion.destroyRecepcionista');
        Route::post('/configuracion/foto-doctor', [ConfiguracionController::class, 'subirFotoDoctor'])->name('configuracion.fotoDoctor');

        // ============================
        // 🔥 API INTERNA (AJAX)
        // ============================

        Route::get('/api/citas/{id}/modal-detalles', [DashboardController::class, 'obtenerDatosModal'])->name('api.cita.detalles');
        Route::post('/api/citas/{id}/completar', [DashboardController::class, 'completarCita'])->name('api.cita.completar');

        Route::get('/api/calendario/disponibilidad', [DashboardController::class, 'obtenerDisponibilidadMes'])->name('api.calendario');

        // ✅ NUEVA RUTA CORRECTA PARA TU CALENDARIO
        Route::get('/api/citas/ocupadas', [CitaController::class, 'horasOcupadas'])->name('api.citas.ocupadas');

        // ❌ ELIMINADA (ya no se usa)
        Route::get('/api/calendario/horas-ocupadas', [CitaController::class, 'horasOcupadas']);
        Route::post('/api/pacientes/{id}/foto', [PacienteHistorialController::class, 'subirFotoProgreso'])->name('api.pacientes.foto');
        Route::get('/api/pacientes/{id}/citas', [PacienteHistorialController::class, 'historialCitas'])->name('api.pacientes.citas');
        Route::get('/api/pacientes/{id}/evoluciones', [PacienteHistorialController::class, 'evoluciones'])->name('api.pacientes.evoluciones');
        Route::post('/api/pacientes/{id}/evoluciones', [PacienteHistorialController::class, 'storeEvolucion'])->name('api.pacientes.evoluciones.store');

        Route::get('/api/pacientes/{id}/odontograma', [OdontogramaController::class, 'index'])->name('api.odontograma.paciente');
        Route::post('/api/pacientes/{id}/odontograma', [OdontogramaController::class, 'store'])->name('api.odontograma.update');
        Route::delete('/api/odontograma/{id_odontograma}', [OdontogramaController::class, 'destroy'])->name('api.odontograma.delete');

        Route::get('/api/notificaciones/reagenda', [DashboardController::class, 'notificacionesReagenda'])->name('api.notificaciones.reagenda');
        Route::post('/api/notificaciones/{id}/leer', [DashboardController::class, 'marcarNotificacionLeida'])->name('api.notificaciones.leer');
        Route::post('/api/notificaciones/{id}/procesar', [DashboardController::class, 'procesarReagenda'])->name('api.notificaciones.procesar');

        // ============================
        // 📅 CITAS
        // ============================
        Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');

        // ============================
        // 🛠️ ADMIN PANEL
        // ============================
        Route::middleware('role:administrador')->group(function () {
            Route::get('/admin/panel', [AdminPanelController::class, 'index'])->name('admin.panel');
        });
    });

    // ==========================================
    // 🟡 ZONA PREMIUM (Nivel 2 o superior)
    // ==========================================
    Route::middleware([\App\Http\Middleware\EnsurePlanLevel::class.':premium'])->group(function () {
        // Solo Premium y Ultra pueden acceder a las campañas de publicidad
        Route::resource('publicidad', PublicidadController::class)
            ->only(['index', 'store', 'destroy']);
    });

    // ==========================================
    // 🟣 ZONA ULTRA (Nivel 3)
    // ==========================================
    Route::middleware([\App\Http\Middleware\EnsurePlanLevel::class.':ultra'])->group(function () {
        // Cuando crees el controlador para manejar múltiples sucursales, pon sus rutas aquí.
    });
});


// 📂 SERVIR ARCHIVOS (FIX RAILWAY)
Route::get('/storage-file/{path}', function ($path) {
    if (strpos($path, '..') !== false) {
        abort(403);
    }

    $rawPath = ltrim((string) $path, '/');
    $decodedPath = ltrim(rawurldecode($rawPath), '/');

    $candidatos = array_values(array_unique([
        $decodedPath,
        preg_replace('#^public/#', '', $decodedPath),
    ]));

    $disk = Storage::disk('public');
    $roots = array_values(array_unique(array_filter([
        config('filesystems.disks.public.root'),
        env('PUBLIC_DISK_ROOT'),
        env('RAILWAY_VOLUME_MOUNT_PATH') ? rtrim(env('RAILWAY_VOLUME_MOUNT_PATH'), '/\\') . '/public' : null,
        storage_path('app/public'),
    ])));

    $fullPath = null;
    foreach ($candidatos as $rel) {
        if ($rel === null || $rel === '') {
            continue;
        }

        if ($disk->exists($rel)) {
            foreach ($roots as $root) {
                $pathCandidate = rtrim((string) $root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
                if (file_exists($pathCandidate)) {
                    $fullPath = $pathCandidate;
                    break 2;
                }
            }
        } else {
            foreach ($roots as $root) {
                $pathCandidate = rtrim((string) $root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
                if (file_exists($pathCandidate)) {
                    $fullPath = $pathCandidate;
                    break 2;
                }
            }
        }
    }

    if (!$fullPath || !file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*')->name('storage.file');