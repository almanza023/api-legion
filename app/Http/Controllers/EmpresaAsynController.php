<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmpresaAsynController extends Controller
{
    private function handleLogoUpload(Request $request, ?string $currentPath = null): ?string
    {
        if (!$request->hasFile('logo')) {
            return $currentPath;
        }

        $file = $request->file('logo');

        if (!$file->isValid()) {
            throw new \RuntimeException('El archivo de logo es inválido');
        }

        return $file->store('logos', 'public');
    }

    public function index()
    {
        try {
            $empresas = Empresa::all();
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $empresas]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $empresaFilter = Empresa::where('nit', $request->nit)->first();
            if ($empresaFilter) {
                return response()->json(['status' => 'fail', 'msg' => 'La empresa ya existe con ese NIT', 'data' => null], 400);
            }

            $logoPath = $this->handleLogoUpload($request);

            $empresa = Empresa::create([
                'nombre' => $request->nombre,
                'nit' => $request->nit,
                'direccion' => $request->direccion,
                'correo' => $request->correo,
                'telefono' => $request->telefono,
                'representante' => $request->representante,
                'logo' => $logoPath,
            ]);

            return response()->json(['status' => 'ok', 'msg' => 'Empresa creada exitosamente', 'data' => $empresa]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $empresa = Empresa::find($id);
            if (!$empresa) {
                return response()->json(['status' => 'fail', 'msg' => 'Empresa no encontrada', 'data' => null], 404);
            }
            return response()->json(['status' => 'ok', 'msg' => 'Operación exitosa', 'data' => $empresa]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $empresa = Empresa::find($id);
            if (!$empresa) {
                return response()->json(['status' => 'fail', 'msg' => 'Empresa no encontrada', 'data' => null], 404);
            }

            if ($request->has('nit') && $request->nit !== $empresa->nit) {
                $nitExists = Empresa::where('nit', $request->nit)->first();
                if ($nitExists) {
                    return response()->json(['status' => 'fail', 'msg' => 'Ya existe una empresa con ese NIT', 'data' => null], 400);
                }
            }

            if ($request->has('nombre')) $empresa->nombre = $request->nombre;
            if ($request->has('nit')) $empresa->nit = $request->nit;
            if ($request->has('direccion')) $empresa->direccion = $request->direccion;
            if ($request->has('correo')) $empresa->correo = $request->correo;
            if ($request->has('telefono')) $empresa->telefono = $request->telefono;
            if ($request->has('representante')) $empresa->representante = $request->representante;

            $empresa->logo = $this->handleLogoUpload($request, $empresa->logo);

            $empresa->save();

            return response()->json(['status' => 'ok', 'msg' => 'Empresa actualizada exitosamente', 'data' => $empresa]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $empresa = Empresa::find($id);
            if (!$empresa) {
                return response()->json(['status' => 'fail', 'msg' => 'Empresa no encontrada', 'data' => null], 404);
            }
            $empresa->delete();
            return response()->json(['status' => 'ok', 'msg' => 'Empresa eliminada exitosamente', 'data' => null]);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Ha ocurrido un error al procesar la solicitud', 'data' => $ex->getMessage()], 500);
        }
    }
}
