<?php

namespace App\Http\Controllers;

use App\Models\RutinaGenerica;
use App\Models\DetalleRutinaGenerica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlantillaPlanEntrenamientoController extends Controller
{
    /**
     * Listar todas las plantillas con paginación.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 12);

            $plantillas = RutinaGenerica::with(['detalles.ejercicio'])
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'data' => $plantillas->items(),
                'current_page' => $plantillas->currentPage(),
                'per_page' => $plantillas->perPage(),
                'total' => $plantillas->total(),
                'last_page' => $plantillas->lastPage()
            ]);
        } catch (\Exception $ex) {
            Log::error('Plantilla index error: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['status' => 'fail', 'msg' => 'No fue posible obtener las plantillas'], 500);
        }
    }

    /**
     * Crear una plantilla junto a sus detalles opcionales.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $plantilla = RutinaGenerica::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'objetivo' => $request->objetivo,
                'estado' => $request->estado ?? 'activo',
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'trimestre' => $request->trimestre,
            ]);

            $detalles = $this->parseDetalles($request->input('dias_ejercicios') ?? $request->input('detalles'));
            $this->guardarDetalles($plantilla, $detalles);

            DB::commit();

            $plantilla->load('detalles.ejercicio');

            return response()->json(['status' => 'ok', 'msg' => 'Plantilla creada exitosamente', 'data' => $plantilla]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Plantilla store error: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['status' => 'fail', 'msg' => 'No fue posible crear la plantilla', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Mostrar una plantilla específica.
     */
    public function show($id)
    {
        try {
            $plantilla = RutinaGenerica::with(['detalles.ejercicio'])->find($id);
            if (!$plantilla) {
                return response()->json(['status' => 'fail', 'msg' => 'Plantilla no encontrada'], 404);
            }

            return response()->json(['status' => 'ok', 'data' => $plantilla]);
        } catch (\Exception $ex) {
            Log::error('Plantilla show error: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['status' => 'fail', 'msg' => 'No fue posible obtener la plantilla'], 500);
        }
    }

    /**
     * Actualizar plantilla y, si se envían, sus detalles.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $plantilla = RutinaGenerica::find($id);
            if (!$plantilla) {
                return response()->json(['status' => 'fail', 'msg' => 'Plantilla no encontrada'], 404);
            }   

            $plantilla->fill($request->only([
                'nombre',
                'descripcion',
                'objetivo',
                'estado',
                'fecha_inicio',
                'fecha_fin',
                'trimestre',
            ]));
            $plantilla->save();

            if ($request->has('dias_ejercicios') || $request->has('detalles')) {
                $detalles = $this->parseDetalles($request->input('dias_ejercicios') ?? $request->input('detalles'));

                DetalleRutinaGenerica::where('rutina_generica_id', $plantilla->id)->delete();
                $this->guardarDetalles($plantilla, $detalles);
            }

            DB::commit();

            $plantilla->load('detalles.ejercicio');
            return response()->json(['status' => 'ok', 'msg' => 'Plantilla actualizada', 'data' => $plantilla]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Plantilla update error: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['status' => 'fail', 'msg' => 'No fue posible actualizar la plantilla', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Eliminar una plantilla y sus detalles.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $plantilla = RutinaGenerica::find($id);
            if (!$plantilla) {
                return response()->json(['status' => 'fail', 'msg' => 'Plantilla no encontrada'], 404);
            }

            DetalleRutinaGenerica::where('rutina_generica_id', $plantilla->id)->delete();
            $plantilla->delete();

            DB::commit();
            return response()->json(['status' => 'ok', 'msg' => 'Plantilla eliminada exitosamente']);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error('Plantilla destroy error: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['status' => 'fail', 'msg' => 'No fue posible eliminar la plantilla'], 500);
        }
    }

    /**
     * Agregar un detalle puntual a una plantilla existente.
     */
    public function addDetalle(Request $request, $plantillaId)
    {
        try {
            $plantilla = RutinaGenerica::find($plantillaId);
            if (!$plantilla) {
                return response()->json(['status' => 'fail', 'msg' => 'Plantilla no encontrada'], 404);
            }

            $detalle = DetalleRutinaGenerica::create([
                'rutina_generica_id' => $plantillaId,
                'ejercicio_id' => $request->ejercicio_id,
                'dia_semana' => $request->dia_semana,
                'series_estandar' => $request->series_estandar,
                'repeticiones_estandar' => $request->repeticiones_estandar,
                'tiempo_descanso_estandar_seg' => $request->tiempo_descanso_estandar_seg,
                'orden_ejecucion' => $request->orden_ejecucion ?? 1,
                'notas' => $request->notas,
                'estado' => $request->estado ?? 'activo',
                'mesociclo' => $request->mesociclo,
                'peso' => $request->peso,
            ]);

            $detalle->load('ejercicio');

            return response()->json(['status' => 'ok', 'msg' => 'Detalle agregado', 'data' => $detalle]);
        } catch (\Exception $ex) {
            Log::error('Plantilla addDetalle error: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['status' => 'fail', 'msg' => 'No fue posible agregar el detalle'], 500);
        }
    }

    /**
     * Actualizar un detalle puntual.
     */
    public function updateDetalle(Request $request, $plantillaId, $detalleId)
    {
        try {
            $detalle = DetalleRutinaGenerica::where('rutina_generica_id', $plantillaId)
                ->where('id', $detalleId)
                ->first();

            if (!$detalle) {
                return response()->json(['status' => 'fail', 'msg' => 'Detalle no encontrado'], 404);
            }

            $detalle->fill($request->only([
                'ejercicio_id',
                'dia_semana',
                'series_estandar',
                'repeticiones_estandar',
                'tiempo_descanso_estandar_seg',
                'orden_ejecucion',
                'notas',
                'estado',
                'mesociclo',
                'peso',
            ]));
            $detalle->save();

            $detalle->load('ejercicio');

            return response()->json(['status' => 'ok', 'msg' => 'Detalle actualizado', 'data' => $detalle]);
        } catch (\Exception $ex) {
            Log::error('Plantilla updateDetalle error: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['status' => 'fail', 'msg' => 'No fue posible actualizar el detalle'], 500);
        }
    }

    /**
     * Eliminar un detalle puntual.
     */
    public function deleteDetalle($plantillaId, $detalleId)
    {
        try {
            $detalle = DetalleRutinaGenerica::where('rutina_generica_id', $plantillaId)
                ->where('id', $detalleId)
                ->first();

            if (!$detalle) {
                return response()->json(['status' => 'fail', 'msg' => 'Detalle no encontrado'], 404);
            }

            $detalle->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Detalle eliminado']);
        } catch (\Exception $ex) {
            Log::error('Plantilla deleteDetalle error: ' . $ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
            return response()->json(['status' => 'fail', 'msg' => 'No fue posible eliminar el detalle'], 500);
        }
    }

    /**
     * Normaliza el payload de detalles.
     */
    private function parseDetalles($detalles)
    {
        if (is_string($detalles)) {
            $decoded = json_decode($detalles, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $detalles = $decoded;
            } else {
                return [];
            }
        }

        if (!is_array($detalles)) {
            return [];
        }

        // Formato dias_ejercicios
        $diasKeys = ['lunes','martes','miercoles','jueves','viernes','sabado','domingo'];
        $diasMap = [
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6,
            'domingo' => 7,
        ];

        foreach ($diasKeys as $dia) {
            if (isset($detalles[$dia]) && is_array($detalles[$dia])) {
                $detallesPlanos = [];
                foreach ($detalles as $nombreDia => $listaDetalles) {
                    if (!isset($diasMap[$nombreDia]) || !is_array($listaDetalles)) {
                        continue;
                    }
                    foreach ($listaDetalles as $detalle) {
                        $detalle['dia_semana'] = $diasMap[$nombreDia];
                        $detallesPlanos[] = $detalle;
                    }
                }
                return $detallesPlanos;
            }
        }

        return $detalles;
    }

    /**
     * Guardar arreglo de detalles asociado a una plantilla.
     */
    private function guardarDetalles(RutinaGenerica $plantilla, array $detalles)
    {
        foreach ($detalles as $detalleData) {
            DetalleRutinaGenerica::create([
                'rutina_generica_id' => $plantilla->id,
                'ejercicio_id' => $detalleData['ejercicio_id'] ?? null,
                'dia_semana' => $detalleData['dia_semana'] ?? null,
                'series_estandar' => $detalleData['series_estandar'] ?? null,
                'repeticiones_estandar' => $detalleData['repeticiones_estandar'] ?? null,
                'tiempo_descanso_estandar_seg' => $detalleData['tiempo_descanso_estandar_seg'] ?? null,
                'orden_ejecucion' => $detalleData['orden_ejecucion'] ?? 1,
                'notas' => $detalleData['notas'] ?? null,
                'estado' => $detalleData['estado'] ?? 'activo',
                'mesociclo' => $detalleData['mesociclo'] ?? null,
                'peso' => $detalleData['peso'] ?? null,
            ]);
        }
    }
}
