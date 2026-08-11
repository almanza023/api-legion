<?php

use App\Http\Controllers\SmsConfiguracionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('sms-configuraciones', SmsConfiguracionController::class);
