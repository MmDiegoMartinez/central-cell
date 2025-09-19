<?php
session_start();
include_once '../funciones.php';

if (!isset($_GET['id'])) {
    die("ID no proporcionado.");
}

$id = intval($_GET['id']);
$conn = conectarBD();

// Verificar que la garantía exista
$verificar = $conn->prepare("SELECT id FROM garantia WHERE id = ?");
$verificar->execute([$id]);
$garantia = $verificar->fetch(PDO::FETCH_ASSOC);

if (!$garantia) {
    die("⚠️ Garantía no encontrada.");
}

// En lugar de eliminar, actualizamos anotado = 1
$actualizar = $conn->prepare("UPDATE garantia SET anotado = 1 WHERE id = ?");
$actualizar->execute([$id]);

    header("Location: tabla.php");

exit;
?>
