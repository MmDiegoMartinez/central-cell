<?php
session_start();
include_once '../../funciones.php';

if (!isset($_GET['id'])) {
    die("ID no proporcionado.");
}

$id = intval($_GET['id']);
$conn = conectarBD();

// Verificar si la garantía ya fue validada
$verificar = $conn->prepare("SELECT id_validador FROM garantia WHERE id = ?");
$verificar->execute([$id]);
$garantia = $verificar->fetch(PDO::FETCH_ASSOC);

if (!$garantia) {
    die("Garantía no encontrada.");
}

if ($garantia['id_validador'] !== null) {
    die("⚠️ Esta garantía ya fue validada y no puede eliminarse.");
}

// Eliminar garantía si aún no está validada
$eliminar = $conn->prepare("DELETE FROM garantia WHERE id = ?");
$eliminar->execute([$id]);

// Verificar si hay sesión de validador
if (isset($_SESSION['validador_id'])) {
    header("Location: ../validador/tabla.php");
} else {
    header("Location: tabla.php");
}
exit;
?>
