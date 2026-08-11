<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ejercicio extends Model
{
    use SoftDeletes;

    protected $table = "ejercicios";
    protected $fillable = [
        'video_id',
        'grupo_muscular_id',
        'maquina_id',
        'nombre',
        'descripcion',
        'series',
        'repeticiones',
        'tiempo_descanso_seg',
        'dificultad',
        'estado',
        'es_estiramiento',
        'tipo'
    ];

    protected $casts = [
        'es_estiramiento' => 'boolean'
    ];

    /**
     * Relación con Video
     */
    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    /**
     * Relación con GrupoMuscular
     */
    public function grupoMuscular()
    {
        return $this->belongsTo(GrupoMuscular::class, 'grupo_muscular_id');
    }

    /**
     * Relación con Maquina
     */
    public function maquina()
    {
        return $this->belongsTo(Maquina::class, 'maquina_id');
    }
}
