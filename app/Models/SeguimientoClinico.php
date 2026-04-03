<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para el seguimiento clínico de una cita.
 *
 * Registra observaciones posteriores a la cita y si requiere tratamiento adicional.
 *
 * @property int $id_seguimiento
 * @property int $id_cita
 * @property string $postratamiento
 * @property int|null $id_servicio
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SeguimientoClinico extends Model
{
    use HasFactory;

    protected $table = 'seguimiento_clinico';
    protected $primaryKey = 'id_seguimiento';
    public $timestamps = true;

    protected $fillable = [
        'id_cita',
        'postratamiento', // 'si', 'no'
        'id_servicio',
        'observaciones',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }
}