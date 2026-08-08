<?php
include_once '../../funciones.php';
$eliminadas = obtenerSucursalesEliminadas();
$mensaje = "";
$mensaje_tipo = "";

if (isset($_GET['borrar'])) {
$id = intval($_GET['borrar']);
if (eliminarSucursalDefinitivamente($id)) {
$mensaje_tipo = "success";
$mensaje = "Sucursal eliminada permanentemente.";
$eliminadas = obtenerSucursalesEliminadas();
    } else {
$mensaje_tipo = "error";
$mensaje = "Error al eliminar definitivamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Sucursales Eliminadas</title>
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

  @media (max-width:720px){
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
    margin-bottom:var(--space-md);font-size:13.5px;line-height:1.5;
  }
  .callout .material-symbols-outlined{font-size:20px;flex-shrink:0;}
  .callout-warning{background:rgba(211,47,47,0.10);color:#C62828;}

  .panel{
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    background:var(--surface-container-low);
    overflow:hidden;
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
  thead th:nth-child(1), tbody td:nth-child(1){width:10%;}
  thead th:nth-child(2), tbody td:nth-child(2){width:36%;}
  thead th:nth-child(3), tbody td:nth-child(3){width:20%;}
  thead th:nth-child(4), tbody td:nth-child(4){width:34%;}

  tbody tr:nth-child(even){background:var(--surface-container-low);}
  .col-nombre{font-weight:700;color:var(--on-surface);}
  .empty-state{
    padding:var(--space-lg);text-align:center;color:var(--on-surface-variant);
    border:1px dashed var(--outline-variant);border-radius:var(--radius-lg);
  }
</style>
</head>
<body>

<!-- ===================== NAVBAR HORIZONTAL ===================== -->
<header class="navbar">
  <a href="sucursales.php" class="navbar-brand">
    <span class="logo-badge">IM</span>
    <div class="navbar-brand-text">
      <p class="text-headline-sm">Tienda - Admin</p>
      <p class="text-label-sm" style="color:var(--outline)">Sucursales eliminadas</p>
    </div>
  </a>

  <button class="navbar-toggle" id="navToggle" type="button" aria-label="Abrir menú">
    <span class="material-symbols-outlined">menu</span>
  </button>

  <nav class="navbar-links" id="navLinks">
    <a href="sucursales.php" class="navbar-link">
      <span class="material-symbols-outlined">storefront</span>
      Sucursales
    </a>
    <a href="sucursales_eliminadas.php" class="navbar-link active">
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
      <h1 class="text-headline-lg" style="margin:6px 0 var(--space-lg)">Sucursales <span>Eliminadas</span></h1>

      <?php if ($mensaje): ?>
        <div class="flash <?= htmlspecialchars($mensaje_tipo) ?>">
          <span class="material-symbols-outlined"><?= $mensaje_tipo === 'success' ? 'check_circle' : 'error' ?></span>
          <span><?= htmlspecialchars($mensaje) ?></span>
        </div>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">
            <span class="material-symbols-outlined" style="font-size:18px">delete_history</span>
            Sucursales marcadas como eliminadas
          </span>
        </div>
        <div class="panel-body">

          <div class="callout callout-warning">
            <span class="material-symbols-outlined">warning</span>
            <span>Si eliminas una sucursal permanentemente, se borrarán todos sus registros de mermas y garantías asociados.</span>
          </div>

          <?php if (empty($eliminadas)): ?>
            <div class="empty-state">No hay sucursales eliminadas.</div>
          <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
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
                  <td class="col-nombre"><?= htmlspecialchars($s['nombre']) ?></td>
                  <td><?= $s['metaIM'] ?></td>
                  <td>
                    <a href="?borrar=<?= $s['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('⚠️ Esto eliminará todos los datos relacionados. ¿Continuar?');">
                      <span class="material-symbols-outlined" style="font-size:16px;vertical-align:-3px">delete_forever</span>
                      Eliminar definitivamente
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

        </div>
      </div>

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