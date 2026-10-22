<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteMembresia;
use App\Models\Membresia;
use App\Models\User;
use App\Models\Usuario;
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
            $usuarios = Usuario::where('sincronizado', 0)
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Operación exitosa',
                'data' => $usuarios
            ]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function updateSincronizado($id)
    {
        try {
            Usuario::where('id', $id)->update([
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
        $validator = Validator::make($request->all(), [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'tipo_documento' => 'required|string|max:50',
            'numero_documento' => 'required|string|max:20',
            'sexo' => 'nullable|string|max:20',
            'fecha_nacimiento' => 'nullable|date',
            'fecha_inicial' => 'required|date',
            'fecha_final' => 'required|date|after_or_equal:fecha_inicial',
            'membresia' => 'required|string',
            'precio_pagado' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'msg' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        if (strtoupper(trim($request->membresia)) === 'PASE DIA') {
            return response()->json([
                'status' => 'fail',
                'msg' => 'Membresía no válida para este proceso.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $membresia = Membresia::where('nombre', $request->membresia)->first();

            if (!$membresia) {
                $membresia = Membresia::create([
                    'nombre' => strtoupper($request->membresia),
                    'precio' => $request->precio_pagado ?? 0,
                    'duracion_dias' => 1,
                    'estado' => 'activo'
                ]);
            } elseif ($request->filled('precio_pagado') && $membresia->precio != $request->precio_pagado) {
                $membresia->update(['precio' => $request->precio_pagado]);
            }

            $cliente = Cliente::where('numero_documento', $request->numero_documento)->first();

            if ($cliente) {
                // 1. Comparar datos de entrada con datos de base de datos. Solo actualizar si alguno cambia.
                $datosActualizar = [];

                $nombreUpper = strtoupper(trim($request->nombres));
                if ($cliente->nombre !== $nombreUpper) {
                    $datosActualizar['nombre'] = $nombreUpper;
                }

                $apellidosUpper = strtoupper(trim($request->apellidos));
                if ($cliente->apellidos !== $apellidosUpper) {
                    $datosActualizar['apellidos'] = $apellidosUpper;
                }

                if ($cliente->tipo_documento !== $request->tipo_documento) {
                    $datosActualizar['tipo_documento'] = $request->tipo_documento;
                }

                if ($request->filled('sexo') && $cliente->sexo !== $request->sexo) {
                    $datosActualizar['sexo'] = $request->sexo;
                }

                if ($request->filled('telefono') && $cliente->telefono !== $request->telefono) {
                    $datosActualizar['telefono'] = $request->telefono;
                }

                if ($request->filled('correo') && $cliente->correo !== $request->correo) {
                    $datosActualizar['correo'] = $request->correo;
                }

                if ($request->filled('fecha_nacimiento')) {
                    $fechaNacInput = Carbon::parse($request->fecha_nacimiento)->format('Y-m-d');
                    $fechaNacDb = $cliente->fecha_nacimiento ? Carbon::parse($cliente->fecha_nacimiento)->format('Y-m-d') : null;
                    if ($fechaNacDb !== $fechaNacInput) {
                        $datosActualizar['fecha_nacimiento'] = $request->fecha_nacimiento;
                    }
                }

                if ((int)$cliente->sincronizado !== 1) {
                    $datosActualizar['sincronizado'] = 1;
                }

                if (!empty($datosActualizar)) {
                    $cliente->update($datosActualizar);
                    $action = 'actualizado';
                } else {
                    $action = 'sin_cambios';
                }
            } else {
                $cliente = Cliente::create([
                    'nombre' => strtoupper(trim($request->nombres)),
                    'apellidos' => strtoupper(trim($request->apellidos)),
                    'tipo_documento' => $request->tipo_documento,
                    'numero_documento' => $request->numero_documento,
                    'sexo' => $request->sexo,
                    'telefono' => $request->telefono,
                    'correo' => $request->correo,
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'sincronizado' => 1
                ]);
                $action = 'creado';
            }

            if (Carbon::parse($request->fecha_final)->gte(Carbon::today())) {
                $usuario = User::where('documento_identidad', $request->numero_documento)->first();

                if (!$usuario) {
                    User::create([
                        'name' => strtoupper($request->nombres . ' ' . $request->apellidos),
                        'email' => $request->correo ?? $request->numero_documento . '@legion.com',
                        'rol_id' => 1,
                        'password' => bcrypt($request->numero_documento),
                        'documento_identidad' => $request->numero_documento,
                        'status' => 'activo'
                    ]);
                } elseif ($usuario->status === 'inactivo') {
                    $usuario->update([
                        'status' => 'activo',
                        'updated_at' => now()
                    ]);
                }
            }

            // 2. Control de membresías activas: No deben quedar clientes con dos membresías activas.
            // Verificar fecha_fin con respecto a la membresía activa previa y desactivar la anterior.
            $nuevaFechaFin = Carbon::parse($request->fecha_final);
            $membresiasActivas = ClienteMembresia::where('cliente_id', $cliente->id)
                ->whereIn('estado', ['activo', 'ACTIVO'])
                ->get();

            $nuevoEstado = 'activo';

            if ($membresiasActivas->isNotEmpty()) {
                // Obtener la membresía activa con mayor fecha_fin
                $ultimaMembresiaActiva = $membresiasActivas->sortByDesc(function ($item) {
                    return Carbon::parse($item->fecha_fin)->timestamp;
                })->first();

                $fechaFinAnterior = Carbon::parse($ultimaMembresiaActiva->fecha_fin);

                if ($nuevaFechaFin->gt($fechaFinAnterior)) {
                    // Si la fecha fin de la nueva membresía es mayor a la anterior, desactivar la(s) anterior(es)
                    ClienteMembresia::where('cliente_id', $cliente->id)
                        ->whereIn('estado', ['activo', 'ACTIVO'])
                        ->update(['estado' => 'vencido']);

                    $nuevoEstado = 'activo';
                } else {
                    // Si la nueva fecha fin NO es mayor a la anterior, la nueva membresía queda vencida para no dejar 2 activas
                    $nuevoEstado = 'vencido';
                }
            }

            $clienteMembresia = ClienteMembresia::where('cliente_id', $cliente->id)
                ->where('fecha_inicio', $request->fecha_inicial)
                ->where('fecha_fin', $request->fecha_final)
                ->first();

            if ($clienteMembresia) {
                $clienteMembresia->update([
                    'membresia_id' => $membresia->id,
                    'estado' => $nuevoEstado,
                    'precio_pagado' => $request->precio_pagado,
                    'observaciones' => $request->observaciones
                ]);
            } else {
                $clienteMembresia = ClienteMembresia::create([
                    'cliente_id' => $cliente->id,
                    'membresia_id' => $membresia->id,
                    'fecha_inicio' => $request->fecha_inicial,
                    'fecha_fin' => $request->fecha_final,
                    'estado' => $nuevoEstado,
                    'precio_pagado' => $request->precio_pagado,
                    'observaciones' => $request->observaciones
                ]);
            }

            DB::commit();

            $msg = $action === 'creado' 
                ? 'Cliente creado exitosamente' 
                : ($action === 'actualizado' ? 'Cliente actualizado exitosamente' : 'Cliente procesado exitosamente');

            return response()->json([
                'status' => 'ok',
                'msg' => $msg,
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
            $today = Carbon::today()->toDateString();

            // 1. Marcar membresías vencidas
            $updatedMembresias = ClienteMembresia::where('estado', 'activo')
                ->whereDate('fecha_fin', '<', $today)
                ->update(['estado' => 'vencido']);

            // 2. Desactivar usuarios solo si NO tienen ninguna otra membresía activa
            $clientesConActivas = ClienteMembresia::where('estado', 'activo')
                ->whereDate('fecha_fin', '>=', $today)
                ->pluck('cliente_id');

            $documentosDesactivar = Cliente::whereNotIn('id', $clientesConActivas)
                ->pluck('numero_documento');

            $updatedUsers = User::whereIn('documento_identidad', $documentosDesactivar)
                ->where('status', 'activo')
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