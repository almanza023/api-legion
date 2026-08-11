<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entrenador extends Model
{
    use SoftDeletes;

    protected $table = "entrenadores";
    protected $fillable = [
        'nombres',
        'apellidos',
        'documento_identidad',
        'telefono',
        'correo',
        'fecha_nacimiento',
        'especialidad',
        'estado'
    ];

    /**
     * Relación con Rutinas
     */
    public function rutinas()
    {
        return $this->hasMany(Rutina::class, 'entrenador_id');
    }
}
