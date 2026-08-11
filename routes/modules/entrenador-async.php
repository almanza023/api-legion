<?php

use App\Http\Controllers\EntrenadorAsynController;
use Illuminate\Support\Facades\Route;

// Ruta personalizada para buscar por documento (POST)
Route::post('entrenadores/por-documento', [EntrenadorAsynController::class, 'porDocumento']);

Route::apiResource('entrenadores', EntrenadorAsynController::class);

Route::controller(EntrenadorAsynController::class)->group(function () {
    Route::get('trainers/documento/{documento_identidad}', 'getByDocumento');
});
