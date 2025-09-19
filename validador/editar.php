<?php
session_start();
include_once '../funciones.php';

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("ID de garantía no especificado.");
}

$id = intval($_GET['id']);
$garantia = obtenerGarantiaPorId($id);

if (!$garantia) {
    die("Garantía no encontrada.");
}

$validador_id = $_SESSION['validador_id'];
date_default_timezone_set('America/Mexico_City');
$hora_actual = date('H:i:s');
$fecha_actual = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estatus = $_POST['estatus'] ?? 'Ajuste Realizado';

    $piezas_validadas = isset($_POST['piezas_validadas']) && $_POST['piezas_validadas'] !== ''
        ? intval($_POST['piezas_validadas'])
        : null;

    $numero_ajuste = isset($_POST['numero_ajuste']) && $_POST['numero_ajuste'] !== ''
        ? intval($_POST['numero_ajuste'])
        : null;

    $anotaciones = trim($_POST['anotaciones_validador'] ?? '');

    $conn = conectarBD();

    // Actualizar estatus, validador, fecha y hora
    $stmt = $conn->prepare("UPDATE garantia SET estatus = ?, id_validador = ?, fecha_validacion = ?, hora = ? WHERE id = ?");
    $stmt->execute([$estatus, $validador_id, $fecha_actual, $hora_actual, $id]);

    // Actualizar piezas_validadas solo si viene un valor mayor o igual a 0
    if (!is_null($piezas_validadas) && $piezas_validadas >= 0) {
        $stmt = $conn->prepare("UPDATE garantia SET piezas_validadas = ? WHERE id = ?");
        $stmt->execute([$piezas_validadas, $id]);
    }

    // Actualizar numero_ajuste solo si viene un valor mayor a 0
    if (!is_null($numero_ajuste) && $numero_ajuste > 0) {
        $stmt = $conn->prepare("UPDATE garantia SET numero_ajuste = ? WHERE id = ?");
        $stmt->execute([$numero_ajuste, $id]);
    }

    // Actualizar anotaciones si no está vacío
    if (!empty($anotaciones)) {
        $stmt = $conn->prepare("UPDATE garantia SET anotaciones_validador = ? WHERE id = ?");
        $stmt->execute([$anotaciones, $id]);
    }

    header("Location: validador.php?actualizado=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Garantía</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        nav {
            background: #ffffff;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        nav a {
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        form {
            background: #ffffff;
            padding: 25px;
            border-radius: 8px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        form p {
            margin: 10px 0;
            font-size: 14px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: #34495e;
        }

        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background: #2980b9;
        }

        textarea {
            resize: vertical;
        }
    </style>
</head>
<body>
<nav style="background:#f1f1f1; padding:10px;">
    <a href="validador.php" style="margin-right:20px;">🏠 Home</a>
</nav>

<h2>Editar Garantía</h2>

<form method="POST">
    <p><strong>PLOWS:</strong> <?= htmlspecialchars($garantia['plows']) ?></p>
    <p><strong>Tipo:</strong> <?= htmlspecialchars($garantia['tipo']) ?></p>
    <p><strong>Causa:</strong> <?= htmlspecialchars($garantia['causa']) ?></p>
    <p><strong>Piezas:</strong> <?= htmlspecialchars($garantia['piezas']) ?></p>
    <p><strong>Sucursal:</strong> <?= htmlspecialchars($garantia['sucursal']) ?></p>
    <p><strong>Colaborador:</strong> <?= htmlspecialchars($garantia['apasionado']) ?></p>
    <p><strong>Fecha de Registro:</strong> <?= htmlspecialchars($garantia['fecha']) ?></p>

    <label for="estatus">Estatus:</label>
    <select name="estatus" required>
        <option value="Recibo por almacen" <?= $garantia['estatus'] === 'Recibo por almacen' ? 'selected' : '' ?>>Recibo por almacen</option>
        <option value="Ajuste Realizado" <?= $garantia['estatus'] === 'Ajuste Realizado' ? 'selected' : '' ?>>Ajuste Realizado</option>
    </select><br><br>

    <label for="piezas_validadas">Piezas Validadas:</label>
    <input type="number" name="piezas_validadas" min="0" value="<?= htmlspecialchars($garantia['piezas_validadas']) ?>"><br><br>

    <label for="numero_ajuste">Número de Ajuste:</label>
    <input type="number" name="numero_ajuste" min="0" value="<?= htmlspecialchars($garantia['numero_ajuste']) ?>"><br><br>

    <label for="anotaciones_validador">Anotación del Validador:</label><br>
    <textarea name="anotaciones_validador" rows="4" cols="50" maxlength="2000"><?= htmlspecialchars($garantia['anotaciones_validador']) ?></textarea><br><br>

    <input type="submit" value="Actualizar garantía">
</form>

</body>
</html>
<?php
session_start();
include_once '../funciones.php';

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("ID de garantía no especificado.");
}

