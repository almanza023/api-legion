<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $query = Reporte::query();

        $query->when($request->has('s'), function($query) use($request){
            $search = trim($request->s);
            $query->where('nombre_reporte','like',"%{$search}%")
                  ->orWhere('categoria','like',"%{$search}%")
                  ->orWhere('descripcion','like',"%{$search}%");
        });

        $query->when($request->has('categoria'), function($query) use($request){
            $query->where('categoria', $request->categoria);
        });

        $query->orderBy('categoria','asc')->orderBy('nombre_reporte','asc');

        return $request->has('per_page') ? $query->paginate($request->per_page) : $query->get();
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            Reporte::create([
                'nombre_reporte' => $request->nombre_reporte,
                'categoria' => $request->categoria,
                'parametros' => $request->parametros,
                'consulta_sql' => $request->consulta_sql,
                'descripcion' => $request->descripcion
            ]);

            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'Reporte guardado exitosamente']);

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function show($id)
    {
        $data = Reporte::where('id', $id)->first();
        return response()->json($data);
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $reporte = Reporte::find($id);

            $reporte->nombre_reporte = $request->nombre_reporte;
            $reporte->categoria = $request->categoria;
            $reporte->parametros = $request->parametros;
            $reporte->consulta_sql = $request->consulta_sql;
            $reporte->descripcion = $request->descripcion;

            $reporte->save();

            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'Reporte actualizado exitosamente']);

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $reporte = Reporte::find($id);
            $reporte->delete();

            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'Reporte eliminado exitosamente']);

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }
}
