<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        $members = Cliente::select('sexo', 'fecha_nacimiento')->get();

        // Calcular edades
        $members = $members->map(function ($member) {
            return [
                'sexo' => $member->sexo,
                'edad' => Carbon::parse($member->fecha_nacimiento)->age
            ];
        });

        // Agrupar por sexo
        $data = $members->groupBy('sexo')->map(function ($group, $sexo) {
            return [
                'sexo' => $sexo,
                'icon' => $sexo === 'Masculino' ? 'fa fa-male' : 'fa fa-female',
                'total' => $group->count(),
                'edades' => $group->groupBy('edad')
                    ->sortKeys() // ordena por clave (edad)
                    ->map(function ($items, $edad) {
                        return [
                            'edad' => (int) $edad,
                            'total' => count($items)
                        ];
                    })->values()->toArray()
            ];
        })->values()->toArray(); // convertimos en array con índices

        return response()->json($data);
    }

    /**
     * Top 5 clientes con más asistencias en el mes actual (o rango personalizado).
     * 
     * Acepa GET / POST:
     * - fecha_inicio (opcional, por defecto inicio del mes actual)
     * - fecha_fin (opcional, por defecto fin del mes actual)
     * - limit (opcional, por defecto 5)
     */
    public function topAsistenciasMes(Request $request)
    {
        try {
            $fechaInicioStr = $request->input('fecha_inicio') ?? $request->input('fecha_inicial');
            $fechaFinStr = $request->input('fecha_fin') ?? $request->input('fecha_final');

            $fechaInicio = $fechaInicioStr ? Carbon::parse($fechaInicioStr)->startOfDay() : Carbon::now()->startOfMonth();
            $fechaFin = $fechaFinStr ? Carbon::parse($fechaFinStr)->endOfDay() : Carbon::now()->endOfMonth();
            $limit = (int) $request->input('limit', 5);

            $resultados = DB::table('asistencia_clientes')
                ->join('clientes', 'asistencia_clientes.cliente_id', '=', 'clientes.id')
                ->whereNull('asistencia_clientes.deleted_at')
                ->whereBetween('asistencia_clientes.fecha_asistencia', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
                ->select(
                    'clientes.id as cliente_id',
                    DB::raw("TRIM(CONCAT(COALESCE(clientes.nombre, ''), ' ', COALESCE(clientes.apellidos, ''))) as cliente_nombre"),
                    'clientes.numero_documento as cliente_documento',
                    'clientes.telefono',
                    'clientes.correo',
                    DB::raw('COUNT(asistencia_clientes.id) as total_asistencias'),
                    DB::raw('MAX(asistencia_clientes.fecha_asistencia) as ultima_asistencia')
                )
                ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellidos', 'clientes.numero_documento', 'clientes.telefono', 'clientes.correo')
                ->orderBy('total_asistencias', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Operación exitosa',
                'data' => [
                    'mes' => Carbon::now()->locale('es')->monthName,
                    'anio' => Carbon::now()->year,
                    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                    'fecha_fin' => $fechaFin->format('Y-m-d'),
                    'limit' => $limit,
                    'ranking' => $resultados
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Top 5 clientes con más progreso / entrenamientos registrados en el mes actual (o rango personalizado).
     * 
     * Acepa GET / POST:
     * - fecha_inicio (opcional, por defecto inicio del mes actual)
     * - fecha_fin (opcional, por defecto fin del mes actual)
     * - limit (opcional, por defecto 5)
     */
    public function topProgresoMes(Request $request)
    {
        try {
            $fechaInicioStr = $request->input('fecha_inicio') ?? $request->input('fecha_inicial');
            $fechaFinStr = $request->input('fecha_fin') ?? $request->input('fecha_final');

            $fechaInicio = $fechaInicioStr ? Carbon::parse($fechaInicioStr)->startOfDay() : Carbon::now()->startOfMonth();
            $fechaFin = $fechaFinStr ? Carbon::parse($fechaFinStr)->endOfDay() : Carbon::now()->endOfMonth();
            $limit = (int) $request->input('limit', 5);

            $resultados = DB::table('progreso_rutina_detalle')
                ->join('detalle_rutina_maestra', 'progreso_rutina_detalle.detalle_rutina_maestra_id', '=', 'detalle_rutina_maestra.id')
                ->join('rutinas_maestras', 'detalle_rutina_maestra.rutina_maestra_id', '=', 'rutinas_maestras.id')
                ->join('clientes', 'rutinas_maestras.cliente_id', '=', 'clientes.id')
                ->whereNull('detalle_rutina_maestra.deleted_at')
                ->whereNull('rutinas_maestras.deleted_at')
                ->whereBetween('progreso_rutina_detalle.fecha_entrenamiento', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')])
                ->select(
                    'clientes.id as cliente_id',
                    DB::raw("TRIM(CONCAT(COALESCE(clientes.nombre, ''), ' ', COALESCE(clientes.apellidos, ''))) as cliente_nombre"),
                    'clientes.numero_documento as cliente_documento',
                    'clientes.telefono',
                    'clientes.correo',
                    DB::raw('COUNT(progreso_rutina_detalle.id) as total_entrenamientos'),
                    DB::raw('MAX(progreso_rutina_detalle.fecha_entrenamiento) as ultimo_entrenamiento')
                )
                ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellidos', 'clientes.numero_documento', 'clientes.telefono', 'clientes.correo')
                ->orderBy('total_entrenamientos', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Operación exitosa',
                'data' => [
                    'mes' => Carbon::now()->locale('es')->monthName,
                    'anio' => Carbon::now()->year,
                    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                    'fecha_fin' => $fechaFin->format('Y-m-d'),
                    'limit' => $limit,
                    'ranking' => $resultados
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}