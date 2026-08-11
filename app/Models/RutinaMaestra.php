<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RutinaMaestra extends Model
{
    use SoftDeletes;

    protected $table = "rutinas_maestras";
    protected $appends = ['cliente_info', 'entrenador_info'];
    protected $fillable = [
        'nombre',
        'descripcion',
        'objetivo',
        'estado',
        'cliente_id',
        'entrenador_id',
        'valoracion_id',
        'fecha_inicio',
        'fecha_fin',
        'trimestre'
    ];

    /**
     * Relación con DetalleRutinaMaestra
     */
    public function detalles()
    {
        return $this->hasMany(DetalleRutinaMaestra::class, 'rutina_maestra_id');
    }

    /**
     * Obtener detalles agrupados por día
     */
    public function detallesPorDia()
    {
        return $this->detalles()->orderBy('dia_semana')->orderBy('orden_ejecucion');
    }

    /**
     * Relación con Cliente
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con Entrenador (User)
     */
    public function entrenador()
    {
        return $this->belongsTo(Entrenador::class, 'entrenador_id');
    }

    /**
     * Relación con Valoracion
     */
    public function valoracion()
    {
        return $this->belongsTo(Valoracion::class, 'valoracion_id');
    }

    /**
     * Información resumida del cliente asociada a la rutina
     */
    public function getClienteInfoAttribute()
    {
        $cliente = $this->relationLoaded('cliente') ? $this->cliente : $this->cliente()->first();

        if (!$cliente) {
            return null;
        }

        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'apellidos' => $cliente->apellidos,
            'telefono' => $cliente->telefono,
            'correo' => $cliente->correo,
        ];
    }

    /**
     * Información resumida del entrenador asociada a la rutina
     */
    public function getEntrenadorInfoAttribute()
    {
        $entrenador = $this->relationLoaded('entrenador') ? $this->entrenador : $this->entrenador()->first();

        if (!$entrenador) {
            return null;
        }

        return [
            'id' => $entrenador->id,
            'nombre' => $entrenador->nombres ?? $entrenador->name,
            'apellidos' => $entrenador->apellidos ?? null,
            'email' => $entrenador->correo ?? $entrenador->email,
            'documento_identidad' => $entrenador->documento_identidad,
        ];
    }
}
