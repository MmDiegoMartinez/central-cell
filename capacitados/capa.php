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
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <style>
    /* ── PALETA ───────────────────────────────────────────── */
    :root {
      --bg:           #f5f7fa;
      --surface:      #ffffff;
      --muted:        #6b7280;
      --text:         #0f1724;
      --primary-600:  #0f5476;
      --primary-400:  #16729a;
      --accent:       #1f9a8a;
      --glass:        rgba(253, 250, 250, 0.04);
      --radius-lg:    14px;
      --radius-md:    10px;
      --shadow-sm:    0 6px 18px rgba(12, 18, 26, 0.06);
      --shadow-md:    0 10px 30px rgba(12, 18, 26, 0.09);
      --transition-fast: 220ms cubic-bezier(.2, .9, .2, 1);
    }

    /* ── RESET / BASE ────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ── NAVBAR ──────────────────────────────────────────── */
    .topnav {
      position: sticky;
      top: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0 1.5rem;
      height: 56px;
      background: var(--primary-600);          /* era negro */
      box-shadow: var(--shadow-sm);
    }

    .topnav-back {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      font-weight: 500;
      color: rgba(255,255,255,.85);
      text-decoration: none;
      transition: color var(--transition-fast);
    }
    .topnav-back:hover { color: #ffffff; }
    .topnav-back svg path { stroke: currentColor; }

    .topnav-links {
      display: flex;
      gap: .25rem;
      margin-left: auto;
    }

    .tnl {
      padding: 6px 14px;
      border-radius: var(--radius-md);
      font-size: 13px;
      font-weight: 500;
      color: rgba(255,255,255,.75);
      text-decoration: none;
      transition: background var(--transition-fast), color var(--transition-fast);
    }
    .tnl:hover            { background: rgba(255, 255, 255, 0.12); color: #fff; }
    .tnl--active          { background: rgba(255, 255, 255, 0.18); color: #fff; }

    /* burger */
    .topnav-burger {
      display: none;
      flex-direction: column;
      justify-content: center;
      gap: 5px;
      width: 36px;
      height: 36px;
      background: transparent;
      border: none;
      cursor: pointer;
      padding: 6px;
      margin-left: auto;
    }
    .topnav-burger span {
      display: block;
      height: 2px;
      border-radius: 2px;
      background: rgba(255,255,255,.85);
      transition: transform var(--transition-fast), opacity var(--transition-fast);
    }
    .topnav-burger.is-open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .topnav-burger.is-open span:nth-child(2) { opacity: 0; }
    .topnav-burger.is-open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    /* drawer mobile */
    .topnav-drawer {
      display: none;
      position: absolute;
      top: 56px;
      left: 0;
      right: 0;
      background: var(--primary-600);
      flex-direction: column;
      padding: .75rem 1.5rem 1rem;
      gap: .25rem;
      box-shadow: var(--shadow-md);
    }
    .topnav-drawer.open { display: flex; }

    .drawer-link {
      padding: 10px 14px;
      border-radius: var(--radius-md);
      font-size: 14px;
      font-weight: 500;
      color: rgba(255,255,255,.8);
      text-decoration: none;
      transition: background var(--transition-fast), color var(--transition-fast);
    }
    .drawer-link:hover  { background: rgba(255,255,255,.1); color: #fff; }
    .drawer-active      { background: rgba(255, 248, 248, 0.15); color: #fff; }

    @media (max-width: 640px) {
      .topnav-links   { display: none; }
      .topnav-burger  { display: flex; }
    }

    /* ── MAIN ────────────────────────────────────────────── */
    .main-wrap {
      max-width: 860px;
      margin: 0 auto;
      padding: 2.5rem 1.5rem 4rem;
      display: flex;
      flex-direction: column;
      gap: 2rem;
    }

    /* ── PAGE HEADER ─────────────────────────────────────── */
    .page-header { text-align: center; }

    .page-title {
      font-size: clamp(1.5rem, 3vw, 2rem);
      font-weight: 600;
      color: var(--text);                       /* era negro puro */
      letter-spacing: -.02em;
    }
    .page-sub {
      margin-top: .4rem;
      font-size: 14px;
      color: var(--muted);
    }

    /* ── FLASH ───────────────────────────────────────────── */
    .flash {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .85rem 1.2rem;
      border-radius: var(--radius-md);
      font-size: 14px;
      font-weight: 500;
    }
    .flash--success { background: #e6f9f5; color: var(--accent); border: 1px solid #a7e8df; }
    .flash--info    { background: #e8f4fb; color: var(--primary-400); border: 1px solid #b3d9ef; }
    .flash--error   { background: #fdecea; color: #c0392b; border: 1px solid #f5c6c2; }

    /* ── PANEL ───────────────────────────────────────────── */
    .panel {
      background: var(--surface);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-sm);
      padding: 1.75rem 2rem;
    }
    .panel-head {
      display: flex;
      align-items: center;
      gap: .75rem;
      margin-bottom: 1.25rem;
    }
    .panel-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text);                       /* era negro */
      margin-bottom: 1.25rem;
    }
    .panel-head .panel-title { margin-bottom: 0; }

    .badge-count {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 26px;
      height: 22px;
      padding: 0 8px;
      border-radius: 999px;
      background: var(--primary-600);          /* era negro/oscuro */
      color: #fff;
      font-size: 12px;
      font-weight: 600;
    }

    /* ── FORM ────────────────────────────────────────────── */
    .reg-form { display: flex; flex-direction: column; gap: 1.25rem; }

    .field { display: flex; flex-direction: column; gap: .45rem; }

    .field label {
      font-size: 13px;
      font-weight: 500;
      color: var(--text);                       /* era negro */
    }
    .field-optional { color: var(--muted); font-weight: 400; }

    .field input[type="text"],
    .field input[type="date"] {
      height: 42px;
      padding: 0 .9rem;
      border: 1.5px solid #d1d9e0;
      border-radius: var(--radius-md);
      font-size: 14px;
      color: var(--text);                       /* era negro */
      background: var(--bg);
      outline: none;
      transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    }
    .field input:focus {
      border-color: var(--primary-400);
      box-shadow: 0 0 0 3px rgba(22, 114, 154, .15);
      background: #fff;
    }

    .btn-save {
      align-self: flex-start;
      padding: 0 1.5rem;
      height: 42px;
      border: none;
      border-radius: var(--radius-md);
      background: var(--primary-600);          /* era negro */
      color: #fff;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background var(--transition-fast), transform var(--transition-fast);
    }
    .btn-save:hover  { background: var(--primary-400); transform: translateY(-1px); }
    .btn-save:active { transform: translateY(0); }

    /* ── TABLE ───────────────────────────────────────────── */
    .tbl-scroll { overflow-x: auto; }

    .cap-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13.5px;
    }
    .cap-table thead tr {
      border-bottom: 2px solid var(--primary-600);  /* era negro */
    }
    .cap-table th {
      padding: .7rem .9rem;
      text-align: left;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--primary-600);               /* era negro/oscuro */
      white-space: nowrap;
    }
    .cap-table td {
      padding: .65rem .9rem;
      border-bottom: 1px solid var(--bg);
      color: var(--text);                      /* era negro */
    }
    .cap-table tbody tr:last-child td { border-bottom: none; }
    .cap-table tbody tr:hover td      { background: var(--glass); }

    .td-num  { color: var(--muted); font-size: 12px; width: 40px; }
    .td-name { font-weight: 500; color: var(--text); }
    .td-date { color: var(--muted); font-family: 'JetBrains Mono', monospace; font-size: 12.5px; }
    .td-days { font-family: 'JetBrains Mono', monospace; font-size: 12.5px; font-weight: 600; }
    .td-empty{ text-align: center; padding: 2rem; color: var(--muted); }

    /* status badges */
    .st-badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 11.5px;
      font-weight: 600;
    }
    .st--ok   { background: #e6f9f5; color: var(--accent); }
    .st--warn { background: #fff8e6; color: #b45309; }
    .st--err  { background: #fdecea; color: #c0392b; }

    /* días column coloring */
    td.st--ok   { color: var(--accent); }
    td.st--warn { color: #b45309; }
    td.st--err  { color: #c0392b; }

    /* jQuery UI autocomplete override */
    .ui-autocomplete { z-index: 9999 !important; border-radius: var(--radius-md); border: 1.5px solid #d1d9e0; }
    .ui-menu-item-wrapper { font-size: 13.5px; color: var(--text); }
    </style>

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
    </script>
</head>
<body>

<!-- ── NAVBAR ────────────────────────────────────────────── -->
<nav class="topnav">
    <a href="../garantias/validador/validador.php" class="topnav-back">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8L10 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Atrás
    </a>

    <div class="topnav-links">
        <a href="../Evaluacion/index.html" class="tnl">Inicio</a>
        <a href="../Evaluacion/material.html" class="tnl">Material</a>
        <a href="../Evaluacion/examen.php" class="tnl">Cuestionario</a>
        <a href="../Evaluacion/lista_colaboradores.php" class="tnl tnl--active">Lista</a>
    </div>

    <button class="topnav-burger" id="burger">
        <span></span><span></span><span></span>
    </button>

    <div class="topnav-drawer" id="drawer">
        <a href="../Evaluacion/index.html" class="drawer-link">Inicio Capacitados</a>
        <a href="../Evaluacion/material.html" class="drawer-link">Material</a>
        <a href="../Evaluacion/examen.php" class="drawer-link">Cuestionario</a>
        <a href="../Evaluacion/lista_colaboradores.php" class="drawer-link drawer-active">Lista</a>
    </div>
</nav>

<!-- ── CONTENIDO ──────────────────────────────────────────── -->
<main class="main-wrap">

    <div class="page-header">
        <h1 class="page-title">Registro de Capacitación</h1>
        <p class="page-sub">Gestiona y consulta las capacitaciones del personal</p>
    </div>

    <?php if ($mensaje): ?>
    <div class="flash flash--<?= $tipo_mensaje ?>">
        <?= $tipo_mensaje === 'success' ? '✓' : ($tipo_mensaje === 'info' ? 'i' : '✕') ?>
        <?= htmlspecialchars($mensaje) ?>
    </div>
    <?php endif; ?>

    <!-- Formulario -->
    <section class="panel">
        <h2 class="panel-title">Nuevo registro</h2>
        <form method="POST" autocomplete="off" class="reg-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="field">
                <label for="nombre">Nombre del colaborador</label>
                <input type="text" name="nombre" id="nombre"
                       placeholder="Escribe el nombre…" maxlength="120" required>
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

            <button type="submit" class="btn-save">Guardar registro</button>
        </form>
    </section>

    <!-- Tabla -->
    <section class="panel">
        <div class="panel-head">
            <h2 class="panel-title">Todos los capacitados</h2>
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

</main>

<script>
const burger = document.getElementById('burger');
const drawer = document.getElementById('drawer');
burger.addEventListener('click', () => {
    drawer.classList.toggle('open');
    burger.classList.toggle('is-open');
});
document.addEventListener('click', e => {
    if (!burger.contains(e.target) && !drawer.contains(e.target)) {
        drawer.classList.remove('open');
        burger.classList.remove('is-open');
    }
});
</script>
</body>
</html>