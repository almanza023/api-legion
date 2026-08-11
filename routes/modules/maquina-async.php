<?php

use App\Http\Controllers\MaquinaAsynController;
use Illuminate\Support\Facades\Route;

Route::apiResource('maquinas', MaquinaAsynController::class);
