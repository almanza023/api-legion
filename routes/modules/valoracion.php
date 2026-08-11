<?php

use App\Http\Controllers\ValoracionController;
use App\Http\Controllers\ValoracionPdfController;
use Illuminate\Support\Facades\Route;

// Ruta para generar PDF de valoración
Route::get('valoraciones/{id}/pdf', [ValoracionPdfController::class, 'generarPdf']);

// Rutas personalizadas para obtener valoraciones por diferentes criterios
Route::post('valoraciones/por-cliente', [ValoracionController::class, 'valoracionesPorCliente']);
Route::post('valoraciones/ultima-cliente', [ValoracionController::class, 'ultimaValoracionCliente']);
Route::post('valoraciones/por-fecha', [ValoracionController::class, 'valoracionesPorFecha']);
Route::post('valoraciones/por-entrenador', [ValoracionController::class, 'valoracionesPorEntrenador']);
Route::post('valoraciones/comparar-avances', [ValoracionController::class, 'compararAvances']);

// Rutas CRUD para valoraciones
Route::apiResource('valoraciones', ValoracionController::class);
