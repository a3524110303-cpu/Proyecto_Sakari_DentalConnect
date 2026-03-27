<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de Usuario del Sistema.
 *
 * Representa a cualquier usuario que puede iniciar sesión (Doctor, Recepcionista, Paciente, Admin).
 * Utiliza Laravel Sanctum para la autenticación mediante tokens API y el sistema de autenticación por defecto de Laravel.
 *
 * @property int $id_usuario 
 * @property int $id_clinica
 * @property string $nombre_completo
 * @property string $email
 * @property string $password
 * @property string $rol
 * @property bool $is_active
 * @property string|null $sobre_mi
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios_sistema';
    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'id_clinica',
        'nombre_completo',
        'email',
        'password',
        'rol',
        'is_active', // Agregado para permitir desactivar usuarios
        'sobre_mi',  // <--- ESTA ES LA LÍNEA QUE SOLUCIONA TU PROBLEMA
        'telefono',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean', // Agregado para manejarlo como booleano
    ];

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'id_clinica', 'id_clinica');
    }
}