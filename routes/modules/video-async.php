<?php

use App\Http\Controllers\VideoAsynController;
use Illuminate\Support\Facades\Route;

Route::apiResource('videos', VideoAsynController::class);
Route::get('videos/{id}/play-url', [VideoAsynController::class, 'getPlayUrl']);
Route::get('videos/{id}/url-temporal', [VideoAsynController::class, 'getTemporaryUrl']);
