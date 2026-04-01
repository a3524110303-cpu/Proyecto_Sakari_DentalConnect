<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Clinica;

/**
 * Modelo para las promociones publicitarias.
 *
 * Gestiona los anuncios visuales que se muestran en la aplicación o sitio web.
 *
 * @property int $id_publicidad
 * @property int $id_usuario
 * @property string $titulo
 * @property string|null $descripcion
 * @property string|null $imagen_path
 * @property bool $activo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Publicidad extends Model
{
    use HasFactory;

    protected $table = 'publicidad';
    protected $primaryKey = 'id_publicidad';

    protected $fillable = [
        'id_clinica',
        'id_usuario',
        'titulo',
        'descripcion',
        'imagen_path',
        'activo',
    ];

    // Relación: Una publicidad pertenece a una Clínica
    public function clinica()
    {
        return $this->belongsTo(Clinica::class, 'id_clinica', 'id_clinica');
    }

    // Relación: Una publicidad pertenece a un Usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}