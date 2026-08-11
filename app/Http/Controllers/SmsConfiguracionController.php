<?php

namespace App\Http\Controllers;

use App\Models\SmsConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SmsConfiguracionController extends Controller
{
    /**
     * Listar todas las configuraciones de SMS.
     */
    public function index(Request $request)
    {
        try {
            $paginate = $request->get('paginate', false);
            $perPage  = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $data = SmsConfiguracion::paginate($perPage);

                return response()->json([
                    'data'         => $data->items(),
                    'current_page' => $data->currentPage(),
                    'per_page'     => $data->perPage(),
                    'total'        => $data->total(),
                    'last_page'    => $data->lastPage(),
                ]);
            }

            $data = SmsConfiguracion::all();

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $data]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    /**
     * Crear una nueva configuración de SMS.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'dias'    => 'required|integer|min:0',
                'mensaje' => 'required|string',
                'estado'  => 'nullable|in:activo,inactivo',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'fail', 'msg' => $validator->errors()->first()], 422);
            }

            $sms = SmsConfiguracion::create([
                'dias'    => $request->dias,
                'mensaje' => $request->mensaje,
                'estado'  => $request->get('estado', 'activo'),
            ]);

            return response()->json(['status' => 'ok', 'msg' => 'Configuración creada correctamente', 'data' => $sms], 201);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    /**
     * Mostrar una configuración de SMS específica.
     */
    public function show(string $id)
    {
        try {
            $sms = SmsConfiguracion::find($id);

            if (!$sms) {
                return response()->json(['status' => 'fail', 'msg' => 'Configuración no encontrada'], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $sms]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    /**
     * Actualizar una configuración de SMS.
     */
    public function update(Request $request, string $id)
    {
        try {
            $sms = SmsConfiguracion::find($id);

            if (!$sms) {
                return response()->json(['status' => 'fail', 'msg' => 'Configuración no encontrada'], 404);
            }

            $validator = Validator::make($request->all(), [
                'dias'    => 'sometimes|required|integer|min:0',
                'mensaje' => 'sometimes|required|string',
                'estado'  => 'nullable|in:activo,inactivo',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'fail', 'msg' => $validator->errors()->first()], 422);
            }

            $sms->update($request->only(['dias', 'mensaje', 'estado']));

            return response()->json(['status' => 'ok', 'msg' => 'Configuración actualizada correctamente', 'data' => $sms]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    /**
     * Eliminar (soft delete) una configuración de SMS.
     */
    public function destroy(string $id)
    {
        try {
            $sms = SmsConfiguracion::find($id);

            if (!$sms) {
                return response()->json(['status' => 'fail', 'msg' => 'Configuración no encontrada'], 404);
            }

            $sms->delete();

            return response()->json(['status' => 'ok', 'msg' => 'Configuración eliminada correctamente']);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }
}
