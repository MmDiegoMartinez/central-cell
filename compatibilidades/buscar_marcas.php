<?php
header('Content-Type: application/json');
require_once '../funciones.php';

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

try {
    $conn = conectarBD();
    
    // Buscar marcas únicas que coincidan con la búsqueda
    $stmt = $conn->prepare("
        SELECT DISTINCT marca 
        FROM modelos 
        WHERE marca LIKE :query 
        ORDER BY marca ASC 
        LIMIT 10
    ");
    
    $stmt->execute([':query' => '%' . $q . '%']);
    $marcas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($marcas);
    
} catch (PDOException $e) {
    error_log("Error en buscar_marcas: " . $e->getMessage());
    echo json_encode([]);
}
?>