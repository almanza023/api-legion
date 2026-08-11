<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::controller(AuthController::class)->group(function () {
    Route::post('logout', 'logout');
    Route::get('me', 'getUser');
    Route::post('change-password', 'changePassword');
});
