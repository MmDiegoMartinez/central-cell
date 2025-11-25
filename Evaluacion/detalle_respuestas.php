<?php
require_once("../../funciones.php");

$id_colaborador = intval($_GET['id_colaborador'] ?? 0);

try {
    $conexion = conectarBD();

    // Datos del colaborador
    $stmt = $conexion->prepare("SELECT * FROM colaboradores WHERE id = :id");
    $stmt->execute([':id' => $id_colaborador]);
    $col = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$col) {
        die("Colaborador no encontrado");
    }

    // Respuestas
    $sql = "
    SELECT 
        p.texto_pregunta, 
        o.texto_opcion, 
        o.es_correcta,
        CASE WHEN r.id_opcion IS NOT NULL THEN 1 ELSE 0 END AS seleccionada
    FROM preguntas p
    JOIN opciones_respuesta o ON p.id = o.id_pregunta
    LEFT JOIN respuestas_colaborador r 
        ON r.id_pregunta = p.id AND r.id_colaborador = :id_colaborador AND r.id_opcion = o.id
    ORDER BY p.id, o.id
    ";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':id_colaborador' => $id_colaborador]);
    $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Respuestas</title>
    <style>
        .pregunta { margin: 20px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; }
        .correcta { color: green; font-weight: bold; }
        .incorrecta { color: red; font-weight: bold; }
        .opcion { margin-left: 20px; }
    </style>
</head>
<body>
<h2>Respuestas de <?= htmlspecialchars($col['nombre']) ?></h2>

<?php
$preguntaActual = null;
foreach ($respuestas as $row):
    if ($preguntaActual != $row['texto_pregunta']):
        if ($preguntaActual !== null) echo "</div>";
        echo "<div class='pregunta'><strong>".htmlspecialchars($row['texto_pregunta'])."</strong><br>";
        $preguntaActual = $row['texto_pregunta'];
    endif;

    $clase = "";
    if ($row['es_correcta']) $clase .= "correcta";
    if ($row['seleccionada'] && !$row['es_correcta']) $clase .= " incorrecta";
?>
    <div class="opcion <?= $clase ?>">
        <?= htmlspecialchars($row['texto_opcion']) ?>
        <?php if ($row['seleccionada']): ?> ← Elegida<?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
</body>
</html>
