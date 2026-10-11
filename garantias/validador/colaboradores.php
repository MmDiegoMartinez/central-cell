<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../funciones.php';

/**
 * Genera un .xlsx real sin librerías externas (solo requiere la extensión zip de PHP).
 */
function generarXlsxSimple(array $encabezados, array $filas, string $nombreArchivo): bool
{
    if (!class_exists('ZipArchive')) return false;

    $esc = fn($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $col = fn(int $i) => chr(65 + $i); // A..Z (suficiente para pocas columnas)

    $anchos = [];
    foreach ($encabezados as $i => $h) $anchos[$i] = mb_strlen((string) $h);
    foreach ($filas as $f) foreach ($f as $i => $v) $anchos[$i] = max($anchos[$i] ?? 0, mb_strlen((string) $v));

    $cols = '';
    foreach ($anchos as $i => $w) {
        $n = $i + 1;
        $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . min(60, $w + 4) . '" customWidth="1"/>';
    }

    $rows = '<row r="1">';
    foreach ($encabezados as $i => $h) {
        $rows .= '<c r="' . $col($i) . '1" s="1" t="inlineStr"><is><t>' . $esc($h) . '</t></is></c>';
    }
    $rows .= '</row>';

    foreach ($filas as $r => $f) {
        $n = $r + 2;
        $rows .= '<row r="' . $n . '">';
        foreach ($f as $i => $v) {
            $ref = $col($i) . $n;
            if (is_int($v) || (is_string($v) && ctype_digit($v) && $i === 0)) {
                $rows .= '<c r="' . $ref . '"><v>' . (int) $v . '</v></c>';
            } else {
                $rows .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $esc($v) . '</t></is></c>';
            }
        }
        $rows .= '</row>';
    }

    $ultima = $col(count($encabezados) - 1);
    $hoja = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<cols>' . $cols . '</cols>'
        . '<sheetData>' . $rows . '</sheetData>'
        . '<autoFilter ref="A1:' . $ultima . (count($filas) + 1) . '"/>'
        . '</worksheet>';

    $estilos = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
        . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF00838F"/><bgColor indexed="64"/></patternFill></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
        . '</styleSheet>';

    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) return false;

    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>');
    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>');
    $zip->addFromString('xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Colaboradores" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>');
    $zip->addFromString('xl/styles.xml', $estilos);
    $zip->addFromString('xl/worksheets/sheet1.xml', $hoja);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '.xlsx"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: max-age=0');
    readfile($tmp);
    unlink($tmp);
    return true;
}

$mensaje_flash = null;
$accion = $_POST['accion'] ?? '';

