<?php

use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteEjecutarController;
use Illuminate\Support\Facades\Route;

Route::controller(ReporteController::class)->group(function(){
    Route::get('reportes','index');
    Route::post('reportes','store');
    Route::get('reportes/{id}','show');
    Route::put('reportes/{id}','update');
    Route::delete('reportes/{id}','destroy');
});

Route::controller(ReporteEjecutarController::class)->group(function(){
    Route::post('reportes/{id}/ejecutar','ejecutar');
    Route::post('reportes/{id}/exportar-pdf','exportarPDF');
});
