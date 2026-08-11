<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolAsynController extends Controller
{
    public function index(Request $request){
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $roles = Rol::paginate($perPage);
                
                return response()->json([
                    'data' => $roles->items(),
                    'current_page' => $roles->currentPage(),
                    'per_page' => $roles->perPage(),
                    'total' => $roles->total(),
                    'last_page' => $roles->lastPage()
                ]);
            } else {
                $roles = Rol::all();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $roles]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        try {
            $rol_filter = Rol::where('nombre', $request->nombre)->first();
            if($rol_filter){
                return response()->json(['status' => 'fail', 'msg' => 'El rol ya existe', 'data' => null], 400);
            }

            $rol = new Rol();
            $rol->nombre = $request->nombre;
            $rol->descripcion = $request->descripcion;
            $rol->estado = 'activo';
            $rol->save();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $rol]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id){
        try {
            $rol = Rol::find($id);
            if(!$rol){
                return response()->json(['status' => 'fail', 'msg' => 'Rol no encontrado', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $rol]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $rol = Rol::find($id);
            if(!$rol){
                return response()->json(['status' => 'fail', 'msg' => 'Rol no encontrado', 'data' => null], 404);
            }

            if($request->has('nombre')) $rol->nombre = $request->nombre;
            if($request->has('descripcion')) $rol->descripcion = $request->descripcion;
            if($request->has('estado')) $rol->estado = $request->estado;

            $rol->save();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $rol]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        try {
            $rol = Rol::find($id);
            if(!$rol){
                return response()->json(['status' => 'fail', 'msg' => 'Rol no encontrado', 'data' => null], 404);
            }
            $rol->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
