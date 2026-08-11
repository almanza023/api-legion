<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use SoftDeletes;

    protected $table = "roles";
    protected $fillable = [
        'nombre',
        'descripcion',
        'estado'
    ];
}
