<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::controller(DashboardController::class)->group(function () {
    Route::get('dashboard', 'index');
    Route::get('dashboard/top-asistencias-mes', 'topAsistenciasMes');
    Route::post('dashboard/top-asistencias-mes', 'topAsistenciasMes');
    Route::get('dashboard/top-progreso-mes', 'topProgresoMes');
    Route::post('dashboard/top-progreso-mes', 'topProgresoMes');
    Route::get('dashboard/proyeccion-flujo-caja', [\App\Http\Controllers\ClienteMembresiaAsynController::class, 'proyeccionFlujoCaja']);
    Route::post('dashboard/proyeccion-flujo-caja', [\App\Http\Controllers\ClienteMembresiaAsynController::class, 'proyeccionFlujoCaja']);
});