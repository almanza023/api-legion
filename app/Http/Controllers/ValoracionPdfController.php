<?php

namespace App\Http\Controllers;

use App\Models\Valoracion;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use setasign\Fpdf\Fpdf;

class ValoracionPdfController extends Controller
{
    protected $pdf;
    protected $valoracion;
    protected $cliente;
    protected $empresa;

    /**
     * Generar PDF de valoración
     */
    public function generarPdf($id)
    {
        try {
            $this->valoracion = Valoracion::with(['cliente', 'entrenador', 'imcClasificacion'])->find($id);
            
            if (!$this->valoracion) {
                return response()->json(['status' => 'fail', 'msg' => 'Valoración no encontrada'], 404);
            }

            $this->cliente = $this->valoracion->cliente;
            $this->empresa = Empresa::find(1);

            // Crear PDF
            $this->pdf = new \FPDF('P', 'mm', 'Letter');
            $this->pdf->AddPage();
            $this->pdf->SetAutoPageBreak(true, 15);

            // Generar contenido
            $this->generarEncabezado();
            $this->generarDatosCliente();
            $this->generarPerimetros();
            $this->generarFrecuenciaCardiaca();
            $this->generarTestFisico();
            $this->generarComposicionCorporal();
            $this->generarAntecedentes();
            $this->generarObjetivos();
            $this->generarObservaciones();

            // Output
            return response($this->pdf->Output('S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="valoracion_' . $id . '.pdf"');

        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Error al generar PDF', 'data' => $ex->getMessage()], 500);
        }
    }

    /**
     * Encabezado del documento con logo y datos de empresa
     */
    private function generarEncabezado()
    {
        $startY = $this->pdf->GetY();
        
        // Logo de la empresa (lado izquierdo)
        if ($this->empresa && $this->empresa->logo) {
            $logoPath = $this->getLogoPath();
            if ($logoPath && ($this->isRemotePath($logoPath) || file_exists($logoPath))) {
                $this->pdf->Image($logoPath, 10, 10, 35);
            }
        }

        // Datos de la empresa (centro-derecha)
        $this->pdf->SetXY(50, 10);
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->Cell(146, 7, utf8_decode($this->empresa->nombre ?? 'GIMNASIO'), 0, 1, 'C');
        
        $this->pdf->SetX(50);
        $this->pdf->SetFont('Arial', '', 9);
        if ($this->empresa) {
            $this->pdf->Cell(146, 5, 'NIT: ' . ($this->empresa->nit ?? ''), 0, 1, 'C');
            $this->pdf->SetX(50);
            $this->pdf->Cell(146, 5, utf8_decode('Dirección: ' . ($this->empresa->direccion ?? '')), 0, 1, 'C');
            $this->pdf->SetX(50);
            $this->pdf->Cell(146, 5, utf8_decode('Teléfono: ' . ($this->empresa->telefono ?? '') . ' | Email: ' . ($this->empresa->correo ?? '')), 0, 1, 'C');
        }

        // Línea separadora
        $this->pdf->Ln(3);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->Line(10, $this->pdf->GetY(), 206, $this->pdf->GetY());
        $this->pdf->Ln(5);

        // Título del documento
        $this->pdf->SetFont('Arial', 'B', 14);
        $this->pdf->SetFillColor(50, 50, 50);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(0, 8, utf8_decode('VALORACIÓN FÍSICA'), 0, 1, 'C', true);
        $this->pdf->SetTextColor(0, 0, 0);
        
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell(0, 6, 'Fecha Registro ' . ($this->valoracion->fecha_registro ? date('d/m/Y', strtotime($this->valoracion->fecha_registro)) : date('d/m/Y')), 0, 1, 'R');
        $this->pdf->Ln(3);
    }

    /**
     * Obtener la ruta del logo
     */
    private function getLogoPath()
    {
        if (!$this->empresa || empty($this->empresa->logo)) {
            return null;
        }

        $logo = trim((string) $this->empresa->logo);

        if ($logo === '') {
            return null;
        }

        // Si es URL, intentar convertirla a ruta local de /storage para evitar fallos por URL remota.
        if (filter_var($logo, FILTER_VALIDATE_URL)) {
            $pathFromUrl = parse_url($logo, PHP_URL_PATH);

            if (is_string($pathFromUrl) && str_contains($pathFromUrl, '/storage/')) {
                $storagePos = strpos($pathFromUrl, '/storage/');
                $storageRelative = substr($pathFromUrl, $storagePos + 9);
                $localPath = storage_path('app/public/' . ltrim($storageRelative, '/'));

                if (file_exists($localPath)) {
                    return $localPath;
                }
            }

            return $logo;
        }

        // Si empieza con /storage o storage/
        if (str_starts_with($logo, '/storage/')) {
            $localPath = public_path($logo);
            if (file_exists($localPath)) {
                return $localPath;
            }

            return storage_path('app/public/' . ltrim(substr($logo, 9), '/'));
        }

        if (str_starts_with($logo, 'storage/')) {
            $localPath = public_path($logo);
            if (file_exists($localPath)) {
                return $localPath;
            }

            return storage_path('app/public/' . ltrim(substr($logo, 8), '/'));
        }

        // Ruta en storage
        return storage_path('app/public/' . $logo);
    }

    /**
     * Verificar si una ruta es una URL remota
     */
    private function isRemotePath($path)
    {
        return is_string($path) && filter_var($path, FILTER_VALIDATE_URL);
    }

    /**
     * Obtener nombre completo del entrenador
     */
    private function getNombreEntrenador()
    {
        if (!$this->valoracion || !$this->valoracion->entrenador) {
            return 'Sin entrenador asignado';
        }

        $entrenador = $this->valoracion->entrenador;
        $nombreCompleto = trim(($entrenador->nombres ?? '') . ' ' . ($entrenador->apellidos ?? ''));

        if (!empty($nombreCompleto)) {
            return $nombreCompleto;
        }

        return $entrenador->name ?? 'Entrenador';
    }

    /**
     * Datos del cliente
     */
    private function generarDatosCliente()
    {
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(0, 7, utf8_decode('Datos del Cliente'), 1, 1, 'C', true);

        $this->pdf->SetFont('Arial', '', 9);
        
        // Fila 1
        $this->pdf->Cell(30, 6, 'Nombre:', 1, 0, 'L');
        $this->pdf->Cell(75, 6, utf8_decode($this->cliente->nombre ?? ''), 1, 0, 'L');
        $this->pdf->Cell(30, 6, utf8_decode('Teléfono:'), 1, 0, 'L');
        $this->pdf->Cell(61, 6, $this->cliente->telefono ?? '', 1, 1, 'L');

        // Fila 2
        $this->pdf->Cell(30, 6, utf8_decode('N°: Documento'), 1, 0, 'L');
        $this->pdf->Cell(75, 6, $this->cliente->numero_documento ?? '', 1, 0, 'L');
        $this->pdf->Cell(30, 6, utf8_decode('Correo:'), 1, 0, 'L');
        $this->pdf->Cell(61, 6, $this->cliente->correo ?? '', 1, 1, 'L');

        $this->pdf->Ln(5);
    }

    /**
     * Sección de perímetros (medición antropométrica)
     */
    private function generarPerimetros()
    {
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(0, 7, utf8_decode('Perímetros (medición antropométrica)'), 1, 1, 'C', true);

        $this->pdf->SetFont('Arial', '', 9);
        $colWidth = 32.66;

        // Fila 1: Estatura, Peso, Edad, Sexo, Pecho, (vacío)
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell($colWidth, 5, 'Estatura:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Peso:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Edad:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Sexo (M/F):', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Pecho:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'IMC:', 1, 1, 'C');

        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->estatura ?? '-') . ' m', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->peso ?? '-') . ' kg', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->edad ?? '-') . utf8_decode(' años'), 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, $this->valoracion->sexo == 'Hombre' ? 'M' : 'F', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->pecho ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, $this->valoracion->imc ?? '-', 1, 1, 'C');

        // Fila 2: Brazo izquierdo, Brazo derecho, Perímetro Abdominal, Pierna izquierda, Pierna derecha
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell($colWidth, 5, 'Brazo izquierdo:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Brazo derecho:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, utf8_decode('Perímetro Abdominal:'), 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Pierna izquierda:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Pierna derecha:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, '', 1, 1, 'C');

        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->brazo_izquierdo ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->brazo_derecho ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->perimetro_abdominal ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->pierna_izquierda ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->pierna_derecha ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, '', 1, 1, 'C');

        $this->pdf->Ln(5);
    }

    /**
     * Sección de frecuencia cardíaca
     */
    private function generarFrecuenciaCardiaca()
    {
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(0, 7, utf8_decode('Frecuencia Cardíaca'), 1, 1, 'C', true);

        $colWidth = 65.33;

        // Encabezados
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell($colWidth, 5, 'Frecuencia 1:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Frecuencia 2:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Frecuencia 3:', 1, 1, 'C');

        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell($colWidth, 4, 'Antes del ejercicio', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 4, 'Durante el ejercicio', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 4, utf8_decode('Después del ejercicio'), 1, 1, 'C');

        // Valores
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->frecuencia1 ?? '-') . ' bpm', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->frecuencia2 ?? '-') . ' bpm', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->frecuencia3 ?? '-') . ' bpm', 1, 1, 'C');

        $this->pdf->Ln(5);
    }

    /**
     * Sección de test físico
     */
    private function generarTestFisico()
    {
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(0, 7, utf8_decode('Test Físico'), 1, 1, 'C', true);

        $colWidth = 65.33;

        // Encabezados
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell($colWidth, 5, 'Push Up:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Abdominales:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, utf8_decode('Perímetro Abdominal:'), 1, 1, 'C');

        // Valores
        $this->pdf->SetFont('Arial', '', 10);
        $this->pdf->Cell($colWidth, 6, $this->valoracion->push_up ?? '-', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, $this->valoracion->abdominales ?? '-', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->perimetro_abdominal ?? '-') . ' cm', 1, 1, 'C');

        $this->pdf->Ln(5);
    }

    /**
     * Sección de composición corporal
     */
    private function generarComposicionCorporal()
    {
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(0, 7, utf8_decode('Composición corporal (escaneo con báscula especializada)'), 1, 1, 'C', true);

        $colWidth = 39.2;

        // Encabezados
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell($colWidth, 5, 'IMC:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, '% Grasa Corporal:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, utf8_decode('% Músculo:'), 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, 'Grasa visceral:', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 5, utf8_decode('% Masa Músculo-Esq:'), 1, 1, 'C');

        // Valores
        $this->pdf->SetFont('Arial', '', 10);
        $imcClasif = $this->valoracion->imcClasificacion ? $this->valoracion->imcClasificacion->nombre : '';
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->imc ?? '-') . ' ' . $imcClasif, 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->porcentaje_grasa ?? '-') . ' %', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->porcentaje_musculo ?? '-') . ' %', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, $this->valoracion->grasa_visceral ?? '-', 1, 0, 'C');
        $this->pdf->Cell($colWidth, 6, ($this->valoracion->masa_musculo_esqueletica ?? '-') . ' %', 1, 1, 'C');

        $this->pdf->Ln(5);
    }

    /**
     * Sección de antecedentes
     */
    private function generarAntecedentes()
    {
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(0, 7, 'Antecedentes', 1, 1, 'C', true);

        // Primera fila de antecedentes
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(32, 5, utf8_decode('Hipertensión:'), 1, 0, 'C');
        $this->pdf->Cell(32, 5, 'Fuma:', 1, 0, 'C');
        $this->pdf->Cell(32, 5, utf8_decode('Lesión:'), 1, 0, 'C');
        $this->pdf->Cell(50, 5, 'Tiempo sin entrenar:', 1, 0, 'C');
        $this->pdf->Cell(50, 5, 'Toma de foto:', 1, 1, 'C');

        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->Cell(32, 6, $this->valoracion->hipertension ? utf8_decode('Sí') : 'No', 1, 0, 'C');
        $this->pdf->Cell(32, 6, $this->valoracion->fuma ? utf8_decode('Sí') : 'No', 1, 0, 'C');
        $this->pdf->Cell(32, 6, $this->valoracion->lesion ? utf8_decode('Sí') : 'No', 1, 0, 'C');
        $this->pdf->Cell(50, 6, $this->valoracion->tiempo_sin_entrenar ?? '-', 1, 0, 'C');
        $this->pdf->Cell(50, 6, $this->valoracion->toma_foto ? utf8_decode('Sí') : 'No', 1, 1, 'C');

        // Descripción de la lesión
        if ($this->valoracion->lesion && $this->valoracion->descripcion_lesion) {
            $this->pdf->SetFont('Arial', 'B', 8);
            $this->pdf->Cell(50, 5, utf8_decode('Descripción de la lesión:'), 1, 0, 'L');
            $this->pdf->SetFont('Arial', '', 9);
            $this->pdf->Cell(146, 5, utf8_decode($this->valoracion->descripcion_lesion ?? ''), 1, 1, 'L');
        }

        $this->pdf->Ln(5);
    }

    /**
     * Sección de objetivos
     */
    private function generarObjetivos()
    {
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(0, 7, utf8_decode('Objetivos'), 1, 1, 'C', true);

        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->MultiCell(0, 6, utf8_decode($this->valoracion->objetivo ?? 'Sin objetivos definidos'), 1, 'L');

        $this->pdf->Ln(5);
    }

    /**
     * Sección de observaciones
     */
    private function generarObservaciones()
    {
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(0, 7, 'Observaciones', 1, 1, 'C', true);

        $this->pdf->SetFont('Arial', '', 9);
        $this->pdf->MultiCell(0, 6, utf8_decode($this->valoracion->observaciones ?? 'Sin observaciones'), 1, 'L');

        $this->pdf->Ln(10);

        // Firma
        $this->pdf->Cell(98, 6, '', 0, 0, 'C');
        $this->pdf->Cell(98, 6, '________________________________', 0, 1, 'C');
      
        $this->pdf->SetFont('Arial', 'B', 9);
     
        $this->pdf->Cell(98, 5, '', 0, 0, 'C');
   
        $this->pdf->Cell(98, 5, utf8_decode($this->getNombreEntrenador()), 0, 1, 'C');
    }
}
