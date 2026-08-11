<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Rutas CRUD para usuarios
Route::controller(AuthController::class)->group(function () {
    Route::get('users', 'index');           // Listar usuarios
    Route::post('users', 'store');          // Crear usuario
    Route::get('users/{id}', 'show');       // Mostrar usuario
    Route::put('users/{id}', 'update');     // Actualizar usuario
    Route::delete('users/{id}', 'destroy'); // Eliminar usuario
});
