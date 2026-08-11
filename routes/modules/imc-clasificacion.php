<?php

use App\Http\Controllers\ImcClasificacionController;
use Illuminate\Support\Facades\Route;

// Ruta personalizada para obtener clasificación por valor de IMC
Route::post('imc-clasificaciones/por-imc', [ImcClasificacionController::class, 'obtenerPorImc']);

// Rutas CRUD para clasificaciones de IMC
Route::apiResource('imc-clasificaciones', ImcClasificacionController::class);
