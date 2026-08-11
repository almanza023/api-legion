<?php

use App\Http\Controllers\ClienteMembresiaAsynController;
use Illuminate\Support\Facades\Route;

Route::apiResource('cliente-membresias', ClienteMembresiaAsynController::class);
Route::get('cliente-membresias-by-cliente/{cliente_id}', [ClienteMembresiaAsynController::class, 'getByCliente']);
Route::get('cliente-membresias-activas', [ClienteMembresiaAsynController::class, 'getActivas']);
Route::post('cliente-membresias-filtrar', [ClienteMembresiaAsynController::class, 'filtrar']);
Route::post('cliente-membresias/resumen-por-rango-fechas', [ClienteMembresiaAsynController::class, 'resumenPorRangoFechas']);
Route::get('cliente-membresias/resumen-por-rango-fechas', [ClienteMembresiaAsynController::class, 'resumenPorRangoFechas']);
Route::post('cliente-membresias/detalle-ventas-por-rango-fechas', [ClienteMembresiaAsynController::class, 'detalleVentasPorRangoFechas']);
Route::get('cliente-membresias/detalle-ventas-por-rango-fechas', [ClienteMembresiaAsynController::class, 'detalleVentasPorRangoFechas']);
Route::post('cliente-membresias/fidelizacion-clientes', [ClienteMembresiaAsynController::class, 'fidelizacionClientes']);
Route::get('cliente-membresias/fidelizacion-clientes', [ClienteMembresiaAsynController::class, 'fidelizacionClientes']);
Route::post('cliente-membresias/proyeccion-flujo-caja', [ClienteMembresiaAsynController::class, 'proyeccionFlujoCaja']);
Route::get('cliente-membresias/proyeccion-flujo-caja', [ClienteMembresiaAsynController::class, 'proyeccionFlujoCaja']);




