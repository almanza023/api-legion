<?php

use App\Http\Controllers\ValoracionV2Controller;
use App\Http\Controllers\ValoracionV2PdfController;
use Illuminate\Support\Facades\Route;

// Ruta para generar PDF de valoración v2 (SST-F-15 + PAR-Q)
Route::get('valoraciones-v2/{id}/pdf', [ValoracionV2PdfController::class, 'generarPdf']);

// Rutas personalizadas para obtener valoraciones v2 por diferentes criterios
Route::post('valoraciones-v2/por-cliente', [ValoracionV2Controller::class, 'valoracionesPorCliente']);
Route::post('valoraciones-v2/ultima-cliente', [ValoracionV2Controller::class, 'ultimaValoracionCliente']);
Route::post('valoraciones-v2/por-fecha', [ValoracionV2Controller::class, 'valoracionesPorFecha']);
Route::post('valoraciones-v2/por-entrenador', [ValoracionV2Controller::class, 'valoracionesPorEntrenador']);
Route::post('valoraciones-v2/comparar-avances', [ValoracionV2Controller::class, 'compararAvances']);

// Rutas CRUD para valoraciones v2
Route::apiResource('valoraciones-v2', ValoracionV2Controller::class);
