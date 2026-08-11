<?php

namespace App\Http\Controllers;

use App\Models\Membresia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MembresiaAsynController extends Controller
{
    public function index(Request $request)
    {
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $membresias = Membresia::paginate($perPage);
                
                return response()->json([
                    'data' => $membresias->items(),
                    'current_page' => $membresias->currentPage(),
                    'per_page' => $membresias->perPage(),
                    'total' => $membresias->total(),
                    'last_page' => $membresias->lastPage()
                ]);
            } else {
                $membresias = Membresia::all();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $membresias]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        try {
            $membresia_filter = Membresia::where('nombre', $request->nombre)->first();
            if($membresia_filter){
                return response()->json(['status' => 'fail', 'msg' => 'La membresía ya existe', 'data' => null], 400);
            }

            $membresia = new Membresia();
            $membresia->nombre = $request->nombre;
            $membresia->descripcion = $request->descripcion;
            $membresia->precio = $request->precio;
            $membresia->duracion_dias = $request->duracion_dias;
            $membresia->estado = 'activo';
            $membresia->save();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $membresia]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id){
        try {
            $membresia = Membresia::find($id);
            if(!$membresia){
                return response()->json(['status' => 'fail', 'msg' => 'Membresía no encontrada', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $membresia]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $membresia = Membresia::find($id);
            if(!$membresia){
                return response()->json(['status' => 'fail', 'msg' => 'Membresía no encontrada', 'data' => null], 404);
            }

            if($request->has('nombre')) $membresia->nombre = $request->nombre;
            if($request->has('descripcion')) $membresia->descripcion = $request->descripcion;
            if($request->has('precio')) $membresia->precio = $request->precio;
            if($request->has('duracion_dias')) $membresia->duracion_dias = $request->duracion_dias;
            if($request->has('estado')) $membresia->estado = $request->estado;

            $membresia->save();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $membresia]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        try {
            $membresia = Membresia::find($id);
            if(!$membresia){
                return response()->json(['status' => 'fail', 'msg' => 'Membresía no encontrada', 'data' => null], 404);
            }
            $membresia->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
