<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleRutinaGenerica extends Model
{
    use SoftDeletes;

    protected $table = 'detalle_rutina_generica';

    protected $fillable = [
        'rutina_generica_id',
        'ejercicio_id',
        'dia_semana',
        'series_estandar',
        'repeticiones_estandar',
        'tiempo_descanso_estandar_seg',
        'orden_ejecucion',
        'notas',
        'estado',
        'mesociclo',
        'peso',
    ];

    /**
     * Plantilla maestra asociada.
     */
    public function plantilla()
    {
        return $this->belongsTo(RutinaGenerica::class, 'rutina_generica_id');
    }

    /**
     * Ejercicio asociado al detalle.
     */
    public function ejercicio()
    {
        return $this->belongsTo(Ejercicio::class, 'ejercicio_id');
    }
}
