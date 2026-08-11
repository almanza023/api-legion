<?php

use App\Http\Controllers\UsuarioAsynController;
use Illuminate\Support\Facades\Route;

Route::controller(UsuarioAsynController::class)->group(function () {
    Route::get('usuarios-async', 'index');
    Route::post('usuarios-async/store', 'store');
    Route::get('usuarios-async/show/{id}', 'show');
    Route::put('usuarios-async/update', 'update');
    Route::put('usuarios-async/synchronize/{id}', 'synchronize');
    Route::get('usuarios/documento/{documento_identidad}', 'getByDocumento');
});
