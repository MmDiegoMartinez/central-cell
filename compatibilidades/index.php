<?php
session_start();

if (!isset($_SESSION['validador_id'])) {
    header("Location: ../validador/loginvalidador.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Compatibilidades - Inicio</title>
    <link rel="stylesheet" href="estilos.css">

</head>
<body>
    

<!-- Encabezado -->
<header>
    <h1>Compatibilidades</h1>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="consultar.php">Consultar</a></li>
            <li><a href="crudcompatibilidades.php">Tabla Compatibilidades</a></li>
            <li><a href="ingresar.php">Ingresar Compatibilidades</a></li>
             <li><a href="tabla_compatibilidades.php">Descargar Compatibilidades</a></li>
        </ul>
    </nav>
</header>

<!-- Contenido principal -->
<main>
    <h2>Bienvenido al sistema de compatibilidades</h2>
    <p>Desde aquí puedes consultar y administrar las compatibilidades de modelos y accesorios.</p>
</main>

</body>
</html>
