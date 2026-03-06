<?php
require_once '../funciones.php';
$conn = conectarBD();

// Consulta para obtener todas las compatibilidades agrupadas por modelo principal y tipo
$sql = "
    SELECT 
        c.tipo,
        CONCAT(m1.marca, ' ', m1.modelo) AS modelo_principal,
        GROUP_CONCAT(DISTINCT CONCAT(m2.marca, ' ', m2.modelo) ORDER BY m2.marca, m2.modelo SEPARATOR ', ') AS modelos_compatibles
    FROM compatibilidades c
    INNER JOIN modelos m1 ON c.modelo_id = m1.id
    INNER JOIN modelos m2 ON c.compatible_id = m2.id
    GROUP BY c.tipo, m1.id
    ORDER BY m1.marca ASC, m1.modelo ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$compatibilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
    <meta charset="UTF-8">
    <title>Tabla de Compatibilidades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
</head>
<body>
    
<header class="main-header">
    <!-- Checkbox PRIMERO, antes de todo -->
    <input type="checkbox" id="check">
    
    <div class="header-top">
        <h1 class="titulo">
            <span class="logo-circle">
                <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" />
            </span>  
               Descargar Compatibilidades
        </h1>

        <!-- Botón hamburguesa animado -->
        <label class="bar" for="check">
            <span class="top"></span>
            <span class="middle"></span>
            <span class="bottom"></span>
        </label>
    </div>

    <nav id="menu">
        <ul>
            <li><a href="consultar.php">Consultar Compatibilidades 🔍</a></li>
        </ul>
    </nav>
</header>
    

    <a href="exportar_compatibilidades.php" class="btn-exportar">📥 Descargar en Excel</a>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-primary">
                <tr>
                    <th>Tipo</th>
                    <th>Modelo Principal</th>
                    <th>Modelos Compatibles</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($compatibilidades): ?>
                    <?php foreach ($compatibilidades as $c): ?>
                        <tr>
                            <td><?= ucfirst(htmlspecialchars($c['tipo'])) ?></td>
                            <td><?= htmlspecialchars($c['modelo_principal']) ?></td>
                            <td><?= htmlspecialchars($c['modelos_compatibles']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">No hay compatibilidades registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
