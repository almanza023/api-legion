<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteMembresia;
use App\Models\Membresia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PublicClienteController extends Controller
{

        public function getUnsyncedUsuarios(Request $request)
        {
            try {
                $usuarios = \App\Models\Usuario::where('sincronizado', 0)->orderBy('id', 'desc')->get();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $usuarios]);
            } catch (\Exception $ex) {
                Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
                return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
            }
        }

public function updateSincronizado($id)
{
    try {

        \App\Models\Usuario::where('id', $id)->update([
            'sincronizado' => 1,
            'updated_at' => now()
        ]);

        return response()->json(['status' => 'ok', 'msg' => 'Usuario sincronizado correctamente']);
    } catch (\Exception $ex) {
        Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
        return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
    }
}


    public function createOrUpdateCliente(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombres' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'tipo_documento' => 'required|string|max:50',
                'numero_documento' => 'required|string|max:20',
                'sexo' => 'nullable|string|max:20',
                'fecha_nacimiento' => 'nullable|date',
                'fecha_inicial' => 'required|date',
                'fecha_final' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            //Registrar la Membresia si no existe, excepto para PASE DIA
            
            if($request->membresia=='PASE DIA'){
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Membresía no válida para este proceso.'
                ], 400);
            }
            $membresia = Membresia::where('nombre', $request->membresia)->first();
            if (!$membresia ) {
                $membresia = Membresia::create([
                    'nombre' => strtoupper($request->membresia),
                    'precio' => 0,
                    'duracion_dias' => 1,
                    'estado' => 'activo'
                ]);
            }

            $cliente = Cliente::where('numero_documento', $request->numero_documento)->first();

            if ($cliente) {
                $cliente->update([
                    'nombre' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'tipo_documento' => $request->tipo_documento,
                    'sexo' => $request->sexo ?? $cliente->sexo,
                    'telefono' => $request->telefono ?? $cliente->telefono,
                    'correo' => $request->correo ?? $cliente->correo,
                    'fecha_nacimiento' => $request->fecha_nacimiento ?? $cliente->fecha_nacimiento,
                    'sincronizado' => 1
                ]);
                $action = 'actualizado';
            } else {
                $cliente = Cliente::create([
                    'nombre' => strtoupper($request->nombres),
                    'apellidos' => strtoupper($request->apellidos),
                    'tipo_documento' => $request->tipo_documento,
                    'numero_documento' => $request->numero_documento,
                    'sexo' => $request->sexo ?? null,
                    'telefono' => $request->telefono ?? null,
                    'correo' => $request->correo ?? null,
                    'fecha_nacimiento' => $request->fecha_nacimiento ?? null,
                    'sincronizado' => 1
                ]);
                $action = 'creado';
                $cliente->save();

            }

            $clienteMembresia = null;
            if (strtoupper($membresia->nombre) !== 'PASE DIA') {
                if (Carbon::parse($request->fecha_final)->gte(Carbon::now())) {
                    $usuario = \App\Models\User::where('documento_identidad', $request->numero_documento)->first();
                    
                    if (!$usuario) {
                        $user = \App\Models\User::create([
                            'name' => strtoupper($request->nombres . ' ' . $request->apellidos),
                            'email' => $request->correo ?? $request->numero_documento.'@legion.com',
                            'rol_id' => 1,
                            'password' => bcrypt($request->numero_documento),
                            'documento_identidad' => $request->numero_documento,
                        ]);
                    }else{
                        if($usuario->status == 'inactivo'){
                            $usuario->update([
                                'status' => 'activo',
                                'updated_at' => now()
                            ]);
                        }
                    }
                }
                
                $clienteMembresia = ClienteMembresia::where('cliente_id', $cliente->id)
                    ->where('fecha_inicio', $request->fecha_inicial)
                    ->where('fecha_fin', $request->fecha_final)
                    ->first();

                if ($clienteMembresia) {
                   //No Actualizar
                } else {
                    $clienteMembresia = ClienteMembresia::create([
                        'cliente_id' => $cliente->id,
                        'membresia_id' => $membresia->id,
                        'fecha_inicio' => $request->fecha_inicial,
                        'fecha_fin' => $request->fecha_final,
                        'estado' => 'activo',
                        'precio_pagado' => $membresia->precio,
                        'observaciones' => null
                    ]);
                }
            }
            
            DB::commit();

            return response()->json([
                'status' => 'ok',
                'msg' => "Cliente {$action} exitosamente",
                'data' => [
                    'cliente' => $cliente,
                    'cliente_membresia' => $clienteMembresia ? $clienteMembresia->load('membresia') : null
                ]
            ], $action === 'creado' ? 201 : 200);

        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json([
                'status' => 'fail',
                'msg' => 'Ha ocurrido un error al procesar la solicitud',
                'error' => $ex->getMessage()
            ], 500);
        }
    }

public function updateExpiredMembresias()
{
    try {
        $today = now()->toDateString(); // Carbon integrado en Laravel

        // Paso 1: Obtener cliente_ids de membresías que vencieron
        $clienteIds = ClienteMembresia::where('estado', 'activo')
            ->whereDate('fecha_fin', '<', $today)
            ->pluck('cliente_id');

        // Paso 2: Actualizar membresías a 'vencido'
        $updatedMembresias = ClienteMembresia::where('estado', 'activo')
            ->whereDate('fecha_fin', '<', $today)
            ->update(['estado' => 'vencido']);

        // Paso 3: Obtener documentos de los clientes
        $documentos = Cliente::whereIn('id', $clienteIds)
            ->pluck('numero_documento');

        // Paso 4: Actualizar usuarios
        $updatedUsers = User::whereIn('documento_identidad', $documentos)
            ->update(['status' => 'inactivo', 'updated_at' => now()]);

        return response()->json([
            'status' => 'ok',
            'msg' => 'Membresías vencidas actualizadas exitosamente',
            'data' => [
                'membresias_actualizadas' => $updatedMembresias,
                'usuarios_actualizados' => $updatedUsers
            ]
        ]);

    } catch (\Exception $ex) {
        Log::error($ex->getMessage(), ['trace' => $ex->getTraceAsString()]);
        return response()->json([
            'status' => 'fail',
            'msg' => 'Ha ocurrido un error al procesar la solicitud'
        ], 500);
    }
}

}
