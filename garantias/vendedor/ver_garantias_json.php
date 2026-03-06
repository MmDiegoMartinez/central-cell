<?php
include_once '../../funciones.php';
header('Content-Type: application/json');

$garantias = verTabla(); // Devuelve un array
echo json_encode($garantias);
?>
