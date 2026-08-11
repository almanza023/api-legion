<?php

use App\Http\Controllers\AsistenciaClienteController;
use Illuminate\Support\Facades\Route;

Route::apiResource('asistencias-clientes', AsistenciaClienteController::class);
Route::get('asistencias-clientes-by-cliente/{cliente_id}', [AsistenciaClienteController::class, 'getByCliente']);
Route::get('asistencias-clientes-by-fecha/{fecha}', [AsistenciaClienteController::class, 'getByFecha']);
Route::post('asistencias-clientes-filtrar', [AsistenciaClienteController::class, 'filtrar']);
Route::get('asistencias-clientes/top-asistencias-mes', [\App\Http\Controllers\DashboardController::class, 'topAsistenciasMes']);
Route::post('asistencias-clientes/top-asistencias-mes', [\App\Http\Controllers\DashboardController::class, 'topAsistenciasMes']);

