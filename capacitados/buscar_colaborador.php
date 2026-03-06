<?php
include_once '../funciones.php';
$conn = conectarBD();

// Obtener parámetro de búsqueda
$nombre = $_GET['term'] ?? '';

// Consulta preparada
$sql = "SELECT id, nombre 
        FROM colaboradores 
        WHERE nombre LIKE :nombre 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->execute([':nombre' => '%' . $nombre . '%']);
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construcción de la respuesta
$respuesta = [];
foreach ($resultado as $fila) {
    $respuesta[] = [
        'label' => $fila['nombre'],
        'value' => $fila['id']
    ];
}

// Envío de respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode($respuesta);
