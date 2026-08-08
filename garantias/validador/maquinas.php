<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../../funciones.php';


function tipoValido(string $tipo): bool {
    return in_array($tipo, ['maquinahgl', 'maquinapp', 'userpay', 'passpay'], true);
}

function esTipoMaquina(string $tipo): bool {
    return in_array($tipo, ['maquinahgl', 'maquinapp'], true);
}

function obtenerSucursalesmaquina(): array {
    $pdo = conectarBD();
    $stmt = $pdo->query(
        "SELECT id, nombre, maquinahgl, userpay, passpay, maquinapp
         FROM sucursales
         WHERE estatus = 1
         ORDER BY nombre ASC"
    );
    return $stmt->fetchAll();
}

function guardarCodigo(int $idSucursal, string $tipo, string $codigo): array {
    if (!tipoValido($tipo)) {
        return ['success' => false, 'message' => 'Tipo de máquina inválido.'];
    }

    $codigo = trim($codigo);
    if ($codigo === '') {
        return ['success' => false, 'message' => 'El código no puede estar vacío.'];
    }
    if (mb_strlen($codigo) > 100) {
        return ['success' => false, 'message' => 'El código es demasiado largo (máx. 100 caracteres).'];
    }

    $pdo = conectarBD();

    // Verificar que la sucursal exista
    $check = $pdo->prepare("SELECT id FROM sucursales WHERE id = :id LIMIT 1");
    $check->execute([':id' => $idSucursal]);
    if (!$check->fetch()) {
        return ['success' => false, 'message' => 'La sucursal no existe.'];
    }

    // $tipo ya fue validado contra la whitelist, es seguro usarlo como nombre de columna
    $sql = "UPDATE sucursales SET {$tipo} = :codigo WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':codigo' => $codigo, ':id' => $idSucursal]);

    return ['success' => true, 'message' => 'Código guardado correctamente.'];
}

function eliminarCodigo(int $idSucursal, string $tipo): array {
    if (!tipoValido($tipo)) {
        return ['success' => false, 'message' => 'Tipo de máquina inválido.'];
    }

    $pdo = conectarBD();

    $check = $pdo->prepare("SELECT id FROM sucursales WHERE id = :id LIMIT 1");
    $check->execute([':id' => $idSucursal]);
    if (!$check->fetch()) {
        return ['success' => false, 'message' => 'La sucursal no existe.'];
    }

    $sql = "UPDATE sucursales SET {$tipo} = NULL WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idSucursal]);

    return ['success' => true, 'message' => 'Código eliminado correctamente.'];
}

function traspasarMaquina(string $tipo, array $cadena): array {
    if (!esTipoMaquina($tipo)) {
        return ['success' => false, 'message' => 'Tipo de máquina inválido.'];
    }

    // Normalizar a enteros y validar
    $cadena = array_map('intval', $cadena);
    $cadena = array_values(array_filter($cadena, fn($id) => $id > 0));

    if (count($cadena) < 2) {
        return ['success' => false, 'message' => 'La cadena de traspaso debe tener al menos origen y destino.'];
    }
    if (count($cadena) !== count(array_unique($cadena))) {
        return ['success' => false, 'message' => 'No se puede repetir la misma sucursal dentro de la cadena.'];
    }

    $pdo = conectarBD();

    try {
        $pdo->beginTransaction();

        // Traer todas las filas involucradas y bloquear su lectura dentro de la transacción
        $placeholders = implode(',', array_fill(0, count($cadena), '?'));
        $stmt = $pdo->prepare(
            "SELECT id, {$tipo} AS valor FROM sucursales WHERE id IN ({$placeholders}) FOR UPDATE"
        );
        $stmt->execute($cadena);
        $filas = $stmt->fetchAll();

        $valores = [];
        foreach ($filas as $fila) {
            $valores[(int)$fila['id']] = $fila['valor'];
        }

        // Validar que todas las sucursales de la cadena existan
        foreach ($cadena as $id) {
            if (!array_key_exists($id, $valores)) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "La sucursal con id {$id} no existe."];
            }
        }

        $origen = $cadena[0];
        if ($valores[$origen] === null) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'La sucursal de origen no tiene una máquina asignada de ese tipo.'];
        }

        $updateStmt = $pdo->prepare("UPDATE sucursales SET {$tipo} = :valor WHERE id = :id");

        // El origen se queda sin código
        $updateStmt->execute([':valor' => null, ':id' => $origen]);

        $valorAMover = $valores[$origen];
        $total = count($cadena);

        for ($i = 1; $i < $total; $i++) {
            $idActual = $cadena[$i];
            $esUltimo = ($i === $total - 1);

            // El código que traíamos se instala aquí
            $updateStmt->execute([':valor' => $valorAMover, ':id' => $idActual]);

            if (!$esUltimo) {
                // Lo que tenía este nodo (si algo tenía) sigue viajando al siguiente
                $valorAMover = $valores[$idActual];
            }
            // Si es el último nodo, cualquier valor previo que tuviera ($valores[$idActual])
            // se descarta definitivamente (queda sobrescrito arriba).
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Traspaso realizado correctamente.'];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error en traspasarMaquina: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ocurrió un error al realizar el traspaso.'];
    }
}


require_once '../../tcpdf/tcpdf.php';


require_once '../../tcpdf/tcpdf_barcodes_2d.php';

function generarQrPng(string $texto, int $escalaPx = 8): string {
    $barcode = new TCPDF2DBarcode($texto, 'QRCODE,M');
    return $barcode->getBarcodePngData($escalaPx, $escalaPx, [0, 0, 0]);
}

/* Endpoint de vista previa: entrega el PNG del QR para el texto que el
   usuario está escribiendo en el modal (antes de guardar el registro). */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['generar_qr'])) {
    $valor = trim((string)($_GET['valor'] ?? ''));
    if ($valor === '' || mb_strlen($valor) > 100) {
        http_response_code(400);
        exit;
    }
    header('Content-Type: image/png');
    echo generarQrPng($valor);
    exit;
}

