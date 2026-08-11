<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleRutinaMaestra extends Model
{
    use SoftDeletes;

    protected $table = "detalle_rutina_maestra";
    protected $fillable = [
        'rutina_maestra_id',
        'ejercicio_id',
        'dia_semana',
        'nombre_dia_personalizado',
        'series_estandar',
        'repeticiones_estandar',
        'tiempo_descanso_estandar_seg',
        'orden_ejecucion',
        'notas',
        'estado',
        'mesociclo',
        'peso'
    ];

    /**
     * Relación con RutinaMaestra
     */
    public function rutinaMaestra()
    {
        return $this->belongsTo(RutinaMaestra::class, 'rutina_maestra_id');
    }

    /**
     * Relación con Ejercicio
     */
    public function ejercicio()
    {
        return $this->belongsTo(Ejercicio::class, 'ejercicio_id');
    }

    /**
     * Relación con ProgresoRutinaDetalle
     */
    public function progresos()
    {
        return $this->hasMany(ProgresoRutinaDetalle::class, 'detalle_rutina_maestra_id');
    }

    /**
     * Obtener nombre del día de la semana
     */
    public function getNombreDiaAttribute()
    {
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo'
        ];
        return $dias[$this->dia_semana] ?? '';
    }
}
