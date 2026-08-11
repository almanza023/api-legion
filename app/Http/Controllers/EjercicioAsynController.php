<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EjercicioAsynController extends Controller
{
public function index(Request $request)
{
    try {
        $paginate = $request->get('paginate', false);
        $perPage = $request->get('per_page', 12);

        if ($paginate === 'true' || $paginate === true) {
            $ejercicios = Ejercicio::with(['video', 'grupoMuscular', 'maquina'])
                ->paginate($perPage);
            
            return response()->json([
                'data' => $ejercicios->items(),
                'current_page' => $ejercicios->currentPage(),
                'per_page' => $ejercicios->perPage(),
                'total' => $ejercicios->total(),
                'last_page' => $ejercicios->lastPage()
            ]);
        } else {
            $ejercicios = Ejercicio::with(['video', 'grupoMuscular', 'maquina'])->get();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $ejercicios]);
        }
    } catch (\Exception $ex) {
        Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
        return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
    }
}
    

    public function store(Request $request){
        try {
            // Validar que el nombre no exista
            $ejercicio_existente = Ejercicio::where('nombre', $request->nombre)->first();
            if($ejercicio_existente){
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un ejercicio con ese nombre', 'data' => null], 400);
            }

            $ejercicio = new Ejercicio();
            $ejercicio->tipo = $request->tipo ?? 0;

           if($ejercicio->tipo==2 && $request->grupo_muscular_id==null){
               return response()->json(['status' => 'fail', 'msg' => 'El grupo muscular es obligatorio para ejercicios de tipo 2', 'data' => null], 400);  
           }

            $ejercicio->video_id = $request->video_id;
            $ejercicio->nombre = $request->nombre;
            $ejercicio->descripcion = $request->descripcion;
            $ejercicio->series = $request->series ?? 3;
            $ejercicio->repeticiones = $request->repeticiones ?? 10;
            $ejercicio->tiempo_descanso_seg = $request->tiempo_descanso_seg ?? 60;
            $ejercicio->dificultad = $request->dificultad ?? 'media';
            $ejercicio->tipo = $request->tipo ?? null;
            $ejercicio->estado = 'activo';
            $ejercicio->save();

            // Cargar relaciones
            $ejercicio->load(['video', 'grupoMuscular', 'maquina']);

            return response()->json(['status' => 'ok', 'msg' => 'Ejercicio creado exitosamente', 'data' => $ejercicio]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id){
        try {
            $ejercicio = Ejercicio::with(['video', 'grupoMuscular', 'maquina'])->find($id);
            if(!$ejercicio){
                return response()->json(['status' => 'fail', 'msg' => 'Ejercicio no encontrado', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $ejercicio]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $ejercicio = Ejercicio::find($id);
            if(!$ejercicio){
                return response()->json(['status' => 'fail', 'msg' => 'Ejercicio no encontrado', 'data' => null], 404);
            }

            // Validar nombre único si se está cambiando
            if($request->has('nombre') && $request->nombre != $ejercicio->nombre){
                $ejercicio_existente = Ejercicio::where('nombre', $request->nombre)->first();
                if($ejercicio_existente){
                    return response()->json(['status' => 'fail', 'msg' => 'Ya existe un ejercicio con ese nombre', 'data' => null], 400);
                }
            }

            if($request->has('es_estiramiento')) $ejercicio->es_estiramiento = $request->es_estiramiento;

            if ($ejercicio->es_estiramiento) {
                if($request->has('grupo_muscular_id')) $ejercicio->grupo_muscular_id = $request->grupo_muscular_id; // Puede ser null
            } else {
                // Si no es estiramiento y se intenta actualizar grupo_muscular a null o no se envía (y era null antes? no debería), validar.
                // Pero en update es parcial. Si envían null explícitamente:
                if($request->has('grupo_muscular_id') && !$request->grupo_muscular_id) {
                     return response()->json(['status' => 'fail', 'msg' => 'El grupo muscular es obligatorio', 'data' => null], 400);
                }
                if($request->has('grupo_muscular_id')) $ejercicio->grupo_muscular_id = $request->grupo_muscular_id;
            }

            if($request->has('video_id')) $ejercicio->video_id = $request->video_id;
            // grupo_muscular_id handled above
            if($request->has('maquina_id')) $ejercicio->maquina_id = $request->maquina_id;
            if($request->has('nombre')) $ejercicio->nombre = $request->nombre;
            if($request->has('descripcion')) $ejercicio->descripcion = $request->descripcion;
            if($request->has('series')) $ejercicio->series = $request->series;
            if($request->has('repeticiones')) $ejercicio->repeticiones = $request->repeticiones;
            if($request->has('tiempo_descanso_seg')) $ejercicio->tiempo_descanso_seg = $request->tiempo_descanso_seg;
            if($request->has('dificultad')) $ejercicio->dificultad = $request->dificultad;
            if($request->has('tipo')) $ejercicio->tipo = $request->tipo;
            if($request->has('estado')) $ejercicio->estado = $request->estado;

            $ejercicio->save();

            // Cargar relaciones
            $ejercicio->load(['video', 'grupoMuscular', 'maquina']);

            return response()->json(['status' => 'ok', 'msg' => 'Ejercicio actualizado exitosamente', 'data' => $ejercicio]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        try {
            $ejercicio = Ejercicio::find($id);
            if(!$ejercicio){
                return response()->json(['status' => 'fail', 'msg' => 'Ejercicio no encontrado', 'data' => null], 404);
            }

            $ejercicio->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Ejercicio eliminado exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
