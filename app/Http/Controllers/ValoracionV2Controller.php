<?php

namespace App\Http\Controllers;

use App\Models\ValoracionV2;
use App\Models\Cliente;
use App\Models\ImcClasificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValoracionV2Controller extends Controller
{
    /**
     * Listar todas las valoraciones v2 con paginación
     */
    public function index(Request $request)
    {
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $valoraciones = ValoracionV2::with(['cliente', 'entrenador', 'imcClasificacion'])
                    ->orderBy('fecha_registro', 'desc')
                    ->paginate($perPage);

                return response()->json([
                    'data' => $valoraciones->items(),
                    'current_page' => $valoraciones->currentPage(),
                    'per_page' => $valoraciones->perPage(),
                    'total' => $valoraciones->total(),
                    'last_page' => $valoraciones->lastPage()
                ]);
            } else {
                $valoraciones = ValoracionV2::with(['cliente', 'entrenador', 'imcClasificacion'])
                    ->orderBy('fecha_registro', 'desc')
                    ->get();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $valoraciones]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    /**
     * Crear una nueva valoración v2
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validar cliente
            $cliente = Cliente::find($request->cliente_id);
            if (!$cliente) {
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
            }

            // Calcular automáticamente el trimestre y tipo de valoración
            $trimestreInfo = $this->calcularTrimestreYTipo($request->cliente_id);

            if (isset($trimestreInfo['error']) && $trimestreInfo['error']) {
                return response()->json(['status' => 'fail', 'msg' => $trimestreInfo['mensaje'], 'data' => null], 400);
            }

            $trimestre = $trimestreInfo['trimestre'];
            $tipo_valoracion = $trimestreInfo['tipo_valoracion'];

            // Validar duplicado
            $existe_duplicado = ValoracionV2::where('cliente_id', $request->cliente_id)
                ->where('trimestre', $trimestre)
                ->where('tipo_valoracion', $tipo_valoracion)
                ->exists();

            if ($existe_duplicado) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => "Ya existe una valoración de tipo '{$tipo_valoracion}' para el trimestre {$trimestre} de este cliente",
                    'data' => null
                ], 400);
            }

            $val = new ValoracionV2();
            $val->cliente_id = $request->cliente_id;
            $val->entrenador_id = $request->entrenador_id ?? null;
            $val->imc_clasificacion_id = $request->imc_clasificacion_id ?? null;
            $val->convenio = $request->convenio ?? false;
            $val->nombre_empresa = $request->nombre_empresa ?? null;

            // Antropometría
            $val->estatura = $request->estatura ?? null;
            $val->peso = $request->peso ?? null;
            $val->edad = $request->edad ?? null;
            $val->sexo = $request->sexo ?? 'Hombre';
            $val->pecho = $request->pecho ?? 0.00;
            $val->brazo_izquierdo = $request->brazo_izquierdo ?? 0.00;
            $val->brazo_derecho = $request->brazo_derecho ?? 0.00;
            $val->perimetro_abdominal = $request->perimetro_abdominal ?? 0.00;
            $val->pierna_izquierda = $request->pierna_izquierda ?? 0.00;
            $val->pierna_derecha = $request->pierna_derecha ?? 0.00;

            // Frecuencia cardíaca
            $val->frecuencia1 = $request->frecuencia1 ?? null;
            $val->frecuencia2 = $request->frecuencia2 ?? null;
            $val->frecuencia3 = $request->frecuencia3 ?? null;

            // Test físico
            $val->push_up = $request->push_up ?? null;
            $val->abdominales = $request->abdominales ?? null;

            // Composición corporal
            $val->porcentaje_grasa = $request->porcentaje_grasa ?? null;
            $val->porcentaje_musculo = $request->porcentaje_musculo ?? null;
            $val->grasa_visceral = $request->grasa_visceral ?? null;
            $val->masa_musculo_esqueletica = $request->masa_musculo_esqueletica ?? null;

            // Antecedentes
            $val->hipertension = $request->hipertension ?? false;
            $val->fuma = $request->fuma ?? false;
            $val->lesion = $request->lesion ?? false;
            $val->tension_arterial = $request->tension_arterial ?? null;
            $val->antecedentes_cardio_personales = $request->antecedentes_cardio_personales ?? false;
            $val->antecedentes_cardio_familiares = $request->antecedentes_cardio_familiares ?? false;
            $val->colesterol_alto = $request->colesterol_alto ?? false;
            $val->trigliceridos_altos = $request->trigliceridos_altos ?? false;
            $val->toma_medicamento = $request->toma_medicamento ?? false;
            $val->toma_foto = $request->toma_foto ?? false;
            $val->descripcion_lesion = $request->descripcion_lesion ?? null;

            // Observaciones
            $val->observaciones = $request->observaciones ?? null;

            // Plan de entrenamiento
            $val->plan_tipo = $request->plan_tipo ?? null;
            $val->plan_hombre_bp = $request->plan_hombre_bp ?? false;
            $val->plan_hombre_gm = $request->plan_hombre_gm ?? false;
            $val->plan_mujer_bp = $request->plan_mujer_bp ?? false;
            $val->plan_mujer_gm = $request->plan_mujer_gm ?? false;

            // PAR-Q (7 preguntas)
            $val->parq_q1 = $request->parq_q1 ?? false;
            $val->parq_q2 = $request->parq_q2 ?? false;
            $val->parq_q3 = $request->parq_q3 ?? false;
            $val->parq_q4 = $request->parq_q4 ?? false;
            $val->parq_q5 = $request->parq_q5 ?? false;
            $val->parq_q6 = $request->parq_q6 ?? false;
            $val->parq_q7 = $request->parq_q7 ?? false;

            // Fechas
            $val->fecha_registro = $request->fecha_registro ?? now();
            $val->fecha_inicio = $request->fecha_inicio ?? null;
            $val->fecha_fin = $request->fecha_fin ?? null;
            $val->trimestre = $trimestre;
            $val->tipo_valoracion = $tipo_valoracion;

            // Calcular IMC
            if ($request->peso && $request->estatura) {
                $imc = $val->calcularImc();
                if ($imc) {
                    $clasificacion = ImcClasificacion::where('rango_min', '<=', $imc)
                        ->where('rango_max', '>=', $imc)
                        ->first();
                    if ($clasificacion) {
                        $val->imc_clasificacion_id = $clasificacion->id;
                    }
                }
            }

            $val->save();

            // Si es valoración final y no es trimestre 4, generar valoración inicial del siguiente trimestre
            $valoracion_siguiente = null;
            if ($tipo_valoracion === 'final' && $trimestre < 4) {
                $siguiente_trimestre = $trimestre + 1;
                $valoracion_siguiente = new ValoracionV2();
                $valoracion_siguiente->cliente_id = $val->cliente_id;
                $valoracion_siguiente->entrenador_id = $val->entrenador_id;
                $valoracion_siguiente->trimestre = $siguiente_trimestre;
                $valoracion_siguiente->tipo_valoracion = 'inicial';
                $valoracion_siguiente->fecha_registro = $val->fecha_registro;
                $valoracion_siguiente->fecha_inicio = $val->fecha_inicio;
                $valoracion_siguiente->fecha_fin = $val->fecha_fin;

                // Copiar datos antropométricos base
                $valoracion_siguiente->estatura = $val->estatura;
                $valoracion_siguiente->peso = $val->peso;
                $valoracion_siguiente->edad = $val->edad;
                $valoracion_siguiente->sexo = $val->sexo;
                $valoracion_siguiente->pecho = $val->pecho;
                $valoracion_siguiente->brazo_izquierdo = $val->brazo_izquierdo;
                $valoracion_siguiente->brazo_derecho = $val->brazo_derecho;
                $valoracion_siguiente->perimetro_abdominal = $val->perimetro_abdominal;
                $valoracion_siguiente->pierna_izquierda = $val->pierna_izquierda;
                $valoracion_siguiente->pierna_derecha = $val->pierna_derecha;
                $valoracion_siguiente->porcentaje_grasa = $val->porcentaje_grasa;
                $valoracion_siguiente->porcentaje_musculo = $val->porcentaje_musculo;
                $valoracion_siguiente->grasa_visceral = $val->grasa_visceral;
                $valoracion_siguiente->masa_musculo_esqueletica = $val->masa_musculo_esqueletica;
                $valoracion_siguiente->frecuencia1 = $val->frecuencia1;
                $valoracion_siguiente->frecuencia2 = $val->frecuencia2;
                $valoracion_siguiente->frecuencia3 = $val->frecuencia3;
                $valoracion_siguiente->push_up = $val->push_up;
                $valoracion_siguiente->abdominales = $val->abdominales;
                $valoracion_siguiente->hipertension = $val->hipertension;
                $valoracion_siguiente->fuma = $val->fuma;
                $valoracion_siguiente->lesion = $val->lesion;
                $valoracion_siguiente->tension_arterial = $val->tension_arterial;
                $valoracion_siguiente->antecedentes_cardio_personales = $val->antecedentes_cardio_personales;
                $valoracion_siguiente->antecedentes_cardio_familiares = $val->antecedentes_cardio_familiares;
                $valoracion_siguiente->colesterol_alto = $val->colesterol_alto;
                $valoracion_siguiente->trigliceridos_altos = $val->trigliceridos_altos;
                $valoracion_siguiente->toma_medicamento = $val->toma_medicamento;
                $valoracion_siguiente->toma_foto = $val->toma_foto;
                $valoracion_siguiente->descripcion_lesion = $val->descripcion_lesion;
                $valoracion_siguiente->observaciones = "Valoración inicial del trimestre {$siguiente_trimestre} (generada automáticamente)";
                $valoracion_siguiente->plan_tipo = $val->plan_tipo;
                $valoracion_siguiente->plan_hombre_bp = $val->plan_hombre_bp;
                $valoracion_siguiente->plan_hombre_gm = $val->plan_hombre_gm;
                $valoracion_siguiente->plan_mujer_bp = $val->plan_mujer_bp;
                $valoracion_siguiente->plan_mujer_gm = $val->plan_mujer_gm;
                $valoracion_siguiente->parq_q1 = $val->parq_q1;
                $valoracion_siguiente->parq_q2 = $val->parq_q2;
                $valoracion_siguiente->parq_q3 = $val->parq_q3;
                $valoracion_siguiente->parq_q4 = $val->parq_q4;
                $valoracion_siguiente->parq_q5 = $val->parq_q5;
                $valoracion_siguiente->parq_q6 = $val->parq_q6;
                $valoracion_siguiente->parq_q7 = $val->parq_q7;

                if ($valoracion_siguiente->peso && $valoracion_siguiente->estatura) {
                    $imc = $valoracion_siguiente->calcularImc();
                    if ($imc) {
                        $clasificacion = ImcClasificacion::where('rango_min', '<=', $imc)
                            ->where('rango_max', '>=', $imc)
                            ->first();
                        if ($clasificacion) {
                            $valoracion_siguiente->imc_clasificacion_id = $clasificacion->id;
                        }
                    }
                }
                $valoracion_siguiente->save();
            }

            DB::commit();

            $val->load(['cliente', 'entrenador', 'imcClasificacion']);
            $responseData = ['valoracion_creada' => $val];

            if ($valoracion_siguiente) {
                $valoracion_siguiente->load(['cliente', 'entrenador', 'imcClasificacion']);
                $responseData['valoracion_siguiente_trimestre'] = $valoracion_siguiente;
                $responseData['mensaje_adicional'] = "Se creó automáticamente la valoración inicial del trimestre {$siguiente_trimestre}";
            }

            return response()->json([
                'status' => 'ok',
                'msg' => 'Valoración v2 creada exitosamente',
                'data' => $responseData
            ]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Mostrar valoración v2 por ID
     */
    public function show($id)
    {
        try {
            $val = ValoracionV2::with(['cliente', 'entrenador', 'imcClasificacion'])->find($id);
            if (!$val) {
                return response()->json(['status' => 'fail', 'msg' => 'Valoración no encontrada', 'data' => null], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $val]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Actualizar valoración v2
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $val = ValoracionV2::find($id);
            if (!$val) {
                return response()->json(['status' => 'fail', 'msg' => 'Valoración no encontrada', 'data' => null], 404);
            }

            if ($request->has('cliente_id')) {
                return response()->json(['status' => 'fail', 'msg' => 'No se puede cambiar el cliente de una valoración existente', 'data' => null], 400);
            }

            if ($request->has('trimestre') || $request->has('tipo_valoracion')) {
                return response()->json(['status' => 'fail', 'msg' => 'El trimestre y tipo de valoración son calculados automáticamente por el sistema', 'data' => null], 400);
            }

            if ($request->has('entrenador_id')) $val->entrenador_id = $request->entrenador_id;
            if ($request->has('imc_clasificacion_id')) $val->imc_clasificacion_id = $request->imc_clasificacion_id;
            if ($request->has('convenio')) $val->convenio = $request->convenio;
            if ($request->has('nombre_empresa')) $val->nombre_empresa = $request->nombre_empresa;
            if ($request->has('estatura')) $val->estatura = $request->estatura;
            if ($request->has('peso')) $val->peso = $request->peso;
            if ($request->has('edad')) $val->edad = $request->edad;
            if ($request->has('sexo')) $val->sexo = $request->sexo;
            if ($request->has('pecho')) $val->pecho = $request->pecho;
            if ($request->has('brazo_izquierdo')) $val->brazo_izquierdo = $request->brazo_izquierdo;
            if ($request->has('brazo_derecho')) $val->brazo_derecho = $request->brazo_derecho;
            if ($request->has('perimetro_abdominal')) $val->perimetro_abdominal = $request->perimetro_abdominal;
            if ($request->has('pierna_izquierda')) $val->pierna_izquierda = $request->pierna_izquierda;
            if ($request->has('pierna_derecha')) $val->pierna_derecha = $request->pierna_derecha;
            if ($request->has('frecuencia1')) $val->frecuencia1 = $request->frecuencia1;
            if ($request->has('frecuencia2')) $val->frecuencia2 = $request->frecuencia2;
            if ($request->has('frecuencia3')) $val->frecuencia3 = $request->frecuencia3;
            if ($request->has('push_up')) $val->push_up = $request->push_up;
            if ($request->has('abdominales')) $val->abdominales = $request->abdominales;
            if ($request->has('porcentaje_grasa')) $val->porcentaje_grasa = $request->porcentaje_grasa;
            if ($request->has('porcentaje_musculo')) $val->porcentaje_musculo = $request->porcentaje_musculo;
            if ($request->has('grasa_visceral')) $val->grasa_visceral = $request->grasa_visceral;
            if ($request->has('masa_musculo_esqueletica')) $val->masa_musculo_esqueletica = $request->masa_musculo_esqueletica;
            if ($request->has('hipertension')) $val->hipertension = $request->hipertension;
            if ($request->has('fuma')) $val->fuma = $request->fuma;
            if ($request->has('lesion')) $val->lesion = $request->lesion;
            if ($request->has('tension_arterial')) $val->tension_arterial = $request->tension_arterial;
            if ($request->has('antecedentes_cardio_personales')) $val->antecedentes_cardio_personales = $request->antecedentes_cardio_personales;
            if ($request->has('antecedentes_cardio_familiares')) $val->antecedentes_cardio_familiares = $request->antecedentes_cardio_familiares;
            if ($request->has('colesterol_alto')) $val->colesterol_alto = $request->colesterol_alto;
            if ($request->has('trigliceridos_altos')) $val->trigliceridos_altos = $request->trigliceridos_altos;
            if ($request->has('toma_medicamento')) $val->toma_medicamento = $request->toma_medicamento;
            if ($request->has('toma_foto')) $val->toma_foto = $request->toma_foto;
            if ($request->has('descripcion_lesion')) $val->descripcion_lesion = $request->descripcion_lesion;
            if ($request->has('observaciones')) $val->observaciones = $request->observaciones;
            if ($request->has('plan_tipo')) $val->plan_tipo = $request->plan_tipo;
            if ($request->has('plan_hombre_bp')) $val->plan_hombre_bp = $request->plan_hombre_bp;
            if ($request->has('plan_hombre_gm')) $val->plan_hombre_gm = $request->plan_hombre_gm;
            if ($request->has('plan_mujer_bp')) $val->plan_mujer_bp = $request->plan_mujer_bp;
            if ($request->has('plan_mujer_gm')) $val->plan_mujer_gm = $request->plan_mujer_gm;
            if ($request->has('parq_q1')) $val->parq_q1 = $request->parq_q1;
            if ($request->has('parq_q2')) $val->parq_q2 = $request->parq_q2;
            if ($request->has('parq_q3')) $val->parq_q3 = $request->parq_q3;
            if ($request->has('parq_q4')) $val->parq_q4 = $request->parq_q4;
            if ($request->has('parq_q5')) $val->parq_q5 = $request->parq_q5;
            if ($request->has('parq_q6')) $val->parq_q6 = $request->parq_q6;
            if ($request->has('parq_q7')) $val->parq_q7 = $request->parq_q7;
            if ($request->has('fecha_inicio')) $val->fecha_inicio = $request->fecha_inicio;
            if ($request->has('fecha_fin')) $val->fecha_fin = $request->fecha_fin;

            if ($request->has('peso') || $request->has('estatura')) {
                if ($val->peso && $val->estatura) {
                    $imc = $val->calcularImc();
                    if ($imc) {
                        $clasificacion = ImcClasificacion::where('rango_min', '<=', $imc)
                            ->where('rango_max', '>=', $imc)
                            ->first();
                        if ($clasificacion) {
                            $val->imc_clasificacion_id = $clasificacion->id;
                        }
                    }
                }
            }

            $val->save();

            DB::commit();

            $val->load(['cliente', 'entrenador', 'imcClasificacion']);

            return response()->json(['status' => 'ok', 'msg' => 'Valoración v2 actualizada exitosamente', 'data' => $val]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar valoración v2
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $val = ValoracionV2::find($id);
            if (!$val) {
                return response()->json(['status' => 'fail', 'msg' => 'Valoración no encontrada', 'data' => null], 404);
            }

            $val->delete();
            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'Valoración v2 eliminada exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Obtener valoraciones por cliente
     */
    public function valoracionesPorCliente(Request $request)
    {
        try {
            $cliente_id = $request->input('cliente_id');
            if (!$cliente_id) {
                return response()->json(['status' => 'fail', 'msg' => 'cliente_id es requerido', 'data' => null], 400);
            }

            $valoraciones = ValoracionV2::where('cliente_id', $cliente_id)
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
    public function ultimaValoracionCliente(Request $request)
    {
        try {
            $cliente_id = $request->input('cliente_id');
            if (!$cliente_id) {
                return response()->json(['status' => 'fail', 'msg' => 'cliente_id es requerido', 'data' => null], 400);
            }

            $valoracion = ValoracionV2::where('cliente_id', $cliente_id)
                ->with(['cliente', 'entrenador', 'imcClasificacion'])
                ->orderBy('fecha_registro', 'desc')
                ->first();

            if (!$valoracion) {
                return response()->json(['status' => 'fail', 'msg' => 'No hay valoraciones v2 para este cliente', 'data' => null], 404);
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
    public function valoracionesPorFecha(Request $request)
    {
        try {
            $cliente_id = $request->input('cliente_id');
            $fecha_inicio = $request->input('fecha_inicio');
            $fecha_fin = $request->input('fecha_fin');

            if (!$cliente_id || !$fecha_inicio || !$fecha_fin) {
                return response()->json(['status' => 'fail', 'msg' => 'cliente_id, fecha_inicio y fecha_fin son requeridos', 'data' => null], 400);
            }

            $valoraciones = ValoracionV2::where('cliente_id', $cliente_id)
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
     * Obtener valoraciones por entrenador
     */
    public function valoracionesPorEntrenador(Request $request)
    {
        try {
            $entrenador_id = $request->input('entrenador_id');
            if (!$entrenador_id) {
                return response()->json(['status' => 'fail', 'msg' => 'entrenador_id es requerido', 'data' => null], 400);
            }

            $valoraciones = ValoracionV2::where('entrenador_id', $entrenador_id)
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
     * Comparar avances entre la primera y última valoración de un cliente en un trimestre
     */
    public function compararAvances(Request $request)
    {
        try {
            $cliente_id = $request->input('cliente_id');
            $trimestre = $request->input('trimestre');

            if (!$cliente_id) {
                return response()->json(['status' => 'fail', 'msg' => 'cliente_id es requerido', 'data' => null], 400);
            }

            if (!$trimestre) {
                $ultima = ValoracionV2::where('cliente_id', $cliente_id)
                    ->orderBy('fecha_registro', 'desc')
                    ->first();

                if (!$ultima || !$ultima->trimestre) {
                    return response()->json(['status' => 'fail', 'msg' => 'No hay valoraciones con trimestre asignado para este cliente', 'data' => null], 404);
                }

                $trimestre = $ultima->trimestre;
            }

            $val1 = ValoracionV2::where('cliente_id', $cliente_id)
                ->where('trimestre', $trimestre)
                ->with(['cliente', 'entrenador', 'imcClasificacion'])
                ->orderBy('fecha_registro', 'asc')
                ->first();

            $val2 = ValoracionV2::where('cliente_id', $cliente_id)
                ->where('trimestre', $trimestre)
                ->with(['cliente', 'entrenador', 'imcClasificacion'])
                ->orderBy('fecha_registro', 'desc')
                ->first();

            if (!$val1 || !$val2) {
                return response()->json(['status' => 'fail', 'msg' => 'No hay suficientes valoraciones para este cliente en el trimestre ' . $trimestre, 'data' => null], 404);
            }

            if ($val1->id === $val2->id) {
                return response()->json(['status' => 'fail', 'msg' => 'Se necesitan al menos 2 valoraciones diferentes en el trimestre para comparar', 'data' => null], 400);
            }

            $comparacion = $this->calcularDiferencias($val1, $val2);
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
    private function calcularDiferencias($val1, $val2)
    {
        $campos = [
            'peso' => 'Peso (kg)',
            'estatura' => 'Estatura (m)',
            'imc' => 'IMC',
            'pecho' => 'Pecho (cm)',
            'brazo_izquierdo' => 'Brazo izquierdo (cm)',
            'brazo_derecho' => 'Brazo derecho (cm)',
            'perimetro_abdominal' => 'Perímetro abdominal (cm)',
            'pierna_izquierda' => 'Pierna izquierda (cm)',
            'pierna_derecha' => 'Pierna derecha (cm)',
            'porcentaje_grasa' => 'Porcentaje grasa (%)',
            'porcentaje_musculo' => 'Porcentaje músculo (%)',
            'grasa_visceral' => 'Grasa visceral',
            'masa_musculo_esqueletica' => 'Masa músculo esquelética (%)',
            'push_up' => 'Push-ups',
            'abdominales' => 'Abdominales',
            'frecuencia1' => 'Frecuencia cardíaca 1 (bpm)',
            'frecuencia2' => 'Frecuencia cardíaca 2 (bpm)',
            'frecuencia3' => 'Frecuencia cardíaca 3 (bpm)',
        ];

        $comparaciones = [];

        foreach ($campos as $campo => $etiqueta) {
            $v1 = $val1->{$campo};
            $v2 = $val2->{$campo};

            if ($v1 !== null || $v2 !== null) {
                $v1 = $v1 ?? 0;
                $v2 = $v2 ?? 0;

                $diferencia = $v2 - $v1;
                $porcentaje_cambio = ($v1 != 0) ? ($diferencia / $v1) * 100 : 0;

                $comparaciones[$campo] = [
                    'etiqueta' => $etiqueta,
                    'valor_inicial' => $v1,
                    'valor_final' => $v2,
                    'diferencia' => round($diferencia, 2),
                    'porcentaje_cambio' => round($porcentaje_cambio, 2),
                    'estado' => $diferencia > 0 ? 'aumento' : ($diferencia < 0 ? 'disminución' : 'sin_cambio')
                ];
            }
        }

        return [
            'cliente' => [
                'id' => $val1->cliente_id,
                'nombre' => $val1->cliente->nombre ?? 'N/A'
            ],
            'valoracion_inicial' => [
                'id' => $val1->id,
                'fecha' => $val1->fecha_registro,
                'entrenador' => $val1->entrenador->name ?? 'N/A',
                'observaciones' => $val1->observaciones
            ],
            'valoracion_final' => [
                'id' => $val2->id,
                'fecha' => $val2->fecha_registro,
                'entrenador' => $val2->entrenador->name ?? 'N/A',
                'observaciones' => $val2->observaciones
            ],
            'dias_transcurridos' => $val1->fecha_registro ? $val1->fecha_registro->diffInDays($val2->fecha_registro) : 0,
            'comparaciones' => $comparaciones
        ];
    }

    /**
     * Calcular automáticamente el trimestre y tipo de valoración
     */
    private function calcularTrimestreYTipo($cliente_id)
    {
        $ultima = ValoracionV2::where('cliente_id', $cliente_id)
            ->orderBy('trimestre', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$ultima) {
            return ['trimestre' => 1, 'tipo_valoracion' => 'inicial'];
        }

        if ($ultima->tipo_valoracion === 'inicial') {
            return ['trimestre' => $ultima->trimestre, 'tipo_valoracion' => 'final'];
        }

        if ($ultima->tipo_valoracion === 'final') {
            $sig = $ultima->trimestre + 1;
            if ($sig > 4) {
                return ['error' => true, 'mensaje' => 'Ya se han completado los 4 trimestres para este cliente'];
            }
            return ['trimestre' => $sig, 'tipo_valoracion' => 'inicial'];
        }

        return ['trimestre' => 1, 'tipo_valoracion' => 'inicial'];
    }
}
