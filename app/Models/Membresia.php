<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membresia extends Model
{
    use SoftDeletes;

    protected $table = "membresias";
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'duracion_dias',
        'estado'
    ];

    public function clientes()
    {
        return $this->hasMany(ClienteMembresia::class, 'membresia_id');
    }
}
