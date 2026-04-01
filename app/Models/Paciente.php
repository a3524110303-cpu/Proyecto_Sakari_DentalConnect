<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SignoVital;
use App\Models\EvolucionTratamiento;
use App\Models\SeguimientoClinico;

/**
 * Modelo principal del paciente.
 *
 * Centraliza toda la información personal, médica y de contacto del paciente.
 * Gestiona relaciones con citas, historial médico, odontogramas, archivos y más.
 *
 * @property int $id_paciente
 * @property int $id_usuario
 * @property string $nombre
 * @property string $apellido_paterno
 * @property string|null $apellido_materno
 * @property string|null $telefono
 * @property string|null $correo_electronico
 * @property \Illuminate\Support\Carbon|null $fecha_nacimiento
 * @property string $sexo
 * @property string|null $ocupacion
 * @property string|null $tipo_sangre
 * @property float|null $peso
 * @property string|null $calle
 * @property string|null $num_exterior
 * @property string|null $num_interior
 * @property string|null $colonia
 * @property string|null $municipio
 * @property string|null $alergias
 * @property string|null $alergias_criticas
 * @property string|null $enfermedades_cronicas
 * @property bool $is_active
 * @property int|null $id_contacto_emergencia
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @property-read string $nombre_completo
 */
class Paciente extends Model
{
    use HasFactory;

    // 1. Configuración de Tabla
    protected $table = 'pacientes';
    protected $primaryKey = 'id_paciente';

    // 2. Campos que se pueden llenar masivamente (Mass Assignment)
    protected $fillable = [
        'id_clinica',
        'id_usuario',            // Relación con el login (App Móvil)
        'created_by_doctor_id',  // Relación con el doctor que lo creó
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'correo_electronico',
        'fecha_nacimiento',
        'sexo',                  // 'M', 'F', 'O'
        'ocupacion',
        'tipo_sangre',           // Valor actual (texto)
        'peso',                  // Valor actual (decimal)

        // Dirección
        'direccion',
        'calle',
        'num_exterior',
        'num_interior',
        'colonia',
        'municipio',

        // Datos Médicos Rápidos (Texto libre)
        'alergias',              // Columna real en BD: texto libre de alergias
        'alergias_criticas',     // Alias alternativo (por compatibilidad)
        'enfermedades_cronicas',
        'is_active',

        // FK
        'id_contacto_emergencia',
    ];

    // 3. Conversión de tipos (Casting)
    protected $casts = [
        'fecha_nacimiento' => 'date', // Importante para usar $paciente->fecha_nacimiento->age
        'peso' => 'decimal:2',
    ];

    protected $appends = ['full_foto_url', 'foto_url'];

    // 4. Accessor: Obtener Nombre Completo fácilmente
    // Uso: $paciente->nombre_completo
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}";
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
        if (str_starts_with($ruta, 'perfiles_pacientes/')) {
            return url('/api/paciente/foto/' . basename($ruta));
        }

        return route('storage.file', ['path' => $ruta]);
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (empty($this->foto_perfil)) {
            return null;
        }

        return $this->full_foto_url;
    }

    // ==========================================================
    // RELACIONES (DEFINITIVAS)
    // ==========================================================

    // Relación con Usuario del Sistema (Login)
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    // Relación con Contacto de Emergencia
    public function contactoEmergencia()
    {
        return $this->belongsTo(ContactoEmergencia::class, 'id_contacto_emergencia', 'id_contacto_emergencia');
    }

    // Relación: Un paciente tiene muchas Citas
    public function citas()
    {
        return $this->hasMany(Cita::class, 'id_paciente', 'id_paciente');
    }

    // Relación: Archivos (PDFs, RX, Fotos)
    public function archivos()
    {
        return $this->hasMany(Archivo::class, 'id_paciente', 'id_paciente');
    }

    // Relación: Reviews (Calificaciones dejadas por el paciente)
    public function reviews()
    {
        return $this->hasMany(Review::class, 'id_paciente', 'id_paciente');
    }

    // ==========================================================
    // RELACIONES DE HISTORIAL MÉDICO
    // ==========================================================

    // Historial de Peso (Evolución)
    public function historialPeso()
    {
        return $this->hasMany(PacientePeso::class, 'id_paciente', 'id_paciente')->orderBy('fecha_registro', 'desc');
    }

    // Historial de Tipo de Sangre (Bitácora de cambios)
    public function historialTipoSangre()
    {
        return $this->hasMany(PacienteTipoSangre::class, 'id_paciente', 'id_paciente');
    }

    // Relación Muchos a Muchos: Alergias (Catálogo)
    // Tabla Pivote: pacientes_alergias
    public function alergias()
    {
        return $this->belongsToMany(
            CatalogoAlergia::class,
            'pacientes_alergias',
            'id_paciente',
            'id_alergia'
        );
    }

    // Relación Muchos a Muchos: Enfermedades Crónicas (Catálogo)
    // Tabla Pivote: pacientes_enfermedades_cronicas
    public function enfermedades()
    {
        return $this->belongsToMany(
            CatalogoEnfermedadCronica::class,
            'pacientes_enfermedades_cronicas',
            'id_paciente',
            'id_enfermedad_cronica'
        );
    }

    // ==========================================================
    // RELACIONES HISTORIAL CLÍNICO (antes tablas huérfanas)
    // ==========================================================

    /**
     * Historial de signos vitales del paciente.
     * Tabla: historial_signos_vitales | FK: paciente_id
     */
    public function signosVitales()
    {
        return $this->hasMany(SignoVital::class, 'paciente_id', 'id_paciente')
            ->orderBy('fecha_registro', 'desc');
    }

    /**
     * Evoluciones / notas SOAP del tratamiento.
     * Tabla: evolucion_tratamiento | FK: id_paciente
     * ⚠ La tabla NO tiene created_at, se ordena por id_evolucion.
     */
    public function evoluciones()
    {
        return $this->hasMany(EvolucionTratamiento::class, 'id_paciente', 'id_paciente')
            ->orderBy('id_evolucion', 'desc');
    }

    /**
     * Historial odontograma del paciente.
     * ⚠ La tabla NO tiene timestamps — PK real es id_odontograma.
     */
    public function odontogramas()
    {
        return $this->hasMany(Odontograma::class, 'id_paciente', 'id_paciente')
            ->orderBy('id_odontograma', 'desc');
    }

    // NOTA: seguimiento_clinico NO tiene id_paciente — tiene FK a id_cita.
    // Se accede a través de: $paciente->citas->flatMap->seguimientos

}
