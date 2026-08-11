<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class HealthController extends Controller
{
    /**
     * Endpoint público para verificar el estado de la aplicación,
     * validar la conexión a la base de datos y comprobar CORS.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        $startTime = microtime(true);
        $dbStatus = 'disconnected';
        $dbError = null;
        $dbLatencyMs = null;
        $databaseName = null;

        try {
            $dbStart = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $dbStart) * 1000, 2);
            $dbStatus = 'connected';
            $databaseName = DB::connection()->getDatabaseName();
        } catch (Exception $e) {
            $dbError = $e->getMessage();
        }

        $totalTimeMs = round((microtime(true) - $startTime) * 1000, 2);
        $isHealthy = ($dbStatus === 'connected');

        $origin = $request->header('Origin') ?: '*';

        $responseData = [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'code' => $isHealthy ? 200 : 503,
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'database' => [
                'status' => $dbStatus,
                'connection' => config('database.default'),
                'name' => $databaseName,
                'latency_ms' => $dbLatencyMs,
                'error' => $dbError,
            ],
            'cors' => [
                'request_origin' => $origin,
                'status' => 'CORS headers active',
            ],
            'response_time_ms' => $totalTimeMs,
        ];

        return response()->json($responseData, $isHealthy ? 200 : 503);
    }
}