$id = intval($_GET['id']);
$garantia = obtenerGarantiaPorId($id);

if (!$garantia) {
    die("Garantía no encontrada.");
}

$validador_id = $_SESSION['validador_id'];
date_default_timezone_set('America/Mexico_City');
$hora_actual = date('H:i:s');
$fecha_actual = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estatus = $_POST['estatus'] ?? 'Ajuste Realizado';

    $piezas_validadas = isset($_POST['piezas_validadas']) && $_POST['piezas_validadas'] !== ''
        ? intval($_POST['piezas_validadas'])
        : null;

    $numero_ajuste = isset($_POST['numero_ajuste']) && $_POST['numero_ajuste'] !== ''
        ? intval($_POST['numero_ajuste'])
        : null;

    $anotaciones = trim($_POST['anotaciones_validador'] ?? '');

    $conn = conectarBD();

    // Actualizar estatus, validador, fecha y hora
    $stmt = $conn->prepare("UPDATE garantia SET estatus = ?, id_validador = ?, fecha_validacion = ?, hora = ? WHERE id = ?");
    $stmt->execute([$estatus, $validador_id, $fecha_actual, $hora_actual, $id]);

    // Actualizar piezas_validadas solo si viene un valor mayor o igual a 0
    if (!is_null($piezas_validadas) && $piezas_validadas >= 0) {
        $stmt = $conn->prepare("UPDATE garantia SET piezas_validadas = ? WHERE id = ?");
        $stmt->execute([$piezas_validadas, $id]);
    }

    // Actualizar numero_ajuste solo si viene un valor mayor a 0
    if (!is_null($numero_ajuste) && $numero_ajuste > 0) {
        $stmt = $conn->prepare("UPDATE garantia SET numero_ajuste = ? WHERE id = ?");
        $stmt->execute([$numero_ajuste, $id]);
    }

    // Actualizar anotaciones si no está vacío
    if (!empty($anotaciones)) {
        $stmt = $conn->prepare("UPDATE garantia SET anotaciones_validador = ? WHERE id = ?");
        $stmt->execute([$anotaciones, $id]);
    }

    header("Location: validador.php?actualizado=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Garantía</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        nav {
            background: #ffffff;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        nav a {
            text-decoration: none;
            color: #3498db;
            font-weight: bold;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        form {
            background: #ffffff;
            padding: 25px;
            border-radius: 8px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        form p {
            margin: 10px 0;
            font-size: 14px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: #34495e;
        }

        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background: #2980b9;
        }

        textarea {
            resize: vertical;
        }
    </style>
</head>
<body>
<nav style="background:#f1f1f1; padding:10px;">
    <a href="validador.php" style="margin-right:20px;">🏠 Home</a>
</nav>

<h2>Editar Garantía</h2>

<form method="POST">
    <p><strong>PLOWS:</strong> <?= htmlspecialchars($garantia['plows']) ?></p>
    <p><strong>Tipo:</strong> <?= htmlspecialchars($garantia['tipo']) ?></p>
    <p><strong>Causa:</strong> <?= htmlspecialchars($garantia['causa']) ?></p>
    <p><strong>Piezas:</strong> <?= htmlspecialchars($garantia['piezas']) ?></p>
    <p><strong>Sucursal:</strong> <?= htmlspecialchars($garantia['sucursal']) ?></p>
    <p><strong>Colaborador:</strong> <?= htmlspecialchars($garantia['apasionado']) ?></p>
    <p><strong>Fecha de Registro:</strong> <?= htmlspecialchars($garantia['fecha']) ?></p>

    <label for="estatus">Estatus:</label>
    <select name="estatus" required>
        <option value="Recibo por almacen" <?= $garantia['estatus'] === 'Recibo por almacen' ? 'selected' : '' ?>>Recibo por almacen</option>
        <option value="Ajuste Realizado" <?= $garantia['estatus'] === 'Ajuste Realizado' ? 'selected' : '' ?>>Ajuste Realizado</option>
    </select><br><br>

    <label for="piezas_validadas">Piezas Validadas:</label>
    <input type="number" name="piezas_validadas" min="0" value="<?= htmlspecialchars($garantia['piezas_validadas']) ?>"><br><br>

    <label for="numero_ajuste">Número de Ajuste:</label>
    <input type="number" name="numero_ajuste" min="0" value="<?= htmlspecialchars($garantia['numero_ajuste']) ?>"><br><br>

    <label for="anotaciones_validador">Anotación del Validador:</label><br>
    <textarea name="anotaciones_validador" rows="4" cols="50" maxlength="2000"><?= htmlspecialchars($garantia['anotaciones_validador']) ?></textarea><br><br>

    <input type="submit" value="Actualizar garantía">
</form>

</body>
</html>