function generarPdfCodigos(string $tipo): void {
    if (!esTipoMaquina($tipo)) {
        http_response_code(400);
        exit('Tipo de máquina inválido.');
    }

    $sucursales = obtenerSucursalesmaquina();
    $conCodigo = array_values(array_filter($sucursales, fn($s) => !empty($s[$tipo])));

    $titulo = $tipo === 'maquinahgl'
        ? 'Códigos de Máquinas Hidrogel (Código de barras)'
        : 'Códigos de Máquinas Protection Pro (Código QR)';

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Panel de Administración');
    $pdf->SetAuthor('Innovación Móvil');
    $pdf->SetTitle($titulo);
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $titulo, 0, 1, 'C');
    $pdf->Ln(6);

    if (empty($conCodigo)) {
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'No hay códigos asignados para este tipo de máquina.', 0, 1, 'C');
    } else {
        foreach ($conCodigo as $s) {
            $codigo = (string)$s[$tipo];

            // Reservar espacio para no cortar la tarjeta entre páginas
            if ($pdf->GetY() > ($tipo === 'maquinahgl' ? 245 : 235)) {
                $pdf->AddPage();
            }

            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, $s['nombre'], 0, 1, 'L');

            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'Código: ' . $codigo, 0, 1, 'L');

            $y = $pdf->GetY() + 2;

            if ($tipo === 'maquinahgl') {
                // Código de barras 1D (Code 128) para Hidrogel
                $pdf->write1DBarcode($codigo, 'C128', 15, $y, 90, 20, 0.4, [
                    'position'    => '',
                    'border'      => false,
                    'padding'     => 2,
                    'fgcolor'     => [0, 0, 0],
                    'bgcolor'     => false,
                    'text'        => true,
                    'font'        => 'helvetica',
                    'fontsize'    => 8,
                    'stretchtext' => 4,
                ], 'N');
                $pdf->SetY($y + 26);
            } else {
                // Código QR (2D) para Protection Pro — se usa generarQrPng(),
                // el MISMO generador que la vista previa del navegador, para
                // que el QR sea idéntico en ambos lados.
                $qrPng = generarQrPng($codigo, 10);
                $pdf->Image('@' . $qrPng, 15, $y, 32, 32, 'PNG');
                $pdf->SetY($y + 36);
            }

            $pdf->Ln(4);
        }
    }

    $nombreArchivo = ($tipo === 'maquinahgl' ? 'codigos_hidrogel_' : 'codigos_protectionpro_') . date('Ymd_His') . '.pdf';
    $pdf->Output($nombreArchivo, 'D');
    exit;
}

