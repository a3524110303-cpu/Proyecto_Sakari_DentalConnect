<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para el registro de ingresos económicos (caja).
 *
 * Registra los pagos realizados por citas o servicios.
 *
 * @property int $id_ingreso
 * @property int $id_clinica
 * @property int $id_cita
 * @property float $monto
 * @property string $metodo
 * @property string|null $descripcion
 * @property \Illuminate\Support\Carbon $fecha_ingreso
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class IngresoCaja extends Model
{
    use HasFactory;

    protected $table = 'ingresos_caja';
    protected $primaryKey = 'id_ingreso';
    public $timestamps = true; // Created_at and updated_at exist in DB schema

    protected $fillable = [
        'id_clinica',
        'id_cita',
        'monto',
        'metodo',       // DB column is 'metodo' ('efectivo','tarjeta', etc.)
        'descripcion',  // DB column is 'descripcion'
        'fecha_ingreso',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'clinica_id'); // Asegúrate de que tu llave foránea no sea 'id_clinica' en lugar de 'clinica_id'
    }

    // 👇 AGREGA ESTA RELACIÓN NUEVA 👇
    public function cita()
    {
        // Indicamos que el Ingreso pertenece a una Cita usando la columna 'id_cita'
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }
}