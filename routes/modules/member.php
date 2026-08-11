<?php

use App\Http\Controllers\ClienteAsynController;
use Illuminate\Support\Facades\Route;

Route::controller(ClienteAsynController::class)->group(function () {
    Route::get('clientes-async', 'index');
    Route::post('clientes-async/store', 'store');
    Route::get('clientes-async/show/{id}', 'show');
    Route::put('clientes-async/update', 'update');
    Route::put('clientes-async/synchronize/{id}', 'synchronize');
    Route::get('clientes/documento/{documento_identidad}', 'getByDocumento');
});
