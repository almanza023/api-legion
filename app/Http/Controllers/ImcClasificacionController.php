<?php

namespace App\Http\Controllers;

use App\Models\ImcClasificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImcClasificacionController extends Controller
{
    /**
     * Listar todas las clasificaciones de IMC
     */
    public function index(Request $request){
        try {
            $perPage = $request->get('per_page', 12);

            $clasificaciones = ImcClasificacion::paginate($perPage);

            return response()->json([
                'data' => $clasificaciones->items(),
                'current_page' => $clasificaciones->currentPage(),
                'per_page' => $clasificaciones->perPage(),
                'total' => $clasificaciones->total(),
                'last_page' => $clasificaciones->lastPage()
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Crear una nueva clasificación de IMC
     */
    public function store(Request $request){
        try {
            // Validar que los rangos no se solapen
            $existente = ImcClasificacion::where(function($query) use ($request) {
                $query->whereBetween('rango_min', [$request->rango_min, $request->rango_max])
                    ->orWhereBetween('rango_max', [$request->rango_min, $request->rango_max])
                    ->orWhere(function($q) use ($request) {
                        $q->where('rango_min', '<=', $request->rango_min)
                            ->where('rango_max', '>=', $request->rango_max);
                    });
            })->first();

            if($existente){
                return response()->json(['status' => 'fail', 'msg' => 'El rango de IMC se solapa con una clasificación existente', 'data' => null], 400);
            }

            $clasificacion = new ImcClasificacion();
            $clasificacion->rango_min = $request->rango_min;
            $clasificacion->rango_max = $request->rango_max;
            $clasificacion->nombre = $request->nombre;
            $clasificacion->descripcion = $request->descripcion ?? null;
            $clasificacion->save();

            return response()->json(['status' => 'ok', 'msg' => 'Clasificación de IMC creada exitosamente', 'data' => $clasificacion]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Mostrar una clasificación de IMC específica
     */
    public function show($id){
        try {
            $clasificacion = ImcClasificacion::find($id);
            if(!$clasificacion){
                return response()->json(['status' => 'fail', 'msg' => 'Clasificación de IMC no encontrada', 'data' => null], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $clasificacion]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Actualizar una clasificación de IMC
     */
    public function update(Request $request, $id){
        try {
            $clasificacion = ImcClasificacion::find($id);
            if(!$clasificacion){
                return response()->json(['status' => 'fail', 'msg' => 'Clasificación de IMC no encontrada', 'data' => null], 404);
            }

            // Validar que los rangos no se solapen (excluyendo el registro actual)
            if($request->has('rango_min') || $request->has('rango_max')){
                $rango_min = $request->rango_min ?? $clasificacion->rango_min;
                $rango_max = $request->rango_max ?? $clasificacion->rango_max;

                $existente = ImcClasificacion::where('id', '!=', $id)
                    ->where(function($query) use ($rango_min, $rango_max) {
                        $query->whereBetween('rango_min', [$rango_min, $rango_max])
                            ->orWhereBetween('rango_max', [$rango_min, $rango_max])
                            ->orWhere(function($q) use ($rango_min, $rango_max) {
                                $q->where('rango_min', '<=', $rango_min)
                                    ->where('rango_max', '>=', $rango_max);
                            });
                    })->first();

                if($existente){
                    return response()->json(['status' => 'fail', 'msg' => 'El rango de IMC se solapa con una clasificación existente', 'data' => null], 400);
                }
            }

            if($request->has('rango_min')) $clasificacion->rango_min = $request->rango_min;
            if($request->has('rango_max')) $clasificacion->rango_max = $request->rango_max;
            if($request->has('nombre')) $clasificacion->nombre = $request->nombre;
            if($request->has('descripcion')) $clasificacion->descripcion = $request->descripcion;
            
            $clasificacion->save();

            return response()->json(['status' => 'ok', 'msg' => 'Clasificación de IMC actualizada exitosamente', 'data' => $clasificacion]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar una clasificación de IMC
     */
    public function destroy($id){
        try {
            $clasificacion = ImcClasificacion::find($id);
            if(!$clasificacion){
                return response()->json(['status' => 'fail', 'msg' => 'Clasificación de IMC no encontrada', 'data' => null], 404);
            }

            $clasificacion->delete();

            return response()->json(['status' => 'ok', 'msg' => 'Clasificación de IMC eliminada exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener clasificación por valor de IMC
     */
    public function obtenerPorImc(Request $request){
        try {
            $imc = $request->input('imc');
            
            if(!$imc){
                return response()->json(['status' => 'fail', 'msg' => 'imc es requerido', 'data' => null], 400);
            }

            $clasificacion = ImcClasificacion::where('rango_min', '<=', $imc)
                ->where('rango_max', '>=', $imc)
                ->first();

            if(!$clasificacion){
                return response()->json(['status' => 'fail', 'msg' => 'Clasificación de IMC no encontrada para este valor', 'data' => null], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $clasificacion]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
