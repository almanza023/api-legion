<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObjetivoRemision extends Model
{
    protected $table = 'objetivos_remision';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
