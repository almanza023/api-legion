<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\PlantillaPlanEntrenamientoDetalle;

class RutinaGenerica extends Model
{
    use SoftDeletes;

    protected $table = 'rutinas_genericas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'objetivo',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'trimestre',
    ];

    /**
     * Detalles asociados a la plantilla.
     */
    public function detalles()
    {
        return $this->hasMany(DetalleRutinaGenerica::class, 'rutina_generica_id');
    }
}
