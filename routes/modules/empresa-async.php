<?php

use App\Http\Controllers\EmpresaAsynController;
use Illuminate\Support\Facades\Route;

Route::apiResource('empresas', EmpresaAsynController::class);
