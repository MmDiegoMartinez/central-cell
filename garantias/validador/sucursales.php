<?php
include_once '../../funciones.php';
$sucursales = obtenerSucursalesActivas();
$mensaje = "";

// Agregar nueva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $nombre = trim($_POST['nombre']);
    $meta = floatval($_POST['metaIM']);
    if (agregarSucursal($nombre, $meta)) {
        $mensaje = "✅ Sucursal agregada correctamente.";
        $sucursales = obtenerSucursalesActivas();
    } else {
        $mensaje = "❌ Error al agregar la sucursal.";
    }
}

// Editar meta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id = intval($_POST['id']);
    $meta = floatval($_POST['metaIM']);
    if (actualizarMetaSucursal($id, $meta)) {
        $mensaje = "✅ Meta actualizada correctamente.";
        $sucursales = obtenerSucursalesActivas();
    } else {
        $mensaje = "❌ Error al actualizar meta.";
    }
}

// Eliminar lógicamente
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    eliminarSucursal($id);
    header("Location: sucursales.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sucursales | Administración</title>
  <link rel="stylesheet" href="../../kpis/estilos.css">
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
<a href="#" class="brand"><div class="logo">IM</div> Tienda - Admin</a>
            <ul id="nav-menu">
                <<li><a href="sucursales.php" class="primary-cta">Sucursales</a></li>
      <li><a href="sucursales_eliminadas.php">Eliminadas</a></li>
       <li><a href="validador.php">Validar Mermas</a></li>
            </ul>
        </div>
    </nav>
    </header>
<div class="container">
  <h1>Gestión de Sucursales</h1>
  <?php if ($mensaje): ?>
    <p><strong><?= htmlspecialchars($mensaje) ?></strong></p>
  <?php endif; ?>

  <div class="card">
    <h2>Agregar nueva sucursal</h2>
    <form method="POST">
      <input type="hidden" name="accion" value="agregar">
      <p><strong>⚠️ Escribe el nombre completo de la tienda exactamente como aparece:</strong></p>
      <p class="text-muted">Ejemplos: Nuño del Mercado, Labotienda, Plaza Bella.</p>
      <input type="text" name="nombre" placeholder="Nombre completo" required style="width:100%;padding:8px;margin:6px 0;">
      <input type="number" step="0.01" name="metaIM" placeholder="Meta IM" required style="width:100%;padding:8px;margin:6px 0;">
      <button class="btn">Agregar Sucursal</button>
    </form>
  </div>

  <h2 style="margin-top:30px;">Sucursales activas</h2>
  
    <table border="0" cellpadding="8" cellspacing="0" width="100%">
      <thead>
        <tr style="text-align:left;">
          <th>ID</th>
          <th>Nombre</th>
          <th>Meta IM</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sucursales as $s): ?>
          <tr>
            <td><?= $s['id'] ?></td>
            <td><?= htmlspecialchars($s['nombre']) ?></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <input type="number" step="0.01" name="metaIM" value="<?= $s['metaIM'] ?>" style="width:100px;">
                <button class="btn secondary" style="padding:4px 8px;">💾</button>
              </form>
            </td>
            <td><a href="?eliminar=<?= $s['id'] ?>" class="btn secondary" onclick="return confirm('¿Marcar como eliminada?');">🗑️ Eliminar</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

</div>

</body>
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
</html>
