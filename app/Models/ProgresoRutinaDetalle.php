<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgresoRutinaDetalle extends Model
{
    protected $table = "progreso_rutina_detalle";
    
    protected $fillable = [
        'detalle_rutina_maestra_id',
        'fecha_entrenamiento',
        'comentarios',
        'estado'
    ];

    protected $casts = [
        'fecha_entrenamiento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación con DetalleRutinaMaestra
     */
    public function detalleRutinaMaestra()
    {
        return $this->belongsTo(DetalleRutinaMaestra::class, 'detalle_rutina_maestra_id');
    }

    /**
     * Relación con RutinaMaestra a través de DetalleRutinaMaestra
     */
    public function rutinaMaestra()
    {
        return $this->belongsTo(RutinaMaestra::class, 'detalle_rutina_maestra_id', 'id')
            ->through('detalleRutinaMaestra');
    }
}
