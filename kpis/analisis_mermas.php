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

<link rel="stylesheet" href="../styles.css">

<style>
  /* ---- Ajustes puntuales que el CSS base no cubre (no se toca styles.css) ---- */

  /* Navbar horizontal */
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

  /* Formulario */
  .controls{
    display:flex;flex-wrap:wrap;gap:var(--space-md);align-items:flex-end;
    margin-bottom:var(--space-md);
  }
  .controls .btn{margin:0;}
  .form-field{
    display:flex;flex-direction:column;gap:6px;
  }
  .form-field label{
    font-size:13px;font-weight:600;color:var(--on-surface-variant);
  }
  .form-field input[type="date"]{
    padding:10px 12px;
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    background:var(--surface-container-low);
    color:var(--on-surface);
    font-size:14px;font-family:inherit;
    min-width:170px;
  }
  .form-field input[type="date"]:focus{outline:none;border-color:var(--primary);}

  .empty-state{
    padding:var(--space-lg);text-align:center;color:var(--on-surface-variant);
    border:1px dashed var(--outline-variant);border-radius:var(--radius-lg);
  }

  .table-wrap{overflow-x:auto;}
  .data-table{
    width:100%;border-collapse:collapse;font-size:13px;
    background:var(--surface);border-radius:var(--radius-lg);overflow:hidden;
  }
  .data-table thead th{
    background:#00838F;color:#fff;font-weight:700;
    padding:10px 12px;text-align:left;white-space:nowrap;
  }
  .data-table tbody td{
    padding:8px 12px;border-bottom:1px solid var(--outline-variant);
  }
  .data-table tbody tr:nth-child(even){background:var(--surface-container-low);}
  .data-table tr.tipo td{
    background:rgba(0,131,143,0.10);
    color:#00838F;font-weight:700;font-size:13px;
    padding:8px 12px;border-bottom:1px solid var(--outline-variant);
  }
</style>
</head>

<body>

<!-- ===================== NAVBAR HORIZONTAL ===================== -->
<header class="navbar">
  <a href="../garantias/validador/validador.php" class="navbar-brand">
    <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="32" height="32">
    <div class="navbar-brand-text">
      <p class="text-headline-sm">Central Cell</p>
      <p class="text-label-sm" style="color:var(--outline)">Innovación Móvil</p>
    </div>
  </a>

  <button class="navbar-toggle" id="navToggle" type="button" aria-label="Abrir menú">
    <span class="material-symbols-outlined">menu</span>
  </button>

  <nav class="navbar-links" id="navLinks">
    <a href="../garantias/validador/validador.php" class="navbar-link">
      <span class="material-symbols-outlined">home</span>
      Home
    </a>
    <a href="modulos.html" class="navbar-link">
      <span class="material-symbols-outlined">bar_chart</span>
      Panel de Herramientas
    </a>
  </nav>
</header>

<!-- ===================== MAIN ===================== -->
<div class="container">
  <div class="lesson">
    <div class="lesson-body">

      <span class="eyebrow">Reportes</span>
      <h1 class="text-headline-lg" style="margin:6px 0 0">Análisis de Mermas Frecuentes</h1>
      <div class="lesson-meta">
        <span><span class="material-symbols-outlined" style="font-size:16px">troubleshoot</span> Innovación Móvil</span>
      </div>

      <div class="intro-panel">
        <div class="icon-badge"><span class="material-symbols-outlined">insights</span></div>
        <div>
          <h3 style="margin:0">Consulta las mermas más frecuentes</h3>
          <p>Selecciona un rango de fechas para ver los productos con más mermas, agrupados por tipo, y descarga el resultado en Excel.</p>
        </div>
      </div>

      <!-- Paso 1: Filtro de fechas -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">1</div>
          <h3 class="step-title text-headline-sm">Selecciona el periodo</h3>
        </div>

        <form method="POST">
          <div class="controls">
            <div class="form-field">
              <label for="fecha_inicio">De</label>
              <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?=htmlspecialchars($inicio)?>" required>
            </div>

            <div class="form-field">
              <label for="fecha_fin">A</label>
              <input type="date" id="fecha_fin" name="fecha_fin" value="<?=htmlspecialchars($fin)?>" required>
            </div>

            <button type="submit" class="btn btn-primary">
              <span class="material-symbols-outlined">search</span>
              Analizar
            </button>
          </div>
        </form>
      </section>

      <!-- Paso 2: Resultados -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">2</div>
          <h3 class="step-title text-headline-sm">Resultados</h3>
        </div>

        <?php if ($resultados && count($resultados) > 0): ?>
            <button id="descargar" class="btn btn-secondary" style="margin-bottom:var(--space-md)">
              <span class="material-symbols-outlined">download</span>
              Descargar en Excel
            </button>
            <div class="table-wrap">
            <table id="tablaMermas" class="data-table">
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
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="empty-state">
              <span class="material-symbols-outlined" style="font-size:28px">search_off</span>
              <p style="margin:8px 0 0">No se encontraron registros en el rango seleccionado.</p>
            </div>
        <?php endif; ?>
      </section>

    </div>
  </div>
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
  // Control del menú horizontal en móvil
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
</script>
</body>
</html>