<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rutina extends Model
{
    use SoftDeletes;

    protected $table = "rutinas";
    
    protected $fillable = [
        'cliente_id',
        'entrenador_id',
        'rutina_maestra_id',
        'trimestre',
        'fecha_inicio',
        'fecha_fin',
        'estado'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
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
        return $this->belongsTo(Entrenador::class, 'entrenador_id');
    }

    /**
     * Relación con RutinaMaestra
     */
    public function rutinaMaestra()
    {
        return $this->belongsTo(RutinaMaestra::class, 'rutina_maestra_id');
    }
}
