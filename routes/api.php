<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PacienteAppController;
use App\Http\Controllers\CitaController;

// ==========================================
// API PÚBLICA / APLICACIÓN MÓVIL DEL PACIENTE
// ==========================================

// Login
Route::post('/login', [AuthController::class, 'login']);
// Recuperar Contraseña App Móvil
Route::post('/recuperar-password', [AuthController::class, 'enviarCorreoRecuperacion']);

// AGREGAR ESTA LÍNEA NUEVA PARA ACTIVAR LA CUENTA
Route::post('/activar', [AuthController::class, 'activarCuenta']);

Route::middleware('auth:sanctum')->group(function () {

    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // ==========================================
    // DATOS DEL PACIENTE
    // ==========================================

    Route::get('/perfil', [PacienteAppController::class, 'perfil']);

    Route::get('/citas-proximas', [PacienteAppController::class, 'citasProximas']);

    Route::get('/citas-pasadas', [PacienteAppController::class, 'citasPasadas']);

    Route::get('/estado-cuenta', [PacienteAppController::class, 'estadoCuenta']);

    Route::get('/clinicas-doctores', [PacienteAppController::class, 'clinicasYDoctores']);

    Route::get('/publicidad', [PacienteAppController::class, 'publicidad']);


    // ==========================================
    // HORARIOS
    // ==========================================

    // Horarios disponibles
    Route::get('/horarios-disponibles', [PacienteAppController::class, 'horariosDisponibles']);

    // 🔴 NUEVA API → HORAS OCUPADAS (para bloquear horarios)
    Route::get('/horas-ocupadas', [CitaController::class, 'horasOcupadas']);

    // ==========================================
    // TRATAMIENTOS / SERVICIOS
    // ==========================================
    Route::get('/tratamientos', [PacienteAppController::class, 'tratamientos']);

    Route::post('/agendar-cita', [PacienteAppController::class, 'agendarCita']);

    // ==========================================
    // HORARIOS Y TRATAMIENTOS REALES (NUEVAS)
    // ==========================================
    Route::get('/horas-disponibles', [PacienteAppController::class, 'horasDisponiblesDia']);
    Route::get('/tratamientos-activos', [PacienteAppController::class, 'tratamientosActivos']);
});