/* Endpoint de descarga (petición GET, separada del router AJAX de abajo) */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['descargar_pdf'])) {
    generarPdfCodigos((string)$_GET['descargar_pdf']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');

    $accion = $_POST['accion'];
    $respuesta = ['success' => false, 'message' => 'Acción no reconocida.'];

    try {
        switch ($accion) {
            case 'listar_sucursales':
                $respuesta = ['success' => true, 'data' => obtenerSucursalesmaquina()];
                break;

            case 'guardar_codigo':
                $respuesta = guardarCodigo(
                    (int)($_POST['idSucursal'] ?? 0),
                    (string)($_POST['tipo'] ?? ''),
                    (string)($_POST['codigo'] ?? '')
                );
                break;

            case 'eliminar_codigo':
                $respuesta = eliminarCodigo(
                    (int)($_POST['idSucursal'] ?? 0),
                    (string)($_POST['tipo'] ?? '')
                );
                break;

            case 'traspasar_maquina':
                $cadenaJson = $_POST['cadena'] ?? '[]';
                $cadena = json_decode($cadenaJson, true);
                $respuesta = traspasarMaquina(
                    (string)($_POST['tipo'] ?? ''),
                    is_array($cadena) ? $cadena : []
                );
                break;
        }
    } catch (Throwable $e) {
        $respuesta = ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
    }

    echo json_encode($respuesta);
    exit;
}

/* Carga inicial de datos para renderizar la tabla en el primer request */
$sucursales = obtenerSucursalesmaquina();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administración de Máquinas por Sucursal</title>
<link rel="stylesheet" href="../../styles.css">
<!-- NUEVO: librería para renderizar el código de barras (Hidrogel) en pantalla.
     El QR (Protection Pro) ya NO se genera con una librería JS: se pide como
     imagen al servidor (endpoint generar_qr, más abajo) usando el MISMO motor
     TCPDF que arma el PDF, para que sean visualmente idénticos. -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<style>
    /* =============================================================
       Estilos adicionales exclusivos de este módulo.
       Reutilizan los tokens de color/espaciado/radio de styles.css,
       NO se modifica el archivo externo.
       ============================================================= */

    .page-header{
        display:flex;align-items:center;justify-content:space-between;
        gap:var(--space-md);flex-wrap:wrap;
        margin-bottom:var(--space-xl);
    }
    .page-header h1{margin:0;}
    .page-header p{margin:4px 0 0;color:var(--on-surface-variant);}

    .toolbar{
        display:flex;gap:var(--space-md);flex-wrap:wrap;
        margin-bottom:var(--space-lg);
    }

    /* Tarjeta contenedora de la tabla, reutilizando el look de .lesson */
    .machines-card{
        background:var(--surface-container-lowest);
        border:1px solid rgba(196,197,215,0.4);
        border-radius:var(--radius-xl);
        box-shadow:0 1px 2px rgba(17,28,45,0.04);
        overflow:hidden;
    }

    .table-wrap{
        width:100%;
        max-width:100%;
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
        /* Barra de desplazamiento siempre visible y estilizada, para que
           quede claro que hay más contenido hacia los lados en pantallas
           angostas. */
        scrollbar-width:thin;
        scrollbar-color:var(--outline) var(--surface-container-low);
    }
    .table-wrap::-webkit-scrollbar{height:10px;}
    .table-wrap::-webkit-scrollbar-track{background:var(--surface-container-low);}
    .table-wrap::-webkit-scrollbar-thumb{
        background:var(--outline);border-radius:var(--radius-full);
    }
    .table-wrap::-webkit-scrollbar-thumb:hover{background:var(--primary);}

    table.machines-table{width:100%;border-collapse:collapse;min-width:680px;}
    table.machines-table thead th{
        text-align:left;padding:14px var(--space-lg);
        background:var(--surface-container-low);
        color:var(--on-surface-variant);
        font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;
        border-bottom:1px solid var(--outline-variant);
        white-space:nowrap;
    }
    table.machines-table tbody td{
        padding:14px var(--space-lg);
        border-bottom:1px solid var(--outline-variant);
        font-size:14px;vertical-align:middle;
    }
    table.machines-table tbody tr:last-child td{border-bottom:none;}
    table.machines-table tbody tr:hover{background:var(--surface-container-low);}

    .sucursal-nombre{font-weight:600;color:var(--on-surface);white-space:nowrap;}

    /* Aviso de scroll horizontal, solo visible en pantallas angostas */
    .scroll-hint{
        display:none;align-items:center;gap:6px;
        font-size:12px;color:var(--on-surface-variant);
        padding:8px var(--space-lg);
        background:var(--surface-container-low);
        border-bottom:1px solid var(--outline-variant);
    }
    .scroll-hint .material-symbols-outlined{font-size:16px;}

    /* Chip que representa un código asignado o vacío */
    .code-chip{
        display:inline-flex;align-items:center;gap:6px;
        padding:6px 12px;border-radius:var(--radius-full);
        font-size:13px;font-weight:600;cursor:pointer;
        border:1px solid transparent;
        transition:opacity .15s ease,max-width .15s ease;
        max-width:100%;
        white-space:nowrap;
    }
    .code-chip:hover{opacity:0.85;}
    .code-chip.filled{
        background:var(--secondary-container);
        color:var(--on-secondary-container);
    }
    .code-chip.empty{
        background:var(--surface-container-high);
        color:var(--on-surface-variant);
        border:1px dashed var(--outline-variant);
    }
    .code-chip .material-symbols-outlined{font-size:16px;}

  

    .row-actions{display:flex;gap:8px;flex-wrap:wrap;}
    .icon-btn{
        display:inline-flex;align-items:center;justify-content:center;
        width:36px;height:36px;border-radius:var(--radius-lg);
        background:var(--surface-container-high);
        color:var(--on-surface-variant);
        transition:background .15s ease,color .15s ease,transform .15s ease;
        flex:0 0 auto;
    }
    .icon-btn:hover{background:var(--primary);color:var(--on-primary);transform:translateY(-1px);}
    .icon-btn .material-symbols-outlined{font-size:18px;}

    /* ---------- Modales ---------- */
    .modal-overlay{
        display:none;position:fixed;inset:0;z-index:200;
        background:rgba(17,28,45,0.45);
        align-items:center;justify-content:center;padding:var(--space-md);
    }
    .modal-overlay.show{display:flex;}
    .modal-box{
        background:var(--surface-container-lowest);
        border-radius:var(--radius-xl);
        width:100%;max-width:440px;
        box-shadow:0 12px 40px rgba(17,28,45,0.25);
        max-height:90vh;overflow-y:auto;
        animation:modalIn .18s ease;
    }
    @keyframes modalIn{from{opacity:0;transform:translateY(8px) scale(.98);}to{opacity:1;transform:none;}}
    .modal-head{
        display:flex;align-items:center;justify-content:space-between;
        padding:var(--space-lg) var(--space-lg) 0;
        gap:var(--space-sm);
    }
    .modal-head h3{margin:0;word-break:break-word;}
    .modal-close{color:var(--on-surface-variant);font-size:22px;flex:0 0 auto;}
    .modal-body{padding:var(--space-lg);}
    .modal-foot{
        display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;
        padding:0 var(--space-lg) var(--space-lg);
    }

    .form-group{margin-bottom:var(--space-md);}
    .form-group label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--on-surface-variant);}
    .form-control{
        width:100%;padding:10px 12px;border-radius:var(--radius-lg);
        border:1px solid var(--outline-variant);background:var(--surface);
        font-size:14px;color:var(--on-surface);font-family:inherit;
        box-sizing:border-box;
    }
    .form-control:focus{outline:2px solid var(--primary);outline-offset:1px;}

    .field-error{color:var(--error);font-size:13px;margin-top:6px;display:none;}
    .field-error.show{display:block;}

    /* Breadcrumb de la cadena de traspaso */
    .chain-breadcrumb{
        display:flex;flex-wrap:wrap;align-items:center;gap:6px;
        margin-bottom:var(--space-md);
        padding:var(--space-sm) var(--space-md);
        background:var(--surface-container-low);
        border-radius:var(--radius-lg);
        font-size:13px;
    }
    .chain-item{
        padding:4px 10px;border-radius:var(--radius-full);
        background:var(--primary-container);color:var(--on-primary-container);
        font-weight:600;
        max-width:100%;
        overflow-wrap:break-word;
    }
    .chain-arrow{color:var(--outline);font-size:16px;}

    .conflict-box{
        background:var(--error-container);color:var(--on-error-container);
        border-radius:var(--radius-lg);padding:var(--space-md);
        font-size:13px;margin-bottom:var(--space-md);
        display:flex;gap:10px;align-items:flex-start;
    }
    .conflict-box .material-symbols-outlined{font-size:20px;flex:0 0 auto;}

    /* Toast de notificación */
    #toast-container{
        position:fixed;bottom:var(--space-lg);right:var(--space-lg);
        left:var(--space-lg);
        z-index:300;display:flex;flex-direction:column;align-items:flex-end;gap:8px;
    }
    .toast{
        display:flex;align-items:center;gap:10px;
        padding:12px 16px;border-radius:var(--radius-lg);
        font-size:14px;font-weight:600;
        box-shadow:0 6px 20px rgba(17,28,45,0.2);
        animation:toastIn .18s ease;
        max-width:320px;
        width:100%;
        box-sizing:border-box;
    }
    @keyframes toastIn{from{opacity:0;transform:translateX(16px);}to{opacity:1;transform:none;}}
    .toast.success{background:var(--secondary-container);color:var(--on-secondary-container);}
    .toast.error{background:var(--error-container);color:var(--on-error-container);}

    .empty-state{padding:var(--space-2xl);text-align:center;color:var(--on-surface-variant);}

    /* Navbar horizontal (idéntico al del Comparador de Series) */
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
    min-width:0;
}
.navbar-brand img{border-radius:6px;flex:0 0 auto;}
.navbar-brand-text{min-width:0;}
.navbar-brand-text p{margin:0;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
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

/* =============================================================
   RESPONSIVE — reglas generales para que TODO el contenido fuera
   de la tabla también se adapte en pantallas angostas (tablet/móvil).
   ============================================================= */
.container{
    max-width:1100px;
    width:100%;
    box-sizing:border-box;
}

@media (max-width:960px){
    .container{padding-left:var(--space-md)!important;padding-right:var(--space-md)!important;}
}

@media (max-width:720px){
    .navbar-links{
        display:none;flex-direction:column;align-items:stretch;
        position:absolute;top:100%;left:0;right:0;
        background:var(--surface);border-bottom:1px solid var(--outline-variant);
        padding:var(--space-sm);gap:4px;
        box-shadow:0 8px 20px rgba(17,28,45,0.12);
    }
    .navbar-links.open{display:flex;}
    .navbar-toggle{
        display:flex;align-items:center;justify-content:center;
        background:none;border:none;cursor:pointer;color:var(--on-surface);
        flex:0 0 auto;
    }
    .navbar-brand-text p:first-child{font-size:14px;}
    .navbar-brand-text p:last-child{display:none;}

    .container{padding:var(--space-lg) var(--space-sm)!important;}

    .page-header{
        flex-direction:column;align-items:stretch;
        margin-bottom:var(--space-lg);
    }
    .page-header .btn{width:100%;justify-content:center;}

    .toolbar{flex-direction:column;}
    .toolbar .btn{width:100%;justify-content:center;box-sizing:border-box;}

    .scroll-hint{display:flex;}

    .modal-box{max-width:100%;margin:0;}
    .modal-foot{flex-direction:column-reverse;}
    .modal-foot .btn{width:100%;justify-content:center;}

    #toast-container{left:var(--space-sm);right:var(--space-sm);bottom:var(--space-sm);align-items:stretch;}
    .toast{max-width:100%;}

    .row-actions{gap:6px;}
    .icon-btn{width:32px;height:32px;}
}

@media (max-width:420px){
    .page-header h1{font-size:20px;}
    table.machines-table thead th,
    table.machines-table tbody td{padding:12px var(--space-md);}
}
</style>
</head>
<body>

<div class="main" style="margin-left:0;">
    <header class="navbar">
        <a href="../../garantias/validador/validador.php" class="navbar-brand">
            <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="32" height="32">
            <div class="navbar-brand-text">
                <p class="text-headline-sm">Central Cell</p>
                <p class="text-label-sm" style="color:var(--outline)">Administración de Máquinas</p>
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
                Panel de Herramientas
            </a>
        </nav>
    </header>

    <div class="container" style="padding:var(--space-xl) var(--space-md);">

        <div class="page-header">
            <div>
                <h1 class="text-headline-md">Máquinas asignadas por sucursal</h1>
                <p class="text-body-sm">Administra los códigos Hidrogel y Protection Pro de cada sucursal.</p>
            </div>
            <button class="btn btn-primary" id="btn-abrir-traspaso">
                <span class="material-symbols-outlined">sync_alt</span>
                Traspaso de máquinas
            </button>
        </div>

        <!-- NUEVO: descargas de PDF con los códigos (barras / QR) -->
        <div class="toolbar">
            <a class="btn btn-outline" href="maquinas.php?descargar_pdf=maquinahgl" target="_blank" rel="noopener">
                <span class="material-symbols-outlined">barcode_reader</span>
                PDF Hidrogel (código de barras)
            </a>
            <a class="btn btn-outline" href="maquinas.php?descargar_pdf=maquinapp" target="_blank" rel="noopener">
                <span class="material-symbols-outlined">qr_code_2</span>
                PDF Protection Pro (código QR)
            </a>
        </div>

        <div class="machines-card">
            <div class="scroll-hint">
                <span class="material-symbols-outlined">swipe</span>
                Desliza hacia los lados para ver toda la tabla
            </div>
            <div class="table-wrap">
                <table class="machines-table">
                    <thead>
                        <tr>
                            <th>Sucursal</th>
                            <th>Máquina Hidrogel</th>
                            <th>Máquina Protection Pro</th>
                            <th>Usuario PayJoy</th>
                            <th>Contraseña PayJoy</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-sucursales-body">
                        <!-- Se pinta también en el primer render desde PHP para evitar
                             una tabla vacía antes de que cargue el JS -->
                        <?php if (empty($sucursales)): ?>
                            <tr><td colspan="6" class="empty-state">No hay sucursales registradas.</td></tr>
                        <?php else: foreach ($sucursales as $s): ?>
                            <tr data-id="<?= (int)$s['id'] ?>">
                                <td class="sucursal-nombre"><?= htmlspecialchars($s['nombre']) ?></td>
                                <td>
                                    <?php if ($s['maquinahgl']): ?>
                                        <span class="code-chip filled" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'maquinahgl')">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="code-chip empty" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'maquinahgl')">
                                            <span class="material-symbols-outlined">add_circle</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($s['maquinapp']): ?>
                                        <span class="code-chip filled" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'maquinapp')">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="code-chip empty" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'maquinapp')">
                                            <span class="material-symbols-outlined">add_circle</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($s['userpay']): ?>
                                        <span class="code-chip filled" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'userpay')">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="code-chip empty" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'userpay')">
                                            <span class="material-symbols-outlined">add_circle</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($s['passpay']): ?>
                                        <span class="code-chip filled" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'passpay')">
                                            <span class="material-symbols-outlined">visibility</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="code-chip empty" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'passpay')">
                                            <span class="material-symbols-outlined">add_circle</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <button class="icon-btn" title="Editar Hidrogel" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'maquinahgl')">
                                            <span class="material-symbols-outlined">water_drop</span>
                                        </button>
                                        <button class="icon-btn" title="Editar Protection Pro" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'maquinapp')">
                                            <span class="material-symbols-outlined">shield</span>
                                        </button>
                                        <button class="icon-btn" title="Editar Usuario PayJoy" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'userpay')">
                                            <span class="material-symbols-outlined">person</span>
                                        </button>
                                        <button class="icon-btn" title="Editar Contraseña PayJoy" onclick="abrirModalCodigo(<?= (int)$s['id'] ?>, 'passpay')">
                                            <span class="material-symbols-outlined">key</span>
                                        </button>
                                        <button class="icon-btn" title="Traspasar máquina" onclick="abrirModalTraspaso(<?= (int)$s['id'] ?>)">
                                            <span class="material-symbols-outlined">sync_alt</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL: Agregar / Editar código de máquina
     ========================================================= -->
