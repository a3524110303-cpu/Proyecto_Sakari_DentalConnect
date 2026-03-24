<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuscripcionClinica extends Model
{
    use HasFactory;

    protected $table = 'suscripciones_clinica';
    protected $primaryKey = 'id_suscripcion';

    protected $fillable = [
        'id_clinica',
        'id_plan',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_checkout_session_id',
        'estado',
        'moneda',
        'monto_periodo',
        'periodo_inicio',
        'periodo_fin',
        'auto_renovar',
    ];

    protected $casts = [
        'periodo_inicio' => 'datetime',
        'periodo_fin' => 'datetime',
        'auto_renovar' => 'boolean',
        'monto_periodo' => 'decimal:2',
    ];

    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'id_clinica', 'id_clinica');
    }

    public function plan()
    {
        return $this->belongsTo(PlanSaas::class, 'id_plan', 'id_plan');
    }
}
