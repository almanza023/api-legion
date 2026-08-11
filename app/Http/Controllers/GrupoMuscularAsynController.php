<?php

namespace App\Http\Controllers;

use App\Models\GrupoMuscular;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrupoMuscularAsynController extends Controller
{
    public function index(Request $request){
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $grupos = GrupoMuscular::paginate($perPage);
                
                return response()->json([
                    'data' => $grupos->items(),
                    'current_page' => $grupos->currentPage(),
                    'per_page' => $grupos->perPage(),
                    'total' => $grupos->total(),
                    'last_page' => $grupos->lastPage()
                ]);
            } else {
                $grupos = GrupoMuscular::all();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $grupos]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        try {
            $grupo_filter = GrupoMuscular::where('nombre', $request->nombre)->first();
            if($grupo_filter){
                return response()->json(['status' => 'fail', 'msg' => 'El grupo muscular ya existe', 'data' => null], 400);
            }

            $grupo = new GrupoMuscular();
            $grupo->nombre = $request->nombre;
            $grupo->descripcion = $request->descripcion;
            $grupo->estado = 'activo';
            $grupo->save();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $grupo]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id){
        try {
            $grupo = GrupoMuscular::find($id);
            if(!$grupo){
                return response()->json(['status' => 'fail', 'msg' => 'Grupo muscular no encontrado', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $grupo]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $grupo = GrupoMuscular::find($id);
            if(!$grupo){
                return response()->json(['status' => 'fail', 'msg' => 'Grupo muscular no encontrado', 'data' => null], 404);
            }

            if($request->has('nombre')) $grupo->nombre = $request->nombre;
            if($request->has('descripcion')) $grupo->descripcion = $request->descripcion;
            if($request->has('estado')) $grupo->estado = $request->estado;

            $grupo->save();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $grupo]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        try {
            $grupo = GrupoMuscular::find($id);
            if(!$grupo){
                return response()->json(['status' => 'fail', 'msg' => 'Grupo muscular no encontrado', 'data' => null], 404);
            }
            $grupo->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
