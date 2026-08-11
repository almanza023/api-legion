<?php

use App\Http\Controllers\RolAsynController;
use Illuminate\Support\Facades\Route;

Route::apiResource('roles', RolAsynController::class);