switch ($accion) {

    case 'crear':
        $res = crearColaborador($_POST['nombre'] ?? '', $_POST['fecha_ingreso'] ?? '');
        $mensaje_flash = ['tipo' => $res['ok'] ? 'success' : 'error', 'texto' => $res['mensaje']];
        break;

    case 'actualizar':
        $res = actualizarColab(
            (int) ($_POST['id'] ?? 0),
            $_POST['nombre'] ?? '',
            $_POST['fecha_ingreso'] ?? '',
            $_POST['fecha_capacitacion'] ?: null,
            (int) ($_POST['payjoy_int'] ?? 0)
        );
        $mensaje_flash = ['tipo' => $res['ok'] ? 'success' : 'error', 'texto' => $res['mensaje']];
        break;

    case 'eliminar':
        $res = eliminarColaborador((int) ($_POST['id'] ?? 0));
        $mensaje_flash = ['tipo' => $res['ok'] ? 'success' : 'error', 'texto' => $res['mensaje']];
        break;

    case 'importar_excel':
        if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['archivo_excel']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'xlsx') {
                $mensaje_flash = ['tipo' => 'error', 'texto' => 'Solo se aceptan archivos .xlsx'];
            } else {
                $res     = importarColaboradoresDesdeExcel($_FILES['archivo_excel']['tmp_name']);
                $resumen = "Insertados: {$res['insertados']} | Actualizados: {$res['actualizados']} | Sin cambios: {$res['sin_cambios']}";
                if (!empty($res['errores'])) $resumen .= ' | Errores: ' . implode('; ', $res['errores']);
                $mensaje_flash = ['tipo' => empty($res['errores']) ? 'success' : 'warning', 'texto' => $resumen];
            }
        } else {
            $mensaje_flash = ['tipo' => 'error', 'texto' => 'No se recibió ningún archivo o hubo un error al subir.'];
        }
        break;

    case 'fusionar':
        $res = fusionarColaboradores((int) ($_POST['id_origen'] ?? 0), (int) ($_POST['id_destino'] ?? 0));
        $mensaje_flash = ['tipo' => $res['ok'] ? 'success' : 'error', 'texto' => $res['mensaje']];
        break;

    case 'exportar_excel':
        $datos = obtenerColab();
        $filas = [];
        foreach ($datos as $c) {
            $estado = calcularEstadoPayjoy((int) $c['payjoy_int'], $c['fecha_ingreso']);
            $filas[] = [
                (int) $c['id'],
                $c['nombre'],
                $c['fecha_ingreso']       ? date('d/m/Y', strtotime($c['fecha_ingreso']))       : '',
                $c['fecha_capacitacion']  ? date('d/m/Y', strtotime($c['fecha_capacitacion']))  : '',
                $estado['etiqueta'],
            ];
        }
        $encabezados   = ['#', 'Nombre', 'Fecha ingreso', 'Capacitación', 'PayJoy'];
        $nombreArchivo = 'colaboradores_' . date('Y-m-d');

        while (ob_get_level()) ob_end_clean();

        // 1) Si existe PhpSpreadsheet, se usa
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoload)) require_once $autoload;

        if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Colaboradores');
            $sheet->fromArray($encabezados, null, 'A1');
            $sheet->fromArray($filas, null, 'A2');
            $sheet->getStyle('A1:E1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '00838F']],
            ]);
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"$nombreArchivo.xlsx\"");
            header('Cache-Control: max-age=0');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
            exit;
        }

        // 2) Sin librerías: genera el .xlsx directamente (solo requiere la extensión zip)
        if (generarXlsxSimple($encabezados, $filas, $nombreArchivo)) {
            exit;
        }

        // 3) Último recurso: CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"$nombreArchivo.csv\"");
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $encabezados);
        foreach ($filas as $f) fputcsv($out, $f);
        fclose($out);
        exit;
}

