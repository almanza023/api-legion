<?php

use App\Http\Controllers\ProgresoRutinaDetalleController;
use Illuminate\Support\Facades\Route;

// Rutas personalizadas para obtener progresos por diferentes criterios
Route::post('progresos-rutina/por-cliente', [ProgresoRutinaDetalleController::class, 'progresosPorCliente']);
Route::post('progresos-rutina/por-detalle', [ProgresoRutinaDetalleController::class, 'progresosPorDetalle']);
Route::post('progresos-rutina/por-fecha', [ProgresoRutinaDetalleController::class, 'progresosPorFecha']);
Route::get('progresos-rutina/top-progreso-mes', [\App\Http\Controllers\DashboardController::class, 'topProgresoMes']);
Route::post('progresos-rutina/top-progreso-mes', [\App\Http\Controllers\DashboardController::class, 'topProgresoMes']);


// Rutas CRUD para progresos de rutina
Route::apiResource('progresos-rutina', ProgresoRutinaDetalleController::class);
