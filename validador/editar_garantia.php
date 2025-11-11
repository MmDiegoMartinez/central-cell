<?php
include_once '../funciones.php';
header('Content-Type: application/json');

try {
    $conn = conectarBD();

    $id = $_POST['id'] ?? null;
    $plows = $_POST['plows'] ?? '';
    $piezas_validadas = $_POST['piezas_validadas'] ?? '';
    $numero_ajuste = $_POST['numero_ajuste'] ?? '';
    $anotaciones_validador = $_POST['anotaciones_validador'] ?? '';

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID no válido']);
        exit;
    }

    $ok = actualizarValidacionGarantia($conn, $id, $plows, $piezas_validadas, $numero_ajuste, $anotaciones_validador);

    echo json_encode(['success' => $ok]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
