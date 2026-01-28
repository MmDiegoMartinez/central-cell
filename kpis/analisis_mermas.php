<?php 
include_once '../funciones.php'; 
$resultados = [];
$inicio = $fin = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inicio = $_POST['fecha_inicio'] ?? '';
    $fin = $_POST['fecha_fin'] ?? '';
    if ($inicio && $fin) {
        try {
            $resultados = obtenerMermasFrecuentes($inicio, $fin);
        } catch (Exception $e) {
            error_log("Error al consultar mermas: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Análisis de Mermas Frecuentes — Innovación Móvil</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f7f7f7; }
h1 { text-align: center; color: #333; }
form {
    display: flex; justify-content: center; gap: 10px; margin-bottom: 20px;
}
input, button {
    padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc;
}
button {
    background: #007bff; color: white; border: none; cursor: pointer;
}
button:hover { background: #0056b3; }
table {
    width: 100%; border-collapse: collapse; background: white; margin-top: 15px;
}
th, td {
    padding: 10px; text-align: left; border-bottom: 1px solid #ddd;
}
th { background: #007bff; color: white; }
tr:hover { background: #f1f1f1; }
.tipo {
    background: #e9ecef; font-weight: bold; text-transform: uppercase;
}
#descargar {
    display: block;
    margin: 15px auto;
    background: #28a745;
}
#descargar:hover { background: #218838; }
</style>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header>
  <nav>
        <div class="nav-inner">
            <!-- Botón hamburguesa -->
            <label class="bar-menu">
                <input type="checkbox" id="menu-check">
                <span class="top"></span>
                <span class="middle"></span>
                <span class="bottom"></span>
            </label>

            <ul id="nav-menu">
                <li>
        <a href="index.php" class="menu-link">
          <span class="logo-container">
            <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />

          </span>
          Home
        </a>
      </li>
            </ul>
        </div>
    </nav>
</header>
<div class="container">
<h1>🔍 Análisis de Mermas Frecuentes</h1>

<form method="POST">
    <label>De: <input type="date" name="fecha_inicio" value="<?=htmlspecialchars($inicio)?>" required></label>
    <label>A: <input type="date" name="fecha_fin" value="<?=htmlspecialchars($fin)?>" required></label>
    <button type="submit">Analizar</button>
</form>

<?php if ($resultados && count($resultados) > 0): ?>
    <button id="descargar">📥 Descargar en Excel</button>
    <table id="tablaMermas">
        <tr><th>Tipo</th><th>Producto (PLOWS)</th><th>Total Mermas</th></tr>
        <?php 
            $actual = ''; 
            foreach ($resultados as $r):
                if ($r['tipo'] !== $actual):
                    $actual = $r['tipo'];
                    echo "<tr class='tipo'><td colspan='3'>{$actual}</td></tr>";
                endif;
                echo "<tr><td></td><td>{$r['plows']}</td><td>{$r['total_mermas']}</td></tr>";
            endforeach;
        ?>
    </table>
<?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <p style="text-align:center; color:#666;">No se encontraron registros en el rango seleccionado.</p>
<?php endif; ?>
</div>
<script>
document.getElementById('descargar')?.addEventListener('click', () => {
    const tabla = document.getElementById('tablaMermas');
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(tabla);
    XLSX.utils.book_append_sheet(wb, ws, "Mermas");
    const nombreArchivo = `Mermas_${'<?=$inicio?>'}_a_${'<?=$fin?>'}.xlsx`;
    XLSX.writeFile(wb, nombreArchivo);
});
</script>
  <script>
    // Controlar menú hamburguesa
    document.getElementById('menu-check').addEventListener('change', function() {
        const menu = document.getElementById('nav-menu');
        if (this.checked) {
            menu.style.opacity = '1';
            menu.style.visibility = 'visible';
            menu.style.pointerEvents = 'auto';
        } else {
            menu.style.opacity = '0';
            menu.style.visibility = 'hidden';
            menu.style.pointerEvents = 'none';
        }
    });
</script>
</body>
</html>
