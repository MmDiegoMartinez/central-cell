<?php
session_start();
include_once '../funciones.php';

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'usuario' => trim($_POST['usuario'] ?? ''),
        'password' => $_POST['password'] ?? '',
    ];

    if (in_array('', $datos, true)) {
        die("Por favor completa todos los campos.");
    }

    $resultado = crearValidador($datos);

    if ($resultado === true) {
        header('Location: validadores.php?creado=1');
        exit;
    } else {
        die($resultado);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Crear Validador</title>
    <link rel="stylesheet" href="../css.css">
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const titulo = document.getElementById("titulo");
        const texto = "Crear Nuevo Validador";
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
    <h1 id="nombre">Crear Nuevo Validador</h1>
    <ul id="menu">
        <li><a href="validador.php">🏠 Home</a></li>
       
        <li><a href="Validadores.php">⬅️ Atras</a></li>
    </ul>
</nav>

<div class="contenedor">
    <div class="formulario">
        <h1 >Crear Nuevo Validador</h1><br>

        <form method="POST">
            <label for="nombre">Nombre:</label><br>
            <input type="text" id="nombre1" name="nombre" required><br><br>

            <label for="apellido">Apellido:</label><br>
            <input type="text" id="apellido" name="apellido" required><br><br>

            <label for="usuario">Usuario:</label><br>
            <input type="text" id="usuario" name="usuario" required><br><br>

            <label for="password">Contraseña:</label><br>
            <input type="password" id="password" name="password" required><br><br>

            <input type="submit" value="Crear Validador">
        </form>

       
    </div>
</div>

</body>
</html>
