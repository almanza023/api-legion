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
            $perPage = (int) $request->get('per_page', 50);

            $query = PreguntaEvaluacion::orderBy('orden', 'asc');

            if ($request->has('activas')) {
                $query->where('activa', $request->boolean('activas'));
            }

            $preguntas = $query->paginate($perPage);

            return response()->json([
                'status' => 'ok',
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
                'activa' => 'nullable|boolean',
            ]);

            $pregunta = new PreguntaEvaluacion();
            $pregunta->titulo = $request->titulo;
            $pregunta->descripcion = $request->descripcion ?? null;
            $pregunta->orden = $request->orden ?? (PreguntaEvaluacion::max('orden') + 1);
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

            $dataToUpdate = [];
            if ($request->has('titulo')) $dataToUpdate['titulo'] = $request->titulo;
            if ($request->has('descripcion')) $dataToUpdate['descripcion'] = $request->descripcion;
            if ($request->has('orden')) $dataToUpdate['orden'] = $request->orden;
            if ($request->has('activa')) $dataToUpdate['activa'] = $request->boolean('activa');

            $pregunta->update($dataToUpdate);

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
