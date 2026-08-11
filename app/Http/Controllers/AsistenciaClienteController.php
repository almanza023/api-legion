<?php

namespace App\Http\Controllers;

use App\Models\AsistenciaCliente;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsistenciaClienteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $asistencias = AsistenciaCliente::with(['cliente'])
                    ->orderBy('fecha_asistencia', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);

                return response()->json([
                    'data' => $asistencias->items(),
                    'current_page' => $asistencias->currentPage(),
                    'per_page' => $asistencias->perPage(),
                    'total' => $asistencias->total(),
                    'last_page' => $asistencias->lastPage(),
                ]);
            }

            $asistencias = AsistenciaCliente::with(['cliente'])
                ->orderBy('fecha_asistencia', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $asistencias]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
                'fecha_asistencia' => ['required', 'date'],
                'observaciones' => ['nullable', 'string'],
            ]);

            $fecha = Carbon::parse($validated['fecha_asistencia'])->format('Y-m-d');
            $yaTieneValida = AsistenciaCliente::where('cliente_id', $validated['cliente_id'])
                ->where('fecha_asistencia', $fecha)
                ->where('es_valida', true)
                ->exists();

            $asistencia = AsistenciaCliente::create([
                'cliente_id' => $validated['cliente_id'],
                'fecha_asistencia' => $fecha,
                'observaciones' => $validated['observaciones'] ?? null,
                'es_valida' => !$yaTieneValida,
            ]);
            $asistencia->load(['cliente']);

            return response()->json([
                'status' => 'ok',
                'msg' => $asistencia->es_valida
                    ? 'Asistencia válida del día registrada'
                    : 'Asistencia registrada (no suma como válida porque ya existe una válida hoy)',
                'data' => $asistencia,
            ], 201);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $asistencia = AsistenciaCliente::with(['cliente'])->find($id);

            if (!$asistencia) {
                return response()->json(['status' => 'fail', 'msg' => 'Registro no encontrado', 'data' => null], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $asistencia]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $asistencia = AsistenciaCliente::find($id);

            if (!$asistencia) {
                return response()->json(['status' => 'fail', 'msg' => 'Registro no encontrado', 'data' => null], 404);
            }

            $validated = $request->validate([
                'cliente_id' => ['sometimes', 'integer', 'exists:clientes,id'],
                'fecha_asistencia' => ['sometimes', 'date'],
                'observaciones' => ['nullable', 'string'],
            ]);

            $clienteAnterior = $asistencia->cliente_id;
            $fechaAnterior = Carbon::parse($asistencia->fecha_asistencia)->format('Y-m-d');

            if (array_key_exists('fecha_asistencia', $validated)) {
                $validated['fecha_asistencia'] = Carbon::parse($validated['fecha_asistencia'])->format('Y-m-d');
            }

            $asistencia->fill($validated);
            $asistencia->save();

            $this->normalizarValidezDiaria($clienteAnterior, $fechaAnterior);
            $this->normalizarValidezDiaria($asistencia->cliente_id, Carbon::parse($asistencia->fecha_asistencia)->format('Y-m-d'));

            $asistencia->refresh();
            $asistencia->load(['cliente']);

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $asistencia]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $asistencia = AsistenciaCliente::find($id);

            if (!$asistencia) {
                return response()->json(['status' => 'fail', 'msg' => 'Registro no encontrado', 'data' => null], 404);
            }
            $clienteId = $asistencia->cliente_id;
            $fecha = Carbon::parse($asistencia->fecha_asistencia)->format('Y-m-d');
            $asistencia->delete();
            $this->normalizarValidezDiaria($clienteId, $fecha);

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function getByCliente($clienteId)
    {
        try {
            $asistencias = AsistenciaCliente::with(['cliente'])
                ->where('cliente_id', $clienteId)
                ->orderBy('fecha_asistencia', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $asistencias]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function getByFecha($fecha)
    {
        try {
            $fechaParseada = Carbon::parse($fecha)->format('Y-m-d');

            $asistencias = AsistenciaCliente::with(['cliente'])
                ->where('fecha_asistencia', $fechaParseada)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $asistencias]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Fecha inválida o error al procesar la solicitud', 'data' => $ex->getMessage()], 400);
        }
    }

    public function filtrar(Request $request)
    {
        try {
            $query = AsistenciaCliente::with(['cliente']);

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
            }

            if ($request->filled('numero_documento')) {
                $numeroDocumento = $request->numero_documento;
                $query->whereHas('cliente', function ($subQuery) use ($numeroDocumento) {
                    $subQuery->where('numero_documento', $numeroDocumento);
                });
            }

            if ($request->filled('fecha_inicio')) {
                $query->whereDate('fecha_asistencia', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->whereDate('fecha_asistencia', '<=', $request->fecha_fin);
            }

            if ($request->filled('solo_validas') && ($request->solo_validas === true || $request->solo_validas === 'true' || $request->solo_validas === 1 || $request->solo_validas === '1')) {
                $query->where('es_valida', true);
            }

            $perPage = $request->get('per_page', 12);
            $asistencias = $query
                ->orderBy('fecha_asistencia', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'ok',
                'msg' => 'Operación exitosa',
                'data' => $asistencias->items(),
                'current_page' => $asistencias->currentPage(),
                'per_page' => $asistencias->perPage(),
                'total' => $asistencias->total(),
                'last_page' => $asistencias->lastPage(),
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function registrarPublicoPorDocumento(Request $request)
    {
        try {
            $validated = $request->validate([
                'numero_documento' => ['required', 'string', 'max:50'],
                'fecha_asistencia' => ['nullable', 'date'],
                'observaciones' => ['nullable', 'string'],
            ]);

            $cliente = Cliente::where('numero_documento', $validated['numero_documento'])->first();

            if (!$cliente) {
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
            }

            $fecha = isset($validated['fecha_asistencia'])
                ? Carbon::parse($validated['fecha_asistencia'])->format('Y-m-d')
                : Carbon::now()->format('Y-m-d');

            $yaTieneValida = AsistenciaCliente::where('cliente_id', $cliente->id)
                ->where('fecha_asistencia', $fecha)
                ->where('es_valida', true)
                ->exists();

            $asistencia = AsistenciaCliente::create([
                'cliente_id' => $cliente->id,
                'fecha_asistencia' => $fecha,
                'observaciones' => $validated['observaciones'] ?? null,
                'es_valida' => !$yaTieneValida,
            ]);

            $asistencia->load(['cliente']);

            return response()->json([
                'status' => 'ok',
                'msg' => $asistencia->es_valida
                    ? 'Asistencia válida del día registrada'
                    : 'Asistencia registrada (no suma como válida porque ya existe una válida hoy)',
                'data' => $asistencia,
            ], 201);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    private function normalizarValidezDiaria(int $clienteId, string $fecha): void
    {
        $baseQuery = AsistenciaCliente::where('cliente_id', $clienteId)
            ->where('fecha_asistencia', $fecha)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        $primeraAsistencia = (clone $baseQuery)->first();

        AsistenciaCliente::where('cliente_id', $clienteId)
            ->where('fecha_asistencia', $fecha)
            ->update(['es_valida' => false]);

        if ($primeraAsistencia) {
            $primeraAsistencia->es_valida = true;
            $primeraAsistencia->save();
        }
    }
}
