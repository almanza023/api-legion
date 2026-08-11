<?php

namespace App\Http\Controllers;

use App\Models\MantenimientoMaquina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MantenimientoMaquinaAsynController extends Controller
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
                $mantenimientos = MantenimientoMaquina::with('maquina')->paginate($perPage);
                
                return response()->json([
                    'data' => $mantenimientos->items(),
                    'current_page' => $mantenimientos->currentPage(),
                    'per_page' => $mantenimientos->perPage(),
                    'total' => $mantenimientos->total(),
                    'last_page' => $mantenimientos->lastPage()
                ]);
            } else {
                $mantenimientos = MantenimientoMaquina::with('maquina')->get();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $mantenimientos]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        try {
            $mantenimiento = new MantenimientoMaquina();
            $mantenimiento->maquina_id = $request->maquina_id;
            $mantenimiento->fecha_programada = $this->parseDate($request->fecha_programada);
            $mantenimiento->fecha_realizada = $this->parseDate($request->fecha_realizada);
            $mantenimiento->tipo_mantenimiento = $request->tipo_mantenimiento;
            $mantenimiento->descripcion = $request->descripcion;
            $mantenimiento->costo = $request->costo;
            $mantenimiento->estado = $request->estado ?? 'programado';
            $mantenimiento->save();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $mantenimiento]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id){
        try {
            $mantenimiento = MantenimientoMaquina::with('maquina')->find($id);
            if(!$mantenimiento){
                return response()->json(['status' => 'fail', 'msg' => 'Mantenimiento no encontrado', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $mantenimiento]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $mantenimiento = MantenimientoMaquina::find($id);
            if(!$mantenimiento){
                return response()->json(['status' => 'fail', 'msg' => 'Mantenimiento no encontrado', 'data' => null], 404);
            }

            if($request->has('maquina_id')) $mantenimiento->maquina_id = $request->maquina_id;
            if($request->has('tipo_mantenimiento')) $mantenimiento->tipo_mantenimiento = $request->tipo_mantenimiento;
            if($request->has('descripcion')) $mantenimiento->descripcion = $request->descripcion;
            if($request->has('costo')) $mantenimiento->costo = $request->costo;
            if($request->has('estado')) $mantenimiento->estado = $request->estado;

            if($request->has('fecha_programada')){
                $mantenimiento->fecha_programada = $this->parseDate($request->fecha_programada);
            }

            if($request->has('fecha_realizada')){
                $mantenimiento->fecha_realizada = $this->parseDate($request->fecha_realizada);
            }

            $mantenimiento->save();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $mantenimiento]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        try {
            $mantenimiento = MantenimientoMaquina::find($id);
            if(!$mantenimiento){
                return response()->json(['status' => 'fail', 'msg' => 'Mantenimiento no encontrado', 'data' => null], 404);
            }
            $mantenimiento->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
