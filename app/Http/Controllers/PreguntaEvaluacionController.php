<?php

namespace App\Http\Controllers;

use App\Models\PreguntaEvaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PreguntaEvaluacionController extends Controller
{
    /**
     * Listar todas las preguntas activas
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 50);
            $activas = $request->get('activas', true);

            $query = PreguntaEvaluacion::orderBy('orden', 'asc');

            if ($activas) {
                $query->where('activa', true);
            }

            $preguntas = $query->paginate($perPage);

            return response()->json([
                'data' => $preguntas->items(),
                'current_page' => $preguntas->currentPage(),
                'per_page' => $preguntas->perPage(),
                'total' => $preguntas->total(),
                'last_page' => $preguntas->lastPage()
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Crear una nueva pregunta
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'titulo' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'orden' => 'nullable|integer|min:0',
            ]);

            $pregunta = new PreguntaEvaluacion();
            $pregunta->titulo = $request->titulo;
            $pregunta->descripcion = $request->descripcion ?? null;
            $pregunta->orden = $request->orden ?? PreguntaEvaluacion::max('orden') + 1;
            $pregunta->activa = $request->boolean('activa', true);
            $pregunta->save();

            return response()->json(['status' => 'ok', 'msg' => 'Pregunta creada exitosamente', 'data' => $pregunta], 201);
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return response()->json(['status' => 'fail', 'msg' => 'Errores de validación', 'data' => $ex->errors()], 422);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Mostrar una pregunta específica
     */
    public function show($id)
    {
        try {
            $pregunta = PreguntaEvaluacion::find($id);
            if (!$pregunta) {
                return response()->json(['status' => 'fail', 'msg' => 'Pregunta no encontrada', 'data' => null], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $pregunta]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Actualizar una pregunta
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'titulo' => 'nullable|string|max:255',
                'descripcion' => 'nullable|string',
                'orden' => 'nullable|integer|min:0',
                'activa' => 'nullable|boolean',
            ]);

            $pregunta = PreguntaEvaluacion::find($id);
            if (!$pregunta) {
                return response()->json(['status' => 'fail', 'msg' => 'Pregunta no encontrada', 'data' => null], 404);
            }

            $pregunta->update([
                'titulo' => $request->titulo ?? $pregunta->titulo,
                'descripcion' => $request->descripcion ?? $pregunta->descripcion,
                'orden' => $request->orden ?? $pregunta->orden,
                'activa' => $request->boolean('activa', $pregunta->activa),
            ]);

            return response()->json(['status' => 'ok', 'msg' => 'Pregunta actualizada exitosamente', 'data' => $pregunta]);
        } catch (\Illuminate\Validation\ValidationException $ex) {
            return response()->json(['status' => 'fail', 'msg' => 'Errores de validación', 'data' => $ex->errors()], 422);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar una pregunta
     */
    public function destroy($id)
    {
        try {
            $pregunta = PreguntaEvaluacion::find($id);
            if (!$pregunta) {
                return response()->json(['status' => 'fail', 'msg' => 'Pregunta no encontrada', 'data' => null], 404);
            }

            $pregunta->delete();

            return response()->json(['status' => 'ok', 'msg' => 'Pregunta eliminada exitosamente']);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
