<?php

use App\Http\Controllers\PlantillaPlanEntrenamientoController;
use Illuminate\Support\Facades\Route;

Route::get('/plantillas-planes', [PlantillaPlanEntrenamientoController::class, 'index']);
Route::post('/plantillas-planes', [PlantillaPlanEntrenamientoController::class, 'store']);
Route::get('/plantillas-planes/{id}', [PlantillaPlanEntrenamientoController::class, 'show']);
Route::put('/plantillas-planes/{id}', [PlantillaPlanEntrenamientoController::class, 'update']);
Route::delete('/plantillas-planes/{id}', [PlantillaPlanEntrenamientoController::class, 'destroy']);

Route::post('/plantillas-planes/{id}/detalles', [PlantillaPlanEntrenamientoController::class, 'addDetalle']);
Route::put('/plantillas-planes/{plantilla_id}/detalles/{detalle_id}', [PlantillaPlanEntrenamientoController::class, 'updateDetalle']);
Route::delete('/plantillas-planes/{plantilla_id}/detalles/{detalle_id}', [PlantillaPlanEntrenamientoController::class, 'deleteDetalle']);
