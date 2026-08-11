<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicidadAsynController;

Route::get('/publicidad', [PublicidadAsynController::class, 'index']);
Route::get('/publicidad/vigentes', [PublicidadAsynController::class, 'vigentes']);
Route::get('/publicidad/{id}', [PublicidadAsynController::class, 'show']);
Route::post('/publicidad', [PublicidadAsynController::class, 'store']);
Route::post('/publicidad/{id}', [PublicidadAsynController::class, 'update']);
Route::delete('/publicidad/{id}', [PublicidadAsynController::class, 'destroy']);
