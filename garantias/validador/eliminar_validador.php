<?php
include_once '../../funciones.php';
session_start();
if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}
if (!isset($_GET['id'])) {
    die("ID de validador no especificado.");
}

$id = intval($_GET['id']);
eliminarValidador($id);

header("Location: Validadores.php");
exit;
