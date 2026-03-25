<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Modelo que representa una cita médica en el sistema.
 *
 * Administra la información de las citas, incluyendo paciente, doctor, servicio, horario y estado.
 * También gestiona las relaciones con los detalles de la cita y los pagos (ingresos).
 *
 * @property int $id_cita
 * @property int $id_clinica
 * @property int $id_paciente
 * @property int $id_doctor
 * @property int $id_servicio
 * @property \Illuminate\Support\Carbon $fecha_hora_inicio
 * @property \Illuminate\Support\Carbon $fecha_hora_fin
 * @property string $estado_cita
 * @property float|null $costo_estimado
 * @property string|null $motivo
 * @property string|null $notas
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';
    protected $primaryKey = 'id_cita';

    protected $fillable = [
        'id_clinica',
        'id_paciente',
        'id_doctor',
        'id_servicio',      // Si usas servicios del catálogo
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'estado_cita',
        'costo_estimado',
        'motivo',
        'notas',
        'reagenda_solicitada_at',
        'reagenda_fecha_solicitada',
        'reagenda_hora_solicitada',
        'reagenda_motivo',
        'reagenda_estatus',
        'cuidados_pdf',
        'tips_pdf',
    ];

    protected $appends = [
        'cuidados_pdf_url',
        'tips_pdf_url',
    ];

    public function getCuidadosPdfUrlAttribute(): ?string
    {
        $ruta = $this->cuidados_pdf;

        if (!$ruta) {
            $archivo = $this->relationLoaded('archivos')
                ? $this->archivos->firstWhere('descripcion', 'cuidados_pdf')
                : $this->archivos()->where('tipo', 'pdf')->where('descripcion', 'cuidados_pdf')->first();

            $ruta = $archivo->url_archivo ?? null;
        }

        if (!$ruta) {
            return null;
        }

        return Storage::disk(env('CITAS_FILESYSTEM_DISK', 'public'))->url($ruta);
    }

    public function getTipsPdfUrlAttribute(): ?string
    {
        $ruta = $this->tips_pdf;

        if (!$ruta) {
            $archivo = $this->relationLoaded('archivos')
                ? $this->archivos->firstWhere('descripcion', 'tips_pdf')
                : $this->archivos()->where('tipo', 'pdf')->where('descripcion', 'tips_pdf')->first();

            $ruta = $archivo->url_archivo ?? null;
        }

        if (!$ruta) {
            return null;
        }

        return Storage::disk(env('CITAS_FILESYSTEM_DISK', 'public'))->url($ruta);
    }

    // Relación: Paciente
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    // Relación: Doctor (Usuario)
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'id_doctor');
    }

    // ESTA ES LA QUE FALTABA: Relación con el Servicio/Tratamiento principal
    public function servicio()
    {
        // Conecta el campo 'id_servicio' de la cita con el modelo Servicio
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }

    // Relación: Detalles (Para tratamientos extra en la misma cita)
    public function detalles()
    {
        return $this->hasMany(CitaDetalle::class, 'id_cita');
    }

    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'id_cita', 'id_cita');
    }

    // Relación: Pagos/Abonos
    public function ingresos()
    {
        return $this->hasMany(IngresoCaja::class, 'id_cita');
    }
}