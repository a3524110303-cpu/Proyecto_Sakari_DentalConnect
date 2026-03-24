<?php

use Illuminate\Support\Facades\Route;
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

/**
 * Rutas Públicas de Autenticación.
 *
 * Manejan el inicio de sesión, registro y cierre de sesión.
 * No requieren autenticación previa.
 */
// Rutas Públicas (Login/Registro)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas de Recuperación de Contraseña
Route::get('/olvide-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::get('/recuperar-password', function () {
    return view('auth.reset-password');
})->name('password.reset');

// Landing SaaS pública
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Webhook de Stripe (sin CSRF, configurado en bootstrap/app.php)
Route::post('/stripe/webhook', [SuscripcionController::class, 'webhook'])->name('stripe.webhook');

/**
 * Rutas Privadas / Protegidas.
 *
 * Requieren que el usuario esté autenticado (middleware 'auth').
 * Incluyen el dashboard, gestión de pacientes, tratamientos, configuración y APIs internas.
 */
// Rutas Privadas (Requieren Login)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Suscripciones SaaS
    Route::get('/suscripciones', [SuscripcionController::class, 'show'])->name('suscripciones.show');
    Route::post('/suscripciones/checkout/{planSlug}', [SuscripcionController::class, 'checkout'])->name('suscripciones.checkout');
    Route::get('/suscripciones/success', [SuscripcionController::class, 'success'])->name('suscripciones.success');
    Route::get('/suscripciones/cancel', [SuscripcionController::class, 'cancel'])->name('suscripciones.cancel');

    // Pacientes
    Route::resource('pacientes', PacienteController::class);
    // Ruta adicional para POST en pacientes (si se usa manualmente en el form)
    // Route::post('/pacientes', [PacienteController::class, 'store'])->name('pacientes.store'); 

    // Tratamientos
    Route::resource('tratamientos', TratamientoController::class)
        ->parameters(['tratamientos' => 'id'])
        ->except(['create', 'edit', 'show']);
    Route::post('/citas/{id}/actualizar', [DashboardController::class, 'actualizarCita'])->name('citas.actualizar');

    // Publicidad
    Route::resource('publicidad', App\Http\Controllers\PublicidadController::class)
        ->only(['index', 'store', 'destroy']);
    // Configuración
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion/clinica', [ConfiguracionController::class, 'updateClinica'])->name('configuracion.updateClinica');
    Route::post('/configuracion/usuario', [ConfiguracionController::class, 'updateUsuario'])->name('configuracion.updateUsuario');
    Route::post('/configuracion/recepcionista', [ConfiguracionController::class, 'storeRecepcionista'])->name('configuracion.storeRecepcionista');
    Route::post('/configuracion/horarios', [ConfiguracionController::class, 'updateHorarios'])->name('configuracion.updateHorarios');
    Route::delete('/configuracion/recepcionista/{id}', [ConfiguracionController::class, 'destroyRecepcionista'])->name('configuracion.destroyRecepcionista');
    Route::post('/configuracion/foto-doctor', [ConfiguracionController::class, 'subirFotoDoctor'])->name('configuracion.fotoDoctor');

    /**
     * API Interna para consumo AJAX.
     *
     * Rutas que devuelven JSON para poblar modales y calendarios sin recargar la página.
     */
    // API Interna
    Route::get('/api/citas/{id}/modal-detalles', [DashboardController::class, 'obtenerDatosModal'])->name('api.cita.detalles');
    Route::post('/api/citas/{id}/completar', [DashboardController::class, 'completarCita'])->name('api.cita.completar');
    Route::get('/api/calendario/disponibilidad', [DashboardController::class, 'obtenerDisponibilidadMes'])->name('api.calendario');
    Route::get('/api/calendario/horas-ocupadas', [DashboardController::class, 'horasOcupadas'])->name('api.calendario.horas');
    Route::post('/api/pacientes/{id}/foto', [PacienteHistorialController::class, 'subirFotoProgreso'])->name('api.pacientes.foto');
    Route::get('/api/pacientes/{id}/citas', [PacienteHistorialController::class, 'historialCitas'])->name('api.pacientes.citas');
    Route::get('/api/pacientes/{id}/evoluciones', [PacienteHistorialController::class, 'evoluciones'])->name('api.pacientes.evoluciones');
    Route::post('/api/pacientes/{id}/evoluciones', [PacienteHistorialController::class, 'storeEvolucion'])->name('api.pacientes.evoluciones.store');
    Route::get('/api/pacientes/{id}/odontograma', [OdontogramaController::class, 'index'])->name('api.odontograma.paciente');
    Route::post('/api/pacientes/{id}/odontograma', [OdontogramaController::class, 'store'])->name('api.odontograma.update');
    Route::delete('/api/odontograma/{id_odontograma}', [OdontogramaController::class, 'destroy'])->name('api.odontograma.delete');
    Route::get('/api/notificaciones/reagenda', [DashboardController::class, 'notificacionesReagenda'])->name('api.notificaciones.reagenda');
    Route::post('/api/notificaciones/{id}/leer', [DashboardController::class, 'marcarNotificacionLeida'])->name('api.notificaciones.leer');
    

    // Citas
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');

    // Panel global para administración SaaS (clientes + marketing)
    Route::middleware('role:administrador')->group(function () {
        Route::get('/admin/panel', [AdminPanelController::class, 'index'])->name('admin.panel');
    });
});