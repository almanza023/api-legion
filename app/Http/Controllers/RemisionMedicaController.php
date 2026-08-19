<?php

namespace App\Http\Controllers;

use App\Models\RemisionMedica;
use App\Models\Cliente;
use App\Models\Medico;
use Illuminate\Http\Request;

class RemisionMedicaController extends Controller
{
    /**
     * Listar todas las remisiones médicas
     */
    public function index(Request $request)
    {
        $query = RemisionMedica::with(['cliente', 'medico', 'cie10']);

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->input('cliente_id'));
        }

        if ($request->has('medico_id')) {
            $query->where('medico_id', $request->input('medico_id'));
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $remisiones = $query->orderBy('fecha_registro', 'desc')
                            ->orderBy('id', 'desc')
                            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $remisiones
        ]);
    }

    /**
     * Consultar remisiones registradas por un médico específico
     */
    public function remisionesPorMedico($medicoId, Request $request)
    {
        $medico = Medico::find($medicoId);

        if (!$medico) {
            return response()->json(['status' => 'fail', 'msg' => 'Médico no encontrado'], 404);
        }

        $query = RemisionMedica::with(['cliente', 'medico', 'cie10'])
            ->where('medico_id', $medicoId);

        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $remisiones = $query->orderBy('fecha_registro', 'desc')
                            ->orderBy('id', 'desc')
                            ->get();

        return response()->json([
            'status' => 'success',
            'medico' => [
                'id' => $medico->id,
                'nombre' => $medico->nombre,
                'cedula' => $medico->cedula,
                'especialidad' => $medico->especialidad,
                'registro_retheus' => $medico->registro_retheus,
            ],
            'total_remisiones' => $remisiones->count(),
            'data' => $remisiones
        ]);
    }

    /**
     * Consultar histórico de remisiones de un cliente específico
     */
    public function historicoCliente($clienteId)
    {
        $cliente = Cliente::find($clienteId);

        if (!$cliente) {
            return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado'], 404);
        }

        $remisiones = RemisionMedica::with(['medico', 'cie10'])
            ->where('cliente_id', $clienteId)
            ->orderBy('fecha_registro', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'cliente' => [
                'id' => $cliente->id,
                'nombre_completo' => trim(($cliente->nombre ?? $cliente->nombres) . ' ' . ($cliente->apellidos ?? '')),
                'numero_documento' => $cliente->numero_documento,
                'fecha_nacimiento' => $cliente->fecha_nacimiento,
                'sexo' => $cliente->sexo,
            ],
            'total_remisiones' => $remisiones->count(),
            'data' => $remisiones
        ]);
    }

    /**
     * Crear nueva remisión médica
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'medico_id' => 'nullable|exists:medicos,id',
            'cie10_id' => 'nullable|exists:diagnosticos_cie10,id',
            'diagnostico' => 'nullable|string',
            'codigo_cie10' => 'nullable|string|max:50',
            'objetivos' => 'nullable|array',
            'objetivo_otro' => 'nullable|string|max:255',
            'restricciones' => 'nullable|string',
            'vigencia' => 'nullable|string|in:6_meses,12_meses,otra',
            'vigencia_otra_descripcion' => 'nullable|string|max:255',
            'estado' => 'nullable|string|in:activa,vencida,anulada',
            'fecha_registro' => 'nullable|date',
        ]);

        // Verificar que el cliente tenga una membresía activa vigente
        $cliente = Cliente::find($validated['cliente_id']);
        if (!$cliente || !$cliente->tieneMembresiaActiva()) {
            return response()->json([
                'status' => 'fail',
                'msg' => 'No es posible registrar la remisión médica. El cliente no cuenta con una membresía activa.'
            ], 422);
        }

        if (empty($validated['fecha_registro'])) {
            $validated['fecha_registro'] = now()->format('Y-m-d');
        }

        $remision = RemisionMedica::create($validated);
        $remision->load(['cliente', 'medico', 'cie10']);

        return response()->json([
            'status' => 'success',
            'msg' => 'Remisión médica registrada exitosamente',
            'data' => $remision
        ], 201);
    }

    /**
     * Ver detalle de remisión
     */
    public function show($id)
    {
        $remision = RemisionMedica::with(['cliente', 'medico', 'cie10'])->find($id);

        if (!$remision) {
            return response()->json(['status' => 'fail', 'msg' => 'Remisión médica no encontrada'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $remision
        ]);
    }

    /**
     * Actualizar remisión médica
     */
    public function update(Request $request, $id)
    {
        $remision = RemisionMedica::find($id);

        if (!$remision) {
            return response()->json(['status' => 'fail', 'msg' => 'Remisión médica no encontrada'], 404);
        }

        $validated = $request->validate([
            'cliente_id' => 'sometimes|required|exists:clientes,id',
            'medico_id' => 'nullable|exists:medicos,id',
            'cie10_id' => 'nullable|exists:diagnosticos_cie10,id',
            'diagnostico' => 'nullable|string',
            'codigo_cie10' => 'nullable|string|max:50',
            'objetivos' => 'nullable|array',
            'objetivo_otro' => 'nullable|string|max:255',
            'restricciones' => 'nullable|string',
            'vigencia' => 'nullable|string|in:6_meses,12_meses,otra',
            'vigencia_otra_descripcion' => 'nullable|string|max:255',
            'estado' => 'nullable|string|in:activa,vencida,anulada',
            'fecha_registro' => 'nullable|date',
        ]);

        $remision->update($validated);
        $remision->load(['cliente', 'medico', 'cie10']);

        return response()->json([
            'status' => 'success',
            'msg' => 'Remisión médica actualizada exitosamente',
            'data' => $remision
        ]);
    }

    /**
     * Eliminar remisión médica
     */
    public function destroy($id)
    {
        $remision = RemisionMedica::find($id);

        if (!$remision) {
            return response()->json(['status' => 'fail', 'msg' => 'Remisión médica no encontrada'], 404);
        }

        $remision->delete();

        return response()->json([
            'status' => 'success',
            'msg' => 'Remisión médica eliminada exitosamente'
        ]);
    }

    /**
     * Obtener listado de todos los clientes que NO tienen una remisión médica vigente.
     * Muestra:
     * - Clientes que NUNCA han tenido una remisión médica.
     * - Clientes cuya remisión médica ya VENCIÓ (según su fecha de registro + vigencia o estado).
     */
    public function clientesSinRemisionVigente(Request $request)
    {
        try {
            $queryStr = trim($request->get('q', ''));
            $paginate = $request->get('paginate', false);
            $perPage = (int) $request->get('per_page', 15);

            // Obtener todos los clientes con sus remisiones médicas
            $query = Cliente::with(['remisionesMedicas' => function ($q) {
                $q->orderBy('fecha_registro', 'desc')->orderBy('id', 'desc');
            }]);

            if (!empty($queryStr)) {
                $query->where(function ($q) use ($queryStr) {
                    $q->where('nombre', 'LIKE', "%{$queryStr}%")
                      ->orWhere('apellidos', 'LIKE', "%{$queryStr}%")
                      ->orWhere('numero_documento', 'LIKE', "%{$queryStr}%")
                      ->orWhere('correo', 'LIKE', "%{$queryStr}%")
                      ->orWhere('telefono', 'LIKE', "%{$queryStr}%");
                });
            }

            $todosClientes = $query->orderBy('nombre', 'asc')->get();

            // Filtrar clientes sin remisión médica vigente
            $clientesSinVigente = $todosClientes->filter(function ($cliente) {
                $ultimaRemision = $cliente->remisionesMedicas->first();

                // Si nunca ha tenido remisión médica, DEBE MOSTRARSE
                if (!$ultimaRemision) {
                    return true;
                }

                // Si la última remisión NO es vigente (venció o está inactiva), SÍ se debe mostrar
                return !$ultimaRemision->esVigente();
            })->values();

            // Mapear datos para la respuesta
            $mappedData = $clientesSinVigente->map(function ($cliente) {
                $ultimaRemision = $cliente->remisionesMedicas->first();

                return [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'apellidos' => $cliente->apellidos,
                    'nombre_completo' => trim(($cliente->nombre ?? '') . ' ' . ($cliente->apellidos ?? '')),
                    'tipo_documento' => $cliente->tipo_documento,
                    'numero_documento' => $cliente->numero_documento,
                    'sexo' => $cliente->sexo,
                    'telefono' => $cliente->telefono,
                    'correo' => $cliente->correo,
                    'tiene_remisiones_previas' => $cliente->remisionesMedicas->count() > 0,
                    'total_remisiones_previas' => $cliente->remisionesMedicas->count(),
                    'ultima_remision' => $ultimaRemision ? [
                        'id' => $ultimaRemision->id,
                        'fecha_registro' => $ultimaRemision->fecha_registro ? $ultimaRemision->fecha_registro->format('Y-m-d') : null,
                        'vigencia' => $ultimaRemision->vigencia,
                        'estado' => $ultimaRemision->estado,
                        'diagnostico' => $ultimaRemision->diagnostico
                    ] : null,
                    'motivo' => $ultimaRemision ? 'Remisión médica anterior vencida o inactiva' : 'Nunca ha tenido remisión médica'
                ];
            });

            if ($paginate === 'true' || $paginate === true) {
                $page = (int) $request->get('page', 1);
                $slice = $mappedData->slice(($page - 1) * $perPage, $perPage)->values();
                $lastPage = (int) ceil($mappedData->count() / $perPage);

                return response()->json([
                    'status' => 'success',
                    'data' => $slice,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $mappedData->count(),
                        'last_page' => max($lastPage, 1)
                    ]
                ]);
            }

            return response()->json([
                'status' => 'success',
                'msg' => 'Clientes sin remisión médica vigente obtenidos exitosamente',
                'total' => $mappedData->count(),
                'data' => $mappedData
            ]);
        } catch (\Exception $ex) {
            \Illuminate\Support\Facades\Log::error("Error en clientesSinRemisionVigente: " . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json([
                'status' => 'fail',
                'msg' => 'Error al consultar clientes sin remisión médica vigente'
            ], 500);
        }
    }
}
