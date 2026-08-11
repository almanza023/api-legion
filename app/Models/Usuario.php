<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = "usuarios";
    protected $fillable = [
        'nombre',
        'apellidos',
        'tipo_documento',
        'numero_documento',
        'sexo',
        'telefono',
        'correo',
        'fecha_nacimiento',
        'sincronizado'
    ];

    
}
