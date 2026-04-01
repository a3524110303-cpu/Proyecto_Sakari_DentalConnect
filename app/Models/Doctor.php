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

    protected $appends = ['full_foto_url'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function usuarioSistema()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function getFullFotoUrlAttribute(): string
    {
        if (empty($this->foto_perfil)) {
            return asset('assets/default-avatar.svg');
        }

        if (filter_var($this->foto_perfil, FILTER_VALIDATE_URL)) {
            return $this->foto_perfil;
        }

        $ruta = ltrim(str_replace('public/', '', (string) $this->foto_perfil), '/');
        if (str_starts_with($ruta, 'fotos_doctores/')) {
            return url('/api/doctor/foto/' . basename($ruta));
        }

        return route('storage.file', ['path' => $ruta]);
    }
}