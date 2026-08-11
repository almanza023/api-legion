<?php

use App\Http\Controllers\RutinaAsynController;
use Illuminate\Support\Facades\Route;

// Rutas personalizadas para obtener rutinas por diferentes criterios
Route::post('rutinas/por-entrenador', [RutinaAsynController::class, 'rutinasPorEntrenador']);
Route::post('rutinas/por-cliente', [RutinaAsynController::class, 'rutinasPorCliente']);

// Rutas CRUD para rutinas
Route::apiResource('rutinas', RutinaAsynController::class);
