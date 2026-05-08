<?php
include_once '../../funciones.php';
session_start();
if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("ID no proporcionado.");
}

$id = intval($_GET['id']);
$conn = conectarBD();

// Verificar si la garantía ya tiene número de ajuste
$verificar = $conn->prepare("SELECT numero_ajuste FROM garantia WHERE id = ?");
$verificar->execute([$id]);
$garantia = $verificar->fetch(PDO::FETCH_ASSOC);

if (!$garantia) {
    die("Garantía no encontrada.");
}

if (!empty($garantia['numero_ajuste'])) {
    echo '
        <meta http-equiv="refresh" content="3;url=validador.php">
        <div style="text-align:center; margin-top:50px; font-family:sans-serif;">
            <h2>⚠️ Esta garantía ya fue validada y no puede eliminarse.</h2>
            <p>Redirigiendo en <span id="countdown">3</span> segundos...</p>
        </div>
        <script>
            let seconds = 3;
            const countdown = document.getElementById("countdown");
            setInterval(() => {
                seconds--;
                if (seconds >= 0) countdown.textContent = seconds;
            }, 1000);
        </script>
    ';
    exit;
}

// Eliminar garantía si no tiene número de ajuste
$eliminar = $conn->prepare("DELETE FROM garantia WHERE id = ?");
$eliminar->execute([$id]);

header("Location: validador.php");
exit;
?>