<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsConfiguracion extends Model
{
    use SoftDeletes;

    protected $table = 'sms_configuraciones';

    protected $fillable = [
        'dias',
        'mensaje',
        'estado',
    ];
}
