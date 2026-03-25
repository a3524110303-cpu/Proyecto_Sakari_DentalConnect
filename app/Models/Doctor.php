<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa a un doctor en el sistema.
 *
 * Vincula la información profesional (cédula, horario) con el usuario del sistema.
 *
 * @property int $id_doctor
 * @property int $id_usuario
 * @property string|null $cedula_profesional
 * @property string|null $horario_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Doctor extends Model
{
    use HasFactory;

    protected $table = 'doctores';
    protected $primaryKey = 'id_doctor';

    protected $fillable = [
        'id_usuario',
        'cedula_profesional',
        'horario_default',
        'foto_perfil',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}