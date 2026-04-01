<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\ClinicaScope;

class Servicio extends Model
{
    use HasFactory;

    protected $table = 'catalogo_servicios';
    protected $primaryKey = 'id_servicio';
    public $timestamps = false;

    protected $fillable = [
        'id_clinica',
        'nombre_servicio',
        'precio_base',
        'categoria',
    ];

    /**
     * El Global Scope garantiza que cada clínica solo vea SUS tratamientos.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new ClinicaScope());
    }
}
