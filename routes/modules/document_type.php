<?php

use App\Http\Controllers\DocumentTypeController;
use Illuminate\Support\Facades\Route;

Route::controller(DocumentTypeController::class)->group(function(){
    Route::get('documentType','index');
    Route::post('documentType/store','store');
    Route::get('documentType/show/{id}','show');
    Route::put('documentType/update','update');
});
