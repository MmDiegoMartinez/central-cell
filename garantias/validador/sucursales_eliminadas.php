<?php
include_once '../../funciones.php';
$eliminadas = obtenerSucursalesEliminadas();
$mensaje = "";

if (isset($_GET['borrar'])) {
    $id = intval($_GET['borrar']);
    if (eliminarSucursalDefinitivamente($id)) {
        $mensaje = "✅ Sucursal eliminada permanentemente.";
        $eliminadas = obtenerSucursalesEliminadas();
    } else {
        $mensaje = "❌ Error al eliminar definitivamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Sucursales Eliminadas</title>
  <link rel="stylesheet" href="../../kpis/estilos.css">
</head>
<body>
<nav>
  <div class="nav-inner">
    <a href="sucursales.php" class="brand"><div class="logo">IM</div> Tienda - Admin</a>
    <ul>
      <li><a href="sucursales.php">Sucursales</a></li>
      <li><a href="sucursales_eliminadas.php" class="primary-cta">Eliminadas</a></li>
    </ul>
  </div>
</nav>

<div class="container">
  <h1>Sucursales Eliminadas</h1>
  <?php if ($mensaje): ?>
    <p><strong><?= htmlspecialchars($mensaje) ?></strong></p>
  <?php endif; ?>

  <div class="card">
    <p class="text-muted">⚠️ Si eliminas una sucursal permanentemente, se borrarán todos sus registros de mermas y garantías asociados.</p>
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
        <?php foreach ($eliminadas as $s): ?>
          <tr>
            <td><?= $s['id'] ?></td>
            <td><?= htmlspecialchars($s['nombre']) ?></td>
            <td><?= $s['metaIM'] ?></td>
            <td><a href="?borrar=<?= $s['id'] ?>" class="btn secondary" onclick="return confirm('⚠️ Esto eliminará todos los datos relacionados. ¿Continuar?');">❌ Eliminar Definitivamente</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
