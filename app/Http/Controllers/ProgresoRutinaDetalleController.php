<?php

namespace App\Http\Controllers;

use App\Models\ProgresoRutinaDetalle;
use App\Models\DetalleRutinaMaestra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgresoRutinaDetalleController extends Controller
{
    /**
     * Listar todos los progresos con paginación
     */
    public function index(Request $request){
        try {
            $perPage = $request->get('per_page', 12);

            $progresos = ProgresoRutinaDetalle::with(['detalleRutinaMaestra.ejercicio', 'detalleRutinaMaestra.rutinaMaestra.cliente'])
                ->paginate($perPage);

            return response()->json([
                'data' => $progresos->items(),
                'current_page' => $progresos->currentPage(),
                'per_page' => $progresos->perPage(),
                'total' => $progresos->total(),
                'last_page' => $progresos->lastPage()
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Crear un nuevo progreso
     */
    public function store(Request $request){
        DB::beginTransaction();
        try {
            // Validar que el detalle existe
            $detalle = DetalleRutinaMaestra::find($request->detalle_id);
            if(!$detalle){
                return response()->json(['status' => 'fail', 'msg' => 'Detalle de rutina no encontrado', 'data' => null], 404);
            }

            $progreso = new ProgresoRutinaDetalle();
            $progreso->detalle_rutina_maestra_id = $request->detalle_id;
            $progreso->fecha_entrenamiento = $request->fecha;
            $progreso->comentarios = $request->comentarios ?? null;
            $progreso->estado = $request->estado ?? 'completado';
            $progreso->save();

            DB::commit();

            // Cargar relaciones
            $progreso->load(['detalleRutinaMaestra.ejercicio', 'detalleRutinaMaestra.rutinaMaestra.cliente']);

            return response()->json(['status' => 'ok', 'msg' => 'Progreso creado exitosamente', 'data' => $progreso]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Mostrar un progreso específico
     */
    public function show($id){
        try {
            $progreso = ProgresoRutinaDetalle::with(['detalleRutinaMaestra.ejercicio', 'detalleRutinaMaestra.rutinaMaestra.cliente'])->find($id);
            if(!$progreso){
                return response()->json(['status' => 'fail', 'msg' => 'Progreso no encontrado', 'data' => null], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $progreso]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Actualizar un progreso
     */
    public function update(Request $request, $id){
        DB::beginTransaction();
        try {
            $progreso = ProgresoRutinaDetalle::find($id);
            if(!$progreso){
                return response()->json(['status' => 'fail', 'msg' => 'Progreso no encontrado', 'data' => null], 404);
            }

            if($request->has('detalle_id')){
                $detalle = DetalleRutinaMaestra::find($request->detalle_id);
                if(!$detalle){
                    return response()->json(['status' => 'fail', 'msg' => 'Detalle de rutina no encontrado', 'data' => null], 404);
                }
                $progreso->detalle_rutina_maestra_id = $request->detalle_id;
            }

            if($request->has('fecha')) $progreso->fecha_entrenamiento = $request->fecha;
            if($request->has('comentarios')) $progreso->comentarios = $request->comentarios;
            if($request->has('estado')) $progreso->estado = $request->estado;
            
            $progreso->save();

            DB::commit();

            // Cargar relaciones
            $progreso->load(['detalleRutinaMaestra.ejercicio', 'detalleRutinaMaestra.rutinaMaestra.cliente']);

            return response()->json(['status' => 'ok', 'msg' => 'Progreso actualizado exitosamente', 'data' => $progreso]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar un progreso
     */
    public function destroy($id){
        DB::beginTransaction();
        try {
            $progreso = ProgresoRutinaDetalle::find($id);
            if(!$progreso){
                return response()->json(['status' => 'fail', 'msg' => 'Progreso no encontrado', 'data' => null], 404);
            }

            $progreso->delete();

            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'Progreso eliminado exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener progresos por cliente
     */
    public function progresosPorCliente(Request $request){
        try {
            $cliente_id = $request->input('cliente_id');
            $rutina_id = $request->input('rutina_id');
            
            if(!$cliente_id){
                return response()->json(['status' => 'fail', 'msg' => 'cliente_id es requerido', 'data' => null], 400);
            }

            $progresos = ProgresoRutinaDetalle::whereHas('detalleRutinaMaestra.rutinaMaestra', function($query) use ($cliente_id, $rutina_id) {
                $query->where('cliente_id', $cliente_id);
                if($rutina_id){
                    $query->where('id', $rutina_id);
                }
            })
            ->with(['detalleRutinaMaestra.ejercicio', 'detalleRutinaMaestra.rutinaMaestra.cliente'])
            ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $progresos]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener progresos por detalle de rutina
     */
    public function progresosPorDetalle(Request $request){
        try {
            $detalle_rutina_maestra_id = $request->input('detalle_rutina_maestra_id');
            
            if(!$detalle_rutina_maestra_id){
                return response()->json(['status' => 'fail', 'msg' => 'detalle_rutina_maestra_id es requerido', 'data' => null], 400);
            }

            $progresos = ProgresoRutinaDetalle::where('detalle_rutina_maestra_id', $detalle_rutina_maestra_id)
                ->with(['detalleRutinaMaestra.ejercicio', 'detalleRutinaMaestra.rutinaMaestra.cliente'])
                ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $progresos]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener progresos por rango de fechas
     */
    public function progresosPorFecha(Request $request){
        try {
            $fecha_inicio = $request->input('fecha_inicio');
            $fecha_fin = $request->input('fecha_fin');
            $cliente_id = $request->input('cliente_id');
            
            if(!$fecha_inicio || !$fecha_fin){
                return response()->json(['status' => 'fail', 'msg' => 'fecha_inicio y fecha_fin son requeridos', 'data' => null], 400);
            }

            $query = ProgresoRutinaDetalle::whereBetween('fecha_entrenamiento', [$fecha_inicio, $fecha_fin]);

            if($cliente_id){
                $query->whereHas('detalleRutinaMaestra.rutinaMaestra', function($q) use ($cliente_id) {
                    $q->where('cliente_id', $cliente_id);
                });
            }

            $progresos = $query->with(['detalleRutinaMaestra.ejercicio', 'detalleRutinaMaestra.rutinaMaestra.cliente'])
                ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $progresos]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
