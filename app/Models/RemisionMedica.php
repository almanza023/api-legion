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

    /**
     * Determina si la remisión médica se encuentra actualmente vigente.
     */
    public function esVigente(): bool
    {
        // 1. Si el estado es explícitamente vencida, inactiva o anulada
        if ($this->estado && in_array(strtolower($this->estado), ['vencida', 'anulada', 'inactiva'])) {
            return false;
        }

        if (!$this->fecha_registro) {
            return true;
        }

        $fechaRegistro = \Carbon\Carbon::parse($this->fecha_registro)->startOfDay();
        $hoy = \Carbon\Carbon::now()->startOfDay();

        $vigenciaStr = strtolower(trim((string)$this->vigencia));

        $meses = 0;
        if (str_contains($vigenciaStr, '12') || str_contains($vigenciaStr, '1_ano') || str_contains($vigenciaStr, '1 año') || str_contains($vigenciaStr, '1 ano')) {
            $meses = 12;
        } elseif (str_contains($vigenciaStr, '6')) {
            $meses = 6;
        } elseif (str_contains($vigenciaStr, '3')) {
            $meses = 3;
        } elseif (str_contains($vigenciaStr, '1')) {
            $meses = 1;
        }

        if ($meses > 0) {
            $fechaVencimiento = $fechaRegistro->copy()->addMonths($meses);
            return $hoy->lessThanOrEqualTo($fechaVencimiento);
        }

        // Si no es posible extraer meses específicos, nos guiamos por la propiedad estado
        return !in_array(strtolower((string)$this->estado), ['vencida', 'anulada', 'inactiva']);
    }
}
