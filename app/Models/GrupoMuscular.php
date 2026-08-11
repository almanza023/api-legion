<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrupoMuscular extends Model
{
    use SoftDeletes;

    protected $table = "grupos_musculares";
    protected $fillable = [
        'nombre',
        'descripcion',
        'estado'
    ];
}
