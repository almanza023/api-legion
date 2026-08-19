<?php

use App\Http\Controllers\MedicoController;
use App\Http\Controllers\ObjetivoRemisionController;
use App\Http\Controllers\DiagnosticoCie10Controller;
use App\Http\Controllers\RemisionMedicaController;
use App\Http\Controllers\RemisionMedicaPdfController;
use Illuminate\Support\Facades\Route;

// Rutas de Mantenimiento de Médicos
Route::prefix('medicos')->group(function () {
    Route::get('/', [MedicoController::class, 'index']);
    Route::post('/', [MedicoController::class, 'store']);
    Route::get('/{id}', [MedicoController::class, 'show']);
    Route::put('/{id}', [MedicoController::class, 'update']);
    Route::delete('/{id}', [MedicoController::class, 'destroy']);
});

// Rutas de Mantenimiento de Objetivos de Remisión
Route::prefix('objetivos-remision')->group(function () {
    Route::get('/', [ObjetivoRemisionController::class, 'index']);
    Route::post('/', [ObjetivoRemisionController::class, 'store']);
    Route::put('/{id}', [ObjetivoRemisionController::class, 'update']);
    Route::delete('/{id}', [ObjetivoRemisionController::class, 'destroy']);
});

// Rutas de Mantenimiento de Diagnósticos CIE-10
Route::prefix('diagnosticos-cie10')->group(function () {
    Route::get('/', [DiagnosticoCie10Controller::class, 'index']);
    Route::post('/', [DiagnosticoCie10Controller::class, 'store']);
    Route::get('/{id}', [DiagnosticoCie10Controller::class, 'show']);
    Route::put('/{id}', [DiagnosticoCie10Controller::class, 'update']);
    Route::delete('/{id}', [DiagnosticoCie10Controller::class, 'destroy']);
});

// Rutas de Remisiones Médicas e Histórico
Route::prefix('remisiones-medicas')->group(function () {
    Route::get('/', [RemisionMedicaController::class, 'index']);
    Route::post('/', [RemisionMedicaController::class, 'store']);
    Route::get('/sin-remision-vigente', [RemisionMedicaController::class, 'clientesSinRemisionVigente']);
    Route::get('/cliente/{clienteId}', [RemisionMedicaController::class, 'historicoCliente']);
    Route::get('/medico/{medicoId}', [RemisionMedicaController::class, 'remisionesPorMedico']);
    Route::get('/{id}', [RemisionMedicaController::class, 'show']);
    Route::put('/{id}', [RemisionMedicaController::class, 'update']);
    Route::delete('/{id}', [RemisionMedicaController::class, 'destroy']);

    // Generador de PDF
    Route::get('/{id}/pdf', [RemisionMedicaPdfController::class, 'generarPdf']);
});
