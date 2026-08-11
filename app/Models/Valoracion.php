<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Valoracion extends Model
{
    protected $table = "valoraciones";
    
    public $timestamps = false;

    protected $fillable = [
        'cliente_id',
        'entrenador_id',
        'imc_clasificacion_id',
        'hombros',
        'pecho',
        'biceps',
        'antebrazos',
        'cintura',
        'cadera',
        'pierna',
        'medio_pierna',
        'pantorrilla',
        'cuello',
        'espalda',
        'peso',
        'estatura',
        'edad',
        'sexo',
        'imc',
        'frecuencia1',
        'frecuencia2',
        'frecuencia3',
        'push_up',
        'abdominales',
        'perimetro_abdominal',
        'brazo_izquierdo',
        'brazo_derecho',
        'pierna_izquierda',
        'pierna_derecha',
        'porcentaje_grasa',
        'porcentaje_musculo',
        'grasa_visceral',
        'masa_musculo_esqueletica',
        'hipertension',
        'fuma',
        'lesion',
        'tiempo_sin_entrenar',
        'toma_foto',
        'descripcion_lesion',
        'observaciones',
        'objetivo',
        'fecha_registro',
        'fecha_inicio',
        'fecha_fin',
        'trimestre',
        'tipo_valoracion'
    ];

    protected $casts = [
        'hombros' => 'decimal:2',
        'pecho' => 'decimal:2',
        'biceps' => 'decimal:2',
        'antebrazos' => 'decimal:2',
        'cintura' => 'decimal:2',
        'cadera' => 'decimal:2',
        'pierna' => 'decimal:2',
        'medio_pierna' => 'decimal:2',
        'pantorrilla' => 'decimal:2',
        'cuello' => 'decimal:2',
        'espalda' => 'decimal:2',
        'peso' => 'decimal:2',
        'estatura' => 'decimal:2',
        'imc' => 'decimal:2',
        'perimetro_abdominal' => 'decimal:2',
        'brazo_izquierdo' => 'decimal:2',
        'brazo_derecho' => 'decimal:2',
        'pierna_izquierda' => 'decimal:2',
        'pierna_derecha' => 'decimal:2',
        'porcentaje_grasa' => 'decimal:2',
        'porcentaje_musculo' => 'decimal:2',
        'grasa_visceral' => 'decimal:2',
        'masa_musculo_esqueletica' => 'decimal:2',
        'hipertension' => 'boolean',
        'fuma' => 'boolean',
        'lesion' => 'boolean',
        'toma_foto' => 'boolean',
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
     * Relación con RutinaMaestra
     */
    public function rutinasMaestras()
    {
        return $this->hasMany(RutinaMaestra::class, 'valoracion_id');
    }

    /**
     * Calcular IMC automáticamente
     */
    public function calcularImc()
    {
        if($this->peso && $this->estatura && $this->estatura > 0) {
            $this->imc = $this->peso / ($this->estatura * $this->estatura);
            return round($this->imc, 2);
        }
        return null;
    }
}
