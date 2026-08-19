<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteMembresia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ClienteMembresiaAsynController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = ClienteMembresia::with(['cliente', 'membresia', 'user']);
            $this->applyFilters($query, $request);

            $paginate = $request->get('paginate', true);
            $perPage = (int) $request->get('per_page', 12);

            if ($paginate === 'false' || $paginate === false) {
                $clienteMembresias = $query->orderBy('created_at', 'desc')->get();
                return response()->json([
                    'status' => 'ok',
                    'msg' => 'Operación exitosa',
                    'data' => $clienteMembresias
                ]);
            } else {
                $clienteMembresias = $query->orderBy('created_at', 'desc')->paginate($perPage);
                return response()->json([
                    'status' => 'ok',
                    'msg' => 'Operación exitosa',
                    'data' => $clienteMembresias->items(),
                    'current_page' => $clienteMembresias->currentPage(),
                    'per_page' => $clienteMembresias->perPage(),
                    'total' => $clienteMembresias->total(),
                    'last_page' => $clienteMembresias->lastPage()
                ]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        try {
            DB::beginTransaction();

            // Verificar si ya existe una membresía activa para este cliente
            $membresiaExistente = ClienteMembresia::where('cliente_id', $request->cliente_id)
                ->where('estado', 'activo')
                ->where('fecha_inicio', '=', $request->fecha_inicio)
                ->where('fecha_fin', '=', $request->fecha_fin)
                ->first();

            if ($membresiaExistente) {
                DB::rollBack();
                return response()->json(['status' => 'fail', 'msg' => 'El cliente ya tiene una membresía activa', 'data' => null], 400);
            }

            $clienteMembresia = new ClienteMembresia();
            $clienteMembresia->cliente_id = $request->cliente_id;
            $clienteMembresia->membresia_id = $request->membresia_id;
            $clienteMembresia->fecha_inicio = $request->fecha_inicio;
            $clienteMembresia->fecha_fin = $request->fecha_fin;
            $clienteMembresia->estado = $request->estado ?? 'activo';
            $clienteMembresia->precio_pagado = $request->precio_pagado;
            $clienteMembresia->observaciones = $request->observaciones;
            $clienteMembresia->save();

            $cliente = Cliente::find($request->cliente_id);
            $cliente->update([
                'sincronizado' => 1
            ]);
            
            $user = User::where('documento_identidad', $cliente->numero_documento)->first();
            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => strtoupper($cliente->nombre . ' ' . $cliente->apellidos),
                    'email' => $cliente->correo,
                    'rol_id' => 1,
                    'password' => bcrypt($cliente->numero_documento),
                    'documento_identidad' => $cliente->numero_documento,
                ]);
            }

            $clienteMembresia->load(['cliente', 'membresia']);

            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $clienteMembresia]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    
    }

    public function show($id){
        try {
            $clienteMembresia = ClienteMembresia::with(['cliente', 'membresia'])->find($id);
            if(!$clienteMembresia){
                return response()->json(['status' => 'fail', 'msg' => 'Registro no encontrado', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $clienteMembresia]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $clienteMembresia = ClienteMembresia::find($id);
            if(!$clienteMembresia){
                return response()->json(['status' => 'fail', 'msg' => 'Registro no encontrado', 'data' => null], 404);
            }

            if($request->has('cliente_id')) $clienteMembresia->cliente_id = $request->cliente_id;
            if($request->has('membresia_id')) $clienteMembresia->membresia_id = $request->membresia_id;
            if($request->has('fecha_inicio')) $clienteMembresia->fecha_inicio = $request->fecha_inicio;
            if($request->has('fecha_fin')) $clienteMembresia->fecha_fin = $request->fecha_fin;
            if($request->has('estado')) $clienteMembresia->estado = $request->estado;
            if($request->has('precio_pagado')) $clienteMembresia->precio_pagado = $request->precio_pagado;
            if($request->has('observaciones')) $clienteMembresia->observaciones = $request->observaciones;

            $clienteMembresia->save();
            $clienteMembresia->load(['cliente', 'membresia']);
            
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $clienteMembresia]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        try {
            $clienteMembresia = ClienteMembresia::find($id);
            if(!$clienteMembresia){
                return response()->json(['status' => 'fail', 'msg' => 'Registro no encontrado', 'data' => null], 404);
            }
            $clienteMembresia->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function getByCliente($cliente_id){
        try {
            $membresias = ClienteMembresia::with(['membresia'])
                ->where('cliente_id', $cliente_id)
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $membresias]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function getActivas(){
        try {
            $hoy = Carbon::now()->format('Y-m-d');
            
            // Actualizar en DB membresías cuya fecha fin ya pasó
            ClienteMembresia::where('estado', 'activo')
                ->whereDate('fecha_fin', '<', $hoy)
                ->update(['estado' => 'vencido']);

            $membresiasActivas = ClienteMembresia::with(['cliente', 'membresia', 'user'])
                ->where('estado', 'activo')
                ->whereDate('fecha_fin', '>=', $hoy)
                ->get();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $membresiasActivas]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Filtrar membresías de clientes por cliente_id, estado, tipo de membresía y búsqueda de texto
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filtrar(Request $request){
        try {
            $query = ClienteMembresia::with(['cliente', 'membresia', 'user']);
            $this->applyFilters($query, $request);

            $paginate = $request->get('paginate', true);
            $perPage = (int) $request->get('per_page', 12);

            if ($paginate === 'false' || $paginate === false) {
                $membresias = $query->orderBy('created_at', 'desc')->get();
                return response()->json([
                    'status' => 'ok',
                    'msg' => 'Operación exitosa',
                    'data' => $membresias
                ]);
            }

            $membresias = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status' => 'ok', 
                'msg' => 'Operación exitosa', 
                'data' => $membresias->items(),
                'current_page' => $membresias->currentPage(),
                'per_page' => $membresias->perPage(),
                'total' => $membresias->total(),
                'last_page' => $membresias->lastPage()
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Método auxiliar privado para aplicar filtros dinámicos a la consulta de ClienteMembresia
     */
    private function applyFilters($query, Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');

        // Sincronizar automáticamente en la DB aquellas membresías con estado 'activo' cuya fecha fin ya venció
        ClienteMembresia::where('estado', 'activo')
            ->whereDate('fecha_fin', '<', $today)
            ->update(['estado' => 'vencido']);

        // 1. Filtrar por estado
        if ($request->has('estado') && $request->estado !== null && $request->estado !== '') {
            $estadoClean = strtolower(trim($request->estado));
            if ($estadoClean !== 'todos' && $estadoClean !== 'all') {
                if (in_array($estadoClean, ['activo', 'activa'])) {
                    $query->where('estado', 'activo')
                          ->whereDate('fecha_fin', '>=', $today);
                } elseif (in_array($estadoClean, ['vencido', 'vencida', 'expired'])) {
                    $query->where(function ($q) use ($today) {
                        $q->whereIn('estado', ['vencido', 'vencida'])
                          ->orWhere(function ($sub) use ($today) {
                              $sub->where('estado', 'activo')
                                  ->whereDate('fecha_fin', '<', $today);
                          });
                    });
                } elseif (in_array($estadoClean, ['inactivo', 'inactiva'])) {
                    $query->whereIn('estado', ['inactivo', 'inactiva']);
                } elseif (in_array($estadoClean, ['cancelado', 'cancelada'])) {
                    $query->whereIn('estado', ['cancelado', 'cancelada']);
                } else {
                    $query->where('estado', $request->estado);
                }
            }
        }

        // 2. Filtrar por cliente_id
        if ($request->has('cliente_id') && $request->cliente_id !== null && $request->cliente_id !== '') {
            $query->where('cliente_id', $request->cliente_id);
        }

        // 3. Filtrar por membresia_id
        if ($request->has('membresia_id') && $request->membresia_id !== null && $request->membresia_id !== '') {
            $query->where('membresia_id', $request->membresia_id);
        }

        // 4. Búsqueda por texto (search / buscar / q)
        $search = trim($request->input('search') ?? $request->input('buscar') ?? $request->input('q') ?? '');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('cliente', function ($qCliente) use ($search) {
                    $qCliente->where('nombre', 'like', "%{$search}%")
                             ->orWhere('apellidos', 'like', "%{$search}%")
                             ->orWhere('numero_documento', 'like', "%{$search}%")
                             ->orWhere(DB::raw("CONCAT(COALESCE(nombre, ''), ' ', COALESCE(apellidos, ''))"), 'like', "%{$search}%");
                })->orWhereHas('membresia', function ($qMembresia) use ($search) {
                    $qMembresia->where('nombre', 'like', "%{$search}%");
                });
            });
        }

        // 5. Filtro por rango de fechas
        $fechaInicio = $request->input('fecha_inicio') ?? $request->input('fecha_desde');
        if ($fechaInicio) {
            $query->whereDate('fecha_inicio', '>=', $fechaInicio);
        }

        $fechaFin = $request->input('fecha_fin') ?? $request->input('fecha_hasta');
        if ($fechaFin) {
            $query->whereDate('fecha_fin', '<=', $fechaFin);
        }

        return $query;
    }

    /**
     * Reporte de resumen de membresías por tipo y dinero acumulado en un rango de fechas.
     * 
     * Acepa POST / GET:
     * - fecha_inicial / fecha_inicio
     * - fecha_final / fecha_fin
     */
    public function resumenPorRangoFechas(Request $request)
    {
        try {
            $fechaInicial = $request->input('fecha_inicial') ?? $request->input('fecha_inicio');
            $fechaFinal = $request->input('fecha_final') ?? $request->input('fecha_fin');

            if (!$fechaInicial || !$fechaFinal) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Debe proporcionar fecha_inicial y fecha_final',
                    'data' => null
                ], 400);
            }

            // Realizar consulta agrupada por tipo de membresía en el rango de fechas
            $resultados = DB::table('cliente_membresia')
                ->join('membresias', 'cliente_membresia.membresia_id', '=', 'membresias.id')
                ->whereNull('cliente_membresia.deleted_at')
                ->whereBetween(DB::raw('DATE(cliente_membresia.fecha_inicio)'), [$fechaInicial, $fechaFinal])
                ->select(
                    'membresias.id as membresia_id',
                    'membresias.nombre as membresia_nombre',
                    DB::raw('COUNT(cliente_membresia.id) as total_registros'),
                    DB::raw('SUM(COALESCE(cliente_membresia.precio_pagado, 0)) as total_dinero')
                )
                ->groupBy('membresias.id', 'membresias.nombre')
                ->orderBy('total_dinero', 'desc')
                ->get();

            $granTotalRegistros = $resultados->sum('total_registros');
            $granTotalDinero = round($resultados->sum('total_dinero'), 2);

            return response()->json([
                'status' => 'ok',
                'msg' => 'Operación exitosa',
                'data' => [
                    'fecha_inicial' => $fechaInicial,
                    'fecha_final' => $fechaFinal,
                    'gran_total_registros' => $granTotalRegistros,
                    'gran_total_dinero' => $granTotalDinero,
                    'detalles' => $resultados
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Reporte detallado de ventas de membresías en un rango de fechas.
     * 
     * Acepta POST / GET:
     * - fecha_inicial / fecha_inicio
     * - fecha_final / fecha_fin
     * - membresia_id (opcional para filtrar por un tipo de membresía específico)
     */
    public function detalleVentasPorRangoFechas(Request $request)
    {
        try {
            $fechaInicial = $request->input('fecha_inicial') ?? $request->input('fecha_inicio');
            $fechaFinal = $request->input('fecha_final') ?? $request->input('fecha_fin');
            $membresiaId = $request->input('membresia_id');

            if (!$fechaInicial || !$fechaFinal) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Debe proporcionar fecha_inicial y fecha_final',
                    'data' => null
                ], 400);
            }

            $query = ClienteMembresia::with(['cliente', 'membresia'])
                ->whereBetween(DB::raw('DATE(fecha_inicio)'), [$fechaInicial, $fechaFinal]);

            if ($membresiaId) {
                $query->where('membresia_id', $membresiaId);
            }

            $ventas = $query->orderBy('fecha_inicio', 'desc')->get();

            $totalDinero = 0;
            $detalles = $ventas->map(function ($item) use (&$totalDinero) {
                $precio = (float) ($item->precio_pagado ?? 0);
                $totalDinero += $precio;

                $nombreCliente = trim(($item->cliente->nombre ?? '') . ' ' . ($item->cliente->apellidos ?? ''));
                if (empty($nombreCliente)) {
                    $nombreCliente = $item->cliente->nombres ?? 'Cliente sin nombre';
                }

                return [
                    'id' => $item->id,
                    'cliente_id' => $item->cliente_id,
                    'cliente_nombre' => $nombreCliente,
                    'cliente_documento' => $item->cliente->numero_documento ?? '',
                    'membresia_id' => $item->membresia_id,
                    'membresia_nombre' => $item->membresia->nombre ?? 'Membresía',
                    'fecha_inicio' => $item->fecha_inicio ? $item->fecha_inicio->format('Y-m-d') : null,
                    'fecha_fin' => $item->fecha_fin ? $item->fecha_fin->format('Y-m-d') : null,
                    'fecha_creacion' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
                    'valor_membresia' => $precio,
                    'estado' => $item->estado
                ];
            });

            return response()->json([
                'status' => 'ok',
                'msg' => 'Operación exitosa',
                'data' => [
                    'fecha_inicial' => $fechaInicial,
                    'fecha_final' => $fechaFinal,
                    'total_registros' => $detalles->count(),
                    'total_dinero' => round($totalDinero, 2),
                    'detalles' => $detalles
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Reporte de fidelización de clientes: Ranking de clientes por días de membresía acumulados en un rango de fechas.
     * 
     * Acepta POST / GET:
     * - fecha_inicial / fecha_inicio
     * - fecha_final / fecha_fin
     * - limit (opcional, por defecto 50)
     */
    public function fidelizacionClientes(Request $request)
    {
        try {
            $fechaInicial = $request->input('fecha_inicial') ?? $request->input('fecha_inicio');
            $fechaFinal = $request->input('fecha_final') ?? $request->input('fecha_fin');
            $limit = (int) $request->input('limit', 50);

            if (!$fechaInicial || !$fechaFinal) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Debe proporcionar fecha_inicial y fecha_final',
                    'data' => null
                ], 400);
            }

            // Consulta agrupada por cliente ordenando por mayor cantidad de días acumulados
            $resultados = DB::table('cliente_membresia')
                ->join('clientes', 'cliente_membresia.cliente_id', '=', 'clientes.id')
                ->whereNull('cliente_membresia.deleted_at')
                ->whereBetween(DB::raw('DATE(cliente_membresia.fecha_inicio)'), [$fechaInicial, $fechaFinal])
                ->select(
                    'clientes.id as cliente_id',
                    DB::raw("TRIM(CONCAT(COALESCE(clientes.nombre, ''), ' ', COALESCE(clientes.apellidos, ''))) as cliente_nombre"),
                    'clientes.numero_documento as cliente_documento',
                    'clientes.telefono',
                    'clientes.correo',
                    DB::raw('COUNT(cliente_membresia.id) as total_membresias'),
                    DB::raw('SUM(GREATEST(DATEDIFF(cliente_membresia.fecha_fin, cliente_membresia.fecha_inicio) + 1, 0)) as total_dias_acumulados'),
                    DB::raw('SUM(COALESCE(cliente_membresia.precio_pagado, 0)) as total_dinero_invertido')
                )
                ->groupBy('clientes.id', 'clientes.nombre', 'clientes.apellidos', 'clientes.numero_documento', 'clientes.telefono', 'clientes.correo')
                ->orderBy('total_dias_acumulados', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Operación exitosa',
                'data' => [
                    'fecha_inicial' => $fechaInicial,
                    'fecha_final' => $fechaFinal,
                    'total_clientes' => $resultados->count(),
                    'ranking' => $resultados
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Proyección de Flujo de Caja basado en la fecha de vencimiento (fecha_fin) de las membresías.
     * 
     * Acepta POST / GET:
     * - fecha_inicial / fecha_inicio (opcional: por defecto fecha actual)
     * - fecha_final / fecha_fin (opcional: por defecto 30 días después)
     */
    public function proyeccionFlujoCaja(Request $request)
    {
        try {
            $fechaInicialStr = $request->input('fecha_inicial') ?? $request->input('fecha_inicio');
            $fechaFinalStr = $request->input('fecha_final') ?? $request->input('fecha_fin');

            $fechaInicial = $fechaInicialStr ? Carbon::parse($fechaInicialStr)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
            $fechaFinal = $fechaFinalStr ? Carbon::parse($fechaFinalStr)->format('Y-m-d') : Carbon::now()->addDays(30)->format('Y-m-d');

            // 1. Detalle de membresías que vencen en el rango
            $detalles = DB::table('cliente_membresia')
                ->join('clientes', 'cliente_membresia.cliente_id', '=', 'clientes.id')
                ->join('membresias', 'cliente_membresia.membresia_id', '=', 'membresias.id')
                ->whereNull('cliente_membresia.deleted_at')
                ->whereBetween(DB::raw('DATE(cliente_membresia.fecha_fin)'), [$fechaInicial, $fechaFinal])
                ->select(
                    'cliente_membresia.id as cliente_membresia_id',
                    'clientes.id as cliente_id',
                    DB::raw("TRIM(CONCAT(COALESCE(clientes.nombre, ''), ' ', COALESCE(clientes.apellidos, ''))) as cliente_nombre"),
                    'clientes.numero_documento as cliente_documento',
                    'clientes.telefono as cliente_telefono',
                    'clientes.correo as cliente_correo',
                    'membresias.id as membresia_id',
                    'membresias.nombre as membresia_nombre',
                    'cliente_membresia.fecha_inicio',
                    'cliente_membresia.fecha_fin',
                    'cliente_membresia.estado',
                    DB::raw('COALESCE(cliente_membresia.precio_pagado, membresias.precio, 0) as monto_proyectado')
                )
                ->orderBy('cliente_membresia.fecha_fin', 'asc')
                ->get();

            // 2. Proyección por mes en el rango
            $proyeccionPorMes = DB::table('cliente_membresia')
                ->join('membresias', 'cliente_membresia.membresia_id', '=', 'membresias.id')
                ->whereNull('cliente_membresia.deleted_at')
                ->whereBetween(DB::raw('DATE(cliente_membresia.fecha_fin)'), [$fechaInicial, $fechaFinal])
                ->select(
                    DB::raw("DATE_FORMAT(cliente_membresia.fecha_fin, '%Y-%m') as mes_key"),
                    DB::raw("YEAR(cliente_membresia.fecha_fin) as anio"),
                    DB::raw("MONTH(cliente_membresia.fecha_fin) as mes"),
                    DB::raw("COUNT(cliente_membresia.id) as total_vencimientos"),
                    DB::raw("SUM(COALESCE(cliente_membresia.precio_pagado, membresias.precio, 0)) as monto_proyectado")
                )
                ->groupBy(DB::raw("DATE_FORMAT(cliente_membresia.fecha_fin, '%Y-%m')"), DB::raw("YEAR(cliente_membresia.fecha_fin)"), DB::raw("MONTH(cliente_membresia.fecha_fin)"))
                ->orderBy('mes_key', 'asc')
                ->get();

            // 3. Proyección por tipo de membresía
            $proyeccionPorTipo = DB::table('cliente_membresia')
                ->join('membresias', 'cliente_membresia.membresia_id', '=', 'membresias.id')
                ->whereNull('cliente_membresia.deleted_at')
                ->whereBetween(DB::raw('DATE(cliente_membresia.fecha_fin)'), [$fechaInicial, $fechaFinal])
                ->select(
                    'membresias.id as membresia_id',
                    'membresias.nombre as membresia_nombre',
                    DB::raw("COUNT(cliente_membresia.id) as total_vencimientos"),
                    DB::raw("SUM(COALESCE(cliente_membresia.precio_pagado, membresias.precio, 0)) as monto_proyectado")
                )
                ->groupBy('membresias.id', 'membresias.nombre')
                ->orderBy('monto_proyectado', 'desc')
                ->get();

            $totalMembresias = $detalles->count();
            $totalMontoProyectado = round($detalles->sum('monto_proyectado'), 2);

            return response()->json([
                'status' => 'ok',
                'msg' => 'Operación exitosa',
                'data' => [
                    'fecha_inicial' => $fechaInicial,
                    'fecha_final' => $fechaFinal,
                    'total_membresias_por_vencer' => $totalMembresias,
                    'total_monto_proyectado' => $totalMontoProyectado,
                    'proyeccion_por_mes' => $proyeccionPorMes,
                    'proyeccion_por_tipo_membresia' => $proyeccionPorTipo,
                    'detalles' => $detalles
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}



