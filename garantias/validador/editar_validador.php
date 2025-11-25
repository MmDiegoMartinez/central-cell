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
$validador = obtenerValidadorPorId($id);

if (!$validador) {
    die("Validador no encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($password !== '') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        actualizarValidadorConPassword($id, $nombre, $apellido, $usuario, $password_hash);
    } else {
        actualizarValidador($id, $nombre, $apellido, $usuario);
    }

    header("Location: Validadores.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Editar Validador</title>
    <link rel="stylesheet" href="../../css.css">
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const titulo = document.getElementById("titulo");
        const texto = "Editar Validador";
        let i = 0;
        let borrando = false;

        function escribirMaquina() {
            const inicioInmediato = 1;

            if (i === 0 && !borrando) {
                titulo.textContent = texto.charAt(0);
                i = inicioInmediato + 1;
            } else if (!borrando && i <= texto.length) {
                titulo.textContent = texto.slice(0, i);
                i++;
            } else if (borrando && i >= 0) {
                titulo.textContent = texto.slice(0, i);
                i--;
            }

            if (i > texto.length) {
                borrando = true;
                setTimeout(escribirMaquina, 1500);
                return;
            } else if (i === 0 && borrando) {
                borrando = false;
            }

            setTimeout(escribirMaquina, borrando ? 70 : 170);
        }

        escribirMaquina();
    });
    </script>
</head>
<body>
<nav>
    <h1 id="nombre">Editar Validador</h1>
    <ul id="menu">
        <li><a href="validador.php">🏠 Home</a></li>
        <li><a href="Validadores.php">⬅️ Atrás</a></li>
    </ul>
</nav>

<div class="contenedor">
    <div class="formulario">
        <h1>Editar Validador</h1>

        <form method="POST">
            <label for="nombre">Nombre:</label><br>
            <input type="text" id="nombre2" name="nombre" value="<?= htmlspecialchars($validador['nombre']) ?>" required><br><br>

            <label for="apellido">Apellido:</label><br>
            <input type="text" id="apellido" name="apellido" value="<?= htmlspecialchars($validador['apellido']) ?>" required><br><br>

            <label for="usuario">Usuario:</label><br>
            <input type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($validador['usuario']) ?>" required><br><br>

            <label for="password">Contraseña (dejar vacío para no cambiar):</label><br>
            <input type="password" id="password" name="password"><br><br>

            <input type="submit" value="Actualizar">
        </form>
    </div>
</div>

</body>
</html>
