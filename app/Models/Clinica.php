<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa una clínica dental.
 *
 * Almacena la información institucional de la clínica, incluyendo
 * dirección normalizada por campos y coordenadas GPS.
 *
 * @property int $id_clinica
 * @property string $nombre_comercial
 * @property string|null $numero_telefono
 * @property string|null $calle
 * @property string|null $ciudad
 * @property string|null $municipio
 * @property string|null $estado
 * @property string|null $pais
 * @property string|null $codigo_postal
 * @property float|null $latitud
 * @property float|null $longitud
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
        'calle',
        'ciudad',
        'municipio',
        'estado',
        'pais',
        'codigo_postal',
        'latitud',
        'longitud',
        'config_anticipo_pct',
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
        // 1. Obtener la suscripción activa
        $suscripcionActiva = $this->suscripciones()->where('estado', 'active')->first();
        if (!$suscripcionActiva || !$suscripcionActiva->plan) {
            return false;
        }

        // 2. Obtener el nivel del plan requerido
        $planRequerido = \App\Models\PlanSaas::where('slug', $requiredSlug)->first();
        if (!$planRequerido) {
            return false;
        }

        // 3. Comparar niveles (Ej. Si tiene Premium (2), y se requiere Básico (1), devuelve true)
        return $suscripcionActiva->plan->nivel >= $planRequerido->nivel;
    }
}