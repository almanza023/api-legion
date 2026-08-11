<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsuarioAsynController extends Controller
{
    public function index(Request $request){
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $usuarios = Usuario::paginate($perPage);
                
                return response()->json([
                    'data' => $usuarios->items(),
                    'current_page' => $usuarios->currentPage(),
                    'per_page' => $usuarios->perPage(),
                    'total' => $usuarios->total(),
                    'last_page' => $usuarios->lastPage()
                ]);
            } else {
                $usuarios = Usuario::all();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $usuarios]);
            }
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request){
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:50'],
            'document_number' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'birthDay' => ['nullable', 'date'],
        ]);

        DB::beginTransaction();
        try {
            $documentNumber = $request->input('document_number');
            $email = $request->input('email');

            $usuario_filter = Usuario::where('numero_documento', $documentNumber)->first();
            if($usuario_filter){
                return response()->json(['status' => 'fail', 'msg' => 'El usuario ya existe', 'data' => null], 400);
            }

            $user_filter = User::where('documento_identidad', $documentNumber)->first();
            if($user_filter){
                DB::rollBack();
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese documento de identidad', 'data' => null], 400);
            }

            if($email){
                $email_filter = User::where('email', $email)->first();
                if($email_filter){
                    DB::rollBack();
                    return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese correo electrónico', 'data' => null], 400);
                }
            }

            $usuario = new Usuario();
            $usuario->nombre = $request->input('name');
            $usuario->apellidos = $request->input('lastname');
            $usuario->tipo_documento = $request->input('document_type');
            $usuario->numero_documento = $documentNumber;
            $usuario->sexo = $request->input('gender');
            $usuario->telefono = $request->input('phone');
            $usuario->correo = $email;
            if($request->filled('birthDay')){
                $usuario->fecha_nacimiento = $request->input('birthDay');
            }
            $usuario->sincronizado = 1;
            $usuario->save();

            $user = new User();
            $user->name = trim($request->input('name') . ' ' . $request->input('lastname'));
            $user->email = $email ?? $documentNumber . '@usuario.com';
            $user->documento_identidad = $documentNumber;
            $user->password = bcrypt($documentNumber);
            $user->rol_id = 1;
            $user->status = 'activo';
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 'ok', 
                'msg' => 'Usuario y usuario creados exitosamente', 
                'data' => [
                    'usuario' => $usuario,
                    'user' => $user
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
            $usuario = Usuario::find($id);
            if(!$usuario){
                return response()->json(['status' => 'fail', 'msg' => 'Usuario no encontrado', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $usuario]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function getByDocumento($documento_identidad){
        try {
            $usuario = Usuario::where('numero_documento', $documento_identidad)->first();
            if(!$usuario){
                return response()->json(['status' => 'fail', 'msg' => 'Usuario no encontrado', 'data' => null], 404);
            }
            return response()->json(['data' => $usuario], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        $usuario = Usuario::find($id);
        if(!$usuario){
            return response()->json(['status' => 'fail', 'msg' => 'Usuario no encontrado', 'data' => null], 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'lastname' => ['sometimes', 'string', 'max:255'],
            'document_type' => ['sometimes', 'string', 'max:50'],
            'document_number' => ['sometimes', 'string', 'max:50'],
            'gender' => ['sometimes', 'string', 'max:50'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'birthDay' => ['nullable', 'date'],
        ]);

        if($request->has('document_number') && $request->input('document_number') !== $usuario->numero_documento){
            $usuario_existente = Usuario::where('numero_documento', $request->input('document_number'))->first();
            if($usuario_existente){
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese número de documento', 'data' => null], 400);
            }
        }

        if($request->has('name')) $usuario->nombre = $request->input('name');
        if($request->has('lastname')) $usuario->apellidos = $request->input('lastname');
        if($request->has('document_type')) $usuario->tipo_documento = $request->input('document_type');
        if($request->has('document_number')) $usuario->numero_documento = $request->input('document_number');
        if($request->has('gender')) $usuario->sexo = $request->input('gender');
        if($request->has('phone')) $usuario->telefono = $request->input('phone');
        if($request->has('email')) $usuario->correo = $request->input('email');
        if($request->has('birthDay') && !empty($request->input('birthDay'))){
            $usuario->fecha_nacimiento = $request->input('birthDay');
        }

        $usuario->save();
        return response()->json(['status' => 'ok', 'msg' => 'Usuario actualizado exitosamente', 'data' => $usuario]);
    }

    public function destroy($id){
        try {
            $usuario = Usuario::find($id);
            if(!$usuario){
                return response()->json(['status' => 'fail', 'msg' => 'Usuario no encontrado', 'data' => null], 404);
            }

            $usuario->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Usuario eliminado exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function syncMember($id){
        try {
            $usuario = Usuario::find($id);
            $usuario->sincronizado = 1;
            $usuario->save();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function createMember(Request $request){
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'max:50'],
            'document_number' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'birthDay' => ['nullable', 'date'],
        ]);

        DB::beginTransaction();
        try {
            $documentNumber = $request->input('document_number');
            $email = $request->input('email');

            $usuario_filter = Usuario::where('numero_documento', $documentNumber)->first();
            if($usuario_filter){
                return response()->json(['status' => 'fail', 'msg' => 'El usuario ya existe', 'data' => null], 400);
            }

            $user_filter = User::where('documento_identidad', $documentNumber)->first();
            if($user_filter){
                DB::rollBack();
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese documento de identidad', 'data' => null], 400);
            }

            if($email){
                $email_filter = User::where('email', $email)->first();
                if($email_filter){
                    DB::rollBack();
                    return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese correo electrónico', 'data' => null], 400);
                }
            }

            $usuario = new Usuario();
            $usuario->nombre = $request->input('name');
            $usuario->apellidos = $request->input('lastname');
            $usuario->tipo_documento = $request->input('document_type');
            $usuario->numero_documento = $documentNumber;
            $usuario->sexo = $request->input('gender');
            $usuario->telefono = $request->input('phone');
            $usuario->correo = $email;
            if($request->filled('birthDay')){
                $usuario->fecha_nacimiento = $request->input('birthDay');
            }
            $usuario->sincronizado = 1;
            $usuario->save();

            $user = new User();
            $user->name = trim($request->input('name') . ' ' . $request->input('lastname'));
            $user->email = $email ?? $documentNumber . '@usuario.com';
            $user->documento_identidad = $documentNumber;
            $user->password = bcrypt($documentNumber);
            $user->rol_id = 1;
            $user->status = 'activo';
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 'ok', 
                'msg' => 'Usuario y usuario creados exitosamente', 
                'data' => [
                    'usuario' => $usuario,
                    'user' => $user
                ]
            ]);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    private function normalizeDocumentType(?string $documentType): ?string
    {
        return $documentType === 'Cédula de Ciudadanía' ? 'CC' : $documentType;
    }
}
