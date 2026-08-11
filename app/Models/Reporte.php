<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reporte extends Model
{
    use SoftDeletes;

    protected $table = "reportes";
    
    protected $fillable = [
        'nombre_reporte',
        'categoria',
        'parametros',
        'consulta_sql',
        'descripcion'
    ];

    protected $dates = ['deleted_at'];
}
