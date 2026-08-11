<?php

namespace App\Http\Controllers;

use App\Models\Valoracion;
use App\Models\Cliente;
use App\Models\ImcClasificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValoracionController extends Controller
{
    /**
     * Listar todas las valoraciones con paginación
     */
    public function index(Request $request){
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $valoraciones = Valoracion::with(['cliente', 'entrenador', 'imcClasificacion'])
                    ->paginate($perPage);
                
                return response()->json([
                    'data' => $valoraciones->items(),
                    'current_page' => $valoraciones->currentPage(),
                    'per_page' => $valoraciones->perPage(),
                    'total' => $valoraciones->total(),
                    'last_page' => $valoraciones->lastPage()
                ]);
            } else {
                $valoraciones = Valoracion::with(['cliente', 'entrenador', 'imcClasificacion'])
                    ->get();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $valoraciones]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    /**
     * Crear una nueva valoración
     */
    public function store(Request $request){
        DB::beginTransaction();
        try {
            // Validar que el cliente existe
            $cliente = Cliente::find($request->cliente_id);
            if(!$cliente){
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
            }

            // Calcular automáticamente el trimestre y tipo de valoración
            $trimestreInfo = $this->calcularTrimestreYTipo($request->cliente_id);
            
            // Validar si hay error (ej: se excedieron los 4 trimestres)
            if(isset($trimestreInfo['error']) && $trimestreInfo['error']){
                return response()->json(['status' => 'fail', 'msg' => $trimestreInfo['mensaje'], 'data' => null], 400);
            }
            
            $trimestre = $trimestreInfo['trimestre'];
            $tipo_valoracion = $trimestreInfo['tipo_valoracion'];

            // Validar que no exista ya una valoración con el mismo trimestre y tipo para este cliente
            $existe_duplicado = Valoracion::where('cliente_id', $request->cliente_id)
                ->where('trimestre', $trimestre)
                ->where('tipo_valoracion', $tipo_valoracion)
                ->exists();

            if($existe_duplicado){
                return response()->json([
                    'status' => 'fail', 
                    'msg' => "Ya existe una valoración de tipo '{$tipo_valoracion}' para el trimestre {$trimestre} de este cliente", 
                    'data' => null
                ], 400);
            }

            $valoracion = new Valoracion();
            $valoracion->cliente_id = $request->cliente_id;
            $valoracion->entrenador_id = $request->entrenador_id ?? null;
            $valoracion->imc_clasificacion_id = $request->imc_clasificacion_id ?? null;
            $valoracion->hombros = $request->hombros ?? 0.00;
            $valoracion->pecho = $request->pecho ?? 0.00;
            $valoracion->biceps = $request->biceps ?? 0.00;
            $valoracion->antebrazos = $request->antebrazos ?? 0.00;
            $valoracion->cintura = $request->cintura ?? 0.00;
            $valoracion->cadera = $request->cadera ?? 0.00;
            $valoracion->pierna = $request->pierna ?? 0.00;
            $valoracion->medio_pierna = $request->medio_pierna ?? 0.00;
            $valoracion->pantorrilla = $request->pantorrilla ?? 0.00;
            $valoracion->cuello = $request->cuello ?? 0.00;
            $valoracion->espalda = $request->espalda ?? 0.00;
            $valoracion->peso = $request->peso ?? 0.00;
            $valoracion->estatura = $request->estatura ?? null;
            $valoracion->edad = $request->edad ?? null;
            $valoracion->sexo = $request->sexo ?? 'Hombre';
            $valoracion->frecuencia1 = $request->frecuencia1 ?? null;
            $valoracion->frecuencia2 = $request->frecuencia2 ?? null;
            $valoracion->frecuencia3 = $request->frecuencia3 ?? null;
            $valoracion->push_up = $request->push_up ?? null;
            $valoracion->abdominales = $request->abdominales ?? null;
            $valoracion->perimetro_abdominal = $request->perimetro_abdominal ?? null;
            $valoracion->brazo_izquierdo = $request->brazo_izquierdo ?? null;
            $valoracion->brazo_derecho = $request->brazo_derecho ?? null;
            $valoracion->pierna_izquierda = $request->pierna_izquierda ?? null;
            $valoracion->pierna_derecha = $request->pierna_derecha ?? null;
            $valoracion->porcentaje_grasa = $request->porcentaje_grasa ?? null;
            $valoracion->porcentaje_musculo = $request->porcentaje_musculo ?? null;
            $valoracion->grasa_visceral = $request->grasa_visceral ?? null;
            $valoracion->masa_musculo_esqueletica = $request->masa_musculo_esqueletica ?? null;
            $valoracion->hipertension = $request->hipertension ?? false;
            $valoracion->fuma = $request->fuma ?? false;
            $valoracion->lesion = $request->lesion ?? false;
            $valoracion->tiempo_sin_entrenar = $request->tiempo_sin_entrenar ?? null;
            $valoracion->toma_foto = $request->toma_foto ?? false;
            $valoracion->descripcion_lesion = $request->descripcion_lesion ?? null;
            $valoracion->observaciones = $request->observaciones ?? null;
            $valoracion->objetivo = $request->objetivo ?? null;
            $valoracion->fecha_registro = $request->fecha_registro;
            $valoracion->fecha_inicio = $request->fecha_inicio ?? null;
            $valoracion->fecha_fin = $request->fecha_fin ?? null;
            $valoracion->objetivo = $request->objetivo ?? null;
            $valoracion->trimestre = $trimestre;
            $valoracion->tipo_valoracion = $tipo_valoracion;
            $valoracion->created_at = now();


            // Calcular IMC si se proporciona peso y estatura
            if($request->peso && $request->estatura) {
                $imc = $valoracion->calcularImc();
                
                // Buscar la clasificación de IMC automáticamente
                if($imc) {
                    $clasificacion = ImcClasificacion::where('rango_min', '<=', $imc)
                        ->where('rango_max', '>=', $imc)
                        ->first();
                    
                    if($clasificacion) {
                        $valoracion->imc_clasificacion_id = $clasificacion->id;
                    }
                }
            }

            $valoracion->save();

            // Si es una valoración final y no es el trimestre 4, crear automáticamente la valoración inicial del siguiente trimestre
            $valoracion_siguiente = null;
            if($tipo_valoracion === 'final' && $trimestre < 4){
                $siguiente_trimestre = $trimestre + 1;
                
                // Crear la valoración inicial del siguiente trimestre con los mismos datos base
                $valoracion_siguiente = new Valoracion();
                $valoracion_siguiente->cliente_id = $valoracion->cliente_id;
                $valoracion_siguiente->entrenador_id = $valoracion->entrenador_id;
                $valoracion_siguiente->trimestre = $siguiente_trimestre;
                $valoracion_siguiente->tipo_valoracion = 'inicial';
                $valoracion_siguiente->fecha_registro = $request->fecha_registro;
                $valoracion_siguiente->fecha_inicio = $request->fecha_inicio ?? null;
                $valoracion_siguiente->fecha_fin = $request->fecha_fin ?? null;
                
                // Copiar las mediciones actuales como punto de partida
                $valoracion_siguiente->peso = $valoracion->peso;
                $valoracion_siguiente->estatura = $valoracion->estatura;
                $valoracion_siguiente->edad = $valoracion->edad;
                $valoracion_siguiente->sexo = $valoracion->sexo;
                $valoracion_siguiente->hombros = $valoracion->hombros;
                $valoracion_siguiente->pecho = $valoracion->pecho;
                $valoracion_siguiente->biceps = $valoracion->biceps;
                $valoracion_siguiente->antebrazos = $valoracion->antebrazos;
                $valoracion_siguiente->cintura = $valoracion->cintura;
                $valoracion_siguiente->cadera = $valoracion->cadera;
                $valoracion_siguiente->pierna = $valoracion->pierna;
                $valoracion_siguiente->medio_pierna = $valoracion->medio_pierna;
                $valoracion_siguiente->pantorrilla = $valoracion->pantorrilla;
                $valoracion_siguiente->cuello = $valoracion->cuello;
                $valoracion_siguiente->espalda = $valoracion->espalda;
                $valoracion_siguiente->perimetro_abdominal = $valoracion->perimetro_abdominal;
                $valoracion_siguiente->brazo_izquierdo = $valoracion->brazo_izquierdo;
                $valoracion_siguiente->brazo_derecho = $valoracion->brazo_derecho;
                $valoracion_siguiente->pierna_izquierda = $valoracion->pierna_izquierda;
                $valoracion_siguiente->pierna_derecha = $valoracion->pierna_derecha;
                $valoracion_siguiente->porcentaje_grasa = $valoracion->porcentaje_grasa;
                $valoracion_siguiente->porcentaje_musculo = $valoracion->porcentaje_musculo;
                $valoracion_siguiente->grasa_visceral = $valoracion->grasa_visceral;
                $valoracion_siguiente->masa_musculo_esqueletica = $valoracion->masa_musculo_esqueletica;
                $valoracion_siguiente->frecuencia1 = $valoracion->frecuencia1;
                $valoracion_siguiente->frecuencia2 = $valoracion->frecuencia2;
                $valoracion_siguiente->frecuencia3 = $valoracion->frecuencia3;
                $valoracion_siguiente->push_up = $valoracion->push_up;
                $valoracion_siguiente->abdominales = $valoracion->abdominales;
                $valoracion_siguiente->hipertension = $valoracion->hipertension;
                $valoracion_siguiente->fuma = $valoracion->fuma;
                $valoracion_siguiente->lesion = $valoracion->lesion;
                $valoracion_siguiente->tiempo_sin_entrenar = $valoracion->tiempo_sin_entrenar;
                $valoracion_siguiente->toma_foto = $valoracion->toma_foto;
                $valoracion_siguiente->descripcion_lesion = $valoracion->descripcion_lesion;
                $valoracion_siguiente->observaciones = "Valoración inicial del trimestre {$siguiente_trimestre} (generada automáticamente)";
                $valoracion_siguiente->objetivo = $valoracion->objetivo;
                
                $valoracion_siguiente->created_at = now();
                
                // Calcular IMC para la nueva valoración
                if($valoracion_siguiente->peso && $valoracion_siguiente->estatura) {
                    $imc = $valoracion_siguiente->calcularImc();
                    if($imc) {
                        $clasificacion = ImcClasificacion::where('rango_min', '<=', $imc)
                            ->where('rango_max', '>=', $imc)
                            ->first();
                        if($clasificacion) {
                            $valoracion_siguiente->imc_clasificacion_id = $clasificacion->id;
                        }
                    }
                }
                
                $valoracion_siguiente->save();
            }

            DB::commit();

            // Cargar relaciones
            $valoracion->load(['cliente', 'entrenador', 'imcClasificacion']);
            
            $response_data = [
                'valoracion_creada' => $valoracion
            ];
            
            // Si se creó la valoración del siguiente trimestre, incluirla en la respuesta
            if($valoracion_siguiente){
                $valoracion_siguiente->load(['cliente', 'entrenador', 'imcClasificacion']);
                $response_data['valoracion_siguiente_trimestre'] = $valoracion_siguiente;
                $response_data['mensaje_adicional'] = "Se creó automáticamente la valoración inicial del trimestre {$siguiente_trimestre}";
            }

            return response()->json([
                'status' => 'ok', 
                'msg' => 'Valoración creada exitosamente', 
                'data' => $response_data
            ]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Mostrar una valoración específica
     */
    public function show($id){
        try {
            $valoracion = Valoracion::with(['cliente', 'entrenador', 'imcClasificacion'])->find($id);
            if(!$valoracion){
                return response()->json(['status' => 'fail', 'msg' => 'Valoración no encontrada', 'data' => null], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $valoracion]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Actualizar una valoración
     */
    public function update(Request $request, $id){
        DB::beginTransaction();
        try {
            $valoracion = Valoracion::find($id);
            if(!$valoracion){
                return response()->json(['status' => 'fail', 'msg' => 'Valoración no encontrada', 'data' => null], 404);
            }

            if($request->has('cliente_id')) {
                return response()->json(['status' => 'fail', 'msg' => 'No se puede cambiar el cliente de una valoración existente', 'data' => null], 400);
            }

            // No permitir cambiar trimestre ni tipo_valoracion manualmente
            if($request->has('trimestre') || $request->has('tipo_valoracion')){
                return response()->json(['status' => 'fail', 'msg' => 'El trimestre y tipo de valoración son calculados automáticamente por el sistema', 'data' => null], 400);
            }

            if($request->has('entrenador_id')) $valoracion->entrenador_id = $request->entrenador_id;
            if($request->has('imc_clasificacion_id')) $valoracion->imc_clasificacion_id = $request->imc_clasificacion_id;
            if($request->has('hombros')) $valoracion->hombros = $request->hombros;
            if($request->has('pecho')) $valoracion->pecho = $request->pecho;
            if($request->has('biceps')) $valoracion->biceps = $request->biceps;
            if($request->has('antebrazos')) $valoracion->antebrazos = $request->antebrazos;
            if($request->has('cintura')) $valoracion->cintura = $request->cintura;
            if($request->has('cadera')) $valoracion->cadera = $request->cadera;
            if($request->has('pierna')) $valoracion->pierna = $request->pierna;
            if($request->has('medio_pierna')) $valoracion->medio_pierna = $request->medio_pierna;
            if($request->has('pantorrilla')) $valoracion->pantorrilla = $request->pantorrilla;
            if($request->has('cuello')) $valoracion->cuello = $request->cuello;
            if($request->has('espalda')) $valoracion->espalda = $request->espalda;
            if($request->has('peso')) $valoracion->peso = $request->peso;
            if($request->has('estatura')) $valoracion->estatura = $request->estatura;
            if($request->has('edad')) $valoracion->edad = $request->edad;
            if($request->has('sexo')) $valoracion->sexo = $request->sexo;
            if($request->has('frecuencia1')) $valoracion->frecuencia1 = $request->frecuencia1;
            if($request->has('frecuencia2')) $valoracion->frecuencia2 = $request->frecuencia2;
            if($request->has('frecuencia3')) $valoracion->frecuencia3 = $request->frecuencia3;
            if($request->has('push_up')) $valoracion->push_up = $request->push_up;
            if($request->has('abdominales')) $valoracion->abdominales = $request->abdominales;
            if($request->has('perimetro_abdominal')) $valoracion->perimetro_abdominal = $request->perimetro_abdominal;
            if($request->has('brazo_izquierdo')) $valoracion->brazo_izquierdo = $request->brazo_izquierdo;
            if($request->has('brazo_derecho')) $valoracion->brazo_derecho = $request->brazo_derecho;
            if($request->has('pierna_izquierda')) $valoracion->pierna_izquierda = $request->pierna_izquierda;
            if($request->has('pierna_derecha')) $valoracion->pierna_derecha = $request->pierna_derecha;
            if($request->has('porcentaje_grasa')) $valoracion->porcentaje_grasa = $request->porcentaje_grasa;
            if($request->has('porcentaje_musculo')) $valoracion->porcentaje_musculo = $request->porcentaje_musculo;
            if($request->has('grasa_visceral')) $valoracion->grasa_visceral = $request->grasa_visceral;
            if($request->has('masa_musculo_esqueletica')) $valoracion->masa_musculo_esqueletica = $request->masa_musculo_esqueletica;
            if($request->has('hipertension')) $valoracion->hipertension = $request->hipertension;
            if($request->has('fuma')) $valoracion->fuma = $request->fuma;
            if($request->has('lesion')) $valoracion->lesion = $request->lesion;
            if($request->has('tiempo_sin_entrenar')) $valoracion->tiempo_sin_entrenar = $request->tiempo_sin_entrenar;
            if($request->has('toma_foto')) $valoracion->toma_foto = $request->toma_foto;
            if($request->has('descripcion_lesion')) $valoracion->descripcion_lesion = $request->descripcion_lesion;
            if($request->has('observaciones')) $valoracion->observaciones = $request->observaciones;
            if($request->has('objetivo')) $valoracion->objetivo = $request->objetivo;
            if($request->has('fecha_inicio')) $valoracion->fecha_inicio = $request->fecha_inicio;
            if($request->has('fecha_fin')) $valoracion->fecha_fin = $request->fecha_fin;
            $valoracion->updated_at = now();

            // Recalcular IMC si se actualiza peso o estatura
            if($request->has('peso') || $request->has('estatura')) {
                if($valoracion->peso && $valoracion->estatura) {
                    $imc = $valoracion->calcularImc();
                    
                    // Buscar la clasificación de IMC automáticamente
                    if($imc) {
                        $clasificacion = ImcClasificacion::where('rango_min', '<=', $imc)
                            ->where('rango_max', '>=', $imc)
                            ->first();
                        
                        if($clasificacion) {
                            $valoracion->imc_clasificacion_id = $clasificacion->id;
                        }
                    }
                }
            }

            $valoracion->save();

            DB::commit();

            // Cargar relaciones
            $valoracion->load(['cliente', 'entrenador', 'imcClasificacion']);

            return response()->json(['status' => 'ok', 'msg' => 'Valoración actualizada exitosamente', 'data' => $valoracion]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar una valoración
     */
    public function destroy($id){
        DB::beginTransaction();
        try {
            $valoracion = Valoracion::find($id);
            if(!$valoracion){
                return response()->json(['status' => 'fail', 'msg' => 'Valoración no encontrada', 'data' => null], 404);
            }

            $valoracion->delete();

            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'Valoración eliminada exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener valoraciones por cliente
     */
    public function valoracionesPorCliente(Request $request){
        try {
            $cliente_id = $request->input('cliente_id');
            
            if(!$cliente_id){
                return response()->json(['status' => 'fail', 'msg' => 'cliente_id es requerido', 'data' => null], 400);
            }

            $valoraciones = Valoracion::where('cliente_id', $cliente_id)
                ->with(['cliente', 'entrenador', 'imcClasificacion'])
                ->orderBy('fecha_registro', 'desc')
                ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $valoraciones]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener última valoración de un cliente
     */
    public function ultimaValoracionCliente(Request $request){
        try {
            $cliente_id = $request->input('cliente_id');
            
            if(!$cliente_id){
                return response()->json(['status' => 'fail', 'msg' => 'cliente_id es requerido', 'data' => null], 400);
            }

            $valoracion = Valoracion::where('cliente_id', $cliente_id)
                ->with(['cliente', 'entrenador', 'imcClasificacion'])
                ->orderBy('fecha_registro', 'desc')
                ->first();

            if(!$valoracion){
                return response()->json(['status' => 'fail', 'msg' => 'No hay valoraciones para este cliente', 'data' => null], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $valoracion]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener valoraciones por rango de fechas
     */
    public function valoracionesPorFecha(Request $request){
        try {
            $cliente_id = $request->input('cliente_id');
            $fecha_inicio = $request->input('fecha_inicio');
            $fecha_fin = $request->input('fecha_fin');
            
            if(!$cliente_id || !$fecha_inicio || !$fecha_fin){
                return response()->json(['status' => 'fail', 'msg' => 'cliente_id, fecha_inicio y fecha_fin son requeridos', 'data' => null], 400);
            }

            $valoraciones = Valoracion::where('cliente_id', $cliente_id)
                ->whereBetween('fecha_registro', [$fecha_inicio, $fecha_fin])
                ->with(['cliente', 'entrenador', 'imcClasificacion'])
                ->orderBy('fecha_registro', 'desc')
                ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $valoraciones]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener valoraciones filtradas por entrenador
     */
    public function valoracionesPorEntrenador(Request $request){
        try {
            $entrenador_id = $request->input('entrenador_id');
            
            if(!$entrenador_id){
                return response()->json(['status' => 'fail', 'msg' => 'entrenador_id es requerido', 'data' => null], 400);
            }

            $valoraciones = Valoracion::where('entrenador_id', $entrenador_id)
                ->with(['cliente', 'entrenador', 'imcClasificacion'])
                ->orderBy('fecha_registro', 'desc')
                ->get();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $valoraciones]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Comparar avances entre la primera y última valoración de un cliente en el mismo trimestre
     * 
     * Parámetros:
     * - cliente_id: ID del cliente (requerido)
     * - trimestre: Número de trimestre (1-4) (opcional, si no se proporciona usa el trimestre de la última valoración)
     */
    public function compararAvances(Request $request){
        try {
            $cliente_id = $request->input('cliente_id');
            $trimestre = $request->input('trimestre');
            
            if(!$cliente_id){
                return response()->json(['status' => 'fail', 'msg' => 'cliente_id es requerido', 'data' => null], 400);
            }

            // Si no se proporciona trimestre, obtener el trimestre de la última valoración
            if(!$trimestre){
                $ultimaValoracion = Valoracion::where('cliente_id', $cliente_id)
                    ->orderBy('fecha_registro', 'desc')
                    ->first();
                
                if(!$ultimaValoracion || !$ultimaValoracion->trimestre){
                    return response()->json(['status' => 'fail', 'msg' => 'No hay valoraciones con trimestre asignado para este cliente', 'data' => null], 404);
                }
                
                $trimestre = $ultimaValoracion->trimestre;
            }

            // Obtener la primera y última valoración del cliente en ese trimestre
            $valoracion1 = Valoracion::where('cliente_id', $cliente_id)
                ->where('trimestre', $trimestre)
                ->with(['cliente', 'entrenador', 'imcClasificacion'])
                ->orderBy('fecha_registro', 'asc')
                ->first();

            $valoracion2 = Valoracion::where('cliente_id', $cliente_id)
                ->where('trimestre', $trimestre)
                ->with(['cliente', 'entrenador', 'imcClasificacion'])
                ->orderBy('fecha_registro', 'desc')
                ->first();

            if(!$valoracion1 || !$valoracion2){
                return response()->json(['status' => 'fail', 'msg' => 'No hay valoraciones para este cliente en el trimestre ' . $trimestre, 'data' => null], 404);
            }

            if($valoracion1->id === $valoracion2->id){
                return response()->json(['status' => 'fail', 'msg' => 'Se necesitan al menos 2 valoraciones diferentes en el trimestre para comparar', 'data' => null], 400);
            }

            // Calcular diferencias y porcentajes de cambio
            $comparacion = $this->calcularDiferencias($valoracion1, $valoracion2);
            $comparacion['trimestre'] = $trimestre;

            return response()->json([
                'status' => 'ok',
                'msg' => 'Comparación de avances exitosa',
                'data' => $comparacion
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Calcular diferencias entre dos valoraciones
     */
    private function calcularDiferencias($valoracion1, $valoracion2){
        $campos_a_comparar = [
            'peso' => 'Peso (kg)',
            'estatura' => 'Estatura (m)',
            'imc' => 'IMC',
            'hombros' => 'Hombros (cm)',
            'pecho' => 'Pecho (cm)',
            'biceps' => 'Bíceps (cm)',
            'antebrazos' => 'Antebrazos (cm)',
            'cintura' => 'Cintura (cm)',
            'cadera' => 'Cadera (cm)',
            'pierna' => 'Pierna (cm)',
            'medio_pierna' => 'Medio pierna (cm)',
            'pantorrilla' => 'Pantorrilla (cm)',
            'cuello' => 'Cuello (cm)',
            'espalda' => 'Espalda (cm)',
            'perimetro_abdominal' => 'Perímetro abdominal (cm)',
            'brazo_izquierdo' => 'Brazo izquierdo (cm)',
            'brazo_derecho' => 'Brazo derecho (cm)',
            'pierna_izquierda' => 'Pierna izquierda (cm)',
            'pierna_derecha' => 'Pierna derecha (cm)',
            'porcentaje_grasa' => 'Porcentaje grasa (%)',
            'porcentaje_musculo' => 'Porcentaje músculo (%)',
            'grasa_visceral' => 'Grasa visceral',
            'masa_musculo_esqueletica' => 'Masa músculo esquelética (kg)',
            'push_up' => 'Push-ups',
            'abdominales' => 'Abdominales',
            'frecuencia1' => 'Frecuencia cardíaca 1 (bpm)',
            'frecuencia2' => 'Frecuencia cardíaca 2 (bpm)',
            'frecuencia3' => 'Frecuencia cardíaca 3 (bpm)',
        ];

        $comparaciones = [];

        foreach($campos_a_comparar as $campo => $etiqueta){
            $valor1 = $valoracion1->{$campo};
            $valor2 = $valoracion2->{$campo};

            // Solo incluir si al menos uno de los valores no es nulo
            if($valor1 !== null || $valor2 !== null){
                $valor1 = $valor1 ?? 0;
                $valor2 = $valor2 ?? 0;

                $diferencia = $valor2 - $valor1;
                
                // Calcular porcentaje de cambio
                $porcentaje_cambio = 0;
                if($valor1 != 0){
                    $porcentaje_cambio = ($diferencia / $valor1) * 100;
                }

                $comparaciones[$campo] = [
                    'etiqueta' => $etiqueta,
                    'valor_inicial' => $valor1,
                    'valor_final' => $valor2,
                    'diferencia' => round($diferencia, 2),
                    'porcentaje_cambio' => round($porcentaje_cambio, 2),
                    'estado' => $diferencia > 0 ? 'aumento' : ($diferencia < 0 ? 'disminución' : 'sin_cambio')
                ];
            }
        }

        return [
            'cliente' => [
                'id' => $valoracion1->cliente_id,
                'nombre' => $valoracion1->cliente->nombre ?? 'N/A'
            ],
            'valoracion_inicial' => [
                'id' => $valoracion1->id,
                'fecha' => $valoracion1->fecha_registro,
                'entrenador' => $valoracion1->entrenador->name ?? 'N/A',
                'observaciones' => $valoracion1->observaciones
            ],
            'valoracion_final' => [
                'id' => $valoracion2->id,
                'fecha' => $valoracion2->fecha_registro,
                'entrenador' => $valoracion2->entrenador->name ?? 'N/A',
                'observaciones' => $valoracion2->observaciones
            ],
            'dias_transcurridos' => $valoracion1->fecha_registro->diffInDays($valoracion2->fecha_registro),
            'comparaciones' => $comparaciones
        ];
    }

    /**
     * Calcular automáticamente el trimestre y tipo de valoración
     * basándose en las valoraciones existentes del cliente
     * 
     * Lógica:
     * - Si no hay valoraciones: Trimestre 1, tipo inicial
     * - Si existe trimestre 1 inicial pero no final: Trimestre 1, tipo final
     * - Si existe trimestre X final pero no trimestre X+1 inicial: Trimestre X+1, tipo inicial
     * - Si existe trimestre X inicial pero no final: Trimestre X, tipo final
     */
    private function calcularTrimestreYTipo($cliente_id){
        // Obtener la última valoración del cliente
        $ultimaValoracion = Valoracion::where('cliente_id', $cliente_id)
            ->orderBy('trimestre', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        // Si no hay valoraciones, empezar con trimestre 1 inicial
        if(!$ultimaValoracion){
            return [
                'trimestre' => 1,
                'tipo_valoracion' => 'inicial'
            ];
        }

        // Si la última valoración es inicial, la siguiente debe ser final del mismo trimestre
        if($ultimaValoracion->tipo_valoracion === 'inicial'){
            return [
                'trimestre' => $ultimaValoracion->trimestre,
                'tipo_valoracion' => 'final'
            ];
        }

        // Si la última valoración es final, la siguiente debe ser inicial del siguiente trimestre
        if($ultimaValoracion->tipo_valoracion === 'final'){
            $siguiente_trimestre = $ultimaValoracion->trimestre + 1;
            
            // Validar que no exceda el trimestre 4
            if($siguiente_trimestre > 4){
                return [
                    'error' => true,
                    'mensaje' => 'Ya se han completado los 4 trimestres para este cliente'
                ];
            }

            return [
                'trimestre' => $siguiente_trimestre,
                'tipo_valoracion' => 'inicial'
            ];
        }

        // Caso por defecto (no debería llegar aquí)
        return [
            'trimestre' => 1,
            'tipo_valoracion' => 'inicial'
        ];
    }

    /**
     * Validar la lógica de trimestres para inserción
     * 
     * Reglas:
     * - Trimestre 1: Solo se registra una vez (inicial)
     * - Trimestre 2, 3, 4: Debe existir la valoración final del trimestre anterior
     *                      y se puede registrar la inicial del trimestre actual
     */
    private function validarTrimestreInsercion($cliente_id, $trimestre, $tipo_valoracion){
        // Trimestre 1: Solo se permite una valoración inicial
        if($trimestre == 1){
            if($tipo_valoracion !== 'inicial'){
                return [
                    'valido' => false,
                    'mensaje' => 'El trimestre 1 solo permite valoración inicial'
                ];
            }

            // Verificar que no exista ya una valoración para el trimestre 1
            $existe = Valoracion::where('cliente_id', $cliente_id)
                ->where('trimestre', 1)
                ->exists();

            if($existe){
                return [
                    'valido' => false,
                    'mensaje' => 'Ya existe una valoración para el trimestre 1 de este cliente'
                ];
            }
        }

        // Trimestres 2, 3, 4
        if($trimestre >= 2 && $trimestre <= 4){
            $trimestre_anterior = $trimestre - 1;

            // Verificar que existe la valoración final del trimestre anterior
            $valoracion_final_anterior = Valoracion::where('cliente_id', $cliente_id)
                ->where('trimestre', $trimestre_anterior)
                ->where('tipo_valoracion', 'final')
                ->exists();

            if(!$valoracion_final_anterior){
                return [
                    'valido' => false,
                    'mensaje' => "Debe registrar primero la valoración final del trimestre {$trimestre_anterior}"
                ];
            }

            // Si es valoración inicial, verificar que no exista ya
            if($tipo_valoracion === 'inicial'){
                $existe_inicial = Valoracion::where('cliente_id', $cliente_id)
                    ->where('trimestre', $trimestre)
                    ->where('tipo_valoracion', 'inicial')
                    ->exists();

                if($existe_inicial){
                    return [
                        'valido' => false,
                        'mensaje' => "Ya existe una valoración inicial para el trimestre {$trimestre}"
                    ];
                }
            }

            // Si es valoración final, verificar que exista la inicial del mismo trimestre
            if($tipo_valoracion === 'final'){
                $existe_inicial = Valoracion::where('cliente_id', $cliente_id)
                    ->where('trimestre', $trimestre)
                    ->where('tipo_valoracion', 'inicial')
                    ->exists();

                if(!$existe_inicial){
                    return [
                        'valido' => false,
                        'mensaje' => "Debe registrar primero la valoración inicial del trimestre {$trimestre}"
                    ];
                }

                // Verificar que no exista ya una valoración final
                $existe_final = Valoracion::where('cliente_id', $cliente_id)
                    ->where('trimestre', $trimestre)
                    ->where('tipo_valoracion', 'final')
                    ->exists();

                if($existe_final){
                    return [
                        'valido' => false,
                        'mensaje' => "Ya existe una valoración final para el trimestre {$trimestre}"
                    ];
                }
            }
        }

        return ['valido' => true, 'mensaje' => 'Validación exitosa'];
    }

    /**
     * Validar la lógica de trimestres para actualización
     */
    private function validarTrimestreActualizacion($valoracion_id, $cliente_id, $nuevo_trimestre, $nuevo_tipo){
        // Obtener la valoración actual
        $valoracion_actual = Valoracion::find($valoracion_id);
        if(!$valoracion_actual){
            return ['valido' => false, 'mensaje' => 'Valoración no encontrada'];
        }

        // Si no se está cambiando trimestre ni tipo, permitir la actualización
        if($nuevo_trimestre == $valoracion_actual->trimestre && $nuevo_tipo == $valoracion_actual->tipo_valoracion){
            return ['valido' => true, 'mensaje' => 'Validación exitosa'];
        }

        // Si se está cambiando, validar como si fuera una nueva inserción
        // pero excluyendo la valoración actual de las verificaciones
        if($nuevo_trimestre == 1){
            if($nuevo_tipo !== 'inicial'){
                return [
                    'valido' => false,
                    'mensaje' => 'El trimestre 1 solo permite valoración inicial'
                ];
            }

            $existe = Valoracion::where('cliente_id', $cliente_id)
                ->where('trimestre', 1)
                ->where('id', '!=', $valoracion_id)
                ->exists();

            if($existe){
                return [
                    'valido' => false,
                    'mensaje' => 'Ya existe una valoración para el trimestre 1 de este cliente'
                ];
            }
        }

        if($nuevo_trimestre >= 2 && $nuevo_trimestre <= 4){
            $trimestre_anterior = $nuevo_trimestre - 1;

            $valoracion_final_anterior = Valoracion::where('cliente_id', $cliente_id)
                ->where('trimestre', $trimestre_anterior)
                ->where('tipo_valoracion', 'final')
                ->exists();

            if(!$valoracion_final_anterior){
                return [
                    'valido' => false,
                    'mensaje' => "Debe existir la valoración final del trimestre {$trimestre_anterior}"
                ];
            }

            if($nuevo_tipo === 'inicial'){
                $existe_inicial = Valoracion::where('cliente_id', $cliente_id)
                    ->where('trimestre', $nuevo_trimestre)
                    ->where('tipo_valoracion', 'inicial')
                    ->where('id', '!=', $valoracion_id)
                    ->exists();

                if($existe_inicial){
                    return [
                        'valido' => false,
                        'mensaje' => "Ya existe una valoración inicial para el trimestre {$nuevo_trimestre}"
                    ];
                }
            }

            if($nuevo_tipo === 'final'){
                $existe_inicial = Valoracion::where('cliente_id', $cliente_id)
                    ->where('trimestre', $nuevo_trimestre)
                    ->where('tipo_valoracion', 'inicial')
                    ->exists();

                if(!$existe_inicial){
                    return [
                        'valido' => false,
                        'mensaje' => "Debe existir la valoración inicial del trimestre {$nuevo_trimestre}"
                    ];
                }

                $existe_final = Valoracion::where('cliente_id', $cliente_id)
                    ->where('trimestre', $nuevo_trimestre)
                    ->where('tipo_valoracion', 'final')
                    ->where('id', '!=', $valoracion_id)
                    ->exists();

                if($existe_final){
                    return [
                        'valido' => false,
                        'mensaje' => "Ya existe una valoración final para el trimestre {$nuevo_trimestre}"
                    ];
                }
            }
        }

        return ['valido' => true, 'mensaje' => 'Validación exitosa'];
    }
}
