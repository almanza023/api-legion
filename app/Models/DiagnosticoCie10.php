<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticoCie10 extends Model
{
    protected $table = 'diagnosticos_cie10';

    protected $fillable = [
        'codigo',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
