<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemisionMedica extends Model
{
    protected $table = 'remisiones_medicas';

    protected $fillable = [
        'cliente_id',
        'medico_id',
        'cie10_id',
        'diagnostico',
        'codigo_cie10',
        'objetivos',
        'objetivo_otro',
        'restricciones',
        'vigencia',
        'vigencia_otra_descripcion',
        'estado',
        'fecha_registro'
    ];

    protected $casts = [
        'objetivos' => 'array',
        'fecha_registro' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function cie10()
    {
        return $this->belongsTo(DiagnosticoCie10::class, 'cie10_id');
    }
}
