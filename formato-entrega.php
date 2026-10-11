<?php
/* ══════════════════════════════════════════════════════════════
   PLANTILLA EN BLANCO — Formato de Entrega, Funcionamiento y
   Conformidad del Equipo (Venta) — Central Cell

   - NO se conecta a ninguna base de datos.
   - Todos los campos (fecha, ticket, marca/modelo, color, IMEI,
     PLOWS, nombre de cliente/vendedor, etc.) se imprimen como
     líneas/casillas en blanco para llenarse a mano con lapicero.
   - El apartado de PayJoy se conserva siempre, solo marcado como
     "(si aplica)" ya que no hay dato de tipo de venta para
     decidirlo automáticamente.

   Este archivo debe colocarse en la MISMA carpeta donde vive la
   subcarpeta /tcpdf/ (igual que garantias.php), para que la ruta
   require_once __DIR__ . '/tcpdf/tcpdf.php' funcione sin cambios.
   ══════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/tcpdf/tcpdf.php';

generarPDFVentaTelefonoBlanco();

function generarPDFVentaTelefonoBlanco()
{
    /* ─── HELPER DE CONVERSIÓN ───────────────────────────────
       Con modo no-Unicode, TCPDF espera ISO-8859-1. */
    $t = function ($s) {
        return mb_convert_encoding((string) $s, 'ISO-8859-1', 'UTF-8');
    };

    /* ─── PDF en modo NO-Unicode (más rápido) ─── */
    $pdf = new TCPDF('P', 'mm', 'LETTER', false, 'ISO-8859-1', false);
    $pdf->SetCreator('Central Cell');
    $pdf->SetAuthor('Central Cell');
    $pdf->SetTitle($t('Entrega, Funcionamiento y Conformidad - Formato en blanco'));
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    $pdf->SetMargins(12, 8, 12);
    $pdf->SetAutoPageBreak(false);
    $pdf->setCellHeightRatio(1.05);
    $pdf->AddPage();

    $lm = 12;
    $pw = 215.9 - ($lm * 2);
    $y  = 8;

    // Encabezado
    $pdf->SetFillColor(18, 52, 104);
    $pdf->Rect($lm, $y, $pw, 14, 'F');
    $logo = __DIR__ . '/recursos/img/central-cell-logo.png';
    if (file_exists($logo)) {
        $pdf->Image($logo, $lm + 3, $y + 1.5, 26, 11, '', '', '', false, 150, '', false, false, 0);
    }
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY($lm + 32, $y + 1.5);
    // Sucursal en blanco (se escribe a mano)
    $pdf->Cell($pw - 32, 6, $t('CENTRAL CELL ______________________________'), 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetXY($lm + 32, $y + 7.5);
    $pdf->Cell($pw - 32, 5, $t('Formato de Entrega, Funcionamiento y Conformidad del Equipo'), 0, 1, 'L');
    $y += 17;

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY($lm, $y);
    // Fecha en blanco
    $pdf->Cell($pw / 2, 5, $t('Fecha: ____/____/________'), 0, 0, 'L');
    $pdf->Cell($pw / 2, 5, $t('No. de ticket: ____________________________'), 0, 1, 'R');
    $y += 7;

    $seccion = function (string $titulo) use ($pdf, $lm, $pw, &$y, $t) {
        $pdf->SetFillColor(18, 52, 104);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($lm, $y);
        $pdf->Cell($pw, 5, $t('  ' . $titulo), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $y += 5.5;
    };

    // Datos del equipo — todo en blanco
    $seccion('DATOS DEL EQUIPO');
    $pdf->SetFont('helvetica', '', 8);
    $half = $pw / 2;
    $pdf->SetXY($lm, $y);
    $pdf->Cell($half, 5, $t('Marca / Modelo: ______________________________'), 0, 0, 'L');
    $pdf->Cell($half, 5, $t('Color: ______________________________'), 0, 1, 'L');
    $pdf->SetXY($lm, $y + 5);
    $pdf->Cell($half, 5, $t('IMEI: ______________________________'), 0, 0, 'L');
    $pdf->Cell($half, 5, $t('PLOWS: ______________________________'), 0, 1, 'L');
    $y += 12;

    // Revisión / checklist (2 columnas) — igual que el original
    $seccion('REVISIÓN Y FUNCIONAMIENTO DEL EQUIPO');
    $pdf->SetFont('helvetica', 'I', 6.8);
    $pdf->SetXY($lm, $y);
    $pdf->MultiCell($pw, 3.2, $t('Antes de la entrega, el equipo fue revisado en presencia del cliente, verificando su correcto funcionamiento.'), 0, 'L');
    $y = $pdf->GetY() + 1.5;

    $items = [
        'Encendido y apagado', 'Pantalla', 'Touch / táctil', 'Bocina / altavoz', 'Micrófono',
        'Cámara frontal', 'Cámara trasera', 'Flash', 'Carga y puerto de carga', 'Botones físicos',
        'Wi-Fi', 'Bluetooth', 'Red móvil / señal', 'Lectura de SIM', 'Vibración',
        'Sin daños físicos visibles', 'IMEI coincide con documentación/caja',
    ];
    $colW  = $pw / 2;
    $mitad = ceil(count($items) / 2);
    $col1  = array_slice($items, 0, $mitad);
    $col2  = array_slice($items, $mitad);
    $pdf->SetFont('helvetica', '', 8.8);
    $yStart = $y;
    foreach ($col1 as $it) {
        $pdf->SetXY($lm, $y);
        $pdf->Cell(4, 3.6, '[  ]', 0, 0, 'L');
        $pdf->Cell($colW - 4, 3.6, $t($it), 0, 1, 'L');
        $y += 3.6;
    }
    $y2 = $yStart;
    foreach ($col2 as $it) {
        $pdf->SetXY($lm + $colW, $y2);
        $pdf->Cell(4, 3.6, '[  ]', 0, 0, 'L');
        $pdf->Cell($colW - 4, 3.6, $t($it), 0, 1, 'L');
        $y2 += 3.6;
    }
    $y = max($y, $y2) + 1;
    $pdf->SetFont('helvetica', '', 6.8);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 3.6, $t('Otros: _____________________________________________________'), 0, 1, 'L');
    $y += 5;
    $pdf->Cell($pw, 3.6, $t('Observaciones: _____________________________________________'), 0, 1, 'L');
    $y += 6;

    // Garantía — texto fijo, igual al original
    $seccion('GARANTÍA');
    $pdf->SetFont('helvetica', '', 6.8);
    $pdf->SetXY($lm, $y);
    $pdf->MultiCell(
        $pw,
        3.2,
        $t('El equipo cuenta con 3 meses de garantía exclusivamente por defectos de fabricación. No cubre daños ocasionados por golpes, caídas, humedad, manipulación, mal uso, accesorios no adecuados, daños físicos o cualquier otra situación ajena a un defecto de fabricación. El cliente deberá conservar su ticket de compra y documentación correspondiente para cualquier aclaración o solicitud de garantía.'),
        0,
        'L'
    );
    $y = $pdf->GetY() + 2;

    // PayJoy — se conserva siempre, marcado como "(si aplica)"
    $seccion('EN CASO DE EQUIPO ADQUIRIDO MEDIANTE PAYJOY  (si aplica)');
    $pdf->SetFont('helvetica', '', 6.6);
    $pdf->SetXY($lm, $y);
    $pdf->MultiCell(
        $pw,
        3.1,
        $t('El cliente se compromete a realizar los pagos de su financiamiento en las fechas y montos que indique la app de PayJoy. De no pagarse, el equipo podrá bloquearse como recordatorio, según las condiciones del financiamiento. Los pagos pueden realizarse en la sucursal donde se adquirió el equipo, con una comisión de $15.00 MXN por servicio. Cada pago debe entregarse con ticket y comprobante de PayJoy; el cliente debe solicitarlos y conservarlos.'),
        0,
        'L'
    );
    $y = $pdf->GetY() + 2;

    // Conformidad (siempre) — texto fijo
    $seccion('CONFORMIDAD DEL CLIENTE');
    $pdf->SetFont('helvetica', '', 6.8);
    $pdf->SetXY($lm, $y);
    $pdf->MultiCell(
        $pw,
        3.2,
        $t('Manifiesto que recibí el equipo descrito en este documento y que, al momento de la entrega, tuve la oportunidad de revisar su funcionamiento y estado físico. Declaro estar informado sobre las condiciones de garantía, así como, en caso de aplicar, sobre las condiciones y obligaciones correspondientes al financiamiento mediante PayJoy.'),
        0,
        'L'
    );
    $y = $pdf->GetY() + 4;

    $half  = ($pw - 10) / 2;
    $col2x = $lm + $half + 10;
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($half, 4, $t('Nombre del cliente:'), 0, 0, 'L');
    $pdf->Line($lm + 32, $y + 3.5, $lm + $half - 2, $y + 3.5);
    $pdf->SetXY($col2x, $y);
    $pdf->Cell($half, 4, $t('Nombre del vendedor:'), 0, 0, 'L');
    $pdf->Line($col2x + 34, $y + 3.5, $col2x + $half - 2, $y + 3.5);
    $y += 9;
    $pdf->SetXY($lm, $y);
    $pdf->Cell($half, 4, $t('Firma del cliente:'), 0, 0, 'L');
    $pdf->Line($lm + 30, $y + 3.5, $lm + $half - 2, $y + 3.5);
    $pdf->SetXY($col2x, $y);
    $pdf->Cell($half, 4, $t('Firma del vendedor:'), 0, 0, 'L');
    $pdf->Line($col2x + 30, $y + 3.5, $col2x + $half - 2, $y + 3.5);
    $y += 8;
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 4, $t('Observaciones adicionales: ____________________________________________'), 0, 1, 'L');

    while (ob_get_level()) {
        ob_end_clean();
    }

    $pdf->Output('Venta-Blanco.pdf', 'D');
    exit;
}