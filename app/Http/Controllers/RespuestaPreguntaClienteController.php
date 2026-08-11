<?php

namespace App\Http\Controllers;

use App\Models\RespuestaPreguntaCliente;
use App\Models\PreguntaEvaluacion;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RespuestaPreguntaClienteController extends Controller
{
    /**
     * Obtener respuestas de un cliente específico
     */
    public function obtenerPorCliente($clienteId, Request $request)
    {
        try {
            // Verificar que el cliente existe
            $cliente = Cliente::find($clienteId);
            if (!$cliente) {
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
            }

            $perPage = $request->get('per_page', 50);

            $respuestas = RespuestaPreguntaCliente::where('cliente_id', $clienteId)
                ->with('pregunta')
                ->paginate($perPage);

            return response()->json([
                'data' => $respuestas->items(),
                'current_page' => $respuestas->currentPage(),
                'per_page' => $respuestas->perPage(),
                'total' => $respuestas->total(),
                'last_page' => $respuestas->lastPage()
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Guardar o actualizar respuesta de cliente a una pregunta
     */
    public function guardarRespuesta(Request $request)
    {
        try {
            $request->validate([
                'cliente_id' => 'required|integer|exists:clientes,id',
                'pregunta_id' => 'required|integer|exists:preguntas_evaluacion,id',
                'respuesta' => 'required|boolean',
            ]);

            // Verificar que la pregunta está activa
            $pregunta = PreguntaEvaluacion::find($request->pregunta_id);
            if (!$pregunta) {
                return response()->json(['status' => 'fail', 'msg' => 'Pregunta no encontrada', 'data' => null], 404);
            }

            // Buscar si ya existe una respuesta anterior
            $respuesta = RespuestaPreguntaCliente::where('cliente_id', $request->cliente_id)
                ->where('pregunta_id', $request->pregunta_id)
                ->first();

            if ($respuesta) {
                // Actualizar respuesta existente
                $respuesta->respuesta = $request->boolean('respuesta');
                $respuesta->fecha_respuesta = now();
                $respuesta->save();
                $mensaje = 'Respuesta actualizada exitosamente';
            } else {
                // Crear nueva respuesta
                $respuesta = new RespuestaPreguntaCliente();
                $respuesta->cliente_id = $request->cliente_id;
                $respuesta->pregunta_id = $request->pregunta_id;
                $respuesta->respuesta = $request->boolean('respuesta');
                $respuesta->fecha_respuesta = now();
                $respuesta->save();
                $mensaje = 'Respuesta guardada exitosamente';
            }

            return response()->json(['status' => 'ok', 'msg' => $mensaje, 'data' => $respuesta], 201);
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return response()->json(['status' => 'fail', 'msg' => 'Errores de validación', 'data' => $ex->errors()], 422);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Guardar múltiples respuestas de un cliente (evaluación completa)
     */
    public function guardarEvaluacionCompleta(Request $request)
    {
        try {
            $request->validate([
                'cliente_id' => 'required|integer|exists:clientes,id',
                'respuestas' => 'required|array',
                'respuestas.*.pregunta_id' => 'required|integer|exists:preguntas_evaluacion,id',
                'respuestas.*.respuesta' => 'required|boolean',
            ]);

            $clienteId = $request->cliente_id;
            $respuestasGuardadas = [];

            foreach ($request->respuestas as $item) {
                $respuesta = RespuestaPreguntaCliente::updateOrCreate(
                    [
                        'cliente_id' => $clienteId,
                        'pregunta_id' => $item['pregunta_id'],
                    ],
                    [
                        'respuesta' => $item['respuesta'],
                        'fecha_respuesta' => now(),
                    ]
                );
                $respuestasGuardadas[] = $respuesta;
            }

            return response()->json([
                'status' => 'ok',
                'msg' => 'Evaluación guardada exitosamente',
                'data' => $respuestasGuardadas
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return response()->json(['status' => 'fail', 'msg' => 'Errores de validación', 'data' => $ex->errors()], 422);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener resumen de respuestas (Sí/No) de un cliente
     */
    public function obtenerResumenPorCliente($clienteId)
    {
        try {
            // Verificar que el cliente existe
            $cliente = Cliente::find($clienteId);
            if (!$cliente) {
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
            }

            $respuestas = RespuestaPreguntaCliente::where('cliente_id', $clienteId)
                ->with('pregunta')
                ->get();

            $resumen = [
                'cliente_id' => $clienteId,
                'total_respuestas' => $respuestas->count(),
                'respuestas_si' => $respuestas->where('respuesta', true)->count(),
                'respuestas_no' => $respuestas->where('respuesta', false)->count(),
                'detalles' => $respuestas->map(function ($r) {
                    return [
                        'pregunta_id' => $r->pregunta_id,
                        'pregunta' => $r->pregunta->titulo,
                        'respuesta' => $r->respuesta ? 'Sí' : 'No',
                        'fecha_respuesta' => $r->fecha_respuesta,
                    ];
                }),
            ];

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $resumen]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar respuesta específica
     */
    public function destroy($id)
    {
        try {
            $respuesta = RespuestaPreguntaCliente::find($id);
            if (!$respuesta) {
                return response()->json(['status' => 'fail', 'msg' => 'Respuesta no encontrada', 'data' => null], 404);
            }

            $respuesta->delete();

            return response()->json(['status' => 'ok', 'msg' => 'Respuesta eliminada exitosamente']);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
