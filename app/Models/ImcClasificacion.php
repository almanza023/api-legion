<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImcClasificacion extends Model
{
    protected $table = "imc_clasificaciones";
    
    public $timestamps = false;

    protected $fillable = [
        'rango_min',
        'rango_max',
        'nombre',
        'descripcion'
    ];

    protected $casts = [
        'rango_min' => 'decimal:2',
        'rango_max' => 'decimal:2'
    ];
}
