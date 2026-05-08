<?php session_start();
include_once '../../funciones.php';

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}


$validador_id = $_SESSION['validador_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Existencias</title>

    <!-- Hoja de estilos -->
   
    <link rel="stylesheet" href="css.css?v=<?php echo time(); ?>">
</head>
<body>
    

    <div class="container">

        <h1>Gestión de Existencias</h1>
        <p class="subtitle">
            Selecciona una opción para continuar
        </p>

        <div style="display: flex; gap: 20px; margin-top: 40px; flex-wrap: wrap;">
            <a href="../garantias/validador/validador.php" style="flex:1; text-decoration: none;">
                <button class="btn-primary">
                    🏠 Home
                </button>
            </a>

            <a href="subir_existencias.php" style="flex:1; text-decoration: none;">
                <button class="btn-primary">
                    📤 Act. Existencias Full
                </button>
            </a>
            <a href="subir_tel.php" style="flex:1; text-decoration: none;">
                <button class="btn-primary">
                    📤 Act. Existencias Cel
                </button>
            </a>

            <a href="buscador.php" style="flex:1; text-decoration: none;">
                <button class="btn-primary">
                    🔍 Consultar Existencias
                </button>
            </a>
            <a href="catalogo.php" style="flex:1; text-decoration: none;">
                <button class="btn-primary">
                    🔍 Catalogo Telefonos
                </button>
            </a>

        </div>

    </div>

</body>
</html>
