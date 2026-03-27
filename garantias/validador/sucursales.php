<?php
include_once '../../funciones.php';
$sucursales = obtenerSucursalesActivas();
$mensaje = "";

// Agregar nueva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $nombre = trim($_POST['nombre']);
    $metaIM = floatval($_POST['metaIM']);
    $metaTM = floatval($_POST['metaTM'] ?? 0);
    if (agregarSucursal($nombre, $metaIM, $metaTM)) {
        $mensaje = "✅ Sucursal agregada correctamente.";
        $sucursales = obtenerSucursalesActivas();
    } else {
        $mensaje = "❌ Error al agregar la sucursal.";
    }
}


// Guardar TODAS las metas de un golpe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_todas') {
    $metas = $_POST['metas'] ?? [];  // ['id' => ['im'=>x, 'tm'=>y], ...]
    $resultado = actualizarTodasLasMetas($metas);
    $mensaje = $resultado
        ? "✅ Todas las metas guardadas correctamente."
        : "❌ Error al guardar las metas.";
    $sucursales = obtenerSucursalesActivas();
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
  <style>
    .badge-im { background: linear-gradient(135deg,#f093fb,#f5576c); color:#fff; padding:3px 12px; border-radius:10px; font-size:.82em; font-weight:bold; }
    .badge-tm { background: linear-gradient(135deg,#4facfe,#00b4d8); color:#fff; padding:3px 12px; border-radius:10px; font-size:.82em; font-weight:bold; }

    .btn-guardar-todo {
      display: block;
      width: 100%;
      padding: 14px;
      font-size: 1.1em;
      font-weight: bold;
      background: linear-gradient(135deg, #43e97b, #38f9d7);
      color: #0f1724;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      margin-bottom: 24px;
      transition: transform .2s, box-shadow .2s;
      box-shadow: 0 4px 14px rgba(67,233,123,0.4);
    }
    .btn-guardar-todo:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(67,233,123,0.5);
    }

    table input[type=number] {
      width: 110px;
      padding: 6px 8px;
      border: 2px solid #d1d5db;
      border-radius: 8px;
      font-size: .95em;
      transition: border-color .2s;
    }
    table input[type=number]:focus {
      outline: none;
      border-color: #16729a;
    }
    .col-nombre { font-weight: 600; }
  </style>
</head>
<body>
<header>
  <nav>
    <div class="nav-inner">
      <label class="bar-menu">
        <input type="checkbox" id="menu-check">
        <span class="top"></span><span class="middle"></span><span class="bottom"></span>
      </label>
      <a href="#" class="brand"><div class="logo">IM</div> Tienda - Admin</a>
      <ul id="nav-menu">
        <li><a href="sucursales.php" class="primary-cta">Sucursales</a></li>
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

  <!-- ── Agregar nueva ──────────────────────────── -->
  <div class="card">
    <h2>Agregar nueva sucursal</h2>
    <form method="POST">
      <input type="hidden" name="accion" value="agregar">
      <p><strong>⚠️ Escribe el nombre completo de la tienda exactamente como aparece:</strong></p>
      <p class="text-muted">Ejemplos: Nuño del Mercado, Labotienda, Plaza Bella.</p>
      <input type="text" name="nombre" placeholder="Nombre completo" required style="width:100%;padding:8px;margin:6px 0;">
      <input type="number" step="0.01" name="metaIM" placeholder="Meta IM" required style="width:100%;padding:8px;margin:6px 0;">
      <input type="number" step="0.01" name="metaTM" placeholder="Meta TM" required style="width:100%;padding:8px;margin:6px 0;">
      <button class="btn">Agregar Sucursal</button>
    </form>
  </div>

  <!-- ── Editar todas las metas ─────────────────── -->
  <h2 style="margin-top:30px;">Metas por sucursal</h2>
  <p style="color:#6b7280;margin-bottom:16px;font-size:.95em;">
    Edita los valores que necesites y presiona <strong>Guardar todo</strong> al terminar.
  </p>

  <form method="POST">
    <input type="hidden" name="accion" value="guardar_todas">

    

    <table border="0" cellpadding="8" cellspacing="0" width="100%">
      <thead>
        <tr style="text-align:left;">
          <th>ID</th>
          <th>Nombre</th>
          <th><span class="badge-im">Meta IM</span></th>
          <th><span class="badge-tm">Meta TM</span></th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sucursales as $s): ?>
        <tr>
          <td><?= $s['id'] ?></td>
          <td class="col-nombre"><?= htmlspecialchars($s['nombre']) ?></td>
          <td>
            <input type="number" step="0.01" min="0"
              name="metas[<?= $s['id'] ?>][im]"
              value="<?= htmlspecialchars($s['metaIM']) ?>">
          </td>
          <td>
            <input type="number" step="0.01" min="0"
              name="metas[<?= $s['id'] ?>][tm]"
              value="<?= htmlspecialchars($s['metaTM'] ?? 0) ?>">
          </td>
          <td>
            <a href="?eliminar=<?= $s['id'] ?>" class="btn secondary"
               onclick="return confirm('¿Marcar como eliminada?');">🗑️ Eliminar</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <button type="submit" class="btn-guardar-todo" style="margin-top:20px;">💾 Guardar todo</button>
  </form>

</div>

<script>
  document.getElementById('menu-check').addEventListener('change', function() {
    const menu = document.getElementById('nav-menu');
    menu.style.opacity       = this.checked ? '1' : '0';
    menu.style.visibility    = this.checked ? 'visible' : 'hidden';
    menu.style.pointerEvents = this.checked ? 'auto' : 'none';
  });
</script>
</body>
</html>