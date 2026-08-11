<?php

namespace App\Http\Controllers;

use App\Models\Entrenador;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntrenadorAsynController extends Controller
{
    public function index(Request $request){
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $entrenadores = Entrenador::where('estado', 'activo')->paginate($perPage);
                
                return response()->json([
                    'data' => $entrenadores->items(),
                    'current_page' => $entrenadores->currentPage(),
                    'per_page' => $entrenadores->perPage(),
                    'total' => $entrenadores->total(),
                    'last_page' => $entrenadores->lastPage()
                ]);
            } else {
                $entrenadores = Entrenador::where('estado', 'activo')->get();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $entrenadores]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        DB::beginTransaction();
        try {
            // Validar documento_identidad requerido
            if(!$request->has('documento_identidad') || empty($request->documento_identidad)){
                return response()->json(['status' => 'fail', 'msg' => 'El documento de identidad es requerido', 'data' => null], 400);
            }

            // Validar si el entrenador ya existe por correo
            $entrenador_filter = Entrenador::where('correo', $request->correo)->first();
            if($entrenador_filter){
                DB::rollBack();
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un entrenador con ese correo', 'data' => null], 400);
            }

            // Validar si el entrenador ya existe por documento
            $entrenador_doc_filter = Entrenador::where('documento_identidad', $request->documento_identidad)->first();
            if($entrenador_doc_filter){
                DB::rollBack();
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un entrenador con ese documento de identidad', 'data' => null], 400);
            }

            // Validar si el usuario ya existe
            $user_filter = User::where('documento_identidad', $request->documento_identidad)->first();
            if($user_filter){
                DB::rollBack();
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese documento de identidad', 'data' => null], 400);
            }

            // Validar si el email ya existe
            $email_filter = User::where('email', $request->correo)->first();
            if($email_filter){
                DB::rollBack();
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese correo electrónico', 'data' => null], 400);
            }

            // Crear entrenador
            $entrenador = new Entrenador();
            $entrenador->nombres = $request->nombres;
            $entrenador->apellidos = $request->apellidos;
            $entrenador->documento_identidad = $request->documento_identidad;
            $entrenador->telefono = $request->telefono;
            $entrenador->correo = $request->correo;

            if(!empty($request->fecha_nacimiento)){
                try {
                    // Try d/m/Y format first
                    $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $request->fecha_nacimiento);
                    $entrenador->fecha_nacimiento = $fecha->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        // Try Y-m-d format as fallback
                        $fecha = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fecha_nacimiento);
                        $entrenador->fecha_nacimiento = $fecha->format('Y-m-d');
                    } catch (\Exception $e2) {
                        // If both fail, try Carbon's parse method
                        $fecha = \Carbon\Carbon::parse($request->fecha_nacimiento);
                        $entrenador->fecha_nacimiento = $fecha->format('Y-m-d');
                    }
                }
            }

            $entrenador->especialidad = $request->especialidad;
            $entrenador->estado = 'activo';
            $entrenador->save();

            // Crear usuario con rol_id=2 (Entrenador)
            $user = new User();
            $user->name = $request->nombres . ' ' . $request->apellidos;
            $user->email = $request->correo;
            $user->documento_identidad = $request->documento_identidad;
            $user->password = bcrypt($request->documento_identidad); // Password por defecto es el número de documento
            $user->rol_id = 2; // Rol Entrenador
            $user->status = 'activo';
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Entrenador creado exitosamente',
                'data' => [
                    'entrenador' => $entrenador,
                ]
            ]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id){
        try {
            $entrenador = Entrenador::find($id);
            if(!$entrenador){
                return response()->json(['status' => 'fail', 'msg' => 'Entrenador no encontrado', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $entrenador]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function getByDocumento($documento_identidad){
        try {
            $entrenador = Entrenador::where('documento_identidad', $documento_identidad)->first();
            if(!$entrenador){
                return response()->json(['status' => 'fail', 'msg' => 'Entrenador no encontrado', 'data' => null], 404);
            }
            return response()->json(['data' => $entrenador], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $entrenador = Entrenador::find($id);
            if(!$entrenador){
                return response()->json(['status' => 'fail', 'msg' => 'Entrenador no encontrado', 'data' => null], 404);
            }

            if($request->has('nombres')) $entrenador->nombres = $request->nombres;
            if($request->has('apellidos')) $entrenador->apellidos = $request->apellidos;
            if($request->has('telefono')) $entrenador->telefono = $request->telefono;
            if($request->has('correo')) $entrenador->correo = $request->correo;
            if($request->has('especialidad')) $entrenador->especialidad = $request->especialidad;
            if($request->has('estado')) $entrenador->estado = $request->estado;

            if(!empty($request->fecha_nacimiento)){
                try {
                    // Try d/m/Y format first
                    $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $request->fecha_nacimiento);
                    $entrenador->fecha_nacimiento = $fecha->format('Y-m-d');
                } catch (\Exception $e) {
                    try {
                        // Try Y-m-d format as fallback
                        $fecha = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fecha_nacimiento);
                        $entrenador->fecha_nacimiento = $fecha->format('Y-m-d');
                    } catch (\Exception $e2) {
                        // If both fail, try Carbon's parse method
                        $fecha = \Carbon\Carbon::parse($request->fecha_nacimiento);
                        $entrenador->fecha_nacimiento = $fecha->format('Y-m-d');
                    }
                }
            }

            $entrenador->save();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $entrenador]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id){
        try {
            $entrenador = Entrenador::find($id);
            if(!$entrenador){
                return response()->json(['status' => 'fail', 'msg' => 'Entrenador no encontrado', 'data' => null], 404);
            }
            $entrenador->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Buscar entrenador por número de documento (POST)
     */
    public function porDocumento(Request $request){
        try {
            $numero_documento = $request->input('numero_documento');
            
            if(!$numero_documento){
                return response()->json(['status' => 'fail', 'msg' => 'numero_documento es requerido', 'data' => null], 400);
            }

            $entrenador = Entrenador::where('documento_identidad', $numero_documento)->first();
            
            if(!$entrenador){
                return response()->json(['status' => 'fail', 'msg' => 'Entrenador no encontrado', 'data' => null], 404);
            }

            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $entrenador]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
