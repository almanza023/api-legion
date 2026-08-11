<?php

use App\Http\Controllers\PreguntaEvaluacionController;
use App\Http\Controllers\RespuestaPreguntaClienteController;
use Illuminate\Support\Facades\Route;

// Rutas para Preguntas de Evaluación (CRUD)
Route::apiResource('preguntas-evaluacion', PreguntaEvaluacionController::class);

// Rutas para Respuestas de Preguntas
Route::prefix('respuestas-preguntas')->group(function () {
    // Guardar o actualizar una respuesta
    Route::post('guardar', [RespuestaPreguntaClienteController::class, 'guardarRespuesta']);
    
    // Guardar evaluación completa (múltiples respuestas)
    Route::post('guardar-evaluacion', [RespuestaPreguntaClienteController::class, 'guardarEvaluacionCompleta']);
    
    // Obtener respuestas de un cliente
    Route::get('cliente/{clienteId}', [RespuestaPreguntaClienteController::class, 'obtenerPorCliente']);
    
    // Obtener resumen de respuestas de un cliente
    Route::get('cliente/{clienteId}/resumen', [RespuestaPreguntaClienteController::class, 'obtenerResumenPorCliente']);
    
    // Eliminar una respuesta
    Route::delete('{id}', [RespuestaPreguntaClienteController::class, 'destroy']);
});
