<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\IsUserAuth;
use Illuminate\Support\Facades\Route;

Route::get('health', [HealthController::class, 'check']);
Route::post('login', [AuthController::class, 'login']);
require __DIR__ . '/modules/member-async.php';
require __DIR__ . '/modules/public-cliente.php';
require __DIR__ . '/modules/notificacion-async.php';

Route::middleware([IsUserAuth::class])->group(function () {
    require __DIR__ . '/modules/auth.php';
    require __DIR__ . '/modules/user.php';
    require __DIR__ . '/modules/member.php';
    require __DIR__ . '/modules/dashboard.php';
    require __DIR__ . '/modules/entrenador-async.php';
    require __DIR__ . '/modules/rol-async.php';
    require __DIR__ . '/modules/membresia-async.php';
    require __DIR__ . '/modules/cliente-membresia-async.php';
    require __DIR__ . '/modules/grupo-muscular-async.php';
    require __DIR__ . '/modules/maquina-async.php';
    require __DIR__ . '/modules/mantenimiento-maquina-async.php';
    require __DIR__ . '/modules/video-async.php';
    require __DIR__ . '/modules/ejercicio-async.php';
    require __DIR__ . '/modules/empresa-async.php';
    require __DIR__ . '/modules/rutina-maestra-async.php';
    require __DIR__ . '/modules/progreso-rutina-detalle.php';
    require __DIR__ . '/modules/imc-clasificacion.php';
    require __DIR__ . '/modules/valoracion.php';
    require __DIR__ . '/modules/valoracion_v2.php';
    require __DIR__ . '/modules/rutina.php';
    require __DIR__ . '/modules/reporte.php';
    require __DIR__ . '/modules/plantillas-planes.php';
    require __DIR__ . '/modules/publicidad-async.php';
    require __DIR__ . '/modules/usuario-async.php';
    require __DIR__ . '/modules/sms-configuracion-async.php';
    require __DIR__ . '/modules/preguntas-evaluacion.php';
    require __DIR__ . '/modules/asistencia-cliente-async.php';
    require __DIR__ . '/modules/remision-medica-async.php';
    require __DIR__ . '/modules/expediente-async.php';

});
