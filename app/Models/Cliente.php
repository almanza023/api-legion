<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = "clientes";
   protected $fillable = [
        'nombre',
        'apellidos',
        'tipo_documento',
        'numero_documento',
        'sexo',
        'telefono',
        'correo',
        'fecha_nacimiento',
        'sincronizado'
    ];

    /**
     * Relación con Rutinas
     */
    public function rutinas()
    {
        return $this->hasMany(Rutina::class, 'cliente_id');
    }

    public function membresias()
    {
        return $this->hasMany(ClienteMembresia::class, 'cliente_id');
    }

    public function asistencias()
    {
        return $this->hasMany(AsistenciaCliente::class, 'cliente_id');
    }

    public function remisionesMedicas()
    {
        return $this->hasMany(RemisionMedica::class, 'cliente_id')->orderBy('fecha_registro', 'desc');
    }

    /**
     * Verifica si el cliente tiene al menos una membresía activa vigente.
     */
    public function tieneMembresiaActiva()
    {
        $hoy = date('Y-m-d');
        return $this->membresias()
            ->whereIn('estado', ['activo', 'ACTIVO'])
            ->where('fecha_fin', '>=', $hoy)
            ->exists();
    }
}
