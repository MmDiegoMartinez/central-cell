<?php
include_once '../../funciones.php';
session_start();
if (!isset($_SESSION['validador_id'])) {
header('Location: loginvalidador.php');
exit;
}

$validadores = obtenerValidadores();

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Lista de Validadores</title>
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
  .navbar-brand img{border-radius:6px;}
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

  .btn-ghost{
    display:inline-flex;align-items:center;gap:6px;
    background:transparent;color:var(--on-surface-variant);
    border:1px solid var(--outline-variant);
  }
  .btn-ghost:hover{border-color:var(--primary);color:var(--primary);}
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
  thead th:nth-child(2), tbody td:nth-child(2){width:20%;}
  thead th:nth-child(3), tbody td:nth-child(3){width:20%;}
  thead th:nth-child(4), tbody td:nth-child(4){width:18%;}
  thead th:nth-child(5), tbody td:nth-child(5){width:18%;}
  thead th:nth-child(6), tbody td:nth-child(6){width:16%;}

  tbody tr:nth-child(even){background:var(--surface-container-low);}
  .text-muted{color:var(--outline);}
  .empty-state{
    padding:var(--space-lg);text-align:center;color:var(--on-surface-variant);
    border:1px dashed var(--outline-variant);border-radius:var(--radius-lg);
  }
</style>
</head>
<body>

<!-- ===================== NAVBAR HORIZONTAL ===================== -->
<header class="navbar">
  <a href="validador.php" class="navbar-brand">
    <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" alt="Logo" width="32" height="32">
    <div class="navbar-brand-text">
      <p class="text-headline-sm">Central Cell</p>
      <p class="text-label-sm" style="color:var(--outline)">Validadores</p>
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
    <a href="crear_validador.php" class="navbar-link">
      <span class="material-symbols-outlined">add</span>
      Nuevo Validador
    </a>
    <a href="../../kpis/modulos.html" class="navbar-link">
      <span class="material-symbols-outlined">apps</span>
      Panel de Herramientas
    </a>
  </nav>
</header>

<!-- ===================== MAIN ===================== -->
<div class="container">
  <div class="lesson">
    <div class="lesson-body">

      <span class="eyebrow">Administración</span>
      <h1 class="text-headline-lg" style="margin:6px 0 var(--space-lg)">Lista de <span>Validadores</span></h1>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">
            <span class="material-symbols-outlined" style="font-size:18px">groups</span>
            Validadores registrados
          </span>
        </div>

        <?php if (empty($validadores)): ?>
          <div style="padding:var(--space-md);">
            <div class="empty-state">No hay validadores registrados.</div>
          </div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Usuario</th>
                <th>Fecha de Registro</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach($validadores as $v): ?>
              <tr>
                <td class="text-muted"><?= htmlspecialchars($v['id']) ?></td>
                <td><?= htmlspecialchars($v['nombre']) ?></td>
                <td><?= htmlspecialchars($v['apellido']) ?></td>
                <td><?= htmlspecialchars($v['usuario']) ?></td>
                <td><?= htmlspecialchars($v['created_at']) ?></td>
                <td>
                  <a href="editar_validador.php?id=<?= $v['id'] ?>" class="btn btn-ghost btn-sm">
                    <span class="material-symbols-outlined" style="font-size:16px">edit</span>
                    Editar
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