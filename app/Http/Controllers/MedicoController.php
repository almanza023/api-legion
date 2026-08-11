<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class MedicoController extends Controller
{
    /**
     * Procesar la carga del archivo de firma del médico
     */
    private function handleFirmaUpload(Request $request, ?string $currentPath = null): ?string
    {
        $file = $request->file('firma') ?? $request->file('firma_file');

        if ($file && $file->isValid()) {
            if ($currentPath && Storage::disk('public')->exists($currentPath)) {
                Storage::disk('public')->delete($currentPath);
            }
            return $file->store('firmas', 'public');
        }

        if ($request->has('firma_path') && is_string($request->input('firma_path')) && !empty($request->input('firma_path'))) {
            return $request->input('firma_path');
        }

        return $currentPath;
    }

    public function index(Request $request)
    {
        $query = Medico::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('nombre', 'like', "%{$search}%")
                  ->orWhere('cedula', 'like', "%{$search}%")
                  ->orWhere('registro_retheus', 'like', "%{$search}%");
        }

        if ($request->has('activo')) {
            $query->where('activo', filter_var($request->input('activo'), FILTER_VALIDATE_BOOLEAN));
        }

        $medicos = $query->orderBy('nombre', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $medicos
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'registro_retheus' => 'nullable|string|max:100',
            'cedula' => 'required|string|max:50',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'firma' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'firma_file' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'firma_path' => 'nullable',
            'activo' => 'nullable',
        ]);

        // Cargar firma si fue enviada como archivo
        $validated['firma_path'] = $this->handleFirmaUpload($request);

        if ($request->has('activo')) {
            $validated['activo'] = filter_var($request->input('activo'), FILTER_VALIDATE_BOOLEAN);
        }

        $medico = Medico::create($validated);

        // Generar automáticamente el usuario del médico si no existe
        if (!empty($medico->cedula)) {
            $userExistente = User::where('documento_identidad', $medico->cedula)->first();
            if (!$userExistente) {
                User::create([
                    'name' => strtoupper($medico->nombre),
                    'email' => $medico->correo ?? ($medico->cedula . '@medico.com'),
                    'password' => Hash::make($medico->cedula),
                    'documento_identidad' => $medico->cedula,
                    'rol_id' => 5, //ROL DE MEDICO
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'msg' => 'Médico y usuario creados exitosamente',
            'data' => $medico
        ], 201);
    }

    public function show($id)
    {
        $medico = Medico::find($id);

        if (!$medico) {
            return response()->json(['status' => 'fail', 'msg' => 'Médico no encontrado'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $medico
        ]);
    }

    public function update(Request $request, $id)
    {
        $medico = Medico::find($id);

        if (!$medico) {
            return response()->json(['status' => 'fail', 'msg' => 'Médico no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'registro_retheus' => 'nullable|string|max:100',
            'cedula' => 'sometimes|required|string|max:50',
            'correo' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'firma' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'firma_file' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:4096',
            'firma_path' => 'nullable',
            'activo' => 'nullable',
        ]);

        if ($request->hasFile('firma') || $request->hasFile('firma_file') || $request->has('firma_path')) {
            $validated['firma_path'] = $this->handleFirmaUpload($request, $medico->firma_path);
        }

        if ($request->has('activo')) {
            $validated['activo'] = filter_var($request->input('activo'), FILTER_VALIDATE_BOOLEAN);
        }

        $medico->update($validated);

        return response()->json([
            'status' => 'success',
            'msg' => 'Médico actualizado exitosamente',
            'data' => $medico
        ]);
    }

    public function destroy($id)
    {
        $medico = Medico::find($id);

        if (!$medico) {
            return response()->json(['status' => 'fail', 'msg' => 'Médico no encontrado'], 404);
        }

        $medico->delete();

        return response()->json([
            'status' => 'success',
            'msg' => 'Médico eliminado exitosamente'
        ]);
    }
}
