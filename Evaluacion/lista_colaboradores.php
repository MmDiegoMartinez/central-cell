
<?php
session_start();

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}


error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../funciones.php");

// Crear la conexión usando PDO
try {
    $conexion = conectarBD();
} catch (Exception $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}

// Consulta
$sql = "
SELECT 
    c.id AS id_colaborador,
    c.nombre,
    COUNT(r.id) AS total_respuestas,
    SUM(CASE WHEN o.es_correcta = 1 AND o.id = r.id_opcion THEN 1 ELSE 0 END) AS correctas
FROM colaboradores c
JOIN respuestas_colaborador r ON c.id = r.id_colaborador
JOIN opciones_respuesta o ON r.id_opcion = o.id
GROUP BY c.id, c.nombre
ORDER BY c.nombre ASC
";

try {
    $stmt = $conexion->query($sql);
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("❌ Error al ejecutar la consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados de Examen</title>
    <style>
        table { border-collapse: collapse; width: 80%; margin: 20px auto; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background: #f0f0f0; }
        a.btn { padding: 5px 10px; background: #007BFF; color: #fff; text-decoration: none; border-radius: 4px; }
        a.btn:hover { background: #0056b3; }
    </style>
    <link rel="stylesheet" href="css/css.css">
</head>
<body>
<nav>
        <h1 id="titulo">Innovación móvil Capacitación</h1>
        <input id="checkbox2" type="checkbox">
        <label class="toggle toggle2" for="checkbox2">
            <div id="bar4" class="bars"></div>
            <div id="bar5" class="bars"></div>
            <div id="bar6" class="bars"></div>
        </label>
        <ul id="menu">
            <li><a href="../garantias/validador/validador.php">Atras</a></li>
            <li><a href="index.php">Inicio Capacitados</a></li>
            <li><a href="material.php" >Material</a></li>
            <li><a href="examen.php">Cuestionario</a></li>
            <li><a href="../capacitados/capa.php">Fechas</a></li>
        </ul>
    </nav>
    <p>.</p><br><br><br><br>
<h2 style="text-align:center;">Lista de Colaboradores con Examen</h2>
<table>
    <tr>
        <th>Nombre</th>
        <th>Calificación</th>
        <th>Ver Respuestas</th>
    </tr>
    <?php foreach($colaboradores as $row): 
        $total = $row['total_respuestas'];
        $correctas = $row['correctas'];
        $calificacion = $total > 0 ? round(($correctas / $total) * 10, 2) : 0;
    ?>
    <tr>
        <td><?= htmlspecialchars($row['nombre']) ?></td>
        <td><?= $calificacion ?> / 10</td>
        <td><a class="btn" href="detalle_respuestas.php?id_colaborador=<?= $row['id_colaborador'] ?>">Ver</a></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
