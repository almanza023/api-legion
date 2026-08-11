<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClienteAsynController extends Controller
{
    public function index(Request $request){
        try {
            $paginate = $request->get('paginate', false);
            $perPage = $request->get('per_page', 12);

            if ($paginate === 'true' || $paginate === true) {
                $clientes = Cliente::paginate($perPage);
                
                return response()->json([
                    'data' => $clientes->items(),
                    'current_page' => $clientes->currentPage(),
                    'per_page' => $clientes->perPage(),
                    'total' => $clientes->total(),
                    'last_page' => $clientes->lastPage()
                ]);
            } else {
                $clientes = Cliente::all();
                return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $clientes]);
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

            // Validar si el cliente ya existe
            $cliente_filter = Cliente::where('numero_documento', $documentNumber)->first();
            if($cliente_filter){
                return response()->json(['status' => 'fail', 'msg' => 'El cliente ya existe', 'data' => null], 400);
            }

            // Validar si el usuario ya existe
            $user_filter = User::where('documento_identidad', $documentNumber)->first();
            if($user_filter){
                DB::rollBack();
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese documento de identidad', 'data' => null], 400);
            }

            // Validar si el email ya existe
            if($email){
                $email_filter = User::where('email', $email)->first();
                if($email_filter){
                    DB::rollBack();
                    return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese correo electrónico', 'data' => null], 400);
                }
            }

            // Crear cliente
            $cliente = new Cliente();
            $cliente->nombre = $request->input('name');
            $cliente->apellidos = $request->input('lastname');
            $cliente->tipo_documento = $request->input('document_type');
            $cliente->numero_documento = $documentNumber;
            $cliente->sexo = $request->input('gender');
            $cliente->telefono = $request->input('phone');
            $cliente->correo = $email;
            if($request->filled('birthDay')){
                $cliente->fecha_nacimiento = $request->input('birthDay');
            }
            $cliente->sincronizado = 1;
            $cliente->save();

            // Crear usuario con rol_id=1 (Cliente)
            $user = new User();
            $user->name = trim($request->input('name') . ' ' . $request->input('lastname'));
            $user->email = $email ?? $documentNumber . '@cliente.com';
            $user->documento_identidad = $documentNumber;
            $user->password = bcrypt($documentNumber); // Password por defecto es el número de documento
            $user->rol_id = 1; // Rol Cliente
            $user->status = 'activo';
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 'ok', 
                'msg' => 'Cliente y usuario creados exitosamente', 
                'data' => [
                    'cliente' => $cliente,
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
            $cliente = Cliente::find($id);
            if(!$cliente){
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $cliente]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function getByDocumento($documento_identidad){
        try {
            $cliente = Cliente::where('numero_documento', $documento_identidad)->first();
            if(!$cliente){
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
            }
            return response()->json(['data' => $cliente], 200);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id){
        $cliente = Cliente::find($id);
        if(!$cliente){
            return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
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

        if($request->has('document_number') && $request->input('document_number') !== $cliente->numero_documento){
            $cliente_existente = Cliente::where('numero_documento', $request->input('document_number'))->first();
            if($cliente_existente){
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un cliente con ese número de documento', 'data' => null], 400);
            }
        }

        if($request->has('name')) $cliente->nombre = $request->input('name');
        if($request->has('lastname')) $cliente->apellidos = $request->input('lastname');
        if($request->has('document_type')) $cliente->tipo_documento = $request->input('document_type');
        if($request->has('document_number')) $cliente->numero_documento = $request->input('document_number');
        if($request->has('gender')) $cliente->sexo = $request->input('gender');
        if($request->has('phone')) $cliente->telefono = $request->input('phone');
        if($request->has('email')) $cliente->correo = $request->input('email');
        if($request->has('birthDay') && !empty($request->input('birthDay'))){
            $cliente->fecha_nacimiento = $request->input('birthDay');
        }

        $cliente->save();
        return response()->json(['status' => 'ok', 'msg' => 'Cliente actualizado exitosamente', 'data' => $cliente]);
    }

    public function destroy($id){
        try {
            $cliente = Cliente::find($id);
            if(!$cliente){
                return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado', 'data' => null], 404);
            }

            $cliente->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Cliente eliminado exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function syncMember($id){
        try {
            $cliente = Cliente::find($id);
            $cliente->sincronizado = 1;
            $cliente->save();
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

            // Validar si el cliente ya existe
            $cliente_filter = Cliente::where('numero_documento', $documentNumber)->first();
            if($cliente_filter){
                return response()->json(['status' => 'fail', 'msg' => 'El cliente ya existe', 'data' => null], 400);
            }

            // Validar si el usuario ya existe
            $user_filter = User::where('documento_identidad', $documentNumber)->first();
            if($user_filter){
                DB::rollBack();
                return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese documento de identidad', 'data' => null], 400);
            }

            // Validar si el email ya existe
            if($email){
                $email_filter = User::where('email', $email)->first();
                if($email_filter){
                    DB::rollBack();
                    return response()->json(['status' => 'fail', 'msg' => 'Ya existe un usuario con ese correo electrónico', 'data' => null], 400);
                }
            }

            // Crear cliente
            $cliente = new Cliente();
            $cliente->nombre = $request->input('name');
            $cliente->apellidos = $request->input('lastname');
            $cliente->tipo_documento = $request->input('document_type');
            $cliente->numero_documento = $documentNumber;
            $cliente->sexo = $request->input('gender');
            $cliente->telefono = $request->input('phone');
            $cliente->correo = $email;
            if($request->filled('birthDay')){
                $cliente->fecha_nacimiento = $request->input('birthDay');
            }
            $cliente->sincronizado = 1;
            $cliente->save();

            // Crear usuario con rol_id=1 (Cliente)
            $user = new User();
            $user->name = trim($request->input('name') . ' ' . $request->input('lastname'));
            $user->email = $email ?? $documentNumber . '@cliente.com';
            $user->documento_identidad = $documentNumber;
            $user->password = bcrypt($documentNumber); // Password por defecto es el número de documento
            $user->rol_id = 1; // Rol Cliente
            $user->status = 'activo';
            $user->save();

            DB::commit();

            return response()->json([
                'status' => 'ok', 
                'msg' => 'Cliente y usuario creados exitosamente', 
                'data' => [
                    'cliente' => $cliente,
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
