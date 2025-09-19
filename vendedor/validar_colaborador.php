<?php
include_once '../funciones.php';

$conn = conectarBD();

$nombre = trim($_GET['nombre'] ?? '');

$sql = "SELECT COUNT(*) FROM colaboradores WHERE nombre = :nombre";
$stmt = $conn->prepare($sql);
$stmt->execute([':nombre' => $nombre]);
$existe = $stmt->fetchColumn() > 0;

header('Content-Type: application/json');
echo json_encode(['existe' => $existe]);
