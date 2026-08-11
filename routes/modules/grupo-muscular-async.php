<?php

use App\Http\Controllers\GrupoMuscularAsynController;
use Illuminate\Support\Facades\Route;

Route::apiResource('grupos-musculares', GrupoMuscularAsynController::class);