<div class="modal-overlay" id="modal-codigo">
    <div class="modal-box">
        <div class="modal-head">
            <h3 class="text-headline-sm" id="modal-codigo-titulo">Código de máquina</h3>
            <button class="modal-close" onclick="cerrarModal('modal-codigo')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Sucursal</label>
                <input type="text" class="form-control" id="codigo-sucursal-nombre" disabled>
            </div>
            <div class="form-group">
                <label id="codigo-tipo-label">Código</label>
                <input type="text" class="form-control" id="codigo-input" placeholder="Ej. HGL-00123">
                <div class="field-error" id="codigo-error"></div>
            </div>
            <!-- NUEVO: vista previa en vivo del código de barras (Hidrogel) o QR (Protection Pro) -->
            <div class="form-group" id="codigo-preview-wrap" style="text-align:center;">
                <label>Vista previa</label>
                <div id="codigo-preview" style="display:flex;justify-content:center;align-items:center;min-height:80px;background:var(--surface-container-low);border-radius:var(--radius-lg);padding:var(--space-md);overflow-x:auto;">
                    <span class="text-body-sm" style="color:var(--on-surface-variant);">Escribe un código para ver la vista previa</span>
                </div>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-outline" id="btn-eliminar-codigo" onclick="confirmarEliminarCodigo()">
                <span class="material-symbols-outlined">delete</span> Eliminar
            </button>
            <button class="btn btn-primary" onclick="guardarCodigoActual()">
                <span class="material-symbols-outlined">save</span> Guardar
            </button>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL: Confirmación genérica (para acciones destructivas)
     ========================================================= -->
