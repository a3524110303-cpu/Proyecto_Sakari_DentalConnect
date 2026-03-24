<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanSaas extends Model
{
    use HasFactory;

    protected $table = 'saas_planes';
    protected $primaryKey = 'id_plan';

    protected $fillable = [
        'slug',
        'nombre',
        'nivel',
        'precio_mensual',
        'stripe_price_id',
        'max_doctores',
        'max_pacientes',
        'features',
        'activo',
    ];

    protected $casts = [
        'features' => 'array',
        'activo' => 'boolean',
        'precio_mensual' => 'decimal:2',
    ];

    public function suscripciones()
    {
        return $this->hasMany(SuscripcionClinica::class, 'id_plan', 'id_plan');
    }
}
