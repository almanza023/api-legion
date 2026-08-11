<?php

namespace App\Http\Controllers;

use App\Models\ObjetivoRemision;
use Illuminate\Http\Request;

class ObjetivoRemisionController extends Controller
{
    public function index(Request $request)
    {
        $query = ObjetivoRemision::query();

        if ($request->has('activo')) {
            $query->where('activo', filter_var($request->input('activo'), FILTER_VALIDATE_BOOLEAN));
        }

        $objetivos = $query->orderBy('id', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $objetivos
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $objetivo = ObjetivoRemision::create($validated);

        return response()->json([
            'status' => 'success',
            'msg' => 'Objetivo de remisión creado exitosamente',
            'data' => $objetivo
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $objetivo = ObjetivoRemision::find($id);

        if (!$objetivo) {
            return response()->json(['status' => 'fail', 'msg' => 'Objetivo no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $objetivo->update($validated);

        return response()->json([
            'status' => 'success',
            'msg' => 'Objetivo actualizado exitosamente',
            'data' => $objetivo
        ]);
    }

    public function destroy($id)
    {
        $objetivo = ObjetivoRemision::find($id);

        if (!$objetivo) {
            return response()->json(['status' => 'fail', 'msg' => 'Objetivo no encontrado'], 404);
        }

        $objetivo->delete();

        return response()->json([
            'status' => 'success',
            'msg' => 'Objetivo eliminado exitosamente'
        ]);
    }
}
