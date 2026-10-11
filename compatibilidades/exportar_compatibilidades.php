<?php
// Genera un .xlsx REAL sin librerías externas (no necesita PhpSpreadsheet ni la extensión zip).
// Guardar este archivo como UTF-8 SIN BOM y sin nada antes de la etiqueta de apertura.

ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once '../funciones.php';

$conn = conectarBD();
// GROUP_CONCAT corta a 1024 caracteres por defecto; con las notas hay que ampliarlo
try { $conn->exec("SET SESSION group_concat_max_len = 1000000"); } catch (Exception $e) {}

$sql = "
    SELECT 
        c.tipo,
        CONCAT(m1.marca, ' ', m1.modelo) AS modelo_principal,
        GROUP_CONCAT(DISTINCT CONCAT(m2.marca, ' ', m2.modelo) ORDER BY m2.marca, m2.modelo SEPARATOR ', ') AS modelos_compatibles,
        GROUP_CONCAT(DISTINCT NULLIF(TRIM(c.nota), '') ORDER BY c.nota SEPARATOR ' | ') AS observaciones
    FROM compatibilidades c
    INNER JOIN modelos m1 ON c.modelo_id = m1.id
    INNER JOIN modelos m2 ON c.compatible_id = m2.id
    GROUP BY c.tipo, m1.id
    ORDER BY m1.marca ASC, m1.modelo ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Utilidades ---------- */

function xml_texto($s) {
    $s = (string)$s;
    // Quitar caracteres de control no válidos en XML
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function celda($ref, $texto, $estilo) {
    return '<c r="' . $ref . '" s="' . $estilo . '" t="inlineStr"><is><t xml:space="preserve">'
         . xml_texto($texto) . '</t></is></c>';
}

/* ---------- Contenido del XLSX ---------- */

$cab = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";

$contentTypes = $cab
 . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
 . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
 . '<Default Extension="xml" ContentType="application/xml"/>'
 . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
 . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
 . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
 . '</Types>';

$rootRels = $cab
 . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
 . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
 . '</Relationships>';

$workbook = $cab
 . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
 . '<sheets><sheet name="Compatibilidades" sheetId="1" r:id="rId1"/></sheets>'
 . '</workbook>';

$workbookRels = $cab
 . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
 . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
 . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
 . '</Relationships>';

// Estilos: 0 = normal, 1 = encabezado (negrita + fondo gris), 2 = texto arriba con ajuste de línea
$styles = $cab
 . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
 . '<fonts count="2">'
 .   '<font><sz val="11"/><name val="Calibri"/></font>'
 .   '<font><b/><sz val="11"/><name val="Calibri"/></font>'
 . '</fonts>'
 . '<fills count="3">'
 .   '<fill><patternFill patternType="none"/></fill>'
 .   '<fill><patternFill patternType="gray125"/></fill>'
 .   '<fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/><bgColor indexed="64"/></patternFill></fill>'
 . '</fills>'
 . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
 . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
 . '<cellXfs count="3">'
 .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
 .   '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
 .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
 . '</cellXfs>'
 . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
 . '</styleSheet>';

$filas = '<row r="1">'
       . celda('A1', 'Tipo', 1)
       . celda('B1', 'Modelo Principal', 1)
       . celda('C1', 'Modelos Compatibles', 1)
       . celda('D1', 'Observaciones', 1)
       . '</row>';

$n = 2;
foreach ($rows as $row) {
    $filas .= '<row r="' . $n . '">'
            . celda('A' . $n, ucfirst((string)$row['tipo']), 2)
            . celda('B' . $n, $row['modelo_principal'], 2)
            . celda('C' . $n, $row['modelos_compatibles'], 2)
            . celda('D' . $n, $row['observaciones'], 2)
            . '</row>';
    $n++;
}
$ultima = $n - 1;

$sheet = $cab
 . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
 . '<dimension ref="A1:D' . $ultima . '"/>'
 . '<sheetViews><sheetView workbookViewId="0">'
 .   '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
 .   '<selection pane="bottomLeft" activeCell="A2" sqref="A2"/>'
 . '</sheetView></sheetViews>'
 . '<sheetFormatPr defaultRowHeight="15"/>'
 . '<cols>'
 .   '<col min="1" max="1" width="14" customWidth="1"/>'
 .   '<col min="2" max="2" width="32" customWidth="1"/>'
 .   '<col min="3" max="3" width="90" customWidth="1"/>'
 .   '<col min="4" max="4" width="50" customWidth="1"/>'
 . '</cols>'
 . '<sheetData>' . $filas . '</sheetData>'
 . '</worksheet>';

$archivos = [
    '[Content_Types].xml'        => $contentTypes,
    '_rels/.rels'                => $rootRels,
    'xl/workbook.xml'            => $workbook,
    'xl/_rels/workbook.xml.rels' => $workbookRels,
    'xl/styles.xml'              => $styles,
    'xl/worksheets/sheet1.xml'   => $sheet,
];

/* ---------- Empaquetar en ZIP (sin compresión, sin depender de ZipArchive) ---------- */

$ahora   = getdate();
$dosHora = ($ahora['hours'] << 11) | ($ahora['minutes'] << 5) | intdiv($ahora['seconds'], 2);
$dosFecha = ((max($ahora['year'], 1980) - 1980) << 9) | ($ahora['mon'] << 5) | $ahora['mday'];

$zip = '';
$central = '';
$cantidad = 0;

foreach ($archivos as $nombre => $datos) {
    $crc    = crc32($datos);
    $tam    = strlen($datos);
    $offset = strlen($zip);
    $lon    = strlen($nombre);

    $zip .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosHora, $dosFecha, $crc, $tam, $tam, $lon, 0)
          . $nombre . $datos;

    $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosHora, $dosFecha, $crc, $tam, $tam, $lon, 0, 0, 0, 0, 0, $offset)
              . $nombre;
    $cantidad++;
}

$final = $zip . $central
       . pack('VvvvvVVv', 0x06054b50, 0, 0, $cantidad, $cantidad, strlen($central), strlen($zip), 0);

/* ---------- Enviar ---------- */

// Limpiar TODO lo que se haya impreso antes (espacios, BOM, warnings, etc.)
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="compatibilidades.xlsx"');
header('Content-Length: ' . strlen($final));
header('Cache-Control: max-age=0');
header('Pragma: public');

echo $final;
exit;