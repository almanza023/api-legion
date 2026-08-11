<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValoracionV2 extends Model
{
    protected $table = 'valoraciones_v2';
    
    public $timestamps = true;

    protected $fillable = [
        'cliente_id',
        'entrenador_id',
        'imc_clasificacion_id',
        'convenio',
        'nombre_empresa',
        'estatura',
        'peso',
        'edad',
        'sexo',
        'pecho',
        'brazo_izquierdo',
        'brazo_derecho',
        'perimetro_abdominal',
        'pierna_izquierda',
        'pierna_derecha',
        'frecuencia1',
        'frecuencia2',
        'frecuencia3',
        'push_up',
        'abdominales',
        'imc',
        'porcentaje_grasa',
        'porcentaje_musculo',
        'grasa_visceral',
        'masa_musculo_esqueletica',
        'hipertension',
        'fuma',
        'lesion',
        'tension_arterial',
        'antecedentes_cardio_personales',
        'antecedentes_cardio_familiares',
        'colesterol_alto',
        'trigliceridos_altos',
        'toma_medicamento',
        'toma_foto',
        'descripcion_lesion',
        'observaciones',
        'plan_tipo',
        'plan_hombre_bp',
        'plan_hombre_gm',
        'plan_mujer_bp',
        'plan_mujer_gm',
        'parq_q1',
        'parq_q2',
        'parq_q3',
        'parq_q4',
        'parq_q5',
        'parq_q6',
        'parq_q7',
        'fecha_registro',
        'fecha_inicio',
        'fecha_fin',
        'trimestre',
        'tipo_valoracion'
    ];

    protected $casts = [
        'convenio' => 'boolean',
        'estatura' => 'decimal:2',
        'peso' => 'decimal:2',
        'pecho' => 'decimal:2',
        'brazo_izquierdo' => 'decimal:2',
        'brazo_derecho' => 'decimal:2',
        'perimetro_abdominal' => 'decimal:2',
        'pierna_izquierda' => 'decimal:2',
        'pierna_derecha' => 'decimal:2',
        'imc' => 'decimal:2',
        'porcentaje_grasa' => 'decimal:2',
        'porcentaje_musculo' => 'decimal:2',
        'grasa_visceral' => 'decimal:2',
        'masa_musculo_esqueletica' => 'decimal:2',
        'hipertension' => 'boolean',
        'fuma' => 'boolean',
        'lesion' => 'boolean',
        'antecedentes_cardio_personales' => 'boolean',
        'antecedentes_cardio_familiares' => 'boolean',
        'colesterol_alto' => 'boolean',
        'trigliceridos_altos' => 'boolean',
        'toma_medicamento' => 'boolean',
        'toma_foto' => 'boolean',
        'plan_hombre_bp' => 'boolean',
        'plan_hombre_gm' => 'boolean',
        'plan_mujer_bp' => 'boolean',
        'plan_mujer_gm' => 'boolean',
        'parq_q1' => 'boolean',
        'parq_q2' => 'boolean',
        'parq_q3' => 'boolean',
        'parq_q4' => 'boolean',
        'parq_q5' => 'boolean',
        'parq_q6' => 'boolean',
        'parq_q7' => 'boolean',
        'fecha_registro' => 'datetime',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date'
    ];

    /**
     * Relación con Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con Entrenador
     */
    public function entrenador()
    {
        return $this->belongsTo(Entrenador::class, 'entrenador_id', 'id');
    }

    /**
     * Relación con ImcClasificacion
     */
    public function imcClasificacion()
    {
        return $this->belongsTo(ImcClasificacion::class, 'imc_clasificacion_id');
    }

    /**
     * Calcular IMC automáticamente
     */
    public function calcularImc()
    {
        if ($this->peso && $this->estatura && $this->estatura > 0) {
            $this->imc = $this->peso / ($this->estatura * $this->estatura);
            return round($this->imc, 2);
        }
        return null;
    }
}
