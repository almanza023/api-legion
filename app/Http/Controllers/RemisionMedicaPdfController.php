<?php

namespace App\Http\Controllers;

use App\Models\RemisionMedica;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use setasign\Fpdf\Fpdf;

class RemisionMedicaPdfController extends Controller
{
    protected $pdf;
    protected $remision;
    protected $cliente;
    protected $medico;
    protected $empresa;

    /**
     * Generar PDF de Remisión Médica
     */
    public function generarPdf($id)
    {
        try {
            $this->remision = RemisionMedica::with(['cliente', 'medico', 'cie10'])->find($id);

            if (!$this->remision) {
                return response()->json(['status' => 'fail', 'msg' => 'Remisión médica no encontrada'], 404);
            }

            $this->cliente = $this->remision->cliente;
            $this->medico = $this->remision->medico;
            $this->empresa = Empresa::find(1);

            // Instanciar FPDF (Carta / Portrait)
            $this->pdf = new \FPDF('P', 'mm', 'Letter');
            $this->pdf->SetMargins(10, 10, 10);
            $this->pdf->SetAutoPageBreak(false);

            $this->pdf->AddPage();

            // Renderizar secciones del documento
            $this->generarEncabezado();
            $this->generarDatosPaciente();
            $this->generarDiagnostico();
            $this->generarRemisionYRestricciones();
            $this->generarDatosMedico();

            return response($this->pdf->Output('S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="remision_medica_' . $id . '.pdf"');

        } catch (\Exception $ex) {
            Log::error($ex->getMessage() . PHP_EOL . $ex->getTraceAsString());
            return response()->json([
                'status' => 'fail',
                'msg' => 'Error al generar el PDF de la remisión médica',
                'data' => $ex->getMessage()
            ], 500);
        }
    }

    /* =========================================================================
       1. ENCABEZADO PRINCIPAL (MÉDICO & FECHA)
       ========================================================================= */
    private function generarEncabezado()
    {
        $startY = $this->pdf->GetY();
        $this->pdf->Rect(10, $startY, 196, 22);

        // Divisiones: Izquierda Espacio Vacío (40mm) | Centro Médico (116mm) | Derecha Fecha (40mm)
        $this->pdf->Line(50, $startY, 50, $startY + 22);
        $this->pdf->Line(166, $startY, 166, $startY + 22);

        // El cuadro izquierdo (40mm) se deja totalmente blanco/vacío según instrucción.

        // Centro: Datos del médico tratante
        $nombreMedico = $this->medico ? $this->medico->nombre : 'DRA. SANDRA DONADO G';
        $especialidad = $this->medico ? ($this->medico->especialidad ?? 'MEDICO') : 'MEDICO';
        $retheus = $this->medico ? ($this->medico->registro_retheus ?? '') : 'RM. 23413';
        $cedula = $this->medico ? ($this->medico->cedula ?? '') : 'C.C. 50.946.977';

        $detallesReg = trim("{$retheus}" . ($retheus && $cedula ? " - " : "") . ($cedula ? "C.C. {$cedula}" : ""));

        $this->pdf->SetXY(50, $startY + 4);
        $this->pdf->SetFont('Arial', 'B', 11);
        $this->pdf->Cell(116, 5, utf8_decode(strtoupper($nombreMedico)), 0, 1, 'C');

        $this->pdf->SetX(50);
        $this->pdf->SetFont('Arial', 'B', 9);
        $this->pdf->Cell(116, 4.5, utf8_decode(strtoupper($especialidad)), 0, 1, 'C');

        if ($detallesReg !== '') {
            $this->pdf->SetX(50);
            $this->pdf->SetFont('Arial', 'B', 8.5);
            $this->pdf->Cell(116, 4.5, utf8_decode($detallesReg), 0, 1, 'C');
        }

        // Derecha: Fecha de emisión
        $fechaRem = $this->remision->fecha_registro
            ? date('d/m/Y', strtotime($this->remision->fecha_registro))
            : date('d/m/Y');

        $this->pdf->SetXY(166, $startY + 6);
        $this->pdf->SetFont('Arial', 'B', 8);
        $this->pdf->Cell(40, 4, utf8_decode('FECHA EMISIÓN'), 0, 1, 'C');
        $this->pdf->SetX(166);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(40, 5, $fechaRem, 0, 1, 'C');

        $this->pdf->SetY($startY + 24);
    }

    /* =========================================================================
       2. SECCIÓN 1: DATOS DEL PACIENTE
       ========================================================================= */
    private function generarDatosPaciente()
    {
        // Barra amarilla de título
        $this->pdf->SetFillColor(255, 255, 0); // Amarillo #FFFF00
        $this->pdf->SetFont('Arial', 'B', 8.5);
        $this->pdf->Cell(196, 5, utf8_decode('1. Datos del paciente'), 1, 1, 'L', true);

        $startY = $this->pdf->GetY();

        // Fila 1: Nombre Completo | Numero Documento
        $nombreCompleto = $this->getNombreCliente();
        $numDocumento = $this->cliente ? ($this->cliente->numero_documento ?? '') : '';

        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(35, 6, utf8_decode('Nombre Completo:'), 'L|T|B', 0, 'L');
        $this->pdf->Cell(80, 6, utf8_decode($nombreCompleto), 'T|B|R', 0, 'L');

        $this->pdf->Cell(35, 6, utf8_decode('Numero Documento:'), 'L|T|B', 0, 'L');
        $this->pdf->Cell(46, 6, utf8_decode($numDocumento), 'T|B|R', 1, 'L');

        // Fila 2: Fecha de nacimiento | DD | MM | AA | Edad | Sexo (Mujer/Hombre)
        $fechaNac = $this->cliente ? $this->cliente->fecha_nacimiento : null;
        $dd = $fechaNac ? date('d', strtotime($fechaNac)) : '';
        $mm = $fechaNac ? date('m', strtotime($fechaNac)) : '';
        $aa = $fechaNac ? date('y', strtotime($fechaNac)) : '';
        $edad = $this->calcularEdad($fechaNac);

        $sexo = strtolower(trim($this->cliente->sexo ?? ''));
        $esMujer = in_array($sexo, ['f', 'femenino', 'mujer']) || str_starts_with($sexo, 'fem') || str_starts_with($sexo, 'muj');
        $esHombre = in_array($sexo, ['m', 'h', 'masculino', 'hombre']) || str_starts_with($sexo, 'masc') || str_starts_with($sexo, 'hom');

        $isMujer = $esMujer ? '[X]' : '[  ]';
        $isHombre = (!$esMujer && $esHombre) ? '[X]' : '[  ]';

        $this->pdf->Cell(35, 6, utf8_decode('Fecha de nacimiento:'), 'L|T|B', 0, 'L');

        // Celdas separadas DD MM AA
        $this->pdf->Cell(12, 6, $dd, 1, 0, 'C');
        $this->pdf->Cell(12, 6, $mm, 1, 0, 'C');
        $this->pdf->Cell(12, 6, $aa, 1, 0, 'C');

        $this->pdf->Cell(15, 6, utf8_decode('Edad:'), 'T|B', 0, 'R');
        $this->pdf->Cell(29, 6, $edad !== '' ? utf8_decode(" {$edad} años") : '', 'T|B|R', 0, 'L');

        $this->pdf->Cell(15, 6, utf8_decode('Sexo:'), 'L|T|B', 0, 'L');
        $this->pdf->Cell(66, 6, utf8_decode("{$isMujer} Mujer    {$isHombre} Hombre"), 'T|B|R', 1, 'L');

        $this->pdf->Ln(1);
    }

    /* =========================================================================
       3. SECCIÓN 2: DIAGNÓSTICO
       ========================================================================= */
    private function generarDiagnostico()
    {
        $this->pdf->SetFillColor(255, 255, 0);
        $this->pdf->SetFont('Arial', 'B', 8.5);
        $this->pdf->Cell(196, 5, utf8_decode('2. Diagnostico'), 1, 1, 'L', true);

        $startY = $this->pdf->GetY();
        $this->pdf->Rect(10, $startY, 196, 16);

        // Texto Diagnostico
        $diagnosticoTexto = $this->remision->diagnostico ?? '';
        $this->pdf->SetXY(11, $startY + 1);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(25, 4, utf8_decode('Diagnostico:'), 0, 0, 'L');
        $this->pdf->MultiCell(158, 4, utf8_decode($diagnosticoTexto), 0, 'L');

        // Código CIE-10 (Opcional)
        $cie10Codigo = '';
        if ($this->remision->cie10) {
            $cie10Codigo = $this->remision->cie10->codigo . ' - ' . $this->remision->cie10->descripcion;
        } elseif (!empty($this->remision->codigo_cie10)) {
            $cie10Codigo = $this->remision->codigo_cie10;
        }

        $this->pdf->SetXY(11, $startY + 10);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(45, 5, utf8_decode('Código CIE - 10 (Opcional):'), 0, 0, 'L');
        $this->pdf->Cell(138, 5, utf8_decode($cie10Codigo), 0, 1, 'L');

        $this->pdf->SetY($startY + 17);
    }

    /* =========================================================================
       4. SECCIÓN 3, 4 y 5: REMISIÓN, RESTRICCIONES Y VIGENCIA (COLUMNAS DERECHA E IZQUIERDA)
       ========================================================================= */
    private function generarRemisionYRestricciones()
    {
        $startY = $this->pdf->GetY();
        $leftW = 88;
        $rightW = 105;
        $gap = 3;

        /* ----------------------- COLUMNA IZQUIERDA: 3. Remisión ----------------------- */
        $this->pdf->SetXY(10, $startY);
        $this->pdf->SetFillColor(255, 255, 0);
        $this->pdf->SetFont('Arial', 'B', 8.5);
        $this->pdf->Cell($leftW, 5, utf8_decode('3. Remisión'), 1, 1, 'L', true);

        $box3Y = $this->pdf->GetY();
        $this->pdf->Rect(10, $box3Y, $leftW, 60);

        // Texto descriptivo de remisión
        $textoRemision = "Remito al paciente para ingresar a un programa de acondicionamiento fisico supervisado en un centro de acondicionamiento y preparación fisica (CAPF)";
        $this->pdf->SetXY(12, $box3Y + 2);
        $this->pdf->SetFont('Arial', '', 7.5);
        $this->pdf->MultiCell($leftW - 4, 3.5, utf8_decode($textoRemision), 0, 'L');

        // Objetivo header
        $this->pdf->SetXY(12, $box3Y + 14);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(20, 4, utf8_decode('Objetivo:'), 0, 1, 'L');

        // Renderizar únicamente los objetivos guardados en la base de datos
        $objetivosGuardados = is_array($this->remision->objetivos) ? $this->remision->objetivos : [];
        $otroTexto = trim($this->remision->objetivo_otro ?? '');

        $objetivosAMostrar = [];
        $otroProcesado = false;

        foreach ($objetivosGuardados as $obj) {
            $objStr = trim((string) $obj);
            if ($objStr === '') {
                continue;
            }

            if (strcasecmp($objStr, 'Otro') === 0) {
                $otroProcesado = true;
                if ($otroTexto !== '') {
                    $objetivosAMostrar[] = "Otro: {$otroTexto}";
                } else {
                    $objetivosAMostrar[] = "Otro";
                }
            } else {
                $objetivosAMostrar[] = $objStr;
            }
        }

        if (!$otroProcesado && $otroTexto !== '') {
            $objetivosAMostrar[] = "Otro: {$otroTexto}";
        }

        $objY = $box3Y + 19;
        if (count($objetivosAMostrar) > 0) {
            foreach ($objetivosAMostrar as $objNombre) {
                $this->pdf->SetXY(18, $objY);
                $this->pdf->SetFont('Arial', '', 7.5);
                $this->pdf->Cell(7, 4, '[X]', 0, 0, 'C');
                $this->pdf->SetXY(25, $objY);
                $this->pdf->MultiCell(70, 4, utf8_decode($objNombre), 0, 'L');
                $objY = max($objY + 4.5, $this->pdf->GetY() + 0.5);
            }
        } else {
            $this->pdf->SetXY(18, $objY);
            $this->pdf->SetFont('Arial', 'I', 7.5);
            $this->pdf->Cell(68, 4, utf8_decode('Sin objetivos especificados'), 0, 1, 'L');
        }

        /* ----------------------- COLUMNA DERECHA: 4. Restricciones y 5. Vigencia ----------------------- */
        $rightX = 10 + $leftW + $gap; // 10 + 88 + 3 = 101

        // 4. Restricciones
        $this->pdf->SetXY($rightX, $startY);
        $this->pdf->SetFillColor(255, 255, 0);
        $this->pdf->SetFont('Arial', 'B', 8.5);
        $this->pdf->Cell($rightW, 5, utf8_decode('4. Restricciones'), 1, 1, 'L', true);

        $box4Y = $this->pdf->GetY();
        $box4H = 43;
        $this->pdf->Rect($rightX, $box4Y, $rightW, $box4H);

        // Texto de restricciones tal como está en la base de datos
        $restriccionesTexto = $this->remision->restricciones ?? '';
        $this->pdf->SetXY($rightX + 2, $box4Y + 2);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->MultiCell($rightW - 4, 4.5, utf8_decode($restriccionesTexto), 0, 'L');

        // 5. Vigencia de la remisión
        $vigenciaY = $box4Y + $box4H + 2;
        $this->pdf->SetXY($rightX, $vigenciaY);
        $this->pdf->SetFillColor(255, 255, 0);
        $this->pdf->SetFont('Arial', 'B', 8.5);
        $this->pdf->Cell($rightW, 5, utf8_decode('5. Vigencia de la remisión'), 1, 1, 'L', true);

        $box5Y = $this->pdf->GetY();
        $this->pdf->Rect($rightX, $box5Y, $rightW, 10);

        $v = $this->remision->vigencia ?? '6_meses';
        $v6 = ($v === '6_meses') ? '[X]' : '[  ]';
        $v12 = ($v === '12_meses') ? '[X]' : '[  ]';
        $vOtra = ($v === 'otra') ? '[X]' : '[  ]';
        $vOtraDesc = $this->remision->vigencia_otra_descripcion ?? '';

        $this->pdf->SetXY($rightX + 10, $box5Y + 2.5);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(25, 5, utf8_decode("{$v6} 6 meses"), 0, 0, 'L');
        $this->pdf->Cell(28, 5, utf8_decode("{$v12} 12 meses"), 0, 0, 'L');
        $this->pdf->Cell(15, 5, utf8_decode("{$vOtra} Otra:"), 0, 0, 'L');
        $this->pdf->Cell(22, 5, utf8_decode($vOtraDesc), 'B', 1, 'L');

        $this->pdf->SetY($box3Y + 62);
    }

    /* =========================================================================
       5. SECCIÓN 6: DATOS DEL MÉDICO
       ========================================================================= */
    private function generarDatosMedico()
    {
        $this->pdf->SetFillColor(255, 255, 0);
        $this->pdf->SetFont('Arial', 'B', 8.5);
        $this->pdf->Cell(196, 5, utf8_decode('6. Datos del médico'), 1, 1, 'L', true);

        $startY = $this->pdf->GetY();
        $this->pdf->Rect(10, $startY, 196, 52);

        $nombreMed = $this->medico ? $this->medico->nombre : '';
        $especialidadMed = $this->medico ? ($this->medico->especialidad ?? '') : '';
        $retheusMed = $this->medico ? ($this->medico->registro_retheus ?? '') : '';
        $correoMed = $this->medico ? ($this->medico->correo ?? '') : '';
        $telMed = $this->medico ? ($this->medico->telefono ?? '') : '';

        // Fila 1: Nombre | Especialidad
        $this->pdf->SetXY(11, $startY + 3);
        $this->pdf->SetFont('Arial', '', 8);
        $this->pdf->Cell(18, 5, utf8_decode('Nombre:'), 0, 0, 'L');
        $this->pdf->Cell(80, 5, utf8_decode($nombreMed), 'B', 0, 'L');

        $this->pdf->Cell(20, 5, utf8_decode('Especialidad:'), 0, 0, 'L');
        $this->pdf->Cell(65, 5, utf8_decode($especialidadMed), 'B', 1, 'L');

        // Fila 2: Registro profesional(RETHEUS) | Correo
        $this->pdf->SetXY(11, $startY + 11);
        $this->pdf->Cell(45, 5, utf8_decode('Registro profesional(RETHEUS)'), 0, 0, 'L');
        $this->pdf->Cell(53, 5, utf8_decode($retheusMed), 'B', 0, 'L');

        $this->pdf->Cell(15, 5, utf8_decode('Correo:'), 0, 0, 'L');
        $this->pdf->Cell(70, 5, utf8_decode($correoMed), 'B', 1, 'L');

        // Fila 3: Telefono / Contacto
        $this->pdf->SetXY(11, $startY + 19);
        $this->pdf->Cell(32, 5, utf8_decode('Telefono / Contacto:'), 0, 0, 'L');
        $this->pdf->Cell(66, 5, utf8_decode($telMed), 'B', 1, 'L');

        // Área de Firma y Sello (Abajo al centro)
        $firmaY = $startY + 30;

        // Si el médico tiene firma guardada
        if ($this->medico && !empty($this->medico->firma_path)) {
            $localFirma = public_path($this->medico->firma_path);
            if (!file_exists($localFirma)) {
                $localFirma = storage_path('app/public/' . ltrim($this->medico->firma_path, '/'));
            }

            if (file_exists($localFirma)) {
                $this->pdf->Image($localFirma, 75, $firmaY - 8, 45, 15);
            }
        }

        $this->pdf->Line(60, $firmaY + 12, 140, $firmaY + 12);
        $this->pdf->SetXY(60, $firmaY + 13);
        $this->pdf->SetFont('Arial', 'B', 8.5);
        $this->pdf->Cell(80, 4, utf8_decode('Firma y Sello'), 0, 1, 'C');
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

    private function calcularEdad($fechaNacimiento)
    {
        if (empty($fechaNacimiento)) {
            return '';
        }
        try {
            $dob = new \DateTime($fechaNacimiento);
            $now = new \DateTime();
            return $dob->diff($now)->y;
        } catch (\Exception $e) {
            return '';
        }
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
}
