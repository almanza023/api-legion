<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreguntaEvaluacion extends Model
{
    protected $table = "preguntas_evaluacion";
    
    protected $fillable = [
        'titulo',
        'descripcion',
        'orden',
        'activa'
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    /**
     * Relación con respuestas de clientes
     */
    public function respuestas()
    {
        return $this->hasMany(RespuestaPreguntaCliente::class, 'pregunta_id');
    }
}