<div class="modal-overlay" id="modal-confirmar">
    <div class="modal-box" style="max-width:380px;">
        <div class="modal-body" style="padding-top:var(--space-lg);">
            <h3 class="text-headline-sm" id="confirmar-titulo">¿Confirmar acción?</h3>
            <p class="text-body-sm" id="confirmar-texto" style="color:var(--on-surface-variant);"></p>
        </div>
        <div class="modal-foot">
            <button class="btn btn-outline" onclick="cerrarModal('modal-confirmar')">Cancelar</button>
            <button class="btn btn-primary" id="btn-confirmar-aceptar">Confirmar</button>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL: Traspaso de máquinas (flujo con cascada)
     ========================================================= -->
<div class="modal-overlay" id="modal-traspaso">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-head">
            <h3 class="text-headline-sm">Traspaso de máquinas</h3>
            <button class="modal-close" onclick="cerrarModal('modal-traspaso')">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="chain-breadcrumb" id="traspaso-breadcrumb" style="display:none;"></div>
            <div id="traspaso-contenido"><!-- se llena dinámicamente por paso --></div>
        </div>
        <div class="modal-foot" id="traspaso-footer">
            <button class="btn btn-outline" onclick="cerrarModal('modal-traspaso')">Cancelar</button>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script>
/* =====================================================================
   Estado en memoria. sucursalesData se sincroniza tras cada acción
   exitosa vía AJAX, sin recargar la página.
   ===================================================================== */
let sucursalesData = <?php echo json_encode($sucursales, JSON_UNESCAPED_UNICODE); ?>;

const TIPO_LABEL = {
    maquinahgl: 'Hidrogel',
    maquinapp: 'Protection Pro',
    userpay: 'Usuario PayJoy',
    passpay: 'Contraseña PayJoy',
};

/* Tipos que son de solo texto (no manejan código de barras/QR/PDF/traspaso) */
const TIPOS_TEXTO = ['userpay', 'passpay'];

/* ---------------------------------------------------------------------
   Helper genérico para llamar al propio archivo (POST con "accion")
   --------------------------------------------------------------------- */
async function postAccion(accion, datos = {}) {
    const body = new URLSearchParams({ accion, ...datos });
    const resp = await fetch('maquinas.php', { method: 'POST', body });
    return resp.json();
}

/* ---------------------------------------------------------------------
   Notificaciones (toast)
   --------------------------------------------------------------------- */
function mostrarToast(mensaje, tipo = 'success') {
    const cont = document.getElementById('toast-container');
    const el = document.createElement('div');
    el.className = `toast ${tipo}`;
    const icono = tipo === 'success' ? 'check_circle' : 'error';
    el.innerHTML = `<span class="material-symbols-outlined">${icono}</span><span>${mensaje}</span>`;
    cont.appendChild(el);
    setTimeout(() => el.remove(), 4000);
}

function abrirModal(id) { document.getElementById(id).classList.add('show'); }
function cerrarModal(id) { document.getElementById(id).classList.remove('show'); }

/* =====================================================================
   Refresco de la tabla (sin recargar la página)
   ===================================================================== */
async function refrescarSucursales() {
    const r = await postAccion('listar_sucursales');
    if (r.success) {
        sucursalesData = r.data;
        pintarTabla();
    }
}

