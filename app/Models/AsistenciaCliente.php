<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsistenciaCliente extends Model
{
    use SoftDeletes;

    protected $table = 'asistencia_clientes';

    protected $fillable = [
        'cliente_id',
        'fecha_asistencia',
        'observaciones',
        'es_valida',
    ];

    protected $casts = [
        'fecha_asistencia' => 'date',
        'es_valida' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
