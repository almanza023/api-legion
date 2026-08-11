<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticoCie10;
use Illuminate\Http\Request;

class DiagnosticoCie10Controller extends Controller
{
    public function index(Request $request)
    {
        $query = DiagnosticoCie10::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('codigo', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
        }

        if ($request->has('activo')) {
            $query->where('activo', filter_var($request->input('activo'), FILTER_VALIDATE_BOOLEAN));
        }

        $cie10List = $query->orderBy('codigo', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $cie10List
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|max:50|unique:diagnosticos_cie10,codigo',
            'descripcion' => 'required|string',
            'activo' => 'nullable|boolean',
        ]);

        $cie10 = DiagnosticoCie10::create($validated);

        return response()->json([
            'status' => 'success',
            'msg' => 'Código CIE-10 creado exitosamente',
            'data' => $cie10
        ], 201);
    }

    public function show($id)
    {
        $cie10 = DiagnosticoCie10::find($id);

        if (!$cie10) {
            return response()->json(['status' => 'fail', 'msg' => 'Código CIE-10 no encontrado'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $cie10
        ]);
    }

    public function update(Request $request, $id)
    {
        $cie10 = DiagnosticoCie10::find($id);

        if (!$cie10) {
            return response()->json(['status' => 'fail', 'msg' => 'Código CIE-10 no encontrado'], 404);
        }

        $validated = $request->validate([
            'codigo' => 'sometimes|required|string|max:50|unique:diagnosticos_cie10,codigo,' . $id,
            'descripcion' => 'sometimes|required|string',
            'activo' => 'nullable|boolean',
        ]);

        $cie10->update($validated);

        return response()->json([
            'status' => 'success',
            'msg' => 'Código CIE-10 actualizado exitosamente',
            'data' => $cie10
        ]);
    }

    public function destroy($id)
    {
        $cie10 = DiagnosticoCie10::find($id);

        if (!$cie10) {
            return response()->json(['status' => 'fail', 'msg' => 'Código CIE-10 no encontrado'], 404);
        }

        $cie10->delete();

        return response()->json([
            'status' => 'success',
            'msg' => 'Código CIE-10 eliminado exitosamente'
        ]);
    }
}
