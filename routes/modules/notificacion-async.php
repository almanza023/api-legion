<?php

use App\Http\Controllers\NoitificacioControoller;
use App\Http\Middleware\IsUserAuth;
use Illuminate\Support\Facades\Route;

Route::middleware([IsUserAuth::class])->group(function () {
	Route::get('notificaciones/sms/logs', [NoitificacioControoller::class, 'historial']);
});

Route::get('notificaciones/sms/membresias-por-vencer', [NoitificacioControoller::class, 'enviarSmsMembresiasPorVencer']);
Route::post('notificaciones/sms', [NoitificacioControoller::class, 'enviarSms']);