/* Etiqueta de "Ver X" para cada tipo, mostrada en el chip antes de revelar */
const CHIP_TEXTO_VER = { maquinahgl: '', maquinapp: '', userpay: '', passpay: '' };
const CHIP_TEXTO_AGREGAR = { maquinahgl: '', maquinapp: '', userpay: '', passpay: '' };

function chipHtml(sucursal, tipo) {
    const valor = sucursal[tipo];

    if (!valor) {
        // Vacío: el clic abre el modal para agregarlo
        return `<span class="code-chip empty" onclick="abrirModalCodigo(${sucursal.id}, '${tipo}')">
                    <span class="material-symbols-outlined">add_circle</span>${CHIP_TEXTO_AGREGAR[tipo]}
                </span>`;
    }

    // Con valor: chip compacto que siempre dice "Ver..." (nunca muestra el
    // valor real en la tabla); el clic abre el modal para ver/editarlo.
    return `<span class="code-chip filled" onclick="abrirModalCodigo(${sucursal.id}, '${tipo}')">
                <span class="material-symbols-outlined">visibility</span>${CHIP_TEXTO_VER[tipo]}
            </span>`;
}

function escapeHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

function pintarTabla() {
    const tbody = document.getElementById('tabla-sucursales-body');
    if (!sucursalesData.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="empty-state">No hay sucursales registradas.</td></tr>`;
        return;
    }
    tbody.innerHTML = sucursalesData.map(s => `
        <tr data-id="${s.id}">
            <td class="sucursal-nombre">${escapeHtml(s.nombre)}</td>
            <td>${chipHtml(s, 'maquinahgl')}</td>
            <td>${chipHtml(s, 'maquinapp')}</td>
            <td>${chipHtml(s, 'userpay')}</td>
            <td>${chipHtml(s, 'passpay')}</td>
            <td>
                <div class="row-actions">
                    <button class="icon-btn" title="Editar Hidrogel" onclick="abrirModalCodigo(${s.id}, 'maquinahgl')">
                        <span class="material-symbols-outlined">water_drop</span>
                    </button>
                    <button class="icon-btn" title="Editar Protection Pro" onclick="abrirModalCodigo(${s.id}, 'maquinapp')">
                        <span class="material-symbols-outlined">shield</span>
                    </button>
                    <button class="icon-btn" title="Editar Usuario PayJoy" onclick="abrirModalCodigo(${s.id}, 'userpay')">
                        <span class="material-symbols-outlined">person</span>
                    </button>
                    <button class="icon-btn" title="Editar Contraseña PayJoy" onclick="abrirModalCodigo(${s.id}, 'passpay')">
                        <span class="material-symbols-outlined">key</span>
                    </button>
                    <button class="icon-btn" title="Traspasar máquina" onclick="abrirModalTraspaso(${s.id})">
                        <span class="material-symbols-outlined">sync_alt</span>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

/* =====================================================================
   Modal: Agregar / Editar código
   ===================================================================== */
let codigoActual = { idSucursal: null, tipo: null };

function abrirModalCodigo(idSucursal, tipo) {
    const sucursal = sucursalesData.find(s => s.id === idSucursal);
    if (!sucursal) return;

    codigoActual = { idSucursal, tipo };
    const esTexto = TIPOS_TEXTO.includes(tipo);
    const etiqueta = esTexto ? TIPO_LABEL[tipo] : `Código ${TIPO_LABEL[tipo]}`;

    document.getElementById('codigo-sucursal-nombre').value = sucursal.nombre;
    document.getElementById('codigo-tipo-label').textContent = etiqueta;

    const input = document.getElementById('codigo-input');
    input.value = sucursal[tipo] || '';
    input.type = (tipo === 'passpay') ? 'password' : 'text';
    input.placeholder = tipo === 'userpay' ? 'Ej. usuario_payjoy'
        : tipo === 'passpay' ? 'Contraseña PayJoy'
        : 'Ej. HGL-00123';

    document.getElementById('codigo-error').classList.remove('show');
    document.getElementById('modal-codigo-titulo').textContent =
        sucursal[tipo] ? `Editar ${etiqueta}` : `Agregar ${etiqueta}`;

    // Solo mostrar "Eliminar" si ya existe un valor
    document.getElementById('btn-eliminar-codigo').style.display = sucursal[tipo] ? 'inline-flex' : 'none';

    // userpay/passpay son solo texto: no tienen vista previa de código de barras/QR
    document.getElementById('codigo-preview-wrap').style.display = esTexto ? 'none' : 'block';

    abrirModal('modal-codigo');
    if (!esTexto) renderizarVistaPrevia();
}

/* =====================================================================
   NUEVO: Vista previa en vivo de código de barras (Hidrogel) / QR (Protection Pro)
   Solo lectura visual en el modal — no interfiere con guardarCodigoActual().
   ===================================================================== */
let previewQrDebounce = null;

function renderizarVistaPrevia() {
    const cont = document.getElementById('codigo-preview');
    const valor = document.getElementById('codigo-input').value.trim();

    if (!valor) {
        cont.innerHTML = '<span class="text-body-sm" style="color:var(--on-surface-variant);">Escribe un código para ver la vista previa</span>';
        return;
    }

    if (codigoActual.tipo === 'maquinahgl') {
        cont.innerHTML = '<svg id="codigo-preview-svg"></svg>';
        try {
            JsBarcode('#codigo-preview-svg', valor, {
                format: 'CODE128',
                width: 2,
                height: 60,
                displayValue: true,
                fontSize: 14,
                margin: 8,
            });
        } catch (e) {
            cont.innerHTML = '<span class="text-body-sm" style="color:var(--error);">No se pudo generar el código de barras.</span>';
        }
    } else {
        // NUEVO: el QR ya no se dibuja con una librería JS. Se pide como
        // imagen al servidor (mismo generador TCPDF que arma el PDF, ver
        // generarQrPng() / endpoint ?generar_qr=1) para que sea idéntico
        // al que sale en el PDF. Con debounce para no pedir una imagen
        // por cada tecla presionada.
        clearTimeout(previewQrDebounce);
        previewQrDebounce = setTimeout(() => {
            const url = 'maquinas.php?generar_qr=1&valor=' + encodeURIComponent(valor);
            cont.innerHTML = `<img src="${url}" alt="Código QR" style="max-width:140px;max-height:140px;">`;
        }, 300);
    }
}

document.getElementById('codigo-input').addEventListener('input', renderizarVistaPrevia);

async function guardarCodigoActual() {
    const codigo = document.getElementById('codigo-input').value.trim();
    const errorEl = document.getElementById('codigo-error');
    errorEl.classList.remove('show');

    if (!codigo) {
        errorEl.textContent = 'El código no puede estar vacío.';
        errorEl.classList.add('show');
        return;
    }

    const r = await postAccion('guardar_codigo', {
        idSucursal: codigoActual.idSucursal,
        tipo: codigoActual.tipo,
        codigo,
    });

    if (r.success) {
        cerrarModal('modal-codigo');
        mostrarToast(r.message, 'success');
        await refrescarSucursales();
    } else {
        errorEl.textContent = r.message;
        errorEl.classList.add('show');
    }
}

function confirmarEliminarCodigo() {
    const sucursal = sucursalesData.find(s => s.id === codigoActual.idSucursal);
    document.getElementById('confirmar-titulo').textContent = 'Eliminar código';
    document.getElementById('confirmar-texto').textContent =
        `¿Seguro que deseas eliminar el código ${TIPO_LABEL[codigoActual.tipo]} de "${sucursal.nombre}"? Esta acción no se puede deshacer.`;

    document.getElementById('btn-confirmar-aceptar').onclick = async () => {
        const r = await postAccion('eliminar_codigo', {
            idSucursal: codigoActual.idSucursal,
            tipo: codigoActual.tipo,
        });
        cerrarModal('modal-confirmar');
        if (r.success) {
            cerrarModal('modal-codigo');
            mostrarToast(r.message, 'success');
            await refrescarSucursales();
        } else {
            mostrarToast(r.message, 'error');
        }
    };

    abrirModal('modal-confirmar');
}

/* =====================================================================
   Módulo de Traspaso de máquinas (con cascada)
   -----------------------------------------------------------------
   Estado: { tipo, cadena: [idSucursal, ...] }
   La cascada se resuelve completamente en el cliente usando
   sucursalesData (ya cargado), y solo se llama al servidor UNA vez,
   al confirmar el traspaso completo -> traspasarMaquina() ejecuta
   todo en una sola transacción.
   ===================================================================== */
let traspaso = { tipo: null, cadena: [] };

function abrirModalTraspaso(idSucursalInicial = null) {
    traspaso = { tipo: null, cadena: idSucursalInicial ? [idSucursalInicial] : [] };
    abrirModal('modal-traspaso');
    if (idSucursalInicial) {
        renderPasoTipo();
    } else {
        renderPasoOrigen();
    }
}

function nombreSucursal(id) {
    const s = sucursalesData.find(s => s.id === id);
    return s ? s.nombre : '—';
}

function actualizarBreadcrumb() {
    const cont = document.getElementById('traspaso-breadcrumb');
    if (!traspaso.cadena.length) { cont.style.display = 'none'; return; }
    cont.style.display = 'flex';
    cont.innerHTML = traspaso.cadena.map((id, i) =>
        (i > 0 ? '<span class="chain-arrow material-symbols-outlined">arrow_forward</span>' : '') +
        `<span class="chain-item">${escapeHtml(nombreSucursal(id))}</span>`
    ).join('');
}

function opcionesSucursales(excluirIds = []) {
    return sucursalesData
        .filter(s => !excluirIds.includes(s.id))
        .map(s => `<option value="${s.id}">${escapeHtml(s.nombre)}</option>`)
        .join('');
}

// Paso 1: elegir sucursal de origen
function renderPasoOrigen() {
    actualizarBreadcrumb();
    const cont = document.getElementById('traspaso-contenido');
    cont.innerHTML = `
        <div class="form-group">
            <label>Sucursal de origen</label>
            <select class="form-control" id="select-origen">
                <option value="">Selecciona una sucursal…</option>
                ${opcionesSucursales()}
            </select>
        </div>
    `;
    document.getElementById('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" onclick="cerrarModal('modal-traspaso')">Cancelar</button>
        <button class="btn btn-primary" onclick="confirmarOrigen()">Continuar</button>
    `;
}

function confirmarOrigen() {
    const id = parseInt(document.getElementById('select-origen').value, 10);
    if (!id) { mostrarToast('Selecciona una sucursal de origen.', 'error'); return; }
    traspaso.cadena = [id];
    renderPasoTipo();
}

// Paso 2: elegir tipo de máquina (solo tipos que el origen sí tiene)
function renderPasoTipo() {
    actualizarBreadcrumb();
    const origen = sucursalesData.find(s => s.id === traspaso.cadena[0]);
    const cont = document.getElementById('traspaso-contenido');

    const tiposDisponibles = ['maquinahgl', 'maquinapp'].filter(t => origen[t]);

    if (!tiposDisponibles.length) {
        cont.innerHTML = `<div class="conflict-box">
            <span class="material-symbols-outlined">warning</span>
            <span>"${escapeHtml(origen.nombre)}" no tiene ninguna máquina asignada para traspasar.</span>
        </div>`;
        document.getElementById('traspaso-footer').innerHTML = `
            <button class="btn btn-outline" onclick="renderPasoOrigen()">Volver</button>
        `;
        return;
    }

    cont.innerHTML = `
        <div class="form-group">
            <label>Tipo de máquina a traspasar</label>
            <select class="form-control" id="select-tipo">
                ${tiposDisponibles.map(t => `<option value="${t}">${TIPO_LABEL[t]} (${origen[t]})</option>`).join('')}
            </select>
        </div>
    `;
    document.getElementById('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" onclick="renderPasoOrigen()">Volver</button>
        <button class="btn btn-primary" onclick="confirmarTipo()">Continuar</button>
    `;
}

function confirmarTipo() {
    traspaso.tipo = document.getElementById('select-tipo').value;
    renderPasoDestino();
}

// Paso 3: elegir sucursal destino (excluyendo las ya usadas en la cadena)
function renderPasoDestino() {
    actualizarBreadcrumb();
    const cont = document.getElementById('traspaso-contenido');
    cont.innerHTML = `
        <div class="form-group">
            <label>Sucursal destino</label>
            <select class="form-control" id="select-destino">
                <option value="">Selecciona una sucursal…</option>
                ${opcionesSucursales(traspaso.cadena)}
            </select>
        </div>
    `;
    document.getElementById('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" onclick="volverAPasoAnterior()">Volver</button>
        <button class="btn btn-primary" onclick="confirmarDestino()">Continuar</button>
    `;
}

function volverAPasoAnterior() {
    if (traspaso.cadena.length <= 1) {
        renderPasoTipo();
    } else {
        // Volver a elegir el destino anterior: se quita el último de la cadena
        traspaso.cadena.pop();
        renderPasoDestino();
    }
}

function confirmarDestino() {
    const idDestino = parseInt(document.getElementById('select-destino').value, 10);
    if (!idDestino) { mostrarToast('Selecciona una sucursal destino.', 'error'); return; }

    traspaso.cadena.push(idDestino);
    const destino = sucursalesData.find(s => s.id === idDestino);

    if (!destino[traspaso.tipo]) {
        // Destino vacío -> la cadena queda cerrada, mostrar resumen final
        renderResumenFinal();
    } else {
        // Destino ocupado -> preguntar qué hacer con la máquina que ya tenía
        renderConflictoDestino(destino);
    }
}

// Sub-paso: la sucursal destino ya tiene máquina -> decidir reubicar o eliminar
function renderConflictoDestino(destino) {
    actualizarBreadcrumb();
    const cont = document.getElementById('traspaso-contenido');
    cont.innerHTML = `
        <div class="conflict-box">
            <span class="material-symbols-outlined">warning</span>
            <span>
                "<b>${escapeHtml(destino.nombre)}</b>" ya tiene una máquina ${TIPO_LABEL[traspaso.tipo]}
                (código <b>${escapeHtml(destino[traspaso.tipo])}</b>).
                ¿Qué deseas hacer con ella?
            </span>
        </div>
        <div class="form-group">
            <label>Mover esa máquina a otra sucursal</label>
            <select class="form-control" id="select-reubicar">
                <option value="">— Elegir sucursal destino —</option>
                ${opcionesSucursales(traspaso.cadena)}
            </select>
        </div>
        <div style="text-align:center;margin:var(--space-sm) 0;color:var(--on-surface-variant);font-size:13px;">o bien</div>
        <button class="btn btn-outline btn-block" onclick="eliminarMaquinaDesplazada('${escapeHtml(destino.nombre)}')">
            <span class="material-symbols-outlined">delete</span>
            Eliminar definitivamente esa máquina
        </button>
    `;
    document.getElementById('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" onclick="volverAPasoAnterior()">Volver</button>
        <button class="btn btn-primary" onclick="continuarReubicacion()">Continuar</button>
    `;
}

function continuarReubicacion() {
    const idSiguiente = parseInt(document.getElementById('select-reubicar').value, 10);
    if (!idSiguiente) { mostrarToast('Elige una sucursal o usa la opción de eliminar.', 'error'); return; }

    const siguiente = sucursalesData.find(s => s.id === idSiguiente);
    traspaso.cadena.push(idSiguiente);

    if (!siguiente[traspaso.tipo]) {
        renderResumenFinal();
    } else {
        renderConflictoDestino(siguiente);
    }
}

function eliminarMaquinaDesplazada(nombreSucursalDestino) {
    // La cadena ya incluye a la sucursal cuya máquina se descarta;
    // no se agrega ningún destino más -> se pierde ese código.
    renderResumenFinal(true, nombreSucursalDestino);
}

// Paso final: resumen y confirmación
function renderResumenFinal(seElimina = false, nombreEliminado = null) {
    actualizarBreadcrumb();
    const cont = document.getElementById('traspaso-contenido');

    let resumenHtml = `<p class="text-body-sm">Se realizarán los siguientes movimientos de <b>${TIPO_LABEL[traspaso.tipo]}</b>:</p><ul class="step-list check">`;
    for (let i = 1; i < traspaso.cadena.length; i++) {
        resumenHtml += `<li>${escapeHtml(nombreSucursal(traspaso.cadena[i-1]))} → ${escapeHtml(nombreSucursal(traspaso.cadena[i]))}</li>`;
    }
    resumenHtml += `</ul>`;

    if (seElimina) {
        resumenHtml += `<div class="conflict-box">
            <span class="material-symbols-outlined">delete_forever</span>
            <span>La máquina que tenía "<b>${escapeHtml(nombreEliminado)}</b>" quedará eliminada definitivamente.</span>
        </div>`;
    }

    cont.innerHTML = resumenHtml;
    document.getElementById('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" onclick="volverAPasoAnterior()">Volver</button>
        <button class="btn btn-primary" onclick="ejecutarTraspasoFinal()">
            <span class="material-symbols-outlined">check</span> Confirmar traspaso
        </button>
    `;
}

async function ejecutarTraspasoFinal() {
    const r = await postAccion('traspasar_maquina', {
        tipo: traspaso.tipo,
        cadena: JSON.stringify(traspaso.cadena),
    });

    if (r.success) {
        cerrarModal('modal-traspaso');
        mostrarToast(r.message, 'success');
        await refrescarSucursales();
    } else {
        mostrarToast(r.message, 'error');
    }
}

/* Cerrar modales al hacer click en el overlay (fuera de la caja) */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('show');
    });
});

document.getElementById('btn-abrir-traspaso').addEventListener('click', () => abrirModalTraspaso());
</script>
<script>
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
</script>
</body>
</html>