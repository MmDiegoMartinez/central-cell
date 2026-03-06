<?php
require_once '../funciones.php';
header('Content-Type: application/json');

$term = trim($_GET['q'] ?? '');
if (!$term) {
    echo json_encode([]);
    exit;
}

try {
    $conn = conectarBD();
    $stmt = $conn->prepare("
        SELECT id, marca, modelo 
        FROM modelos 
        WHERE CONCAT(marca,' ',modelo) LIKE :term
        ORDER BY marca, modelo
        LIMIT 10
    ");
    $stmt->execute([':term' => "%$term%"]);
    $results = $stmt->fetchAll();
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode([]);
}
