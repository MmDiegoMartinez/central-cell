<?php
session_start();

if (!isset($_SESSION['validador_id'])) {
    header("Location: ../validador/loginvalidador.php");
    exit;
}
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../funciones.php';
$mensaje = "";

// Insertar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $id = intval($_POST['id'] ?? 0);

    try {
        if ($accion === 'insertar') {
            insertarModelo($marca, $modelo);
            $mensaje = "✅ Modelo agregado con éxito.";
        } elseif ($accion === 'actualizar') {
            actualizarModelo($id, $marca, $modelo);
            $mensaje = "✅ Modelo actualizado con éxito.";
        } elseif ($accion === 'eliminar') {
            eliminarModelo($id);
            $mensaje = "✅ Modelo eliminado con éxito.";
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}

$modelos = obtenerModelos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Modelos</title>
    <link rel="stylesheet" href="estilos.css">


</head>
<body>
    <header>
    <div class="logo">Agregar modelos</div>
    <nav>
        <ul>
            <li><a href="index.php">Inicio 🏠</a></li>
            <li><a href="crudcompatibilidades.php">Atras 🔙</a></li>
            
        </ul>
    </nav>
</header>
<h1>CRUD de Modelos</h1>

<?php if ($mensaje): ?>
<p><?= htmlspecialchars($mensaje) ?></p>
<?php endif; ?>

<h2>Agregar Modelo</h2>
<form method="post">
    <input type="hidden" name="accion" value="insertar">
    Marca: <input type="text" name="marca" required>
    Modelo: <input type="text" name="modelo" required>
    <button type="submit">Agregar</button>
</form>

<h2>Lista de Modelos</h2>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Marca</th>
        <th>Modelo</th>
        <th>Acciones</th>
    </tr>
    <?php foreach ($modelos as $m): ?>
    <tr>
        <td><?= $m['id'] ?></td>
        <td><?= htmlspecialchars($m['marca']) ?></td>
        <td><?= htmlspecialchars($m['modelo']) ?></td>
        <td>
            <form style="display:inline;" method="post">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button type="submit" onclick="return confirm('Eliminar este modelo?')">Eliminar</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
