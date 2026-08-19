<?php

use App\Http\Controllers\ExpedienteController;
use Illuminate\Support\Facades\Route;

Route::prefix('expedientes')->group(function () {
    // Búsqueda pública de clientes para abrir expediente (Sin restricciones de membresía)
    Route::get('/buscar', [ExpedienteController::class, 'buscarClientes']);

    // Expediente integral completo por ID de Cliente
    Route::get('/cliente/{clienteId}', [ExpedienteController::class, 'obtenerExpedienteCliente']);

    // Resumen ejecutivo rápido de 1 solo vistazo (Alertas, PAR-Q, última valoración)
    Route::get('/cliente/{clienteId}/resumen-ejecutivo', [ExpedienteController::class, 'obtenerResumenEjecutivo']);

    // Serie de tiempo / Métricas de evolución física para gráficos
    Route::get('/cliente/{clienteId}/evolucion', [ExpedienteController::class, 'obtenerEvolucionMetrics']);
});
