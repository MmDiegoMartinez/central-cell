<?php
include_once '../../funciones.php';
$conn = conectarBD();

$nombre = $_GET['term'] ?? '';

$sql = "SELECT id, nombre FROM colaboradores WHERE nombre LIKE :nombre LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->execute([':nombre' => '%' . $nombre . '%']);
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

$respuesta = [];
foreach ($resultado as $fila) {
    $respuesta[] = [
        'label' => $fila['nombre'],  // Texto visible en la lista
        'value' => $fila['id']       // Valor oculto que se usará para guardar el ID
    ];
}

header('Content-Type: application/json');
echo json_encode($respuesta);
