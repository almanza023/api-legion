<?php

use App\Http\Controllers\MembresiaAsynController;
use Illuminate\Support\Facades\Route;

Route::apiResource('membresias', MembresiaAsynController::class);
