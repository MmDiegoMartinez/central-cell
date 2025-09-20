<?php
session_start();
include_once '../funciones.php';

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

$validador_id = $_SESSION['validador_id'];
date_default_timezone_set('America/Mexico_City');
$hora_actual = date('H:i:s');
$fecha_actual = date('Y-m-d');

$piezas_validadas = $_POST['piezas_validadas'] ?? [];
$numero_ajuste    = $_POST['numero_ajuste'] ?? [];
$anotaciones      = $_POST['anotaciones_validador'] ?? [];

$conn = conectarBD();

foreach ($piezas_validadas as $id => $piezasInput) {
    $piezas = ($piezasInput !== '' && $piezasInput !== null) ? intval($piezasInput) : null;
    $ajuste = (isset($numero_ajuste[$id]) && $numero_ajuste[$id] !== '') ? intval($numero_ajuste[$id]) : null;
    $nota   = isset($anotaciones[$id]) ? trim($anotaciones[$id]) : '';

    // Saltar SOLO si TODO está vacío (piezas, ajuste y nota)
    if (($piezas === null) && ($ajuste === null) && $nota === '') {
        continue;
    }

    // Obtener los datos actuales desde la BD
    $stmt_check = $conn->prepare("SELECT piezas_validadas, numero_ajuste, anotaciones_validador, estatus 
                                  FROM garantia WHERE id = ?");
    $stmt_check->execute([$id]);
    $actual = $stmt_check->fetch(PDO::FETCH_ASSOC);

    // Caso 1: hubo ajuste (piezas o ajuste no nulos y > 0)
    if (($piezas !== null && $piezas > 0) || ($ajuste !== null && $ajuste > 0)) {
        $estatus = 'Ajuste Realizado';

        if (
            $actual &&
            (
                intval($actual['piezas_validadas']) !== (int)$piezas ||
                intval($actual['numero_ajuste']) !== (int)$ajuste ||
                trim($actual['anotaciones_validador'] ?? '') !== $nota ||
                trim($actual['estatus'] ?? '') !== $estatus
            )
        ) {
            $stmt = $conn->prepare("UPDATE garantia 
                SET piezas_validadas = ?, 
                    numero_ajuste = ?, 
                    anotaciones_validador = ?, 
                    id_validador = ?, 
                    fecha_validacion = ?, 
                    hora = ?, 
                    estatus = ? 
                WHERE id = ?");
            $stmt->execute([$piezas, $ajuste, $nota, $validador_id, $fecha_actual, $hora_actual, $estatus, $id]);
        }
    }
    // Caso 2: solo anotación, sin ajustes
    elseif ($nota !== '') {
        $estatus = 'Anotado';

        if (
            $actual &&
            (
                trim($actual['anotaciones_validador'] ?? '') !== $nota ||
                trim($actual['estatus'] ?? '') !== $estatus
            )
        ) {
            $stmt = $conn->prepare("UPDATE garantia 
                SET anotaciones_validador = ?, 
                    estatus = ? 
                WHERE id = ?");
            $stmt->execute([$nota, $estatus, $id]);
        }
    }
}

header('Location: validador.php?guardado=1');
exit;
