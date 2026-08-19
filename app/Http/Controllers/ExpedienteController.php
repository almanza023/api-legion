<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ValoracionV2;
use App\Models\Valoracion;
use App\Models\RemisionMedica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpedienteController extends Controller
{
    /**
     * Textos de las preguntas estándar del cuestionario PAR-Q (Physical Activity Readiness Questionnaire)
     */
    private const PARQ_PREGUNTAS = [
        'parq_q1' => '¿Alguna vez un médico le ha dicho que tiene una condición cardíaca y que solo debería hacer actividad física recomendada por un médico?',
        'parq_q2' => '¿Siente dolor en el pecho cuando realiza actividad física?',
        'parq_q3' => '¿En el último mes, ha tenido dolor en el pecho sin haber estado haciendo actividad física?',
        'parq_q4' => '¿Pierde el equilibrio debido a mareos o ha perdido el conocimiento alguna vez?',
        'parq_q5' => '¿Tiene algún problema óseo o articular que pueda empeorar debido a un cambio en su actividad física?',
        'parq_q6' => '¿Le receta su médico medicamentos para la presión arterial o alguna condición cardíaca?',
        'parq_q7' => '¿Conoce alguna otra razón por la cual no debería hacer actividad física?'
    ];

    /**
     * Búsqueda pública de clientes para abrir expediente.
     * NO exige ni filtra por membresía activa.
     */
    public function buscarClientes(Request $request)
    {
        try {
            $queryStr = trim($request->get('q', ''));
            $perPage = (int) $request->get('per_page', 15);

            $query = Cliente::query();

            if (!empty($queryStr)) {
                $query->where(function ($q) use ($queryStr) {
                    $q->where('nombre', 'LIKE', "%{$queryStr}%")
                      ->orWhere('apellidos', 'LIKE', "%{$queryStr}%")
                      ->orWhere('numero_documento', 'LIKE', "%{$queryStr}%")
                      ->orWhere('correo', 'LIKE', "%{$queryStr}%")
                      ->orWhere('telefono', 'LIKE', "%{$queryStr}%");
                });
            }

            $clientes = $query->orderBy('nombre', 'asc')
                ->paginate($perPage);

            // Mapear información resumida con contadores de expediente
            $data = collect($clientes->items())->map(function ($cliente) {
                $totalValoracionesV2 = ValoracionV2::where('cliente_id', $cliente->id)->count();
                $totalValoracionesV1 = Valoracion::where('cliente_id', $cliente->id)->count();
                $totalRemisiones = RemisionMedica::where('cliente_id', $cliente->id)->count();
                $ultimaValoracionV2 = ValoracionV2::where('cliente_id', $cliente->id)
                    ->orderBy('fecha_registro', 'desc')
                    ->first();

                // Verificar si tiene alguna alerta PAR-Q positiva en la última valoración
                $requiereRemisionParq = false;
                if ($ultimaValoracionV2) {
                    for ($i = 1; $i <= 7; $i++) {
                        if ((bool) $ultimaValoracionV2->{"parq_q{$i}"}) {
                            $requiereRemisionParq = true;
                            break;
                        }
                    }
                }

                return [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'apellidos' => $cliente->apellidos,
                    'nombre_completo' => trim("{$cliente->nombre} {$cliente->apellidos}"),
                    'tipo_documento' => $cliente->tipo_documento,
                    'numero_documento' => $cliente->numero_documento,
                    'sexo' => $cliente->sexo,
                    'telefono' => $cliente->telefono,
                    'correo' => $cliente->correo,
                    'total_valoraciones' => $totalValoracionesV2 + $totalValoracionesV1,
                    'total_remisiones' => $totalRemisiones,
                    'parq_riesgo_detectado' => $requiereRemisionParq,
                    'fecha_ultima_valoracion' => $ultimaValoracionV2 ? $ultimaValoracionV2->fecha_registro : null
                ];
            });

            return response()->json([
                'status' => 'ok',
                'msg' => 'Búsqueda ejecutada exitosamente',
                'data' => $data,
                'pagination' => [
                    'current_page' => $clientes->currentPage(),
                    'per_page' => $clientes->perPage(),
                    'total' => $clientes->total(),
                    'last_page' => $clientes->lastPage()
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error("Error en ExpedienteController@buscarClientes: " . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json([
                'status' => 'fail',
                'msg' => 'Error al realizar la búsqueda de clientes'
            ], 500);
        }
    }

    /**
     * Obtener el Expediente Completo e Integrado de un Cliente por ID.
     * Retorna Cliente, Alertas de Salud, PAR-Q, Remisiones Médicas e Histórico de Valoraciones.
     */
    public function obtenerExpedienteCliente($clienteId)
    {
        try {
            $cliente = Cliente::find($clienteId);

            if (!$cliente) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Cliente no encontrado',
                    'data' => null
                ], 404);
            }

            // 1. Cargar Valoraciones V2
            $valoracionesV2 = ValoracionV2::with(['entrenador', 'imcClasificacion'])
                ->where('cliente_id', $clienteId)
                ->orderBy('fecha_registro', 'desc')
                ->get();

            // 2. Cargar Valoraciones Legacy (V1)
            $valoracionesV1 = Valoracion::with(['entrenador', 'imcClasificacion'])
                ->where('cliente_id', $clienteId)
                ->orderBy('fecha_registro', 'desc')
                ->get();

            // 3. Cargar Remisiones Médicas
            $remisionesMedicas = RemisionMedica::with(['medico', 'cie10'])
                ->where('cliente_id', $clienteId)
                ->orderBy('fecha_registro', 'desc')
                ->get();

            // Cargar la valoración V2 más reciente
            $ultimaV2 = $valoracionesV2->first();
            $ultimaV1 = $valoracionesV1->first();

            // 4. Construir Cuestionario y Evaluación PAR-Q
            $parqEvaluacion = $this->procesarParq($ultimaV2);

            // 5. Construir Alertas de Salud y Riesgos Clínicos
            $alertasSalud = $this->procesarAlertasSalud($ultimaV2, $ultimaV1, $remisionesMedicas, $parqEvaluacion['requiere_remision']);

            // 6. Formatear Histórico de Remisiones Médicas
            $remisionesFormatted = $remisionesMedicas->map(function ($rem) {
                return [
                    'id' => $rem->id,
                    'fecha_registro' => $rem->fecha_registro ? $rem->fecha_registro->format('Y-m-d') : null,
                    'medico_id' => $rem->medico_id,
                    'medico_nombre' => $rem->medico ? $rem->medico->nombre : null,
                    'cie10_id' => $rem->cie10_id,
                    'codigo_cie10' => $rem->codigo_cie10 ?? ($rem->cie10 ? $rem->cie10->codigo : null),
                    'diagnostico' => $rem->diagnostico,
                    'objetivos' => $rem->objetivos,
                    'objetivo_otro' => $rem->objetivo_otro,
                    'restricciones' => $rem->restricciones,
                    'vigencia' => $rem->vigencia,
                    'vigencia_otra_descripcion' => $rem->vigencia_otra_descripcion,
                    'estado' => $rem->estado ?? 'ACTIVA',
                    'pdf_url' => "/api/remisiones-medicas/{$rem->id}/pdf"
                ];
            });

            // 7. Formatear Histórico Consolidado de Valoraciones (V2 + V1)
            $valoracionesHistorico = collect();

            foreach ($valoracionesV2 as $v2) {
                $valoracionesHistorico->push([
                    'id' => $v2->id,
                    'version' => 'v2',
                    'fecha_registro' => $v2->fecha_registro ? $v2->fecha_registro->format('Y-m-d H:i:s') : null,
                    'fecha_inicio' => $v2->fecha_inicio ? $v2->fecha_inicio->format('Y-m-d') : null,
                    'fecha_fin' => $v2->fecha_fin ? $v2->fecha_fin->format('Y-m-d') : null,
                    'trimestre' => $v2->trimestre,
                    'tipo_valoracion' => $v2->tipo_valoracion,
                    'entrenador_id' => $v2->entrenador_id,
                    'entrenador_nombre' => $v2->entrenador ? $v2->entrenador->nombre : null,
                    'convenio' => (bool) $v2->convenio,
                    'nombre_empresa' => $v2->nombre_empresa,
                    'antropometria' => [
                        'estatura' => (float) $v2->estatura,
                        'peso' => (float) $v2->peso,
                        'edad' => $v2->edad,
                        'sexo' => $v2->sexo,
                        'imc' => (float) $v2->imc,
                        'imc_clasificacion' => $v2->imcClasificacion ? $v2->imcClasificacion->nombre : null,
                        'pecho' => (float) $v2->pecho,
                        'brazo_izquierdo' => (float) $v2->brazo_izquierdo,
                        'brazo_derecho' => (float) $v2->brazo_derecho,
                        'perimetro_abdominal' => (float) $v2->perimetro_abdominal,
                        'pierna_izquierda' => (float) $v2->pierna_izquierda,
                        'pierna_derecha' => (float) $v2->pierna_derecha,
                        'porcentaje_grasa' => (float) $v2->porcentaje_grasa,
                        'porcentaje_musculo' => (float) $v2->porcentaje_musculo,
                        'grasa_visceral' => (float) $v2->grasa_visceral,
                        'masa_musculo_esqueletica' => (float) $v2->masa_musculo_esqueletica
                    ],
                    'pruebas_fisicas' => [
                        'frecuencia1' => $v2->frecuencia1,
                        'frecuencia2' => $v2->frecuencia2,
                        'frecuencia3' => $v2->frecuencia3,
                        'push_up' => $v2->push_up,
                        'abdominales' => $v2->abdominales
                    ],
                    'observaciones' => $v2->observaciones,
                    'pdf_url' => "/api/valoraciones-v2/{$v2->id}/pdf"
                ]);
            }

            foreach ($valoracionesV1 as $v1) {
                $valoracionesHistorico->push([
                    'id' => $v1->id,
                    'version' => 'v1',
                    'fecha_registro' => $v1->fecha_registro ? $v1->fecha_registro->format('Y-m-d H:i:s') : null,
                    'fecha_inicio' => $v1->fecha_inicio ? $v1->fecha_inicio->format('Y-m-d') : null,
                    'fecha_fin' => $v1->fecha_fin ? $v1->fecha_fin->format('Y-m-d') : null,
                    'trimestre' => $v1->trimestre,
                    'tipo_valoracion' => $v1->tipo_valoracion,
                    'entrenador_id' => $v1->entrenador_id,
                    'entrenador_nombre' => $v1->entrenador ? $v1->entrenador->nombre : null,
                    'antropometria' => [
                        'estatura' => (float) $v1->estatura,
                        'peso' => (float) $v1->peso,
                        'edad' => $v1->edad,
                        'sexo' => $v1->sexo,
                        'imc' => (float) $v1->imc,
                        'imc_clasificacion' => $v1->imcClasificacion ? $v1->imcClasificacion->nombre : null,
                        'pecho' => (float) $v1->pecho,
                        'biceps' => (float) $v1->biceps,
                        'cadera' => (float) $v1->cadera,
                        'perimetro_abdominal' => (float) $v1->perimetro_abdominal,
                        'porcentaje_grasa' => (float) $v1->porcentaje_grasa,
                        'porcentaje_musculo' => (float) $v1->porcentaje_musculo
                    ],
                    'observaciones' => $v1->observaciones,
                    'objetivo' => $v1->objetivo
                ]);
            }

            // Ordenar histórico completo por fecha descendente
            $valoracionesHistorico = $valoracionesHistorico->sortByDesc('fecha_registro')->values();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Expediente consultado exitosamente',
                'data' => [
                    'cliente' => [
                        'id' => $cliente->id,
                        'nombre' => $cliente->nombre,
                        'apellidos' => $cliente->apellidos,
                        'nombre_completo' => trim("{$cliente->nombre} {$cliente->apellidos}"),
                        'tipo_documento' => $cliente->tipo_documento,
                        'numero_documento' => $cliente->numero_documento,
                        'sexo' => $cliente->sexo,
                        'fecha_nacimiento' => $cliente->fecha_nacimiento,
                        'telefono' => $cliente->telefono,
                        'correo' => $cliente->correo
                    ],
                    'alertas_salud' => $alertasSalud,
                    'parq' => $parqEvaluacion,
                    'remisiones_medicas' => $remisionesFormatted,
                    'valoraciones_historico' => $valoracionesHistorico
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error("Error en ExpedienteController@obtenerExpedienteCliente: " . $ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json([
                'status' => 'fail',
                'msg' => 'Ha ocurrido un error al obtener el expediente del cliente'
            ], 500);
        }
    }

    /**
     * Dashboard / Resumen Ejecutivo de 1 solo vistazo para el entrenador o médico.
     */
    public function obtenerResumenEjecutivo($clienteId)
    {
        try {
            $cliente = Cliente::find($clienteId);

            if (!$cliente) {
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado'], 404);
            }

            $ultimaV2 = ValoracionV2::with(['entrenador', 'imcClasificacion'])
                ->where('cliente_id', $clienteId)
                ->orderBy('fecha_registro', 'desc')
                ->first();

            $remisionActiva = RemisionMedica::with(['medico', 'cie10'])
                ->where('cliente_id', $clienteId)
                ->where('estado', 'ACTIVA')
                ->orderBy('fecha_registro', 'desc')
                ->first();

            $parqEvaluacion = $this->procesarParq($ultimaV2);

            return response()->json([
                'status' => 'ok',
                'msg' => 'Resumen ejecutivo obtenido',
                'data' => [
                    'cliente_id' => $cliente->id,
                    'nombre_completo' => trim("{$cliente->nombre} {$cliente->apellidos}"),
                    'numero_documento' => $cliente->numero_documento,
                    'apto_actividad_fisica' => !$parqEvaluacion['requiere_remision'],
                    'requiere_remision_medica' => $parqEvaluacion['requiere_remision'],
                    'remision_activa' => $remisionActiva ? [
                        'id' => $remisionActiva->id,
                        'medico' => $remisionActiva->medico ? $remisionActiva->medico->nombre : null,
                        'diagnostico' => $remisionActiva->diagnostico,
                        'restricciones' => $remisionActiva->restricciones
                    ] : null,
                    'ultima_valoracion' => $ultimaV2 ? [
                        'id' => $ultimaV2->id,
                        'fecha_registro' => $ultimaV2->fecha_registro ? $ultimaV2->fecha_registro->format('Y-m-d') : null,
                        'peso' => (float) $ultimaV2->peso,
                        'estatura' => (float) $ultimaV2->estatura,
                        'imc' => (float) $ultimaV2->imc,
                        'imc_clasificacion' => $ultimaV2->imcClasificacion ? $ultimaV2->imcClasificacion->nombre : null,
                        'porcentaje_grasa' => (float) $ultimaV2->porcentaje_grasa,
                        'porcentaje_musculo' => (float) $ultimaV2->porcentaje_musculo,
                        'lesion' => (bool) $ultimaV2->lesion,
                        'descripcion_lesion' => $ultimaV2->descripcion_lesion
                    ] : null
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error("Error en ExpedienteController@obtenerResumenEjecutivo: " . $ex->getMessage());
            return response()->json(['status' => 'fail', 'msg' => 'Error al procesar resumen ejecutivo'], 500);
        }
    }

    /**
     * Retorna datos de progreso físico a lo largo del tiempo para renderizar gráficos en frontend.
     */
    public function obtenerEvolucionMetrics($clienteId)
    {
        try {
            $cliente = Cliente::find($clienteId);

            if (!$cliente) {
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado'], 404);
            }

            $valoracionesV2 = ValoracionV2::where('cliente_id', $clienteId)
                ->orderBy('fecha_registro', 'asc')
                ->get();

            $puntos = $valoracionesV2->map(function ($val) {
                return [
                    'id' => $val->id,
                    'fecha' => $val->fecha_registro ? $val->fecha_registro->format('Y-m-d') : null,
                    'trimestre' => $val->trimestre,
                    'tipo_valoracion' => $val->tipo_valoracion,
                    'peso' => (float) $val->peso,
                    'estatura' => (float) $val->estatura,
                    'imc' => (float) $val->imc,
                    'porcentaje_grasa' => (float) $val->porcentaje_grasa,
                    'porcentaje_musculo' => (float) $val->porcentaje_musculo,
                    'grasa_visceral' => (float) $val->grasa_visceral,
                    'perimetro_abdominal' => (float) $val->perimetro_abdominal,
                    'push_up' => (int) $val->push_up,
                    'abdominales' => (int) $val->abdominales
                ];
            });

            return response()->json([
                'status' => 'ok',
                'msg' => 'Datos de evolución generados exitosamente',
                'data' => [
                    'cliente_id' => $cliente->id,
                    'nombre_completo' => trim("{$cliente->nombre} {$cliente->apellidos}"),
                    'total_puntos' => $puntos->count(),
                    'evolucion' => $puntos
                ]
            ]);
        } catch (\Exception $ex) {
            Log::error("Error en ExpedienteController@obtenerEvolucionMetrics: " . $ex->getMessage());
            return response()->json(['status' => 'fail', 'msg' => 'Error al consultar métricas de evolución'], 500);
        }
    }

    /**
     * Procesa las respuestas de la evaluación PAR-Q
     */
    private function procesarParq(?ValoracionV2 $valV2): array
    {
        $respuestas = [];
        $requiereRemision = false;

        if ($valV2) {
            foreach (self::PARQ_PREGUNTAS as $key => $preguntaTexto) {
                $valRespuesta = (bool) $valV2->{$key};
                if ($valRespuesta) {
                    $requiereRemision = true;
                }

                $respuestas[] = [
                    'item' => $key,
                    'pregunta' => $preguntaTexto,
                    'respuesta' => $valRespuesta
                ];
            }
        }

        return [
            'fecha_evaluacion' => $valV2 && $valV2->fecha_registro ? $valV2->fecha_registro->format('Y-m-d') : null,
            'requiere_remision' => $requiereRemision,
            'apto_actividad_fisica' => !$requiereRemision,
            'respuestas' => $respuestas
        ];
    }

    /**
     * Procesa y consolida alertas médicas y antecedentes
     */
    private function procesarAlertasSalud(?ValoracionV2 $valV2, ?Valoracion $valV1, $remisiones, bool $requiereRemisionParq): array
    {
        $restriccionesActivas = [];

        foreach ($remisiones as $rem) {
            if (!empty($rem->restricciones)) {
                $restriccionesActivas[] = $rem->restricciones;
            }
        }

        $razonesRiesgo = [];
        if ($requiereRemisionParq) {
            $razonesRiesgo[] = 'Respondió SÍ en una o más preguntas del cuestionario PAR-Q';
        }
        if (count($restriccionesActivas) > 0) {
            $razonesRiesgo[] = 'Cuenta con remisiones médicas y restricciones clínicas registradas';
        }

        $val = $valV2 ?? $valV1;

        return [
            'apto_actividad_fisica' => !$requiereRemisionParq && count($restriccionesActivas) === 0,
            'razones_riesgo' => $razonesRiesgo,
            'hipertension' => $val ? (bool) $val->hipertension : false,
            'fuma' => $val ? (bool) $val->fuma : false,
            'lesion_activa' => $val ? (bool) $val->lesion : false,
            'descripcion_lesion' => $val ? $val->descripcion_lesion : null,
            'tension_arterial' => $valV2 ? $valV2->tension_arterial : null,
            'antecedentes_cardio_personales' => $valV2 ? (bool) $valV2->antecedentes_cardio_personales : false,
            'antecedentes_cardio_familiares' => $valV2 ? (bool) $valV2->antecedentes_cardio_familiares : false,
            'colesterol_alto' => $valV2 ? (bool) $valV2->colesterol_alto : false,
            'trigliceridos_altos' => $valV2 ? (bool) $valV2->trigliceridos_altos : false,
            'toma_medicamento' => $valV2 ? (bool) $valV2->toma_medicamento : false,
            'restricciones_medicas_activas' => array_unique($restriccionesActivas)
        ];
    }
}
