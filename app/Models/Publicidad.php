<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publicidad extends Model
{
    use HasFactory;

    protected $table = 'publicidad';

    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function detalles()
    {
        return $this->hasMany(PublicidadDetalle::class, 'publicidad_id')->orderBy('orden');
    }

    public function scopeVigentes($query)
    {
        $hoy = now()->toDateString();
        return $query->where('estado', 'activo')
                     ->where('fecha_inicio', '<=', $hoy)
                     ->where('fecha_fin', '>=', $hoy);
    }
}
