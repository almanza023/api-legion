<?php

namespace App\Http\Controllers;

use App\Models\ValoracionV2;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use setasign\Fpdf\Fpdf;

class ValoracionV2PdfController extends Controller
{
    protected $pdf;
    protected $valoracion;
    protected $cliente;
    protected $empresa;

    /**
     * Generar PDF de valoración v2 (Formulario SST-F-15 + Cuestionario PAR-Q)
     */
    public function generarPdf($id)
    {
        try {
            $this->valoracion = ValoracionV2::with(['cliente', 'entrenador', 'imcClasificacion'])->find($id);

            if (!$this->valoracion) {
                return response()->json(['status' => 'fail', 'msg' => 'Valoración no encontrada'], 404);
            }

            $this->cliente = $this->valoracion->cliente;
            $this->empresa = Empresa::find(1);

            // Instanciar FPDF (Carta)
            $this->pdf = new \FPDF('P', 'mm', 'Letter');
            $this->pdf->SetMargins(10, 10, 10);
            $this->pdf->SetAutoPageBreak(false);

            // PAGINA 1: FORMULARIO DE VALORACIÓN
            $this->generarPagina1();

            // PAGINA 2: CUESTIONARIO PAR-Q
            $this->generarPagina2();

            return response($this->pdf->Output('S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="valoracion_v2_' . $id . '.pdf"');

        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json(['status' => 'fail', 'msg' => 'Error al generar PDF', 'data' => $ex->getMessage()], 500);
        }
    }

    /* =========================================================================
       PÁGINA 1: FORMULARIO DE VALORACIÓN
       ========================================================================= */

    private function generarPagina1()
    {
        $this->pdf->AddPage();

        // 1. Encabezado principal
        $this->generarEncabezadoPagina1();

        // 2. Datos del cliente y empresa
        $this->generarDatosClientePagina1();

        // 3. Perímetros (Medición Antropométrica)
        $this->generarPerimetrosPagina1();

        // 4. Frecuencia Cardíaca
        $this->generarFrecuenciaCardiacaPagina1();

        // 5. Test Físico
        $this->generarTestFisicoPagina1();

        // 6. Composición Corporal
        $this->generarComposicionCorporalPagina1();

        // 7. Antecedentes
        $this->generarAntecedentesPagina1();

        // 8. Observaciones Generales
        $this->generarObservacionesPagina1();

        // 9. Plan de Entrenamiento (si aplica)
        $this->generarPlanEntrenamientoPagina1();

        // 10. Firmas
        $this->generarFirmasPagina1();
    }

    private function generarEncabezadoPagina1()
    {
        $startY = $this->pdf->GetY();
        $this->pdf->Rect(10, $startY, 196, 20);

        // Subdivisión de 3 columnas: Logo (45mm) | Título (105mm) | Código (46mm)
        $this->pdf->Line(55, $startY, 55, $startY + 20);
        $this->pdf->Line(160, $startY, 160, $startY + 20);

        // Logo
        $logoPath = $this->getLogoPath();
        if ($logoPath && (filter_var($logoPath, FILTER_VALIDATE_URL) || file_exists($logoPath))) {
            $this->pdf->Image($logoPath, 12, $startY + 2, 38, 16);
        } else {
            $this->pdf->SetXY(10, $startY + 6);
            $this->pdf->SetFont('Arial', 'B', 14);
            $this->pdf->Cell(45, 8, utf8_decode('LEGION'), 0, 0, 'C');
        }

        // Título central
        $this->pdf->SetXY(55, $startY + 2);
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->Cell(105, 5, utf8_decode('FORMULARIO DE VALORACIÓN'), 0, 1, 'C');

        $this->pdf->SetXY(55, $startY + 7);
        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->MultiCell(105, 3.5, utf8_decode("Registro de medidas antropométricas, composición corporal y concepto de restricciones."), 0, 'C');

        // Código y versión derecha
        $this->pdf->SetXY(160, $startY + 4);
        $this->pdf->SetFont('Arial', 'B', 7);
        $this->pdf->Cell(46, 4, utf8_decode('Código: SST-F-15 Versión: 1'), 0, 1, 'C');
        $this->pdf->SetXY(160, $startY + 10);
        $this->pdf->SetFont('Arial', '', 7);
        $fechaForm = $this->valoracion->fecha_registro ? date('d-m-Y', strtotime($this->valoracion->fecha_registro)) : date('d-m-Y');
        $this->pdf->Cell(46, 4, utf8_decode('Fecha: ' . $fechaForm), 0, 1, 'C');

        $this->pdf->SetY($startY + 22);
    }

    private function generarDatosClientePagina1()
    {
        $this->pdf->SetFont('Arial', '', 8);

        // Fila 1: Fecha valoración | Nombre | CC
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(25, 6, utf8_decode('Fecha valoración:'), 1, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $fechaVal = $this->valoracion->fecha_registro ? date('d/m/Y', strtotime($this->valoracion->fecha_registro)) : date('d/m/Y');
        $this->pdf->Cell(25, 6, $fechaVal, 1, 0, 'C');

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(20, 6, 'Nombre:', 1, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(80, 6, utf8_decode($this->getNombreCliente()), 1, 0, 'L');

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 6, 'CC:', 1, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(31, 6, $this->cliente->numero_documento ?? '', 1, 1, 'L');

        // Fila 2: Convenio (Si/No) | Nombre de la empresa
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(50, 6, 'Convenio:', 1, 0, 'L');

        $siConvenio = $this->valoracion->convenio ? '[ X ] Si   [   ] No' : '[   ] Si   [ X ] No';
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY(30, $this->pdf->GetY());
        $this->pdf->Cell(30, 6, $siConvenio, 0, 0, 'L');
        $this->pdf->SetXY(60, $this->pdf->GetY());

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(45, 6, 'Nombre de la empresa:', 1, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $empresaNombre = $this->valoracion->nombre_empresa ?? ($this->empresa->nombre ?? '');
        $this->pdf->Cell(101, 6, utf8_decode($empresaNombre), 1, 1, 'L');

        $this->pdf->Ln(1);
    }

    private function generarPerimetrosPagina1()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, utf8_decode('Perímetros (medición antropometrica)'), 1, 1, 'C', true);

        $w = 196 / 5; // 39.2 mm por columna

        // Fila 1 headers
        $this->pdf->SetFont('Arial', 'B', 7.5);
        $this->pdf->Cell($w, 5, 'Estatura:', 1, 0, 'C');
        $this->pdf->Cell($w, 5, 'Peso:', 1, 0, 'C');
        $this->pdf->Cell($w, 5, 'Edad:', 1, 0, 'C');
        $this->pdf->Cell($w, 5, 'Sexo. (M/F):', 1, 0, 'C');
        $this->pdf->Cell($w, 5, 'Pecho:', 1, 1, 'C');

        // Fila 1 valores
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell($w, 5, ($this->valoracion->estatura ?? '-') . ' m', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->peso ?? '-') . ' kg', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->edad ?? '-') . utf8_decode(' años'), 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->sexo == 'Mujer' || $this->valoracion->sexo == 'F') ? 'F' : 'M', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->pecho ?? '-') . ' cm', 1, 1, 'C');

        // Fila 2 headers
        $this->pdf->SetFont('Arial', 'B', 7.5);
        $this->pdf->Cell($w, 5, 'Brazo izquierdo:', 1, 0, 'C');
        $this->pdf->Cell($w, 5, 'Brazo derecho:', 1, 0, 'C');
        $this->pdf->Cell($w, 5, utf8_decode('Perimetro Abdominal:'), 1, 0, 'C');
        $this->pdf->Cell($w, 5, 'Pierna izquierda:', 1, 0, 'C');
        $this->pdf->Cell($w, 5, 'Pierna derecha:', 1, 1, 'C');

        // Fila 2 valores
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell($w, 5, ($this->valoracion->brazo_izquierdo ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->brazo_derecho ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->perimetro_abdominal ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->pierna_izquierda ?? '-') . ' cm', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->pierna_derecha ?? '-') . ' cm', 1, 1, 'C');

        $this->pdf->Ln(1);
    }

    private function generarFrecuenciaCardiacaPagina1()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, utf8_decode('Frecuencia Cardiaca'), 1, 1, 'C', true);

        $w = 196 / 3;

        // Headers
        $this->pdf->SetFont('Arial', 'B', 7.5);
        $this->pdf->Cell($w, 4, 'Frecuencia 1:', 1, 0, 'C');
        $this->pdf->Cell($w, 4, 'Frecuencia 2:', 1, 0, 'C');
        $this->pdf->Cell($w, 4, 'Frecuencia 3:', 1, 1, 'C');

        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->Cell($w, 3.5, 'Antes del ejercicio', 1, 0, 'C');
        $this->pdf->Cell($w, 3.5, 'Durante el ejercicio', 1, 0, 'C');
        $this->pdf->Cell($w, 3.5, utf8_decode('Después del ejercicio'), 1, 1, 'C');

        // Values
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell($w, 5, ($this->valoracion->frecuencia1 ?? '-') . ' bpm', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->frecuencia2 ?? '-') . ' bpm', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->frecuencia3 ?? '-') . ' bpm', 1, 1, 'C');

        $this->pdf->Ln(1);
    }

    private function generarTestFisicoPagina1()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, utf8_decode('Test Físico'), 1, 1, 'C', true);

        $w = 196 / 3;

        // Headers
        $this->pdf->SetFont('Arial', 'B', 7.5);
        $this->pdf->Cell($w, 5, 'Push Up:', 1, 0, 'C');
        $this->pdf->Cell($w, 5, 'Abdominales:', 1, 0, 'C');
        $this->pdf->Cell($w, 5, utf8_decode('Perimetro Abdominal:'), 1, 1, 'C');

        // Values
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell($w, 5, $this->valoracion->push_up ?? '-', 1, 0, 'C');
        $this->pdf->Cell($w, 5, $this->valoracion->abdominales ?? '-', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->perimetro_abdominal ?? '-') . ' cm', 1, 1, 'C');

        $this->pdf->Ln(1);
    }

    private function generarComposicionCorporalPagina1()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, utf8_decode('Composición corporal (escaneo con báscula especializada)'), 1, 1, 'C', true);

        $w = 196 / 5;

        // Headers
        $this->pdf->SetFont('Arial', 'B', 7.5);
        $this->pdf->Cell($w, 5, 'IMC', 1, 0, 'C');
        $this->pdf->Cell($w, 5, '% Grasa Corporal', 1, 0, 'C');
        $this->pdf->Cell($w, 5, utf8_decode('% Musculo'), 1, 0, 'C');
        $this->pdf->Cell($w, 5, 'Grasa visceral', 1, 0, 'C');
        $this->pdf->Cell($w, 5, utf8_decode('% de masa Musculo - Esqueletica'), 1, 1, 'C');

        // Values
        $this->pdf->SetFont('Arial', '', 8);
        $imcClasif = $this->valoracion->imcClasificacion ? $this->valoracion->imcClasificacion->nombre : '';
        $this->pdf->Cell($w, 5, ($this->valoracion->imc ?? '-') . ' ' . $imcClasif, 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->porcentaje_grasa ?? '-') . ' %', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->porcentaje_musculo ?? '-') . ' %', 1, 0, 'C');
        $this->pdf->Cell($w, 5, $this->valoracion->grasa_visceral ?? '-', 1, 0, 'C');
        $this->pdf->Cell($w, 5, ($this->valoracion->masa_musculo_esqueletica ?? '-') . ' %', 1, 1, 'C');

        $this->pdf->Ln(1);
    }

    private function generarAntecedentesPagina1()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, 'Antecedentes', 1, 1, 'C', true);

        // Fila 1 de Antecedentes (5 columnas)
        $this->pdf->SetFont('Arial', 'B', 7);
        $this->pdf->Cell(39, 4, utf8_decode('Hipertension:'), 1, 0, 'L');
        $this->pdf->Cell(39, 4, 'Fuma:', 1, 0, 'L');
        $this->pdf->Cell(39, 4, utf8_decode('Lesión:'), 1, 0, 'L');
        $this->pdf->Cell(39, 4, utf8_decode('Tensión Arterial'), 1, 0, 'C');
        $this->pdf->Cell(40, 4, utf8_decode('Antecedentes Cardiológicos'), 1, 1, 'C');

        // Valores Fila 1
        $this->pdf->SetFont('Arial', '', 7.5);
        $this->pdf->Cell(39, 5, $this->valoracion->hipertension ? 'Si:  X   No: __' : 'Si: __  No:  X ', 1, 0, 'C');
        $this->pdf->Cell(39, 5, $this->valoracion->fuma ? 'Si:  X   No: __' : 'Si: __  No:  X ', 1, 0, 'C');
        $this->pdf->Cell(39, 5, $this->valoracion->lesion ? 'Si:  X   No: __' : 'Si: __  No:  X ', 1, 0, 'C');
        $this->pdf->Cell(39, 5, utf8_decode($this->valoracion->tension_arterial ?? '-'), 1, 0, 'C');
        $this->pdf->Cell(40, 5, $this->valoracion->antecedentes_cardio_personales ? 'Si:  X   No: __' : 'Si: __  No:  X ', 1, 1, 'C');

        // Fila 2 de Antecedentes (5 columnas)
        $this->pdf->SetFont('Arial', 'B', 7);
        $this->pdf->Cell(39, 4, utf8_decode('Antecedentes Cardiológicos'), 1, 0, 'C');
        $this->pdf->Cell(39, 4, 'Colesterol alto:', 1, 0, 'L');
        $this->pdf->Cell(39, 4, 'Trigliceridos altos:', 1, 0, 'L');
        $this->pdf->Cell(39, 4, 'Toma medicamento?:', 1, 0, 'L');
        $this->pdf->Cell(40, 4, 'Toma de foto:', 1, 1, 'L');

        // Valores Fila 2
        $this->pdf->SetFont('Arial', '', 7.5);
        $this->pdf->Cell(39, 5, $this->valoracion->antecedentes_cardio_familiares ? 'Si:  X   No: __' : 'Si: __  No:  X ', 1, 0, 'C');
        $this->pdf->Cell(39, 5, $this->valoracion->colesterol_alto ? 'Si:  X   No: __' : 'Si: __  No:  X ', 1, 0, 'C');
        $this->pdf->Cell(39, 5, $this->valoracion->trigliceridos_altos ? 'Si:  X   No: __' : 'Si: __  No:  X ', 1, 0, 'C');
        $this->pdf->Cell(39, 5, $this->valoracion->toma_medicamento ? 'Si:  X   No: __' : 'Si: __  No:  X ', 1, 0, 'C');
        $this->pdf->Cell(40, 5, $this->valoracion->toma_foto ? 'Si:  X   No: __' : 'Si: __  No:  X ', 1, 1, 'C');

        // Descripción de lesión
        $this->pdf->SetFont('Arial', 'B', 7.5);
        $this->pdf->Cell(50, 6, utf8_decode('Descripción de la lesión (si aplica):'), 1, 0, 'L');
        $this->pdf->SetFont('Arial', '', 7.5);
        $this->pdf->Cell(146, 6, utf8_decode($this->valoracion->descripcion_lesion ?? ''), 1, 1, 'L');

        $this->pdf->Ln(1);
    }

    private function generarObservacionesPagina1()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, 'Observaciones generales', 1, 1, 'C', true);

        $startY = $this->pdf->GetY();
        $this->pdf->Rect(10, $startY, 196, 16);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->MultiCell(196, 4, utf8_decode($this->valoracion->observaciones ?? ''), 0, 'L');
        $this->pdf->SetY($startY + 17);
    }

    private function generarPlanEntrenamientoPagina1()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, utf8_decode('Plan de entrenamiento (si aplica)'), 1, 1, 'C', true);

        $startY = $this->pdf->GetY();
        $this->pdf->Rect(10, $startY, 196, 8);

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetXY(12, $startY + 2);
        $this->pdf->Cell(15, 4, 'Tipo:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(45, 4, utf8_decode($this->valoracion->plan_tipo ?? ''), 0, 0, 'L');

        // Hombre BP / GM
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(20, 4, 'Hombre:', 0, 0, 'R');
        $this->pdf->SetFont('Arial', '', 8);
        $h_bp = $this->valoracion->plan_hombre_bp ? 'X' : '__';
        $h_gm = $this->valoracion->plan_hombre_gm ? 'X' : '__';
        $this->pdf->Cell(40, 4, "BP  {$h_bp}   GM  {$h_gm}", 0, 0, 'L');

        // Mujer BP / GM
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 4, 'Mujer:', 0, 0, 'R');
        $this->pdf->SetFont('Arial', '', 8);
        $m_bp = $this->valoracion->plan_mujer_bp ? 'X' : '__';
        $m_gm = $this->valoracion->plan_mujer_gm ? 'X' : '__';
        $this->pdf->Cell(40, 4, "BP  {$m_bp}   GM  {$m_gm}", 0, 1, 'L');

        $this->pdf->SetY($startY + 9);
    }

    private function generarFirmasPagina1()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(98, 5, 'Datos del usuario valorado', 1, 0, 'C', true);
        $this->pdf->Cell(98, 5, 'Datos del entrenador - Valorador', 1, 1, 'C', true);

        $boxY = $this->pdf->GetY();
        $this->pdf->Rect(10, $boxY, 98, 25);
        $this->pdf->Rect(108, $boxY, 98, 25);

        // Lado izquierdo: Usuario
        $this->pdf->SetXY(12, $boxY + 5);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 4, 'Firma:', 0, 0, 'L');
        $this->pdf->Cell(75, 4, '_____________________________________', 0, 1, 'L');

        $this->pdf->SetX(12);
        $this->pdf->Cell(15, 4, 'Nombre:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(75, 4, utf8_decode($this->getNombreCliente()), 0, 1, 'L');

        $this->pdf->SetX(12);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 4, utf8_decode('Cedula:'), 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(75, 4, $this->cliente->numero_documento ?? '', 0, 1, 'L');

        // Lado derecho: Entrenador
        $this->pdf->SetXY(110, $boxY + 5);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 4, 'Firma:', 0, 0, 'L');
        $this->pdf->Cell(75, 4, '_____________________________________', 0, 1, 'L');

        $this->pdf->SetX(110);
        $this->pdf->Cell(15, 4, 'Nombre:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(75, 4, utf8_decode($this->getNombreEntrenador()), 0, 1, 'L');

        $this->pdf->SetX(110);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 4, utf8_decode('Cedula:'), 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(75, 4, $this->getCedulaEntrenador(), 0, 1, 'L');
    }

    /* =========================================================================
       PÁGINA 2: CUESTIONARIO PAR-Q
       ========================================================================= */

    private function generarPagina2()
    {
        $this->pdf->AddPage();

        // 1. Encabezado principal PARQ
        $this->generarEncabezadoPagina2();

        // 2. Datos cliente PARQ
        $this->generarDatosClientePagina2();

        // 3. Explicación "¿Por qué el cuestionario?"
        $this->generarExplicacionPARQ();

        // 4. Tabla del Cuestionario PAR-Q (7 preguntas)
        $this->generarTablaPARQ();

        // 5. Recomendaciones de respuesta (SI / NO)
        $this->generarRecomendacionesPARQ();

        // 6. Nota legal y exención
        $this->generarNotaLegalPARQ();

        // 7. Firmas
        $this->generarFirmasPagina2();
    }

    private function generarEncabezadoPagina2()
    {
        $startY = $this->pdf->GetY();
        $this->pdf->Rect(10, $startY, 196, 18);

        // Subdivisión: Logo (45mm) | Título (105mm) | Código (46mm)
        $this->pdf->Line(55, $startY, 55, $startY + 18);
        $this->pdf->Line(160, $startY, 160, $startY + 18);

        // Logo
        $logoPath = $this->getLogoPath();
        if ($logoPath && (filter_var($logoPath, FILTER_VALIDATE_URL) || file_exists($logoPath))) {
            $this->pdf->Image($logoPath, 12, $startY + 2, 38, 14);
        } else {
            $this->pdf->SetXY(10, $startY + 5);
            $this->pdf->SetFont('Arial', 'B', 14);
            $this->pdf->Cell(45, 8, utf8_decode('LEGION'), 0, 0, 'C');
        }

        // Título central
        $this->pdf->SetXY(55, $startY + 5);
        $this->pdf->SetFont('Arial', 'B', 13);
        $this->pdf->Cell(105, 8, utf8_decode('CUESTIONARIO PARQ'), 0, 1, 'C');

        // Código y versión
        $this->pdf->SetXY(160, $startY + 3);
        $this->pdf->SetFont('Arial', 'B', 7);
        $this->pdf->Cell(46, 4, utf8_decode('Código: SST-F-15 Versión: 1'), 0, 1, 'C');
        $this->pdf->SetXY(160, $startY + 9);
        $this->pdf->SetFont('Arial', '', 7);
        $fechaForm = $this->valoracion->fecha_registro ? date('d-m-Y', strtotime($this->valoracion->fecha_registro)) : date('d-m-Y');
        $this->pdf->Cell(46, 4, utf8_decode('Fecha: ' . $fechaForm), 0, 1, 'C');

        $this->pdf->SetY($startY + 20);
    }

    private function generarDatosClientePagina2()
    {
        $this->pdf->SetFont('Arial', 'B', 8);

        // Fila 1: Fecha | Nombre | Cedula
        $this->pdf->Cell(15, 5, 'Fecha:', 1, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $fechaVal = $this->valoracion->fecha_registro ? date('d/m/Y', strtotime($this->valoracion->fecha_registro)) : date('d/m/Y');
        $this->pdf->Cell(25, 5, $fechaVal, 1, 0, 'C');

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(18, 5, 'Nombre:', 1, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(82, 5, utf8_decode($this->getNombreCliente()), 1, 0, 'L');

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(18, 5, utf8_decode('Cedula'), 1, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(38, 5, $this->cliente->numero_documento ?? '', 1, 1, 'L');

        // Fila 2: Convenio | Nombre de la empresa
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(40, 5, 'Convenio:', 1, 0, 'L');
        $siConvenio = $this->valoracion->convenio ? '[ X ] Si   [   ] No' : '[   ] Si   [ X ] No';
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->SetXY(25, $this->pdf->GetY());
        $this->pdf->Cell(25, 5, $siConvenio, 0, 0, 'L');
        $this->pdf->SetXY(50, $this->pdf->GetY());

        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(45, 5, 'Nombre de la empresa:', 1, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $empresaNombre = $this->valoracion->nombre_empresa ?? ($this->empresa->nombre ?? '');
        $this->pdf->Cell(101, 5, utf8_decode($empresaNombre), 1, 1, 'L');

        $this->pdf->Ln(1);
    }

    private function generarExplicacionPARQ()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, utf8_decode('¿Por qué el cuestionario?'), 1, 1, 'C', true);

        $text = "Comenzar un programa de ejercicio o actividad física no suele conllevar riesgo para la mayoría de las personas, pero algunas tendrían que consultar a su médico o realizarse un reconocimiento antes de iniciarse.\n" .
            "Si usted tiene entre 15 y 69 años de edad y desea comenzar a ser activo y desarrollar algún programa de ejercicio o actividad física, es recomendable que conteste las siete preguntas descritas en el cuestionario PARQ (Physical Activity Readiness Questionnaire). Con las respuestas que usted proporcione, el resultado del cuestionario le indicará si puede comenzar con el programa, de una forma razonablemente segura, o si debería consultar con su médico antes de iniciarse.\n" .
            "Si es mayor de 69 años, y no está acostumbrado a realizar ejercicio, en cualquier caso, consulte con su médico. El sentido común es la mejor guía para contestar a estas preguntas. Por favor, léalas cuidadosamente y conteste a cada una con honestidad: Indique SI o NO.";

        $this->pdf->SetFont('Arial', '', 7);
        $this->pdf->MultiCell(196, 3.2, utf8_decode($text), 1, 'J');

        $this->pdf->Ln(1);
    }

    private function generarTablaPARQ()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, 'Cuestionario', 1, 1, 'C', true);

        // Header de columnas
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(12, 5, 'SI', 1, 0, 'C');
        $this->pdf->Cell(12, 5, 'NO', 1, 0, 'C');
        $this->pdf->Cell(172, 5, 'Pregunta', 1, 1, 'C');

        $preguntas = [
            1 => utf8_decode('¿Alguna vez un médico te ha dicho que tienes una enfermedad del corazón y que solo debes hacer ejercicio bajo indicación médica?'),
            2 => utf8_decode('¿Tiene dolor en el pecho cuando hace actividad física?'),
            3 => utf8_decode('En el último mes, ¿ha tenido dolor en el pecho cuando no hacía actividad física?'),
            4 => utf8_decode('¿Pierde el equilibrio debido a mareos o se ha desmayado alguna vez?'),
            5 => utf8_decode('¿Tiene problemas en huesos o articulaciones (por ejemplo, espalda, rodilla o cadera) que puedan empeorar si aumenta la actividad física?'),
            6 => utf8_decode('¿Le receta su médico algún medicamento para la tensión arterial o un problema cardíaco?'),
            7 => utf8_decode('¿Conoce alguna razón por la cual no debería realizar actividad física?')
        ];

        $this->pdf->SetFont('Arial', '', 7.5);

        for ($i = 1; $i <= 7; $i++) {
            $valKey = 'parq_q' . $i;
            $respuestaSi = $this->valoracion->{$valKey} ? 'X' : '';
            $respuestaNo = !$this->valoracion->{$valKey} ? 'X' : '';

            $startY = $this->pdf->GetY();

            // Dibujar la pregunta en la celda de la derecha
            $this->pdf->SetXY(34, $startY);
            $this->pdf->MultiCell(172, 4.5, $preguntas[$i], 1, 'L');
            $endY = $this->pdf->GetY();

            $rowHeight = $endY - $startY;

            // Dibujar las celdas de SI y NO con la misma altura
            $this->pdf->SetXY(10, $startY);
            $this->pdf->Cell(12, $rowHeight, $respuestaSi, 1, 0, 'C');
            $this->pdf->Cell(12, $rowHeight, $respuestaNo, 1, 0, 'C');

            // Posicionar el cursor al final de la fila
            $this->pdf->SetY($endY);
        }

        $this->pdf->Ln(1);
    }

    private function generarRecomendacionesPARQ()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(196, 5, utf8_decode('Si usted respondió:'), 1, 1, 'C', true);

        $startY = $this->pdf->GetY();
        $colW = 98;

        // Subheaders
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell($colW, 5, utf8_decode('NO a todas las preguntas'), 1, 0, 'C');
        $this->pdf->Cell($colW, 5, utf8_decode('SI a todas las preguntas'), 1, 1, 'C');

        $contentY = $this->pdf->GetY();

        // Contenido NO
        $txtNo = "Sí, puede comenzar un programa de actividad física de forma razonablemente segura si sigue estas recomendaciones:\n\n" .
            "1. Empiece de manera gradual y progresiva, aumentando poco a poco la intensidad y la duración de la actividad física.\n\n" .
            "2. Consulte con un profesional cualificado en actividad física para que evalúe su condición física y diseñe un plan adecuado para usted.\n\n" .
            "Es muy recomendable que evalúe también su tensión arterial. Si tiene más de 144/94, hable con su médico antes de iniciar el programa de actividad física.";

        // Contenido SI
        $txtSi = "1. Consulte con su médico antes de empezar un programa de actividad física, indicándole que realizó este cuestionario y las preguntas en las que respondió SI.";

        $this->pdf->SetFont('Arial', '', 7);

        // Columna NO
        $this->pdf->SetXY(10, $contentY);
        $this->pdf->MultiCell($colW, 3.2, utf8_decode($txtNo), 0, 'L');
        $endYNo = $this->pdf->GetY();

        // Columna SI
        $this->pdf->SetXY(108, $contentY);
        $this->pdf->MultiCell($colW, 3.2, utf8_decode($txtSi), 0, 'L');
        $endYSi = $this->pdf->GetY();

        $maxH = max(40, max($endYNo, $endYSi) - $contentY + 2);

        // Dibujar marcos
        $this->pdf->Rect(10, $contentY, $colW, $maxH);
        $this->pdf->Rect(108, $contentY, $colW, $maxH);

        $this->pdf->SetY($contentY + $maxH + 1);
    }

    private function generarNotaLegalPARQ()
    {
        $startY = $this->pdf->GetY();
        $this->pdf->Rect(10, $startY, 196, 14);

        $text = "Nota: este cuestionario es válido para un máximo de 12 meses a partir de la fecha en que se completa y se convierte en inválido si su condición cambia de manera que usted debiera responder SÍ a cualquiera de las 7 preguntas.\n" .
            "LEGION GYM no asume ninguna responsabilidad legal respecto a las personas que realizan actividad física y/o que han contestado a este cuestionario. En caso de duda le recomendamos que consulte a su médico.";

        $this->pdf->SetXY(11, $startY + 1);
        $this->pdf->SetFont('Arial', '', 6.5);
        $this->pdf->MultiCell(194, 2.8, utf8_decode($text), 0, 'C');

        $this->pdf->SetY($startY + 15);
    }

    private function generarFirmasPagina2()
    {
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->SetFillColor(230, 230, 230);
        $this->pdf->Cell(98, 5, 'Datos del usuario valorado', 1, 0, 'C', true);
        $this->pdf->Cell(98, 5, 'Datos del entrenador - Valorador', 1, 1, 'C', true);

        $boxY = $this->pdf->GetY();
        $this->pdf->Rect(10, $boxY, 98, 22);
        $this->pdf->Rect(108, $boxY, 98, 22);

        // Lado izquierdo: Usuario
        $this->pdf->SetXY(12, $boxY + 4);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 4, 'Firma:', 0, 0, 'L');
        $this->pdf->Cell(75, 4, '_____________________________________', 0, 1, 'L');

        $this->pdf->SetX(12);
        $this->pdf->Cell(15, 4, 'Nombre:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(75, 4, utf8_decode($this->getNombreCliente()), 0, 1, 'L');

        $this->pdf->SetX(12);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 4, utf8_decode('Cedula:'), 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(75, 4, $this->cliente->numero_documento ?? '', 0, 1, 'L');

        // Lado derecho: Entrenador
        $this->pdf->SetXY(110, $boxY + 4);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 4, 'Firma:', 0, 0, 'L');
        $this->pdf->Cell(75, 4, '_____________________________________', 0, 1, 'L');

        $this->pdf->SetX(110);
        $this->pdf->Cell(15, 4, 'Nombre:', 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(75, 4, utf8_decode($this->getNombreEntrenador()), 0, 1, 'L');

        $this->pdf->SetX(110);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(15, 4, utf8_decode('Cedula:'), 0, 0, 'L');
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(75, 4, $this->getCedulaEntrenador(), 0, 1, 'L');
    }

    /* =========================================================================
       MÉTODOS DE APOYO
       ========================================================================= */

    private function getNombreCliente()
    {
        if (!$this->cliente) {
            return '';
        }
        $nombre = trim(($this->cliente->nombre ?? ($this->cliente->nombres ?? '')) . ' ' . ($this->cliente->apellidos ?? ''));
        return $nombre !== '' ? $nombre : 'Cliente';
    }

    private function getLogoPath()
    {
        if (!$this->empresa || empty($this->empresa->logo)) {
            return null;
        }

        $logo = trim((string) $this->empresa->logo);

        if ($logo === '') {
            return null;
        }

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

        return storage_path('app/public/' . $logo);
    }

    private function getNombreEntrenador()
    {
        if (!$this->valoracion || !$this->valoracion->entrenador) {
            return 'Sin entrenador asignado';
        }

        $entrenador = $this->valoracion->entrenador;
        $nombreCompleto = trim(($entrenador->nombres ?? ($entrenador->nombre ?? '')) . ' ' . ($entrenador->apellidos ?? ''));

        if (!empty($nombreCompleto)) {
            return $nombreCompleto;
        }

        return $entrenador->name ?? 'Entrenador';
    }

    private function getCedulaEntrenador()
    {
        if (!$this->valoracion || !$this->valoracion->entrenador) {
            return '';
        }

        $entrenador = $this->valoracion->entrenador;
        return $entrenador->documento_identidad ?? ($entrenador->numero_documento ?? ($entrenador->cedula ?? ''));
    }
}
