<?php

use App\Http\Controllers\EjercicioAsynController;
use Illuminate\Support\Facades\Route;

Route::apiResource('ejercicios', EjercicioAsynController::class);
