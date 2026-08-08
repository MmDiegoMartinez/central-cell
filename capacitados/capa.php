<?php
session_start();

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

ini_set('display_errors', 0);
error_reporting(E_ALL);

include_once '../funciones.php';

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

$mensaje      = "";
$tipo_mensaje = "";

try {
    $conn = conectarBD();
} catch (Exception $e) {
    die("Error al conectar a la base de datos.");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Solicitud inválida.");
    }
    try {
        $nombreColaborador = mb_strtolower(trim($_POST['nombre'] ?? ''));
        if ($nombreColaborador === '') throw new Exception("El nombre no puede estar vacío.");
        if (mb_strlen($nombreColaborador) > 120) throw new Exception("Nombre demasiado largo.");

        $fechaCapacitacion = $_POST['fecha'] ?? null;
        if ($fechaCapacitacion === '') $fechaCapacitacion = null;
        if ($fechaCapacitacion !== null) {
            $dt = DateTime::createFromFormat('Y-m-d', $fechaCapacitacion);
            if (!$dt || $dt->format('Y-m-d') !== $fechaCapacitacion)
                throw new Exception("Formato de fecha inválido.");
        }

        $stmt = $conn->prepare("SELECT id, fecha_capacitacion FROM colaboradores WHERE LOWER(nombre) = :nombre");
        $stmt->execute([':nombre' => $nombreColaborador]);
        $colaborador = $stmt->fetch();

        if ($colaborador) {
            if (empty($colaborador['fecha_capacitacion']) && $fechaCapacitacion !== null) {
                $stmt = $conn->prepare("UPDATE colaboradores SET fecha_capacitacion = :fecha WHERE id = :id");
                $stmt->execute([':fecha' => $fechaCapacitacion, ':id' => $colaborador['id']]);
                $tipo_mensaje = 'success';
                $mensaje = "Fecha de capacitación actualizada correctamente.";
            } else {
                $tipo_mensaje = 'info';
                $mensaje = "El colaborador ya tenía registrada la capacitación o no se proporcionó fecha.";
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO colaboradores (nombre, fecha_capacitacion) VALUES (:nombre, :fecha)");
            $stmt->execute([':nombre' => $nombreColaborador, ':fecha' => $fechaCapacitacion]);
            $tipo_mensaje = 'success';
            $mensaje = "Colaborador registrado con éxito.";
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $tipo_mensaje = 'error';
        $mensaje = $e->getMessage();
    }
}

try {
    $stmt = $conn->query(
        "SELECT nombre, fecha_capacitacion
         FROM colaboradores
         WHERE fecha_capacitacion IS NOT NULL
         ORDER BY fecha_capacitacion DESC"
    );
    $colaboradores = $stmt->fetchAll();
} catch (Exception $e) {
    $colaboradores = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Capacitación</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <link rel="stylesheet" href="../styles.css">

    <style>
      /* ---- Ajustes puntuales que el CSS base no cubre (no se toca styles.css) ---- */
      .sidebar-brand-logo{display:flex;align-items:center;gap:10px;}
      .sidebar-brand-logo img{border-radius:6px;}

      /* Mensaje flash */
      .flash{
        display:flex;align-items:center;gap:10px;
        padding:12px 16px;border-radius:var(--radius-lg);
        font-size:14px;font-weight:600;margin-bottom:var(--space-lg);
      }
      .flash .material-symbols-outlined{font-size:20px;}
      .flash--success{background:rgba(46,160,67,0.12);color:#2E7D32;}
      .flash--info{background:rgba(0,131,143,0.12);color:#00838F;}
      .flash--error{background:rgba(211,47,47,0.12);color:#C62828;}

      /* Formulario de registro */
      .reg-form{
        display:flex;flex-wrap:wrap;gap:var(--space-md);align-items:flex-end;
      }
      .field{display:flex;flex-direction:column;gap:6px;flex:1;min-width:200px;}
      .field label{font-size:13px;font-weight:600;color:var(--on-surface-variant);}
      .field-optional{font-weight:400;color:var(--outline);}
      .field input[type="text"],
      .field input[type="date"]{
        padding:10px 12px;
        border:1px solid var(--outline-variant);
        border-radius:var(--radius-lg);
        background:var(--surface-container-low);
        color:var(--on-surface);
        font-size:14px;font-family:inherit;
      }
      .field input:focus{outline:none;border-color:var(--primary);}

      /* Panel head con badge de conteo */
      .panel-head{
        display:flex;align-items:center;justify-content:space-between;
        margin-bottom:var(--space-sm);
      }
      .badge-count{
        display:inline-flex;align-items:center;justify-content:center;
        min-width:26px;height:26px;padding:0 8px;
        border-radius:var(--radius-full,999px);
        background:var(--primary);color:var(--on-primary,#fff);
        font-size:13px;font-weight:700;
      }

      /* Tabla de capacitados */
      .tbl-scroll{overflow-x:auto;}
      .cap-table{
        width:100%;border-collapse:collapse;font-size:13px;
        background:var(--surface);border-radius:var(--radius-lg);overflow:hidden;
      }
      .cap-table thead th{
        background:#00838F;color:#fff;font-weight:700;
        padding:10px 12px;text-align:left;white-space:nowrap;
      }
      .cap-table tbody td{
        padding:8px 12px;border-bottom:1px solid var(--outline-variant);
        white-space:nowrap;
      }
      .cap-table tbody tr:nth-child(even){background:var(--surface-container-low);}
      .td-empty{text-align:center;color:var(--on-surface-variant);padding:var(--space-lg) !important;}

      .st-badge{
        display:inline-flex;align-items:center;
        padding:4px 10px;border-radius:var(--radius-full,999px);
        font-size:12px;font-weight:700;
      }
      .st-badge.st--ok{background:rgba(46,160,67,0.12);color:#2E7D32;}
      .st-badge.st--warn{background:rgba(245,166,35,0.15);color:#B26A00;}
      .st-badge.st--err{background:rgba(211,47,47,0.12);color:#C62828;}
      .td-days.st--ok{color:#2E7D32;font-weight:700;}
      .td-days.st--warn{color:#B26A00;font-weight:700;}
      .td-days.st--err{color:#C62828;font-weight:700;}
    </style>
</head>
<body>

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-head">
    <div class="sidebar-brand-logo">
      <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="32" height="32">
      <div>
        <p class="sidebar-brand text-headline-sm">Central Cell</p>
        <p class="sidebar-sub text-label-sm">Capacitación</p>
      </div>
    </div>
    <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Cerrar menú">
      <span class="material-symbols-outlined">close</span>
    </button>
  </div>

  <nav class="sidebar-nav">
    <p class="sidebar-label">Navegación</p>
    <a href="../garantias/validador/validador.php" class="sidebar-link">
      <span class="material-symbols-outlined">home</span>
      Inicio
    </a>
    <a href="../kpis/modulos.html" class="sidebar-link">
      <span class="material-symbols-outlined">apps</span>
      Panel de Herramientas
    </a>
    <a href="../Evaluacion/material.html" class="sidebar-link">
      <span class="material-symbols-outlined">menu_book</span>
      Material
    </a>
    <a href="../Evaluacion/examen.php" class="sidebar-link">
      <span class="material-symbols-outlined">quiz</span>
      Cuestionario
    </a>
    <a href="../Evaluacion/lista_colaboradores.php" class="sidebar-link">
      <span class="material-symbols-outlined">fact_check</span>
      Calificaciones
    </a>
  </nav>

  <div class="sidebar-foot">
    <p class="text-label-sm" style="color:var(--outline)">Innovación Móvil</p>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===================== MAIN ===================== -->
<div class="main">

  <header class="topheader">
    <div class="topheader-left">
      <button class="menu-toggle" id="menuToggle" type="button" aria-label="Abrir menú">
        <span class="material-symbols-outlined">menu</span>
      </button>
      <h2 class="text-headline-sm" style="margin:0">Registro de Capacitación</h2>
    </div>
  </header>

  <div class="container">
    <div class="lesson">
      <div class="lesson-body">

        <span class="eyebrow">Capacitación</span>
        <h1 class="text-headline-lg" style="margin:6px 0 0">Registro de Capacitación</h1>
        <div class="lesson-meta">
          <span><span class="material-symbols-outlined" style="font-size:16px">school</span> Gestiona y consulta las capacitaciones del personal</span>
        </div>

        <?php if ($mensaje): ?>
        <div class="flash flash--<?= $tipo_mensaje ?>">
            <span class="material-symbols-outlined">
              <?= $tipo_mensaje === 'success' ? 'check_circle' : ($tipo_mensaje === 'info' ? 'info' : 'error') ?>
            </span>
            <?= htmlspecialchars($mensaje) ?>
        </div>
        <?php endif; ?>

        <!-- Formulario -->
        <section class="step-section">
          <div class="step-head">
            <div class="step-num">1</div>
            <h3 class="step-title text-headline-sm">Nuevo registro</h3>
          </div>

          <form method="POST" autocomplete="off" class="reg-form">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

              <div class="field">
                  <label for="nombre">Nombre del colaborador</label>
                  <input type="text"
                      name="nombre"
                      id="nombre"
                      value="<?php echo isset($colaboradorNombre) ? htmlspecialchars($colaboradorNombre) : ''; ?>"
                      placeholder="Escribe el nombre…"
                      maxlength="120"
                      autocomplete="off"
                      required>
              </div>

              <div class="field">
                  <label for="fecha">
                      Fecha de capacitación
                      <span class="field-optional">· opcional</span>
                  </label>
                  <input type="date" name="fecha" id="fecha"
                         value="<?= date('Y-m-d') ?>"
                         max="<?= date('Y-m-d') ?>">
              </div>

              <button type="submit" class="btn btn-primary">
                <span class="material-symbols-outlined">save</span>
                Guardar registro
              </button>
          </form>
        </section>

        <!-- Tabla -->
        <section class="step-section">
          <div class="panel-head">
            <div class="step-head" style="margin:0">
              <div class="step-num">2</div>
              <h3 class="step-title text-headline-sm" style="margin:0">Todos los capacitados</h3>
            </div>
            <span class="badge-count"><?= count($colaboradores) ?></span>
          </div>

          <div class="tbl-scroll">
              <table class="cap-table">
                  <thead>
                      <tr>
                          <th>#</th>
                          <th>Nombre</th>
                          <th>Capacitado</th>
                          <th>Vence</th>
                          <th>Estado</th>
                          <th>Días</th>
                      </tr>
                  </thead>
                  <tbody>
                  <?php
                  $hoy = new DateTime();
                  $i   = 1;
                  foreach ($colaboradores as $col):
                      $fechaCap = new DateTime($col['fecha_capacitacion']);
                      $fechaFin = (clone $fechaCap)->modify('+1 month');
                      $dias     = (int)$hoy->diff($fechaFin)->format('%r%a');

                      if ($dias >= 8)       { $estado = 'Vigente';    $cls = 'st--ok';  }
                      elseif ($dias >= 0)   { $estado = 'Por vencer'; $cls = 'st--warn'; }
                      else                  { $estado = 'Vencida';    $cls = 'st--err';  }

                      $diasLabel = $dias >= 0 ? "+$dias días" : "$dias días";
                  ?>
                  <tr>
                      <td class="td-num"><?= $i++ ?></td>
                      <td class="td-name"><?= htmlspecialchars(ucwords($col['nombre'])) ?></td>
                      <td class="td-date"><?= $fechaCap->format('d/m/Y') ?></td>
                      <td class="td-date"><?= $fechaFin->format('d/m/Y') ?></td>
                      <td><span class="st-badge <?= $cls ?>"><?= $estado ?></span></td>
                      <td class="td-days <?= $cls ?>"><?= $diasLabel ?></td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if (empty($colaboradores)): ?>
                  <tr><td colspan="6" class="td-empty">Sin registros aún</td></tr>
                  <?php endif; ?>
                  </tbody>
              </table>
          </div>
        </section>

      </div>
    </div>
  </div>

</div>

<script>
$(function() {
    let autocompleteData = [];
    $("#nombre").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "buscar_colaborador.php",
                dataType: "json",
                data: { term: request.term },
                success: function(data) { autocompleteData = data; response(data); }
            });
        },
        minLength: 1, delay: 300,
        select: function(event, ui) { $("#nombre").val(ui.item.label); return false; }
    });
    $("#nombre").on('keydown', function(e) {
        if (e.key === "Enter") { e.preventDefault(); if (autocompleteData.length > 0) $("#nombre").val(autocompleteData[0].label); }
    });
});
//buscar colaborador 

$(function () {

    let autocompleteData = [];

    $("#nombre").autocomplete({
        source: function (request, response) {

            $.ajax({
                url: "../garantias/vendedor/buscar_colaborador.php",
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function (data) {
                    autocompleteData = data;
                    response(data);
                }
            });

        },

        minLength: 1,
        delay: 300,

        select: function (event, ui) {
            $("#nombre").val(ui.item.label);
            return false;
        },

        open: function () {
            let w = $(this).autocomplete("widget");
            w.children("li").removeClass("ui-state-focus");
            w.children("li:first").addClass("ui-state-focus");
        }

    });

    $("#nombre").on("keydown", function (e) {

        if (e.key === "Enter") {
            e.preventDefault();

            if (autocompleteData.length > 0) {
                $("#nombre").val(autocompleteData[0].label);
            }
        }

    });

});
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
  // Control del sidebar en móvil (mismo patrón que el resto del panel)
  const sidebar        = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const menuToggle      = document.getElementById('menuToggle');
  const sidebarClose    = document.getElementById('sidebarClose');

  function abrirSidebar() {
    sidebar.classList.add('open');
    sidebarOverlay.classList.add('show');
  }
  function cerrarSidebar() {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('show');
  }

  menuToggle.addEventListener('click', abrirSidebar);
  sidebarClose.addEventListener('click', cerrarSidebar);
  sidebarOverlay.addEventListener('click', cerrarSidebar);
</script>
</body>
</html>