<?php

use App\Http\Controllers\RutinaMaestraAsynController;
use Illuminate\Support\Facades\Route;

// Rutas personalizadas para crear/actualizar rutina completa (deben ir antes de apiResource)
Route::post('rutinas-maestras/completa', [RutinaMaestraAsynController::class, 'storeCompleta']);
Route::put('rutinas-maestras/{id}/completa', [RutinaMaestraAsynController::class, 'updateCompleta']);

// Ruta para obtener rutina por cliente_id
Route::post('rutinas-maestras/by-cliente', [RutinaMaestraAsynController::class, 'showByCliente']);

// Ruta para obtener rutinas por entrenador_id
Route::post('rutinas-maestras/por-entrenador', [RutinaMaestraAsynController::class, 'porEntrenador']);

// Ruta para obtener clientes con rutinas activas
Route::get('rutinas-maestras/clientes-activos', [RutinaMaestraAsynController::class, 'clientesConRutinasActivas']);

// Rutas CRUD para rutinas maestras
Route::apiResource('rutinas-maestras', RutinaMaestraAsynController::class);

// Rutas para manejo de detalles
Route::post('rutinas-maestras/{id}/detalles', [RutinaMaestraAsynController::class, 'addDetalle']);
Route::put('rutinas-maestras/{rutina_id}/detalles/{detalle_id}', [RutinaMaestraAsynController::class, 'updateDetalle']);
Route::delete('rutinas-maestras/{rutina_id}/detalles/{detalle_id}', [RutinaMaestraAsynController::class, 'deleteDetalle']);
