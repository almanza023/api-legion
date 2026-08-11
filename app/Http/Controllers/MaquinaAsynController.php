<?php

namespace App\Http\Controllers;

use App\Models\Maquina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaquinaAsynController extends Controller
{
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Intentar formato d/m/Y (ejemplo: 15/11/2024)
            if (strpos($date, '/') !== false) {
                return \Carbon\Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
            }
            // Intentar formato Y-m-d (ejemplo: 2024-11-15)
            if (strpos($date, '-') !== false) {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d');
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Error parseando fecha: ' . $date . ' - ' . $e->getMessage());
            return null;
        }
    }

    public function index(Request $request){
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $maquinas = Maquina::paginate($perPage);
                
                return response()->json([
                    'data' => $maquinas->items(),
                    'current_page' => $maquinas->currentPage(),
                    'per_page' => $maquinas->perPage(),
                    'total' => $maquinas->total(),
                    'last_page' => $maquinas->lastPage()
                ]);
            } else {
                $maquinas = Maquina::all();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $maquinas]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        try {
            $maquina_filter = Maquina::where('codigo_maquina', $request->codigo_maquina)->first();
            if($maquina_filter){
                return response()->json(['status' => 'fail', 'msg' => 'La máquina ya existe', 'data' => null], 400);
            }

            $maquina = new Maquina();
            $maquina->codigo_maquina = $request->codigo_maquina;
            $maquina->nombre = $request->nombre;
            $maquina->marca = $request->marca;
            $maquina->modelo = $request->modelo;
            
            $maquina->fecha_adquisicion = $this->parseDate($request->fecha_adquisicion);
            $maquina->estado = $request->estado ?? 'operativa';
            $maquina->ultimo_mantenimiento = $this->parseDate($request->ultimo_mantenimiento);
            
            $maquina->ubicacion = $request->ubicacion;
            $maquina->save();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $maquina]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id){
        try {
            $maquina = Maquina::find($id);
            if(!$maquina){
                return response()->json(['status' => 'fail', 'msg' => 'Máquina no encontrada', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $maquina]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $maquina = Maquina::find($id);
            if(!$maquina){
                return response()->json(['status' => 'fail', 'msg' => 'Máquina no encontrada', 'data' => null], 404);
            }

            if($request->has('codigo_maquina')) $maquina->codigo_maquina = $request->codigo_maquina;
            if($request->has('nombre')) $maquina->nombre = $request->nombre;
            if($request->has('marca')) $maquina->marca = $request->marca;
            if($request->has('modelo')) $maquina->modelo = $request->modelo;
            if($request->has('estado')) $maquina->estado = $request->estado;
            if($request->has('ubicacion')) $maquina->ubicacion = $request->ubicacion;

            if($request->has('fecha_adquisicion')){
                $maquina->fecha_adquisicion = $this->parseDate($request->fecha_adquisicion);
            }

            if($request->has('ultimo_mantenimiento')){
                $maquina->ultimo_mantenimiento = $this->parseDate($request->ultimo_mantenimiento);
            }

            $maquina->save();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $maquina]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        try {
            $maquina = Maquina::find($id);
            if(!$maquina){
                return response()->json(['status' => 'fail', 'msg' => 'Máquina no encontrada', 'data' => null], 404);
            }
            $maquina->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
