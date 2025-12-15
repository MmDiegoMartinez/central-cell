<?php
include_once '../funciones.php';  // Ajusta la ruta si es necesario
$conn = conectarBD();

$nombre = $_GET['nombre'] ?? '';

// Consulta si existe colaborador con nombre similar
$sql = "SELECT COUNT(*) FROM colaboradores WHERE nombre LIKE :nombre";
$stmt = $conn->prepare($sql);
$stmt->execute([':nombre' => '%' . $nombre . '%']);
$existe = $stmt->fetchColumn() > 0;

// Responder JSON
header('Content-Type: application/json');
echo json_encode(['existe' => $existe]);
