<?php

use App\Http\Controllers\MantenimientoMaquinaAsynController;
use Illuminate\Support\Facades\Route;

Route::apiResource('mantenimiento-maquinas', MantenimientoMaquinaAsynController::class);
