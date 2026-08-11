<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Reporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use FPDF;

class ReporteEjecutarController extends Controller
{
    public function ejecutar(Request $request, $id)
    {
        try {
            $reporte = Reporte::find($id);

            if (!$reporte) {
                return response()->json(['status' => 'fail', 'msg' => 'Reporte no encontrado'], 404);
            }

            // Obtener parámetros adicionales del request
            $parametros = $request->all();
            $sql=$reporte->consulta_sql;
            $sql = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $sql);
            $sql = preg_replace("/\r|\n/", "", $sql);

            // Reemplazar parámetros @param1 y @param2 por fechas del request si existen
            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');

            if ($fechaInicio !== null && $fechaFin !== null) {
                $sql = str_replace('@param1', $fechaInicio, $sql);
                $sql = str_replace('@param2', $fechaFin, $sql);
            }

            // Desactivar ONLY_FULL_GROUP_BY temporalmente
            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

            // Ejecutar la consulta SQL del reporte
            $resultados = DB::select($sql);

            return response()->json([
                'status' => 'ok',   
                'reporte' => $reporte,
                'resultados' => $resultados,
                'parametros' => $parametros
            ]);

        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Error al ejecutar el reporte: ' . $ex->getMessage(), 'sql' => $sql], 500);
        }
    }

    public function exportarPDF(Request $request, $id)
    {
        try {
            $reporte = Reporte::find($id);

            if (!$reporte) {
                return response()->json(['status' => 'fail', 'msg' => 'Reporte no encontrado'], 404);
            }

            $sql=$reporte->consulta_sql;
            $sql = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $sql);
            $sql = preg_replace("/\r|\n/", "", $sql);

            // Reemplazar parámetros @param1 y @param2 por fechas del request si existen
            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');

            if ($fechaInicio !== null && $fechaFin !== null) {
                $sql = str_replace('@param1', "'".$fechaInicio."'", $sql);
                $sql = str_replace('@param2', "'".$fechaFin."'", $sql);
            }

            // Desactivar ONLY_FULL_GROUP_BY temporalmente
            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

            // Ejecutar la consulta SQL del reporte
            $resultados = DB::select($sql);

            // Crear instancia de FPDF
            $pdf = new PDF_Reporte();
            $pdf->AliasNbPages();
            $pdf->SetTitle($reporte->nombre_reporte);
            $pdf->AddPage();

            // Título del reporte centrado
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, utf8_decode($reporte->nombre_reporte), 0, 1, 'C');
            $pdf->Ln(5);

            // // Información adicional
            // if ($reporte->descripcion) {
            //     $pdf->SetFont('Arial', 'I', 10);
            //     $pdf->MultiCell(0, 5, utf8_decode($reporte->descripcion));
            //     $pdf->Ln(5);
            // }

            // Si hay resultados, crear tabla
            if (count($resultados) > 0) {
                // Obtener columnas del primer resultado
                $columnas = array_keys((array)$resultados[0]);
                
                // Encabezados de tabla
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->SetFillColor(52, 73, 94);
                $pdf->SetTextColor(255, 255, 255);
                
                // Calcular ancho de columnas dinámicamente
                $anchoColumna = 190 / count($columnas);
                
                foreach ($columnas as $columna) {
                    $pdf->Cell($anchoColumna, 8, utf8_decode(ucfirst(str_replace('_', ' ', $columna))), 1, 0, 'C', true);
                }
                $pdf->Ln();

                // Datos de la tabla
                $pdf->SetFont('Arial', '', 9);
                $pdf->SetTextColor(0, 0, 0);
                $fill = false;
                
                foreach ($resultados as $fila) {
                    $pdf->SetFillColor(236, 240, 241);
                    foreach ($columnas as $columna) {
                        $valor = isset($fila->$columna) ? $fila->$columna : '';
                        $pdf->Cell($anchoColumna, 7, utf8_decode($valor), 1, 0, 'L', $fill);
                    }
                    $pdf->Ln();
                    $fill = !$fill;
                }
            } else {
                $pdf->SetFont('Arial', 'I', 12);
                $pdf->Cell(0, 10, 'No se encontraron resultados', 0, 1, 'C');
            }

            // Generar PDF
            $nombreArchivo = 'reporte_' . $id . '_' . date('YmdHis') . '.pdf';
            
            return response($pdf->Output('S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"');

        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Error al exportar el reporte: ' . $ex->getMessage(), 'sql' => $sql], 500);
        }
    }
}

// Clase personalizada de FPDF para encabezado y pie de página
class PDF_Reporte extends FPDF
{
    // Encabezado
    function Header()
    {
        // Obtener datos de la empresa
        $empresa = Empresa::find(1);
        
        // Logo o nombre de la empresa (puedes personalizarlo)
        $x = $this->w - 30; // Posición X a la derecha
        if ($empresa && $empresa->logo) {
            $logoPath = storage_path('app/public/logos/' . basename($empresa->logo));
            if (file_exists($logoPath)) {
                $this->Image($logoPath, $x, 10, 20);
            }
        }
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(52, 73, 94);
        $this->Cell(0, 10, utf8_decode($empresa->nombre), 0, 1, 'L');
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, utf8_decode($empresa->direccion . ' - ' . $empresa->nit . ' - ' . $empresa->telefono), 0, 1, 'L');
        $this->Ln(5);
        
        // Línea divisoria
        $this->SetDrawColor(52, 73, 94);
        $this->SetLineWidth(0.5);
        $this->Line(10, 20, 200, 20);
        
        // Fecha y hora de generación
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Generado: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
        $this->Ln(5);
    }

    // Pie de página
    function Footer()
    {
        // Posición a 1.5 cm del final
        $this->SetY(-15);
        
        // Línea divisoria
        $this->SetDrawColor(52, 73, 94);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        
        // Número de página
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . ' de {nb}', 0, 0, 'C');
    }

    

   
}
