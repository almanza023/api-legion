<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespuestaPreguntaCliente extends Model
{
    protected $table = "respuestas_preguntas_cliente";
    
    protected $fillable = [
        'cliente_id',
        'pregunta_id',
        'respuesta',
        'fecha_respuesta'
    ];

    protected $casts = [
        'respuesta' => 'boolean',
        'fecha_respuesta' => 'datetime',
    ];

    /**
     * Relación con Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con Pregunta
     */
    public function pregunta()
    {
        return $this->belongsTo(PreguntaEvaluacion::class, 'pregunta_id');
    }
}
