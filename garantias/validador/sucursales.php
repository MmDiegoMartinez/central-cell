<?php
include_once '../../funciones.php';
$sucursales = obtenerSucursalesActivas();
$mensaje = "";
$mensaje_tipo = "";

// Agregar nueva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $nombre = trim($_POST['nombre']);
    $metaIM = floatval($_POST['metaIM']);
    $metaTM = floatval($_POST['metaTM'] ?? 0);
    if (agregarSucursal($nombre, $metaIM, $metaTM)) {
        $mensaje_tipo = "success";
        $mensaje = "Sucursal agregada correctamente.";
        $sucursales = obtenerSucursalesActivas();
    } else {
        $mensaje_tipo = "error";
        $mensaje = "Error al agregar la sucursal.";
    }
}


// Guardar TODAS las metas de un golpe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_todas') {
    $metas = $_POST['metas'] ?? [];  // ['id' => ['im'=>x, 'tm'=>y], ...]
    $resultado = actualizarTodasLasMetas($metas);
    $mensaje_tipo = $resultado ? "success" : "error";
    $mensaje = $resultado
        ? "Todas las metas guardadas correctamente."
        : "Error al guardar las metas.";
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Sucursales | Administración</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="../../styles.css">

  <style>
    /* ---- Ajustes puntuales que el CSS base no cubre (no se toca styles.css) ---- */

    /* Navbar horizontal (mismo patrón del resto del portal) */
    .navbar{
      position:sticky;top:0;z-index:50;
      display:flex;align-items:center;justify-content:space-between;
      gap:var(--space-md);
      padding:12px var(--space-lg);
      background:var(--surface);
      border-bottom:1px solid var(--outline-variant);
    }
    .navbar-brand{
      display:flex;align-items:center;gap:10px;
      text-decoration:none;color:var(--on-surface);
    }
    .navbar-brand .logo-badge{
      display:flex;align-items:center;justify-content:center;
      width:36px;height:36px;border-radius:50%;
      background:var(--primary);color:var(--on-primary,#fff);
      font-weight:700;font-size:13px;
    }
    .navbar-brand-text p{margin:0;line-height:1.2;}
    .navbar-links{
      display:flex;align-items:center;gap:6px;flex-wrap:wrap;
    }
    .navbar-link{
      display:flex;align-items:center;gap:6px;
      padding:8px 14px;border-radius:var(--radius-lg);
      color:var(--on-surface-variant);text-decoration:none;
      font-size:14px;font-weight:600;
      transition:all .15s ease;
    }
    .navbar-link .material-symbols-outlined{font-size:20px;}
    .navbar-link:hover{background:var(--surface-container-low);color:var(--primary);}
    .navbar-link.active{background:var(--primary);color:var(--on-primary,#fff);}
    .navbar-toggle{display:none;}

    @media (max-width:820px){
      .navbar-links{
        display:none;flex-direction:column;align-items:stretch;
        position:absolute;top:100%;left:0;right:0;
        background:var(--surface);border-bottom:1px solid var(--outline-variant);
        padding:var(--space-sm);gap:4px;
      }
      .navbar-links.open{display:flex;}
      .navbar-toggle{
        display:flex;align-items:center;justify-content:center;
        background:none;border:none;cursor:pointer;color:var(--on-surface);
      }
    }

    h1 span{color:var(--primary);}

    /* Mensaje flash */
    .flash{
      display:flex;align-items:center;gap:10px;
      padding:12px 16px;border-radius:var(--radius-lg);
      font-size:14px;font-weight:600;margin-bottom:var(--space-lg);
    }
    .flash .material-symbols-outlined{font-size:20px;}
    .flash.success{background:rgba(46,160,67,0.12);color:#2E7D32;}
    .flash.error{background:rgba(211,47,47,0.12);color:#C62828;}

    /* Callout de advertencia */
    .callout{
      display:flex;gap:10px;align-items:flex-start;
      padding:12px 14px;border-radius:var(--radius-lg);
      margin-bottom:var(--space-sm);font-size:13.5px;line-height:1.5;
    }
    .callout .material-symbols-outlined{font-size:20px;flex-shrink:0;}
    .callout-warning{background:rgba(211,47,47,0.10);color:#C62828;}
    .callout-warning strong{color:#C62828;}
    .form-hint{font-size:12px;color:var(--on-surface-variant);line-height:1.6;margin:0 0 var(--space-md);}

    .panel{
      border:1px solid var(--outline-variant);
      border-radius:var(--radius-lg);
      background:var(--surface-container-low);
      overflow:hidden;
      margin-bottom:var(--space-lg);
    }
    .panel-header{
      padding:14px var(--space-md);
      border-bottom:1px solid var(--outline-variant);
      background:var(--surface);
    }
    .panel-title{
      font-weight:700;font-size:15px;color:var(--on-surface);
      display:flex;align-items:center;gap:8px;
    }
    .panel-body{padding:var(--space-md);}

    /* Formulario */
    .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:var(--space-md);}
    .form-group label{font-size:13px;font-weight:600;color:var(--on-surface-variant);}
    .form-group input[type="text"],
    .form-group input[type="number"]{
      padding:10px 12px;
      border:1px solid var(--outline-variant);
      border-radius:var(--radius-lg);
      background:var(--surface);
      color:var(--on-surface);
      font-size:14px;font-family:inherit;
      width:100%;
    }
    .form-group input:focus{outline:none;border-color:var(--primary);}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:var(--space-md);}
    @media (max-width:600px){ .form-row{grid-template-columns:1fr;} }

    .btn-full{width:100%;justify-content:center;}
    .btn-danger{background:#C62828;color:#fff;border-color:#C62828;}
    .btn-danger:hover{filter:brightness(1.05);}
    .btn-sm{padding:6px 12px;font-size:12px;}

    /* Tabla — sin scroll horizontal */
    .table-wrap{overflow-x:visible;width:100%;}
    table{
      width:100%;
      table-layout:fixed;
      border-collapse:collapse;font-size:13px;
      background:var(--surface);border-radius:var(--radius-lg);overflow:hidden;
    }
    thead th{
      background:#00838F;color:#fff;font-weight:700;
      padding:10px 10px;text-align:left;
      white-space:normal;word-break:break-word;
      font-size:12px;
    }
    tbody td{
      padding:8px 10px;border-bottom:1px solid var(--outline-variant);
      white-space:normal;word-break:break-word;
      vertical-align:middle;
    }
    thead th:nth-child(1), tbody td:nth-child(1){width:8%;}
    thead th:nth-child(2), tbody td:nth-child(2){width:32%;}
    thead th:nth-child(3), tbody td:nth-child(3){width:20%;}
    thead th:nth-child(4), tbody td:nth-child(4){width:20%;}
    thead th:nth-child(5), tbody td:nth-child(5){width:20%;}

    tbody tr:nth-child(even){background:var(--surface-container-low);}
    .col-nombre{font-weight:700;color:var(--on-surface);}

    table input[type=number]{
      width:100%;
      padding:8px 10px;
      border:1px solid var(--outline-variant);
      border-radius:var(--radius-lg);
      background:var(--surface);
      color:var(--on-surface);
      font-size:13px;font-family:inherit;
    }
    table input[type=number]:focus{outline:none;border-color:var(--primary);}

    .badge-im, .badge-tm{
      display:inline-flex;align-items:center;
      padding:4px 10px;border-radius:var(--radius-full,999px);
      font-size:11px;font-weight:700;color:#fff;
    }
    .badge-im{background:#8E24AA;}
    .badge-tm{background:#0097A7;}

    .btn-guardar-todo{margin-top:var(--space-lg);}
  </style>
</head>
<body>

<!-- ===================== NAVBAR HORIZONTAL ===================== -->
<header class="navbar">
  <a href="validador.php" class="navbar-brand">
    <span class="logo-badge">IM</span>
    <div class="navbar-brand-text">
      <p class="text-headline-sm">Tienda - Admin</p>
      <p class="text-label-sm" style="color:var(--outline)">Sucursales</p>
    </div>
  </a>

  <button class="navbar-toggle" id="navToggle" type="button" aria-label="Abrir menú">
    <span class="material-symbols-outlined">menu</span>
  </button>

  <nav class="navbar-links" id="navLinks">
    <a href="validador.php" class="navbar-link">
      <span class="material-symbols-outlined">home</span>
      Home
    </a>
    <a href="../../kpis/modulos.html" class="navbar-link">
      <span class="material-symbols-outlined">apps</span>
      Panel de Herramientas
    </a>
    <a href="sucursales.php" class="navbar-link active">
      <span class="material-symbols-outlined">storefront</span>
      Sucursales
    </a>
    <a href="sucursales_eliminadas.php" class="navbar-link">
      <span class="material-symbols-outlined">delete_history</span>
      Eliminadas
    </a>
  </nav>
</header>

<!-- ===================== MAIN ===================== -->
<div class="container">
  <div class="lesson">
    <div class="lesson-body">

      <span class="eyebrow">Administración</span>
      <h1 class="text-headline-lg" style="margin:6px 0 var(--space-lg)">Gestión de <span>Sucursales</span></h1>

      <?php if ($mensaje): ?>
        <div class="flash <?= htmlspecialchars($mensaje_tipo) ?>">
          <span class="material-symbols-outlined"><?= $mensaje_tipo === 'success' ? 'check_circle' : 'error' ?></span>
          <span><?= htmlspecialchars($mensaje) ?></span>
        </div>
      <?php endif; ?>

      <!-- ── Agregar nueva ──────────────────────────── -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">1</div>
          <h3 class="step-title text-headline-sm">Agregar nueva sucursal</h3>
        </div>

        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">
              <span class="material-symbols-outlined" style="font-size:18px">add_business</span>
              Nueva sucursal
            </span>
          </div>
          <div class="panel-body">
            <form method="POST">
              <input type="hidden" name="accion" value="agregar">

              <div class="callout callout-warning">
                <span class="material-symbols-outlined">warning</span>
                <span><strong>Escribe el nombre completo de la tienda exactamente como aparece.</strong></span>
              </div>
              <p class="form-hint">Ejemplos: Nuño del Mercado, Labotienda, Plaza Bella.</p>

              <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" placeholder="Nombre completo" required>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="metaIM">Meta IM</label>
                  <input type="number" step="0.01" id="metaIM" name="metaIM" placeholder="Meta IM" required>
                </div>
                <div class="form-group">
                  <label for="metaTM">Meta TM</label>
                  <input type="number" step="0.01" id="metaTM" name="metaTM" placeholder="Meta TM" required>
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-full">
                <span class="material-symbols-outlined">add</span>
                Agregar sucursal
              </button>
            </form>
          </div>
        </div>
      </section>

      <!-- ── Editar todas las metas ─────────────────── -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">2</div>
          <h3 class="step-title text-headline-sm">Metas por sucursal</h3>
        </div>

        <p class="form-hint">Edita los valores que necesites y presiona <strong>Guardar todo</strong> al terminar.</p>

        <div class="panel">
          <div class="panel-header"><span class="panel-title">Lista de sucursales</span></div>
          <div class="panel-body" style="padding:0;">
            <form method="POST">
              <input type="hidden" name="accion" value="guardar_todas">

              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
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
                        <a href="?eliminar=<?= $s['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('¿Marcar como eliminada?');">
                          <span class="material-symbols-outlined" style="font-size:16px;vertical-align:-3px">delete</span>
                          Eliminar
                        </a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div style="padding:var(--space-md);">
                <button type="submit" class="btn btn-primary btn-full btn-guardar-todo">
                  <span class="material-symbols-outlined">save</span>
                  Guardar todo
                </button>
              </div>
            </form>
          </div>
        </div>
      </section>

    </div>
  </div>
</div>

<script>
  // Control del menú horizontal en móvil
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
</script>
</body>
</html>