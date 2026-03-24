<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa una clínica dental.
 *
 * Almacena la información institucional de la clínica, como nombre, RFC y dirección.
 *
 * @property int $id_clinica
 * @property string $nombre_comercial
 * @property string|null $numero_telefono
 * @property string|null $localidad
 * @property string|null $estado
 * @property string|null $codigo_postal
 * @property float|null $config_anticipo_pct
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Clinica extends Model
{
    use HasFactory;

    protected $table = 'clinicas';
    protected $primaryKey = 'id_clinica';

    protected $fillable = [
        'nombre_comercial',
        'numero_telefono',
        'localidad',
        'estado',
        'codigo_postal',
        'config_anticipo_pct'
    ];

    public function usuarios()
    {
        return $this->hasMany(User::class, 'id_clinica', 'id_clinica');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'id_clinica', 'id_clinica');
    }

    public function suscripciones()
    {
        return $this->hasMany(SuscripcionClinica::class, 'id_clinica', 'id_clinica');
    }

    public function suscripcionActiva()
    {
        return $this->hasOne(SuscripcionClinica::class, 'id_clinica', 'id_clinica')
            ->whereIn('estado', ['active', 'trialing'])
            ->latest('periodo_fin');
    }

    public function hasPlanAtLeast(string $requiredSlug): bool
    {
        $requiredPlan = PlanSaas::where('slug', $requiredSlug)->first();
        $currentSubscription = $this->suscripcionActiva()->with('plan')->first();

        if (!$requiredPlan || !$currentSubscription || !$currentSubscription->plan) {
            return false;
        }

        return (int) $currentSubscription->plan->nivel >= (int) $requiredPlan->nivel;
    }
}