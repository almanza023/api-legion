<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsEnvioLog extends Model
{
    protected $table = 'sms_envio_logs';

    protected $fillable = [
        'telefono',
        'mensaje',
        'sms_configuracion_id',
        'request_payload',
        'response_payload',
        'http_status',
        'se_envio',
        'error',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'se_envio' => 'boolean',
    ];

    public function smsConfiguracion()
    {
        return $this->belongsTo(SmsConfiguracion::class, 'sms_configuracion_id');
    }
}
