<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para las notificaciones del sistema.
 *
 * Almacena mensajes dirigidos a los usuarios, incluyendo soporte para notificaciones push.
 *
 * @property int $id_notificacion
 * @property int $id_usuario
 * @property string $tipo
 * @property string $mensaje
 * @property string|null $device_token
 * @property string $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';
    protected $primaryKey = 'id_notificacion';

    protected $fillable = [
        'id_clinica',
        'id_usuario',
        'id_cita',
        'tipo',
        'mensaje',
        'datos',
        'device_token',
        'estado',
    ];

    protected $casts = [
        'datos' => 'array',
    ];
}