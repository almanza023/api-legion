<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    protected $table = 'medicos';

    protected $fillable = [
        'nombre',
        'especialidad',
        'registro_retheus',
        'cedula',
        'correo',
        'telefono',
        'firma_path',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function remisionesMedicas()
    {
        return $this->hasMany(RemisionMedica::class, 'medico_id');
    }
}
