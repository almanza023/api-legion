<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteMembresia;
use App\Models\Entrenador;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index(Request $request)
    {
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $users = User::select('id', 'name', 'email', 'documento_identidad', 'rol_id', 'status', 'ultimo_acceso')
                    ->with('rol:id,nombre')
                    ->paginate($perPage);
                
                return response()->json([
                    'data' => $users->items(),
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage()
                ]);
            } else {
                $users = User::select('id', 'name', 'email', 'documento_identidad', 'rol_id', 'status', 'ultimo_acceso')
                    ->with('rol:id,nombre')
                    ->get();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $users]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'documento_identidad' => 'required|string|max:20|unique:users,documento_identidad',
                'password' => 'required|string|min:6',
                'rol_id' => 'required|integer|exists:roles,id',
                'status' => 'in:activo,inactivo',
                'signature' => 'nullable|string',
                'change_password' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'documento_identidad' => $request->documento_identidad,
                'password' => bcrypt($request->password),
                'rol_id' => $request->rol_id,
                'status' => $request->status ?? 'activo',
                'signature' => $request->signature,
                'change_password' => $request->change_password ?? false
            ]);

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Usuario creado exitosamente',
                'data' => $user->load('rol')
            ], 201);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al crear el usuario'], 500);
        }
    }

    /**
     * Mostrar un usuario específico
     */
    public function show($id)
    {
        try {
            $user = User::with('rol')->find($id);

            if (!$user) {
                return response()->json(['status' => 'fail', 'msg' => 'Usuario no encontrado'], 404);
            }

            return response()->json(['status' => 'ok', 'data' => $user], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Error al obtener el usuario'], 500);
        }
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['status' => 'fail', 'msg' => 'Usuario no encontrado'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'documento_identidad' => 'sometimes|required|string|max:20|unique:users,documento_identidad,' . $id,
                'password' => 'sometimes|nullable|string|min:6',
                'rol_id' => 'sometimes|required|integer|exists:roles,id',
                'status' => 'sometimes|in:activo,inactivo',
                'signature' => 'nullable|string',
                'change_password' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $dataToUpdate = $request->only(['name', 'email', 'documento_identidad', 'rol_id', 'status', 'signature', 'change_password']);

            // Solo actualizar password si se proporciona
            if ($request->filled('password')) {
                $dataToUpdate['password'] = bcrypt($request->password);
            }

            $user->update($dataToUpdate);

            DB::commit();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Usuario actualizado exitosamente',
                'data' => $user->load('rol')
            ], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Error al actualizar el usuario'], 500);
        }
    }

    /**
     * Eliminar un usuario (soft delete)
     */
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['status' => 'fail', 'msg' => 'Usuario no encontrado'], 404);
            }

            DB::beginTransaction();
            $user->delete();
            DB::commit();

            return response()->json(['status' => 'ok', 'msg' => 'Usuario eliminado exitosamente'], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Error al eliminar el usuario'], 500);
        }
    }

    public function login(Request $request)
    {
        // Mapear 'usuario' a 'documento_identidad' para la autenticación
        $credentials = [
            'documento_identidad' => $request->usuario ?? $request->documento_identidad,
            'password' => $request->password,
            'status'=>'activo'

        ];

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }

            $user = Auth::user();
            $clienteId = null;
            
            // Solo verificar membresía para clientes (rol_id = 1)
            if ($user && $user->rol_id == 1) {
                $cliente = Cliente::where('numero_documento', $user->documento_identidad)->first();
                
                if (!$cliente) {
                    return response()->json(['error' => 'Cliente no encontrado'], 404);
                }

                $clienteId = $cliente->id;
                
                $hasActiveMembresia = ClienteMembresia::where('cliente_id', $cliente->id)
                    ->where('estado', 'activo')
                    ->exists();
                    
                if (!$hasActiveMembresia) {
                    return response()->json(['error' => 'Usuario no tiene membresía activa'], 403);
                }
            }

            // Actualizar último acceso
            $user->update(['ultimo_acceso' => now()]);

            if ($clienteId !== null) {
                $user->cliente_id = $clienteId;
            }

            return response()->json([
                'token' => $token,
                'user' => $user
            ], 200);
        } catch (JWTException $e) {
            return response()->json(['error' => 'No se pudo crear el token', 'details' => $e->getMessage()], 500);
        }
    }

    public function getUser()
    {
        $user = Auth::user();
        
        if($user->rol_id == 1){
            $id=Cliente::where('numero_documento',$user->documento_identidad)->first()->id;
            $user->cliente_id = $id;
        }else if($user->rol_id == 2){
            $id=Entrenador::where('documento_identidad',$user->documento_identidad)->first()->id;
            $user->entrenador_id = $id;
        }

        return response()->json($user, 200);
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'message' => 'Successfully logged out'
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'error' => 'Failed to logout, please try again.'
            ], 500);
        }
    }

    /**
     * Cambiar contraseña del usuario autenticado
     */
    public function changePassword(Request $request)
    {
        try {
            // Validar los datos de entrada
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8',
                'new_password_confirmation' => 'required|string',
                'gender' => 'nullable|string|max:20',
                'birthDay' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            // Validar que la contraseña actual sea correcta
            if (!password_verify($request->current_password, $user->password)) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'La contraseña actual es incorrecta'
                ], 400);
            }

            // Validar que las nuevas contraseñas coincidan
            if ($request->new_password !== $request->new_password_confirmation) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'Las contraseñas nuevas no coinciden'
                ], 400);
            }

            // Validar que la nueva contraseña sea diferente a la actual
            if (password_verify($request->new_password, $user->password)) {
                return response()->json([
                    'status' => 'fail',
                    'msg' => 'La nueva contraseña debe ser diferente a la actual'
                ], 400);
            }

            // Actualizar la contraseña
            DB::beginTransaction();
            $shouldUpdateProfile = !$user->change_password;
            $user->password = bcrypt($request->new_password);
            $user->change_password = true;
            $user->save();

            if ($shouldUpdateProfile && ($request->filled('gender') || $request->filled('birthDay'))) {
                $cliente = Cliente::where('numero_documento', $user->documento_identidad)->first();

                if ($cliente) {
                    $clienteData = [];

                    if ($request->filled('gender')) {
                        $clienteData['sexo'] = $request->gender;
                    }

                    if ($request->filled('birthDay')) {
                        $clienteData['fecha_nacimiento'] = $request->birthDay;
                    }

                    if (!empty($clienteData)) {
                        $cliente->update($clienteData);
                    }
                }
            }
            DB::commit();

            return response()->json([
                'status' => 'ok',
                'msg' => 'Contraseña actualizada exitosamente'
            ], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json([
                'status' => 'fail',
                'msg' => 'Error al cambiar la contraseña'
            ], 500);
        }
    }
}
