<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MantenimientoMaquina extends Model
{
    use SoftDeletes;

    protected $table = "mantenimiento_maquinas";
    protected $fillable = [
        'maquina_id',
        'fecha_programada',
        'fecha_realizada',
        'tipo_mantenimiento',
        'descripcion',
        'costo',
        'estado'
    ];

    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
}
