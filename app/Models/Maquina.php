<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maquina extends Model
{
    use SoftDeletes;

    protected $table = "maquinas";
    protected $fillable = [
        'codigo_maquina',
        'nombre',
        'marca',
        'modelo',
        'fecha_adquisicion',
        'estado',
        'ultimo_mantenimiento',
        'ubicacion'
    ];
}
