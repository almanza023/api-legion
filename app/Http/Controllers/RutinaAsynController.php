<?php

namespace App\Http\Controllers;

use App\Models\RutinaMaestra;
use App\Models\DetalleRutinaMaestra;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RutinaAsynController extends Controller
{
    /**
     * Listar todas las rutinas maestras con paginación
     */
    public function index(Request $request){
        try {
            $perPage = $request->get('per_page', 12);

            $rutinas = RutinaMaestra::withCount('detalles as total_ejercicios')
                ->with(['detalles', 'cliente', 'entrenador'])
                ->paginate($perPage);

            // Agregar campo dias_semana a cada rutina
            $rutinas->getCollection()->transform(function ($rutina) {
                $dias_unicos = $rutina->detalles->pluck('dia_semana')->unique()->count();
                $rutina->dias_semana = $dias_unicos;

                // Remover detalles del resultado para el index
                unset($rutina->detalles);

                return $rutina;
            });

            return response()->json([
                'data' => $rutinas->items(),
                'current_page' => $rutinas->currentPage(),
                'per_page' => $rutinas->perPage(),
                'total' => $rutinas->total(),
                'last_page' => $rutinas->lastPage()
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    /**
     * Crear rutina maestra completa con sus detalles (ruta personalizada para frontend)
     */
    public function storeCompleta(Request $request){
        DB::beginTransaction();
        try {
            Log::info('storeCompleta: request data', $request->all());

            // Validar que valoracion_id sea requerido y exista
            if(!$request->has('valoracion_id') || !$request->valoracion_id){
                return response()->json(['status' => 'fail', 'msg' => 'El campo valoracion_id es requerido', 'data' => null], 400);
            }

            $valoracion = \App\Models\Valoracion::find($request->valoracion_id);
            if(!$valoracion){
                return response()->json(['status' => 'fail', 'msg' => 'La valoración especificada no existe', 'data' => null], 404);
            }

            // Validar que el nombre no exista
            $rutina_existente = RutinaMaestra::where('cliente_id', $request->cliente_id)
                ->where('trimestre', $request->trimestre)
                ->first();
            if($rutina_existente){
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe una rutina para este cliente en este trimestre', 'data' => null], 400);
            }

            // Crear rutina maestra
            $rutina = new RutinaMaestra();
            $rutina->nombre = $request->nombre;
            $rutina->descripcion = $request->descripcion;
            $rutina->objetivo = $request->objetivo;
            $rutina->estado = $request->estado ?? 'activo';
            $rutina->cliente_id = $request->cliente_id ?? null;
            $rutina->entrenador_id = $request->entrenador_id ?? null;
            $rutina->valoracion_id = $request->valoracion_id;
            $rutina->trimestre=$request->trimestre;
            $rutina->fecha_inicio=$request->fecha_inicio;
            $rutina->fecha_fin=$request->fecha_fin;
            $rutina->save();

            Log::info('storeCompleta: rutina creada con id', ['id' => $rutina->id]);

            // Intentar obtener detalles de dias_ejercicios o detalles
            $detalles_input = $request->input('dias_ejercicios') ?? $request->input('detalles');
            Log::info('storeCompleta: detalles_input', ['detalles_input' => $detalles_input]);

            $detalles = $this->parseDetalles($detalles_input);
            Log::info('storeCompleta: detalles parseados', ['count' => count($detalles)]);

            // Crear detalles si se enviaron
            if(!empty($detalles)){
                foreach($detalles as $detalle_data){
                    $detalle = new DetalleRutinaMaestra();
                    $detalle->rutina_maestra_id = $rutina->id;
                    $detalle->ejercicio_id = $detalle_data['ejercicio_id'] ?? null;
                    $detalle->dia_semana = $detalle_data['dia_semana'] ?? null;
                    $detalle->nombre_dia_personalizado = $detalle_data['nombre_dia_personalizado'] ?? null;
                    $detalle->series_estandar = $detalle_data['series_estandar'] ?? 0;
                    $detalle->repeticiones_estandar = $detalle_data['repeticiones_estandar'] ?? 0;
                    $detalle->tiempo_descanso_estandar_seg = $detalle_data['tiempo_descanso_estandar_seg'] ?? 0;
                    $detalle->orden_ejecucion = $detalle_data['orden_ejecucion'] ?? 1;
                    $detalle->notas = $detalle_data['notas'] ?? null;
                    $detalle->estado = $detalle_data['estado'] ?? 'activo';
                    $detalle->mesociclo = $detalle_data['mesociclo'] ?? 0;
                    $detalle->peso = $detalle_data['peso'] ?? 0;
                    $detalle->save();
                    Log::info('storeCompleta: detalle guardado', ['ejercicio_id' => $detalle->ejercicio_id, 'dia_semana' => $detalle->dia_semana]);
                }
            }

            DB::commit();

            // Cargar relaciones
            $rutina->load(['detalles.ejercicio.grupoMuscular', 'detalles.ejercicio.video', 'cliente', 'entrenador']);

            return response()->json(['status' => 'ok', 'msg' => 'Rutina maestra creada exitosamente', 'data' => $rutina]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Crear rutina maestra con sus detalles
     */
    public function store(Request $request){
        DB::beginTransaction();
        try {
            Log::info('store: request data', $request->all());

            // Validar que valoracion_id sea requerido y exista
            if(!$request->has('valoracion_id') || !$request->valoracion_id){
                return response()->json(['status' => 'fail', 'msg' => 'El campo valoracion_id es requerido', 'data' => null], 400);
            }

            $valoracion = \App\Models\Valoracion::find($request->valoracion_id);
            if(!$valoracion){
                return response()->json(['status' => 'fail', 'msg' => 'La valoración especificada no existe', 'data' => null], 404);
            }

            // Validar que el nombre no exista
            $rutina_existente = RutinaMaestra::where('cliente_id', $request->cliente_id)->where('estado', 'activo')
            ->where('trimestre', $request->trimestre) ->first();
            if($rutina_existente){
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe una rutina activa para este cliente en este trimestre', 'data' => null], 400);
            }

            // Crear rutina maestra
            $rutina = new RutinaMaestra();
            $rutina->nombre = $request->nombre;
            $rutina->descripcion = $request->descripcion;
            $rutina->objetivo = $request->objetivo;
            $rutina->estado = 'activo';
            $rutina->cliente_id = $request->cliente_id ?? null;
            $rutina->entrenador_id = $request->entrenador_id ?? null;
            $rutina->valoracion_id = $request->valoracion_id;
            $rutina->fecha_inicio = $request->fecha_inicio ?? null;
            $rutina->fecha_fin = $request->fecha_fin ?? null;
            $rutina->trimestre = $request->trimestre ?? null;
            $rutina->save();

            Log::info('store: rutina creada con id', ['id' => $rutina->id]);

            // Intentar obtener detalles de dias_ejercicios o detalles
            $detalles_input = $request->input('dias_ejercicios') ?? $request->input('detalles');
            Log::info('store: detalles_input', ['detalles_input' => $detalles_input]);

            $detalles = $this->parseDetalles($detalles_input);
            Log::info('store: detalles parseados', ['count' => count($detalles)]);

            // Crear detalles si se enviaron
            if(!empty($detalles)){
                foreach($detalles as $detalle_data){
                    $detalle = new DetalleRutinaMaestra();
                    $detalle->rutina_maestra_id = $rutina->id;
                    $detalle->ejercicio_id = $detalle_data['ejercicio_id'] ?? null;
                    $detalle->dia_semana = $detalle_data['dia_semana'] ?? null;
                    $detalle->nombre_dia_personalizado = $detalle_data['nombre_dia_personalizado'] ?? null;
                    $detalle->series_estandar = $detalle_data['series_estandar'] ?? 0;
                    $detalle->repeticiones_estandar = $detalle_data['repeticiones_estandar'] ?? 0;
                    $detalle->tiempo_descanso_estandar_seg = $detalle_data['tiempo_descanso_estandar_seg'] ?? 0;
                    $detalle->orden_ejecucion = $detalle_data['orden_ejecucion'] ?? 1;
                    $detalle->notas = $detalle_data['notas'] ?? null;
                    $detalle->estado = 'activo';
                    $detalle->mesociclo = $detalle_data['mesociclo'] ?? 0;
                    $detalle->peso = $detalle_data['peso'] ?? 0;
                    $detalle->save();
                    Log::info('store: detalle guardado', ['ejercicio_id' => $detalle->ejercicio_id, 'dia_semana' => $detalle->dia_semana]);
                }
            }

            DB::commit();

            // Cargar relaciones
            $rutina->load(['detalles.ejercicio.grupoMuscular', 'detalles.ejercicio.video', 'cliente', 'entrenador']);

            return response()->json(['status' => 'ok', 'msg' => 'Rutina maestra creada exitosamente', 'data' => $rutina]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Mostrar rutina maestra con sus detalles
     */
    public function show($id){
        try {
            $rutina = RutinaMaestra::with(['detalles' => function($query) {
                $query->orderBy('mesociclo')->orderBy('orden_ejecucion');
            }, 'detalles.ejercicio.video', 'cliente', 'entrenador'])->find($id);
            if(!$rutina){
                return response()->json(['status' => 'fail', 'msg' => 'Plan de Entrenamiento no encontrado', 'data' => null], 404);
            }
            
            // Formatear detalles para que solo incluyan los campos necesarios
            $rutina->detalles = $rutina->detalles->map(function($detalle) {
                $video_url = null;
                if($detalle->ejercicio && $detalle->ejercicio->video) {
                    $video_url = $detalle->ejercicio->video->url_temporal;
                }
                
                return [
                    'id' => $detalle->id,
                    'ejercicio_id' => $detalle->ejercicio_id,
                    'dia_semana' => $detalle->dia_semana,
                    'nombre_dia_personalizado' => $detalle->nombre_dia_personalizado,
                    'series_estandar' => $detalle->series_estandar,
                    'repeticiones_estandar' => $detalle->repeticiones_estandar,
                    'tiempo_descanso_estandar_seg' => $detalle->tiempo_descanso_estandar_seg,
                    'orden_ejecucion' => $detalle->orden_ejecucion,
                    'notas' => $detalle->notas,
                    'estado' => $detalle->estado,
                    'mesociclo' => $detalle->mesociclo,
                    'peso' => $detalle->peso,
                    'video_url' => $video_url
                ];
            });
            
            return response()->json(['data' => $rutina]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener rutina maestra de un cliente por número de documento
     */
    public function showByCliente(Request $request){
        try {
            $numero_documento = $request->input('numero_documento');
            
            if(!$numero_documento){
                return response()->json(['status' => 'fail', 'msg' => 'numero_documento es requerido', 'data' => null], 400);
            }

            // Buscar cliente por número de documento
            $cliente = Cliente::where('numero_documento', $numero_documento)->first();
            
            if(!$cliente){
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
            }

            // Buscar rutina activa del cliente
            $rutina = RutinaMaestra::where('cliente_id', $cliente->id)
                ->where('estado', 'activo')
                ->with(['detalles.ejercicio.video', 'cliente', 'entrenador'])
                ->first();
            
            if(!$rutina){
                return response()->json(['status' => 'fail', 'msg' => 'Plan de Entrenamiento no encontrado para este cliente', 'data' => null], 404);
            }

            // Calcular mesociclo activo basado en fecha_inicio y fecha actual
            $mesociclo_activo = 1;
            if($rutina->fecha_inicio){
                $fecha_inicio = \Carbon\Carbon::parse($rutina->fecha_inicio);
                $fecha_actual = \Carbon\Carbon::now();
                
                // Si la fecha actual es anterior a fecha_inicio, mesociclo = 1
                if($fecha_actual->lt($fecha_inicio)){
                    $mesociclo_activo = 1;
                } else {
                    // Calcular cuántos meses completos han pasado desde fecha_inicio
                    $meses_transcurridos = (int)$fecha_inicio->diffInMonths($fecha_actual);
                    
                    // Mesociclo es 1, 2 o 3 (uno por mes)
                    $mesociclo_activo = min($meses_transcurridos + 1, 3);
                }
            }

            Log::info('showByCliente: mesociclo_activo calculado', [
                'mesociclo_activo' => $mesociclo_activo,
                'fecha_inicio' => $rutina->fecha_inicio,
                'total_detalles' => $rutina->detalles->count()
            ]);
            
            // Filtrar detalles solo del mesociclo activo y ordenar
            $detalles_filtrados = [];
            $detalles_filtrados = DetalleRutinaMaestra::where('rutina_maestra_id', $rutina->id)
                ->where('mesociclo', $mesociclo_activo)
                ->with(['ejercicio.video', 'progresos'])
                ->get()
                ->map(function($detalle) {
                    $video_url = null;
                    if($detalle->ejercicio && $detalle->ejercicio->video) {
                        $video_url = $detalle->ejercicio->video->url_temporal;
                    }

                    $progresos = $detalle->progresos->map(function($progreso) {
                        return [
                            'id' => $progreso->id,
                            'fecha_entrenamiento' => $progreso->fecha_entrenamiento ? $progreso->fecha_entrenamiento->toDateString() : null,
                            'comentarios' => $progreso->comentarios,
                            'estado' => $progreso->estado,
                            'created_at' => $progreso->created_at,
                            'updated_at' => $progreso->updated_at
                        ];
                    });

                    return [
                        'id' => $detalle->id,
                        'ejercicio_id' => $detalle->ejercicio_id,
                        'dia_semana' => $detalle->dia_semana,
                        'nombre_dia_personalizado' => $detalle->nombre_dia_personalizado,
                        'series_estandar' => $detalle->series_estandar,
                        'repeticiones_estandar' => $detalle->repeticiones_estandar,
                        'tiempo_descanso_estandar_seg' => $detalle->tiempo_descanso_estandar_seg,
                        'orden_ejecucion' => $detalle->orden_ejecucion,
                        'notas' => $detalle->notas,
                        'estado' => $detalle->estado,
                        'mesociclo' => $detalle->mesociclo,
                        'peso' => $detalle->peso,
                        'video_url' => $video_url,
                        'progreso' => $progresos
                    ];
                })
                ->toArray();

            // Ordenar por orden_ejecucion
            usort($detalles_filtrados, function($a, $b) {
                return $a['orden_ejecucion'] <=> $b['orden_ejecucion'];
            });

            Log::info('showByCliente: detalles filtrados', [
                'count' => count($detalles_filtrados)
            ]);
           
            $rutina->detalles = $detalles_filtrados;
            $rutina->detalles_filtrados = $detalles_filtrados;
            
            // Agregar info del mesociclo activo a la respuesta
            $rutina->mesociclo_activo = $mesociclo_activo;
            
            return response()->json(['data' => $rutina]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Actualizar rutina maestra completa con sus detalles (ruta personalizada para frontend)
     */
    public function updateCompleta(Request $request, $id){
        DB::beginTransaction();
        try {
            Log::info('updateCompleta: request data', $request->all());

            $rutina = RutinaMaestra::find($id);
            if(!$rutina){
                return response()->json(['status' => 'fail', 'msg' => 'Plan de Entrenamiento no encontrado', 'data' => null], 404);
            }

            // Validar valoracion_id si se está actualizando
            if($request->has('valoracion_id')){
                $valoracion = \App\Models\Valoracion::find($request->valoracion_id);
                if(!$valoracion){
                    return response()->json(['status' => 'fail', 'msg' => 'La valoración especificada no existe', 'data' => null], 404);
                }
            }

            // Actualizar rutina maestra
            if($request->has('nombre')) $rutina->nombre = $request->nombre;
            if($request->has('descripcion')) $rutina->descripcion = $request->descripcion;
            if($request->has('objetivo')) $rutina->objetivo = $request->objetivo;
            if($request->has('estado')) $rutina->estado = $request->estado;
            if($request->has('cliente_id')) $rutina->cliente_id = $request->cliente_id;
            if($request->has('entrenador_id')) $rutina->entrenador_id = $request->entrenador_id;
            if($request->has('valoracion_id')) $rutina->valoracion_id = $request->valoracion_id;
            $rutina->save();

            Log::info('updateCompleta: rutina actualizada con id', ['id' => $rutina->id]);

            // Intentar obtener detalles de dias_ejercicios o detalles
            $detalles_input = $request->input('dias_ejercicios') ?? $request->input('detalles');
            Log::info('updateCompleta: detalles_input', ['detalles_input' => $detalles_input, 'has_dias_ejercicios' => $request->has('dias_ejercicios'), 'has_detalles' => $request->has('detalles')]);

            // Solo procesar detalles si se enviaron explícitamente
            if($request->has('dias_ejercicios') || $request->has('detalles')){
                $detalles = $this->parseDetalles($detalles_input);
                Log::info('updateCompleta: detalles parseados', ['count' => count($detalles), 'detalles' => $detalles]);

                // Actualizar detalles si se enviaron
                if(!empty($detalles)){
                    // Eliminar detalles existentes (soft delete)
                    $deleted_count = DetalleRutinaMaestra::where('rutina_maestra_id', $rutina->id)->delete();
                    Log::info('updateCompleta: detalles eliminados', ['count' => $deleted_count]);

                    // Crear nuevos detalles
                    foreach($detalles as $index => $detalle_data){
                        Log::info('updateCompleta: procesando detalle', ['index' => $index, 'detalle_data' => $detalle_data]);
                        
                        $detalle = new DetalleRutinaMaestra();
                        $detalle->rutina_maestra_id = $rutina->id;
                        $detalle->ejercicio_id = $detalle_data['ejercicio_id'] ?? null;
                        $detalle->dia_semana = $detalle_data['dia_semana'] ?? null;
                        $detalle->nombre_dia_personalizado = $detalle_data['nombre_dia_personalizado'] ?? null;
                        $detalle->series_estandar = $detalle_data['series_estandar'] ?? 0;
                        $detalle->repeticiones_estandar = $detalle_data['repeticiones_estandar'] ?? 0;
                        $detalle->tiempo_descanso_estandar_seg = $detalle_data['tiempo_descanso_estandar_seg'] ?? 0;
                        $detalle->orden_ejecucion = $detalle_data['orden_ejecucion'] ?? 1;
                        $detalle->notas = $detalle_data['notas'] ?? null;
                        $detalle->estado = $detalle_data['estado'] ?? 'activo';
                        $detalle->mesociclo = $detalle_data['mesociclo'] ?? 0;
                        $detalle->peso = $detalle_data['peso'] ?? 0;
                        
                        $save_result = $detalle->save();
                        Log::info('updateCompleta: detalle guardado', [
                            'save_result' => $save_result,
                            'detalle_id' => $detalle->id,
                            'ejercicio_id' => $detalle->ejercicio_id, 
                            'dia_semana' => $detalle->dia_semana
                        ]);
                    }
                } else {
                    Log::info('updateCompleta: detalles array vacío después de parsear');
                }
            } else {
                Log::info('updateCompleta: no se enviaron detalles, manteniendo los existentes');
            }

            DB::commit();

            // Cargar relaciones
            $rutina->load(['detalles.ejercicio.grupoMuscular', 'detalles.ejercicio.video', 'cliente', 'entrenador']);

            return response()->json(['status' => 'ok', 'msg' => 'Rutina maestra actualizada exitosamente', 'data' => $rutina]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Actualizar rutina maestra y sus detalles
     */
    public function update(Request $request, $id){
        DB::beginTransaction();
        try {
            Log::info('update: request data', $request->all());

            $rutina = RutinaMaestra::find($id);
            if(!$rutina){
                return response()->json(['status' => 'fail', 'msg' => 'Rutina maestra no encontrada', 'data' => null], 404);
            }

            // Validar nombre único si se está cambiando
            if($request->has('nombre') && $request->nombre != $rutina->nombre){
                $rutina_existente = RutinaMaestra::where('nombre', $request->nombre)->first();
                if($rutina_existente){
                    return response()->json(['status' => 'fail', 'msg' => 'Ya existe una rutina con ese nombre', 'data' => null], 400);
                }
            }

            // Validar valoracion_id si se está actualizando
            if($request->has('valoracion_id')){
                $valoracion = \App\Models\Valoracion::find($request->valoracion_id);
                if(!$valoracion){
                    return response()->json(['status' => 'fail', 'msg' => 'La valoración especificada no existe', 'data' => null], 404);
                }
            }

            // Actualizar rutina maestra
            if($request->has('nombre')) $rutina->nombre = $request->nombre;
            if($request->has('descripcion')) $rutina->descripcion = $request->descripcion;
            if($request->has('objetivo')) $rutina->objetivo = $request->objetivo;
            if($request->has('estado')) $rutina->estado = $request->estado;
            if($request->has('cliente_id')) $rutina->cliente_id = $request->cliente_id;
            if($request->has('entrenador_id')) $rutina->entrenador_id = $request->entrenador_id;
            if($request->has('valoracion_id')) $rutina->valoracion_id = $request->valoracion_id;
            $rutina->save();

            Log::info('update: rutina actualizada con id', ['id' => $rutina->id]);

            // Intentar obtener detalles de dias_ejercicios o detalles
            $detalles_input = $request->input('dias_ejercicios') ?? $request->input('detalles');
            Log::info('update: detalles_input', ['detalles_input' => $detalles_input, 'has_dias_ejercicios' => $request->has('dias_ejercicios'), 'has_detalles' => $request->has('detalles')]);

            // Solo procesar detalles si se enviaron explícitamente
            if($request->has('dias_ejercicios') || $request->has('detalles')){
                $detalles = $this->parseDetalles($detalles_input);
                Log::info('update: detalles parseados', ['count' => count($detalles), 'detalles' => $detalles]);

                // Actualizar detalles si se enviaron
                if(!empty($detalles)){
                    // Eliminar detalles existentes (soft delete)
                    $deleted_count = DetalleRutinaMaestra::where('rutina_maestra_id', $rutina->id)->delete();
                    Log::info('update: detalles eliminados', ['count' => $deleted_count]);

                    // Crear nuevos detalles
                    foreach($detalles as $index => $detalle_data){
                        Log::info('update: procesando detalle', ['index' => $index, 'detalle_data' => $detalle_data]);
                        
                        $detalle = new DetalleRutinaMaestra();
                        $detalle->rutina_maestra_id = $rutina->id;
                        $detalle->ejercicio_id = $detalle_data['ejercicio_id'] ?? null;
                        $detalle->dia_semana = $detalle_data['dia_semana'] ?? null;
                        $detalle->nombre_dia_personalizado = $detalle_data['nombre_dia_personalizado'] ?? null;
                        $detalle->series_estandar = $detalle_data['series_estandar'] ?? 0;
                        $detalle->repeticiones_estandar = $detalle_data['repeticiones_estandar'] ?? 0;
                        $detalle->tiempo_descanso_estandar_seg = $detalle_data['tiempo_descanso_estandar_seg'] ?? 0;
                        $detalle->orden_ejecucion = $detalle_data['orden_ejecucion'] ?? 1;
                        $detalle->notas = $detalle_data['notas'] ?? null;
                        $detalle->estado = 'activo';
                        $detalle->mesociclo = $detalle_data['mesociclo'] ?? 0;
                        $detalle->peso = $detalle_data['peso'] ?? 0;
                        
                        $save_result = $detalle->save();
                        Log::info('update: detalle guardado', [
                            'save_result' => $save_result,
                            'detalle_id' => $detalle->id,
                            'ejercicio_id' => $detalle->ejercicio_id, 
                            'dia_semana' => $detalle->dia_semana
                        ]);
                    }
                } else {
                    Log::info('update: detalles array vacío después de parsear');
                }
            } else {
                Log::info('update: no se enviaron detalles, manteniendo los existentes');
            }

            DB::commit();

            // Cargar relaciones
            $rutina->load(['detalles.ejercicio.grupoMuscular', 'detalles.ejercicio.video', 'cliente', 'entrenador']);

            return response()->json(['status' => 'ok', 'msg' => 'Rutina maestra actualizada exitosamente', 'data' => $rutina]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar rutina maestra y sus detalles
     */
    public function destroy($id){
        DB::beginTransaction();
        try {
            $rutina = RutinaMaestra::find($id);
            if(!$rutina){
                return response()->json(['status' => 'fail', 'msg' => 'Rutina maestra no encontrada', 'data' => null], 404);
            }

            // Eliminar detalles (soft delete - CASCADE)
            DetalleRutinaMaestra::where('rutina_maestra_id', $rutina->id)->delete();

            // Eliminar rutina maestra
            $rutina->delete();

            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'Rutina maestra eliminada exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Agregar detalle a una rutina maestra existente
     */
    public function addDetalle(Request $request, $id){
        try {
            $rutina = RutinaMaestra::find($id);
            if(!$rutina){
                return response()->json(['status' => 'fail', 'msg' => 'Rutina maestra no encontrada', 'data' => null], 404);
            }

            $detalle = new DetalleRutinaMaestra();
            $detalle->rutina_maestra_id = $rutina->id;
            $detalle->ejercicio_id = $request->ejercicio_id;
            $detalle->dia_semana = $request->dia_semana;
            $detalle->nombre_dia_personalizado = $request->nombre_dia_personalizado ?? null;
            $detalle->series_estandar = $request->series_estandar ?? 0;
            $detalle->repeticiones_estandar = $request->repeticiones_estandar ?? 0;
            $detalle->tiempo_descanso_estandar_seg = $request->tiempo_descanso_estandar_seg ?? 0;
            $detalle->orden_ejecucion = $request->orden_ejecucion ?? 1;
            $detalle->notas = $request->notas ?? null;
            $detalle->estado = 'activo';
            $detalle->mesociclo = $request->mesociclo ?? 0;
            $detalle->peso = $request->peso ?? 0;
            $detalle->save();

            // Cargar relaciones
            $detalle->load(['ejercicio.grupoMuscular', 'ejercicio.video']);

            return response()->json(['status' => 'ok', 'msg' => 'Detalle agregado exitosamente', 'data' => $detalle]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Actualizar un detalle específico
     */
    public function updateDetalle(Request $request, $rutina_id, $detalle_id){
        try {
            $detalle = DetalleRutinaMaestra::where('id', $detalle_id)
                ->where('rutina_maestra_id', $rutina_id)
                ->first();

            if(!$detalle){
                return response()->json(['status' => 'fail', 'msg' => 'Detalle no encontrado', 'data' => null], 404);
            }

            if($request->has('ejercicio_id')) $detalle->ejercicio_id = $request->ejercicio_id;
            if($request->has('dia_semana')) $detalle->dia_semana = $request->dia_semana;
            if($request->has('nombre_dia_personalizado')) $detalle->nombre_dia_personalizado = $request->nombre_dia_personalizado;
            if($request->has('series_estandar')) $detalle->series_estandar = $request->series_estandar;
            if($request->has('repeticiones_estandar')) $detalle->repeticiones_estandar = $request->repeticiones_estandar;
            if($request->has('tiempo_descanso_estandar_seg')) $detalle->tiempo_descanso_estandar_seg = $request->tiempo_descanso_estandar_seg;
            if($request->has('orden_ejecucion')) $detalle->orden_ejecucion = $request->orden_ejecucion;
            if($request->has('descripcion')) $detalle->descripcion = $request->notas;
            if($request->has('estado')) $detalle->estado = $request->estado;
            if($request->has('mesociclo')) $detalle->mesociclo = $request->mesociclo;
            if($request->has('peso')) $detalle->peso = $request->peso;

            $detalle->save();

            // Cargar relaciones
            $detalle->load(['ejercicio.grupoMuscular', 'ejercicio.video']);

            return response()->json(['status' => 'ok', 'msg' => 'Detalle actualizado exitosamente', 'data' => $detalle]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar un detalle específico
     */
    public function deleteDetalle($rutina_id, $detalle_id){
        try {
            $detalle = DetalleRutinaMaestra::where('id', $detalle_id)
                ->where('rutina_maestra_id', $rutina_id)
                ->first();

            if(!$detalle){
                return response()->json(['status' => 'fail', 'msg' => 'Detalle no encontrado', 'data' => null], 404);
            }

            $detalle->delete();

            return response()->json(['status' => 'ok', 'msg' => 'Detalle eliminado exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener rutinas maestras filtradas por entrenador
     */
    public function porEntrenador(Request $request){
        try {
            $entrenador_id = $request->input('entrenador_id');
            
            if(!$entrenador_id){
                return response()->json(['status' => 'fail', 'msg' => 'entrenador_id es requerido', 'data' => null], 400);
            }

            $rutinas = RutinaMaestra::where('entrenador_id', $entrenador_id)
                ->with(['detalles.ejercicio.grupoMuscular', 'detalles.ejercicio.video', 'cliente', 'entrenador'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $rutinas]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Normaliza el payload de detalles (acepta array, JSON string o estructura dias_ejercicios)
     */
    private function parseDetalles($detalles)
    {
        // Log para debugging
        Log::info('parseDetalles input:', ['detalles' => $detalles, 'type' => gettype($detalles)]);

        if (is_string($detalles)) {
            $decoded = json_decode($detalles, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $detalles = $decoded;
            } else {
                return [];
            }
        }

        if (!is_array($detalles)) {
            Log::info('parseDetalles: no es array, retornando []');
            return [];
        }

        // Si viene en formato dias_ejercicios, transformar a array plano con dia_semana
        if (isset($detalles['lunes']) || isset($detalles['martes']) || isset($detalles['miercoles']) || 
            isset($detalles['jueves']) || isset($detalles['viernes']) || isset($detalles['sabado']) || 
            isset($detalles['domingo'])) {
            
            Log::info('parseDetalles: detectado formato dias_ejercicios');
            
            $dias_map = [
                'lunes' => 1,
                'martes' => 2,
                'miercoles' => 3,
                'jueves' => 4,
                'viernes' => 5,
                'sabado' => 6,
                'domingo' => 7
            ];

            $detalles_planos = [];
            foreach ($dias_map as $dia_nombre => $dia_numero) {
                if (isset($detalles[$dia_nombre]) && is_array($detalles[$dia_nombre])) {
                    foreach ($detalles[$dia_nombre] as $detalle) {
                        $detalle['dia_semana'] = $dia_numero;
                        $detalles_planos[] = $detalle;
                    }
                }
            }
            Log::info('parseDetalles: detalles_planos generados', ['count' => count($detalles_planos)]);
            return $detalles_planos;
        }

        Log::info('parseDetalles: retornando detalles como array plano', ['count' => count($detalles)]);
        return $detalles;
    }
}
