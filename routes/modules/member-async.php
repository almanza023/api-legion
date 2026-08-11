<?php

use App\Http\Controllers\ClienteAsynController;
use Illuminate\Support\Facades\Route;

// Rutas apiResource para clientes
Route::apiResource('clientes', ClienteAsynController::class);

// Rutas personalizadas adicionales
Route::controller(ClienteAsynController::class)->group(function () {
    Route::get('clientes-async', 'index');
    Route::get('clientes-async/update/{id}', 'syncMember');
    Route::post('clientes-async/create', 'createMember');
});
