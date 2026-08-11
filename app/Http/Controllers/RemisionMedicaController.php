<?php

namespace App\Http\Controllers;

use App\Models\RemisionMedica;
use App\Models\Cliente;
use App\Models\Medico;
use Illuminate\Http\Request;

class RemisionMedicaController extends Controller
{
    /**
     * Listar todas las remisiones médicas
     */
    public function index(Request $request)
    {
        $query = RemisionMedica::with(['cliente', 'medico', 'cie10']);

        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->input('cliente_id'));
        }

        if ($request->has('medico_id')) {
            $query->where('medico_id', $request->input('medico_id'));
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $remisiones = $query->orderBy('fecha_registro', 'desc')
                            ->orderBy('id', 'desc')
                            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $remisiones
        ]);
    }

    /**
     * Consultar remisiones registradas por un médico específico
     */
    public function remisionesPorMedico($medicoId, Request $request)
    {
        $medico = Medico::find($medicoId);

        if (!$medico) {
            return response()->json(['status' => 'fail', 'msg' => 'Médico no encontrado'], 404);
        }

        $query = RemisionMedica::with(['cliente', 'medico', 'cie10'])
            ->where('medico_id', $medicoId);

        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $remisiones = $query->orderBy('fecha_registro', 'desc')
                            ->orderBy('id', 'desc')
                            ->get();

        return response()->json([
            'status' => 'success',
            'medico' => [
                'id' => $medico->id,
                'nombre' => $medico->nombre,
                'cedula' => $medico->cedula,
                'especialidad' => $medico->especialidad,
                'registro_retheus' => $medico->registro_retheus,
            ],
            'total_remisiones' => $remisiones->count(),
            'data' => $remisiones
        ]);
    }

    /**
     * Consultar histórico de remisiones de un cliente específico
     */
    public function historicoCliente($clienteId)
    {
        $cliente = Cliente::find($clienteId);

        if (!$cliente) {
            return response()->json(['status' => 'fail', 'msg' => 'Cliente no encontrado'], 404);
        }

        $remisiones = RemisionMedica::with(['medico', 'cie10'])
            ->where('cliente_id', $clienteId)
            ->orderBy('fecha_registro', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'cliente' => [
                'id' => $cliente->id,
                'nombre_completo' => trim(($cliente->nombre ?? $cliente->nombres) . ' ' . ($cliente->apellidos ?? '')),
                'numero_documento' => $cliente->numero_documento,
                'fecha_nacimiento' => $cliente->fecha_nacimiento,
                'sexo' => $cliente->sexo,
            ],
            'total_remisiones' => $remisiones->count(),
            'data' => $remisiones
        ]);
    }

    /**
     * Crear nueva remisión médica
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'medico_id' => 'nullable|exists:medicos,id',
            'cie10_id' => 'nullable|exists:diagnosticos_cie10,id',
            'diagnostico' => 'nullable|string',
            'codigo_cie10' => 'nullable|string|max:50',
            'objetivos' => 'nullable|array',
            'objetivo_otro' => 'nullable|string|max:255',
            'restricciones' => 'nullable|string',
            'vigencia' => 'nullable|string|in:6_meses,12_meses,otra',
            'vigencia_otra_descripcion' => 'nullable|string|max:255',
            'estado' => 'nullable|string|in:activa,vencida,anulada',
            'fecha_registro' => 'nullable|date',
        ]);

        // Verificar que el cliente tenga una membresía activa vigente
        $cliente = Cliente::find($validated['cliente_id']);
        if (!$cliente || !$cliente->tieneMembresiaActiva()) {
            return response()->json([
                'status' => 'fail',
                'msg' => 'No es posible registrar la remisión médica. El cliente no cuenta con una membresía activa.'
            ], 422);
        }

        if (empty($validated['fecha_registro'])) {
            $validated['fecha_registro'] = now()->format('Y-m-d');
        }

        $remision = RemisionMedica::create($validated);
        $remision->load(['cliente', 'medico', 'cie10']);

        return response()->json([
            'status' => 'success',
            'msg' => 'Remisión médica registrada exitosamente',
            'data' => $remision
        ], 201);
    }

    /**
     * Ver detalle de remisión
     */
    public function show($id)
    {
        $remision = RemisionMedica::with(['cliente', 'medico', 'cie10'])->find($id);

        if (!$remision) {
            return response()->json(['status' => 'fail', 'msg' => 'Remisión médica no encontrada'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $remision
        ]);
    }

    /**
     * Actualizar remisión médica
     */
    public function update(Request $request, $id)
    {
        $remision = RemisionMedica::find($id);

        if (!$remision) {
            return response()->json(['status' => 'fail', 'msg' => 'Remisión médica no encontrada'], 404);
        }

        $validated = $request->validate([
            'cliente_id' => 'sometimes|required|exists:clientes,id',
            'medico_id' => 'nullable|exists:medicos,id',
            'cie10_id' => 'nullable|exists:diagnosticos_cie10,id',
            'diagnostico' => 'nullable|string',
            'codigo_cie10' => 'nullable|string|max:50',
            'objetivos' => 'nullable|array',
            'objetivo_otro' => 'nullable|string|max:255',
            'restricciones' => 'nullable|string',
            'vigencia' => 'nullable|string|in:6_meses,12_meses,otra',
            'vigencia_otra_descripcion' => 'nullable|string|max:255',
            'estado' => 'nullable|string|in:activa,vencida,anulada',
            'fecha_registro' => 'nullable|date',
        ]);

        $remision->update($validated);
        $remision->load(['cliente', 'medico', 'cie10']);

        return response()->json([
            'status' => 'success',
            'msg' => 'Remisión médica actualizada exitosamente',
            'data' => $remision
        ]);
    }

    /**
     * Eliminar remisión médica
     */
    public function destroy($id)
    {
        $remision = RemisionMedica::find($id);

        if (!$remision) {
            return response()->json(['status' => 'fail', 'msg' => 'Remisión médica no encontrada'], 404);
        }

        $remision->delete();

        return response()->json([
            'status' => 'success',
            'msg' => 'Remisión médica eliminada exitosamente'
        ]);
    }
}