$colaboradores = obtenerColab();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Gestión de Colaboradores</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../styles.css">

    <style>
      /* ---- Ajustes puntuales que el CSS base no cubre (no se toca styles.css) ---- */

      /* Navbar horizontal */
      .navbar{
        position:sticky;top:0;z-index:50;
        display:flex;align-items:center;justify-content:space-between;
        gap:var(--space-md);
        padding:12px var(--space-lg);
        background:var(--surface);
        border-bottom:1px solid var(--outline-variant);
      }
      .navbar-brand{
        display:flex;align-items:center;gap:10px;
        text-decoration:none;color:var(--on-surface);
      }
      .navbar-brand img{border-radius:6px;}
      .navbar-brand-text p{margin:0;line-height:1.2;}
      .navbar-links{
        display:flex;align-items:center;gap:6px;flex-wrap:wrap;
      }
      .navbar-link{
        display:flex;align-items:center;gap:6px;
        padding:8px 14px;border-radius:var(--radius-lg);
        color:var(--on-surface-variant);text-decoration:none;
        font-size:14px;font-weight:600;
        transition:all .15s ease;
      }
      .navbar-link .material-symbols-outlined{font-size:20px;}
      .navbar-link:hover{background:var(--surface-container-low);color:var(--primary);}
      .navbar-link.active{background:var(--primary);color:var(--on-primary,#fff);}
      .navbar-toggle{display:none;}

      @media (max-width:720px){
        .navbar-links{
          display:none;flex-direction:column;align-items:stretch;
          position:absolute;top:100%;left:0;right:0;
          background:var(--surface);border-bottom:1px solid var(--outline-variant);
          padding:var(--space-sm);gap:4px;
        }
        .navbar-links.open{display:flex;}
        .navbar-toggle{
          display:flex;align-items:center;justify-content:center;
          background:none;border:none;cursor:pointer;color:var(--on-surface);
        }
      }

      .lesson-meta .material-symbols-outlined{font-size:16px;vertical-align:-3px;}
      h1 span{color:var(--primary);}

      /* Mensaje flash */
      .flash{
        display:flex;align-items:center;gap:10px;
        padding:12px 16px;border-radius:var(--radius-lg);
        font-size:14px;font-weight:600;margin-bottom:var(--space-lg);
      }
      .flash.success{background:rgba(46,160,67,0.12);color:#2E7D32;}
      .flash.warning{background:rgba(245,166,35,0.15);color:#B26A00;}
      .flash.error{background:rgba(211,47,47,0.12);color:#C62828;}

      /* Grid de paneles */
      .grid-2{
        display:grid;grid-template-columns:1fr 1fr;gap:var(--space-lg);
      }
      @media (max-width:820px){ .grid-2{grid-template-columns:1fr;} }

      .panel{
        border:1px solid var(--outline-variant);
        border-radius:var(--radius-lg);
        background:var(--surface-container-low);
        overflow:hidden;
      }
      .panel-header{
        padding:14px var(--space-md);
        border-bottom:1px solid var(--outline-variant);
        background:var(--surface);
      }
      .panel-header-flex{display:flex;align-items:center;justify-content:space-between;gap:var(--space-sm);flex-wrap:wrap;}
      .panel-header-flex form{margin:0;}
      .panel-title{font-weight:700;font-size:15px;color:var(--on-surface);}
      .panel-body{padding:var(--space-md);}
      .section-gap{margin-top:var(--space-lg);}

      /* Formularios */
      .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:var(--space-md);}
      .form-group label{font-size:13px;font-weight:600;color:var(--on-surface-variant);}
      .form-group input[type="text"],
      .form-group input[type="date"],
      .form-group input[type="file"],
      .form-group select{
        padding:10px 12px;
        border:1px solid var(--outline-variant);
        border-radius:var(--radius-lg);
        background:var(--surface);
        color:var(--on-surface);
        font-size:14px;font-family:inherit;
      }
      .form-group input:focus,
      .form-group select:focus{outline:none;border-color:var(--primary);}
      .form-hint{font-size:12px;color:var(--on-surface-variant);line-height:1.6;margin:-6px 0 var(--space-md);}
      .form-row{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md);}
      @media (max-width:600px){ .form-row{grid-template-columns:1fr;} }

      .btn-full{width:100%;justify-content:center;}
      .btn-sm{padding:6px 12px;font-size:12px;}
      .btn-accent{background:#5C6BC0;color:#fff;border-color:#5C6BC0;}
      .btn-accent:hover{filter:brightness(1.05);}
      .btn-ghost{background:transparent;color:var(--on-surface-variant);border:1px solid var(--outline-variant);}
      .btn-ghost:hover{border-color:var(--primary);color:var(--primary);}
      .btn-danger{background:#C62828;color:#fff;border-color:#C62828;}
      .btn-danger:hover{filter:brightness(1.05);}

      /* Tabla — ahora se ajusta al ancho del panel, sin necesitar scroll horizontal */
      .table-wrap{overflow-x:visible;width:100%;}
      table{
        width:100%;
        table-layout:fixed;
        border-collapse:collapse;font-size:13px;
        background:var(--surface);border-radius:var(--radius-lg);overflow:hidden;
      }
      thead th{
        background:#00838F;color:#fff;font-weight:700;
        padding:10px 10px;text-align:left;
        white-space:normal;word-break:break-word;
        font-size:12px;
      }
      tbody td{
        padding:8px 10px;border-bottom:1px solid var(--outline-variant);
        white-space:normal;word-break:break-word;
        vertical-align:middle;
      }
      /* Anchos relativos de columnas para que todo quepa sin scroll */
      thead th:nth-child(1), tbody td:nth-child(1){width:6%;}
      thead th:nth-child(2), tbody td:nth-child(2){width:26%;}
      thead th:nth-child(3), tbody td:nth-child(3){width:14%;}
      thead th:nth-child(4), tbody td:nth-child(4){width:14%;}
      thead th:nth-child(5), tbody td:nth-child(5){width:16%;}
      thead th:nth-child(6), tbody td:nth-child(6){width:24%;}

      tbody tr:nth-child(even){background:var(--surface-container-low);}
      .text-muted{color:var(--outline);}
      .name-bold{font-weight:700;color:var(--on-surface);}
      .name-normal{color:var(--on-surface);}
      .empty-state{
        padding:var(--space-lg);text-align:center;color:var(--on-surface-variant);
        border:1px dashed var(--outline-variant);border-radius:var(--radius-lg);
      }
      .badge{
        display:inline-flex;align-items:center;
        padding:4px 10px;border-radius:var(--radius-full,999px);
        font-size:12px;font-weight:700;
        background:var(--surface-container-low);color:var(--on-surface-variant);
        border:1px solid var(--outline-variant);
      }
      /* Colores del badge de PayJoy según estado (0-3), independientes de clase_css */
      .badge[data-estado="0"]{ background:rgba(120,120,128,0.14); color:#5F6368; border-color:rgba(120,120,128,0.3); }
      .badge[data-estado="1"]{ background:rgba(46,160,67,0.14); color:#2E7D32; border-color:rgba(46,160,67,0.35); }
      .badge[data-estado="2"]{ background:rgba(245,166,35,0.16); color:#B26A00; border-color:rgba(245,166,35,0.4); }
      .badge[data-estado="3"]{ background:rgba(211,47,47,0.14); color:#C62828; border-color:rgba(211,47,47,0.35); }

      .actions{display:flex;gap:6px;flex-wrap:wrap;}
      .actions form{display:inline;}

      /* Modal */
      .modal-backdrop{
        display:none;position:fixed;inset:0;z-index:100;
        background:rgba(0,0,0,0.45);
        align-items:center;justify-content:center;padding:var(--space-md);
      }
      .modal-backdrop.open{display:flex;}
      .modal{
        background:var(--surface);border-radius:var(--radius-lg);
        width:100%;max-width:480px;max-height:90vh;overflow-y:auto;
        border:1px solid var(--outline-variant);
      }
      .modal-header{
        display:flex;align-items:center;justify-content:space-between;
        padding:var(--space-md);border-bottom:1px solid var(--outline-variant);
      }
      .modal-title{font-weight:700;font-size:16px;color:var(--on-surface);}
      .modal-close{
        background:none;border:none;font-size:22px;line-height:1;
        cursor:pointer;color:var(--on-surface-variant);
      }
      .modal-close:hover{color:var(--primary);}
      .modal-body{padding:var(--space-md);}
      .modal-footer{display:flex;gap:var(--space-sm);margin-top:var(--space-sm);}
    </style>
</head>
<body>

<!-- ===================== NAVBAR HORIZONTAL ===================== -->
<header class="navbar">
  <a href="validador.php" class="navbar-brand">
    <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" alt="Logo" width="32" height="32">
    <div class="navbar-brand-text">
      <p class="text-headline-sm">Central Cell</p>
      <p class="text-label-sm" style="color:var(--outline)">Colaboradores</p>
    </div>
  </a>

  <button class="navbar-toggle" id="navToggle" type="button" aria-label="Abrir menú">
    <span class="material-symbols-outlined">menu</span>
  </button>

  <nav class="navbar-links" id="navLinks">
    <a href="validador.php" class="navbar-link">
      <span class="material-symbols-outlined">home</span>
      Home
    </a>
    <a href="../../kpis/modulos.html" class="navbar-link">
      <span class="material-symbols-outlined">apps</span>
      Barra de Herramientas
    </a>
  </nav>
</header>

<!-- ===================== MAIN ===================== -->
<div class="container">
  <div class="lesson">
    <div class="lesson-body">

      <span class="eyebrow">Personal</span>
      <h1 class="text-headline-lg" style="margin:6px 0 0">Gestión de <span>Colaboradores</span></h1>
      <div class="lesson-meta">
        <span><span class="material-symbols-outlined">groups</span> <?= date('d/m/Y') ?> &nbsp;·&nbsp; <?= count($colaboradores) ?> registros</span>
      </div>

      <?php if ($mensaje_flash): ?>
          <div class="flash <?= htmlspecialchars($mensaje_flash['tipo']) ?>">
              <?= htmlspecialchars($mensaje_flash['texto']) ?>
          </div>
      <?php endif; ?>

      <!-- Nuevo + Importar -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">1</div>
          <h3 class="step-title text-headline-sm">Alta de colaboradores</h3>
        </div>

        <div class="grid-2">

            <div class="panel">
                <div class="panel-header"><span class="panel-title">
                  <span class="material-symbols-outlined" style="font-size:18px;vertical-align:-4px">person_add</span>
                  Nuevo colaborador</span></div>
                <div class="panel-body">
                    <form method="POST" action="">
                        <input type="hidden" name="accion" value="crear">
                        <div class="form-group">
                            <label for="c_nombre">Nombre completo</label>
                            <input type="text" id="c_nombre" name="nombre" placeholder="Nombre del colaborador" required>
                        </div>
                        <div class="form-group">
                            <label for="c_fecha_ingreso">Fecha de ingreso</label>
                            <input type="date" id="c_fecha_ingreso" name="fecha_ingreso" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">
                          <span class="material-symbols-outlined">check</span>
                          Registrar colaborador
                        </button>
                    </form>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header"><span class="panel-title">
                  <span class="material-symbols-outlined" style="font-size:18px;vertical-align:-4px">upload_file</span>
                  Importar desde Excel</span></div>
                <div class="panel-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="accion" value="importar_excel">
                        <div class="form-group">
                            <label>Archivo .xlsx</label>
                            <input type="file" name="archivo_excel" accept=".xlsx" required>
                        </div>
                        <p class="form-hint">
                            Encabezados en fila 4 · Datos desde fila 5<br>
                            B = NOMBRE &nbsp;·&nbsp; E = PUESTO &nbsp;·&nbsp; H = F. INGRESO<br>
                            Puestos: <em>Apasionado de la telefonía</em>, <em>Encargado de Sucursal</em>
                        </p>
                        <button type="submit" class="btn btn-accent btn-full">
                          <span class="material-symbols-outlined">sync</span>
                          Actualizar desde Excel
                        </button>
                    </form>
                </div>
            </div>

        </div>
      </section>

      <!-- Fusión -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">2</div>
          <h3 class="step-title text-headline-sm">Fusionar colaboradores</h3>
        </div>

        <div class="panel">
            <div class="panel-header"><span class="panel-title">
              <span class="material-symbols-outlined" style="font-size:18px;vertical-align:-4px">merge</span>
              Fusionar colaboradores</span></div>
            <div class="panel-body">
                <form method="POST" action=""
                      onsubmit="return confirm('¿Reasignar todas las garantías del ORIGEN al DESTINO?')">
                    <input type="hidden" name="accion" value="fusionar">
                    <?php
                        $colaboradores_fusion = $colaboradores;
                        usort($colaboradores_fusion, fn($a, $b) => $a['id'] <=> $b['id']);
                    ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="f_origen">Origen (será vaciado)</label>
                            <select id="f_origen" name="id_origen" required>
                                <option value="">— Seleccionar —</option>
                                <?php foreach ($colaboradores_fusion as $c): ?>
                                    <option value="<?= $c['id'] ?>">[<?= $c['id'] ?>] <?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="f_destino">Destino (recibirá garantías)</label>
                            <select id="f_destino" name="id_destino" required>
                                <option value="">— Seleccionar —</option>
                                <?php foreach ($colaboradores_fusion as $c): ?>
                                    <option value="<?= $c['id'] ?>">[<?= $c['id'] ?>] <?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-ghost">
                      <span class="material-symbols-outlined">merge_type</span>
                      Ejecutar fusión
                    </button>
                </form>
            </div>
        </div>
      </section>

      <!-- Tabla -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">3</div>
          <h3 class="step-title text-headline-sm">Lista de colaboradores</h3>
        </div>

        <div class="panel">
            <div class="panel-header panel-header-flex">
                <span class="panel-title">Lista de colaboradores</span>
                <?php if (!empty($colaboradores)): ?>
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="exportar_excel">
                    <button type="submit" class="btn btn-accent btn-sm">
                        <span class="material-symbols-outlined" style="font-size:16px">download</span>
                        Descargar Excel
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <div class="table-wrap">
                <?php if (empty($colaboradores)): ?>
                    <div class="empty-state">No hay colaboradores registrados.</div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Fecha ingreso</th>
                            <th>Capacitación</th>
                            <th>PayJoy</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($colaboradores as $c):
                        $estado       = calcularEstadoPayjoy((int) $c['payjoy_int'], $c['fecha_ingreso']);
                        $nombre_class = $c['tiene_garantias'] > 0 ? 'name-bold' : 'name-normal';
                    ?>
                        <tr>
                            <td data-label="#" class="text-muted"><?= $c['id'] ?></td>
                            <td data-label="Nombre" class="<?= $nombre_class ?>"><?= htmlspecialchars($c['nombre']) ?></td>
                            <td data-label="Ingreso">
                                <?= $c['fecha_ingreso'] ? date('d/m/Y', strtotime($c['fecha_ingreso'])) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td data-label="Capacitación">
                                <?= $c['fecha_capacitacion'] ? date('d/m/Y', strtotime($c['fecha_capacitacion'])) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td data-label="PayJoy">
                                <span class="badge <?= $estado['clase_css'] ?>" data-estado="<?= (int) $c['payjoy_int'] ?>"><?= htmlspecialchars($estado['etiqueta']) ?></span>
                            </td>
                            <td data-label="Acciones">
                                <div class="actions">
                                    <button class="btn btn-ghost btn-sm"
                                            onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($c)) ?>)">
                                        Editar
                                    </button>
                                    <form method="POST" action=""
                                          onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($c['nombre'])) ?>?')">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
      </section>

    </div>
  </div>
</div>

<!-- MODAL EDITAR -->
<div id="modal-editar" class="modal-backdrop" onclick="cerrarModalEditar(event)">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Editar colaborador</span>
            <button class="modal-close" onclick="cerrarModalEditar()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="e_id">

                <div class="form-group">
                    <label for="e_nombre">Nombre</label>
                    <input type="text" id="e_nombre" name="nombre" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="e_fecha_ingreso">Fecha de ingreso</label>
                        <input type="date" id="e_fecha_ingreso" name="fecha_ingreso">
                    </div>
                    <div class="form-group">
                        <label for="e_fecha_capacitacion">Fecha capacitación</label>
                        <input type="date" id="e_fecha_capacitacion" name="fecha_capacitacion">
                    </div>
                </div>

                <div class="form-group">
                    <label for="e_payjoy">Estado PayJoy</label>
                    <select id="e_payjoy" name="payjoy_int">
                        <option value="0">0 — No tiene cuenta</option>
                        <option value="1">1 — Cuenta activa</option>
                        <option value="2">2 — Bloqueada / Inactiva</option>
                        <option value="3">3 — Ya no labora</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" style="flex:1">Guardar cambios</button>
                    <button type="button" class="btn btn-ghost" onclick="cerrarModalEditar()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalEditar(col) {
    document.getElementById('e_id').value                 = col.id;
    document.getElementById('e_nombre').value             = col.nombre;
    document.getElementById('e_fecha_ingreso').value      = col.fecha_ingreso      ?? '';
    document.getElementById('e_fecha_capacitacion').value = col.fecha_capacitacion ?? '';
    document.getElementById('e_payjoy').value             = col.payjoy_int;
    document.getElementById('modal-editar').classList.add('open');
}

function cerrarModalEditar(event) {
    if (!event || event.target === document.getElementById('modal-editar')) {
        document.getElementById('modal-editar').classList.remove('open');
    }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModalEditar(); });

document.querySelector('[name="id_destino"]')?.closest('form')
    ?.addEventListener('submit', function(e) {
        if (document.getElementById('f_origen').value === document.getElementById('f_destino').value) {
            e.preventDefault();
            alert('El colaborador origen y destino no pueden ser el mismo.');
        }
    });
</script>

<script>
  // Control del menú horizontal en móvil
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
</script>

</body>
</html>