<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

require_once '../funciones.php';

$mensaje      = '';
$tipo_mensaje = '';
$resultado    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
    $archivo   = $_FILES['archivo_excel'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensaje      = 'Error al subir el archivo (código ' . $archivo['error'] . ')';
        $tipo_mensaje = 'error';
    } elseif ($extension !== 'xlsx') {
        $mensaje      = 'Solo se aceptan archivos .xlsx';
        $tipo_mensaje = 'error';
    } else {
        $dirTemp     = '../temp';
        if (!is_dir($dirTemp)) mkdir($dirTemp, 0777, true);
        $rutaTemporal = $dirTemp . '/' . uniqid('exist_') . '.xlsx';

        if (move_uploaded_file($archivo['tmp_name'], $rutaTemporal)) {
            $resultado    = procesarArchivoExcel($rutaTemporal);
            @unlink($rutaTemporal);
            $mensaje      = $resultado['mensaje'];
            $tipo_mensaje = $resultado['exito'] ? 'success' : 'error';
        } else {
            $mensaje      = 'Error al guardar el archivo temporal';
            $tipo_mensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cargar Existencias</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css.css?v=<?php echo time(); ?>">

<style>

nav {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  height: 60px;
}

#nombre {
  font-weight: 700;
  font-size: 16px;
}

nav ul {
  list-style: none;
  display: flex;
  gap: 6px;
}

nav ul li a {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 7px 14px;
  border-radius: 6px;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
}

.header {
  display: flex;
  align-items: center;
  gap: 18px;
  margin-bottom: 28px;
}

.header-icon {
  font-size: 36px;
  line-height: 1;
  flex-shrink: 0;
}

.header h1 {
  font-size: 26px;
  font-weight: 600;
  letter-spacing: -0.02em;
  line-height: 1.2;
}

.header p {
  font-size: 14px;
  margin-top: 4px;
}
</style>
</head>
<body>

<nav>
  <span id="nombre">Central Cell</span>
  <ul>
    <li>
      <a href="../garantias/validador/validador.php">
        <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="24" height="24">
        Home
      </a>
    </li>
    <li>
      <a href="index.php">Panel Existencias</a>
    </li>
  </ul>
</nav>

<div class="page" style="margin-top: 80px;">
<div class="page">

  <!-- Header -->
  <div class="header">
    <div class="header-icon">📦</div>
    <div>
      <h1>Cargar Existencias</h1>
      <p>Importa el inventario desde un archivo Excel (.xlsx)</p>
    </div>
  </div>

  <!-- Instrucciones de columnas -->
  <div class="card">
    <div class="card-title">Columnas requeridas</div>
    <div class="cols-grid">
      <?php
      $cols = [
        ['A','Almacén'],['C','Descripción'],
        ['H','Existencia'],['M','Barcode'],['N','Categoría'],['Q','Precio público'],
      ];
      foreach ($cols as [$letra, $nombre]):
      ?>
      <div class="col-pill">
        <span class="col-letter"><?= $letra ?></span>
        <span class="col-name"><?= $nombre ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Alerta resultado anterior -->
  <?php if ($mensaje): ?>
  <div class="alert <?= $tipo_mensaje === 'success' ? 'success' : 'error' ?>">
    <span class="alert-icon"><?= $tipo_mensaje === 'success' ? '✅' : '❌' ?></span>
    <span><?= htmlspecialchars($mensaje) ?></span>
  </div>
  <?php endif; ?>

  <!-- Formulario -->
  <div class="card">
    <div class="card-title">Archivo de inventario</div>
    <form method="POST" enctype="multipart/form-data" id="uploadForm">
      <div class="drop-zone" id="dropZone">
        <div class="dz-icon">📄</div>
        <label class="dz-label" for="file-input">Seleccionar archivo</label>
        <input type="file" id="file-input" name="archivo_excel" accept=".xlsx" required>
        <div class="dz-file-name" id="fileName">— ningún archivo seleccionado —</div>
      </div>

      <!-- Barra de progreso (visible al subir) -->
      <div class="progress-wrap" id="progressWrap" style="margin-top:20px;">
        <div class="steps-row" id="stepsRow">
          <span class="step" id="step-upload">① Subiendo</span>
          <span class="step" id="step-parse">② Leyendo Excel</span>
          <span class="step" id="step-validate">③ Validando</span>
          <span class="step" id="step-insert">④ Insertando</span>
          <span class="step" id="step-done">⑤ Listo</span>
        </div>
        <div class="progress-bar-bg">
          <div class="progress-bar-fill" id="progressFill"></div>
        </div>
        <div class="progress-label">
          <span id="progressText">Iniciando…</span>
          <span id="progressPct">0%</span>
        </div>
      </div>

      <button type="submit" class="btn-submit" id="submitBtn" disabled>
        Cargar existencias
      </button>
    </form>
  </div>

  <!-- Resultados -->
  <?php if ($resultado && $resultado['exito']): ?>
  <div class="card">
    <div class="card-title">Resultados</div>
    <div class="stats-grid">
      <div class="stat-box ok">
        <div class="stat-num"><?= number_format($resultado['registros_insertados']) ?></div>
        <div class="stat-lbl">Insertados</div>
      </div>
      <div class="stat-box bad">
        <div class="stat-num"><?= number_format(count($resultado['registros_omitidos'])) ?></div>
        <div class="stat-lbl">Omitidos</div>
      </div>
    </div>

    <?php if (!empty($resultado['registros_omitidos'])): ?>
    <div class="omit-header">⚠ Registros con errores</div>
    <div class="omit-list">
      <?php foreach ($resultado['registros_omitidos'] as $o): ?>
      <div class="omit-item">
        <div class="omit-row">
          <span class="omit-fila">fila <?= $o['fila'] ?></span>
          <span class="omit-desc"><?= htmlspecialchars(mb_strimwidth($o['descripcion'], 0, 70, '…')) ?></span>
        </div>
        <div class="omit-store">🏪 <?= htmlspecialchars($o['almacen']) ?></div>
        <div class="omit-motivo">↳ <?= htmlspecialchars($o['motivo']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div><!-- /page -->

<script>
const fileInput  = document.getElementById('file-input');
const dropZone   = document.getElementById('dropZone');
const fileName   = document.getElementById('fileName');
const submitBtn  = document.getElementById('submitBtn');
const form       = document.getElementById('uploadForm');
const progressWrap = document.getElementById('progressWrap');
const fill       = document.getElementById('progressFill');
const pct        = document.getElementById('progressPct');
const txt        = document.getElementById('progressText');

fileInput.addEventListener('change', () => {
  if (fileInput.files[0]) {
    fileName.textContent = fileInput.files[0].name;
    dropZone.classList.add('has-file');
    submitBtn.disabled = false;
  }
});

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  const f = e.dataTransfer.files[0];
  if (f && f.name.endsWith('.xlsx')) {
    const dt = new DataTransfer();
    dt.items.add(f);
    fileInput.files = dt.files;
    fileName.textContent = f.name;
    dropZone.classList.add('has-file');
    submitBtn.disabled = false;
  }
});

const STEPS = [
  { id: 'step-upload',   pct: 10, label: 'Subiendo archivo…' },
  { id: 'step-parse',    pct: 30, label: 'Leyendo Excel…' },
  { id: 'step-validate', pct: 55, label: 'Validando registros…' },
  { id: 'step-insert',   pct: 80, label: 'Insertando en BD…' },
  { id: 'step-done',     pct: 100,label: 'Finalizando…' },
];

form.addEventListener('submit', () => {
  submitBtn.disabled = true;
  submitBtn.textContent = 'Procesando…';
  progressWrap.classList.add('show');

  let step = 0;
  function advance() {
    if (step >= STEPS.length) return;
    const s = STEPS[step];
    if (step > 0) {
      document.getElementById(STEPS[step-1].id).className = 'step done';
    }
    document.getElementById(s.id).className = 'step active';
    fill.style.width = s.pct + '%';
    pct.textContent  = s.pct + '%';
    txt.textContent  = s.label;
    step++;
    if (step < STEPS.length) {
      setTimeout(advance, 600 + Math.random() * 400);
    }
  }
  advance();
});
</script>
</body>
</html>