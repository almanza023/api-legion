<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteMembresia extends Model
{
    use SoftDeletes;

    protected $table = "cliente_membresia";
    protected $fillable = [
        'cliente_id',
        'membresia_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'precio_pagado',
        'observaciones'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'precio_pagado' => 'decimal:2'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
    }

public function user()
{
    return $this->hasOneThrough(
        User::class,
        Cliente::class,
        'id', // Foreign key on Cliente table
        'documento_identidad', // Foreign key on User table
        'cliente_id', // Local key on ClienteMembresia table
        'numero_documento' // Local key on Cliente table
    );
}

}
