<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicClienteController;
use App\Http\Controllers\AsistenciaClienteController;

Route::post('public/cliente/register', [PublicClienteController::class, 'createOrUpdateCliente']);
Route::get('public/cliente/unsynced-usuarios', [PublicClienteController::class, 'getUnsyncedUsuarios']);
Route::get('public/cliente/update-sincronizado/{id}', [PublicClienteController::class, 'updateSincronizado']);
Route::get('public/cliente/update-expired-membresias', [PublicClienteController::class, 'updateExpiredMembresias']);
Route::post('public/asistencias-clientes/registrar-por-documento', [AsistenciaClienteController::class, 'registrarPublicoPorDocumento']);


