<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

include_once '../../funciones.php';
$conn = conectarBD();

$actualizadas = actualizarGarantiasDiario($conn);
$garantias = verTablavalidador();

$nombre     = $_SESSION['validador_nombre'] ?? '';
$apellido   = $_SESSION['validador_apellido'] ?? '';
$validador_id = $_SESSION['validador_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel del Validador</title>
  <link rel="stylesheet" href="../../csstabla.css?v=<?php echo time(); ?>">
  <style>
    .btn-edit {
      display: inline-block !important;
      visibility: visible !important;
      opacity: 1 !important;
    }
    .badge-dpto {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 10px;
      font-size: .78em;
      font-weight: 700;
      letter-spacing: .03em;
      white-space: nowrap;
    }
    .badge-im { background: #fde8ec; color: #c0392b; }
    .badge-tm { background: #e8f4fd; color: #1a6fa8; }
    .btn-fotos {
      border: none; border-radius: 6px;
      padding: 4px 8px; cursor: pointer; font-size: .85em;
    }
    .btn-fotos.con-foto    { background: #e67e22; color: #fff; }
    .btn-fotos.con-foto:hover { background: #ca6f1e; }
    .btn-fotos.sin-foto    { background: #ccc; color: #888; cursor: default; }
    .modal-fotos {
      display: none; position: fixed; z-index: 2000;
      left: 0; top: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.88);
      justify-content: center; align-items: center; flex-direction: column;
    }
    .modal-fotos.activo { display: flex; }
    .modal-fotos-contenido {
      position: relative; display: flex;
      align-items: center; gap: 12px; max-width: 90vw;
    }
    .modal-fotos img {
      max-width: 80vw; max-height: 80vh;
      border-radius: 8px; object-fit: contain; background: #111;
    }
    .modal-fotos .flecha {
      background: rgba(255,255,255,0.2); color: white;
      border: none; border-radius: 50%; width: 44px; height: 44px;
      font-size: 1.5em; cursor: pointer; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      transition: background .2s;
    }
    .modal-fotos .flecha:hover  { background: rgba(255,255,255,0.4); }
    .modal-fotos .flecha:disabled { opacity: .2; cursor: default; }
    .modal-fotos .cerrar-fotos {
      position: absolute; top: -22px; right: -8px;
      background: none; border: none; color: #fff;
      font-size: 1.8em; cursor: pointer; line-height: 1;
    }
    .contador-foto { color: #fff; font-size: .9em; margin-top: 10px; }
    .modal-envio {
      display: none; position: fixed; z-index: 2000;
      left: 0; top: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.55);
      justify-content: center; align-items: center;
    }
    .modal-envio.activo { display: flex; }
    .modal-envio-caja {
      background: #fff; border-radius: 10px; padding: 24px 28px;
      max-width: 500px; width: 90%; position: relative;
      box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    }
    .modal-envio-caja h3 { margin: 0 0 16px; color: #0F5476; font-size: 1.1em; }
    .modal-envio-caja .fila { margin-bottom: 12px; font-size: .95em; line-height: 1.5; }
    .modal-envio-caja .fila strong {
      display: block; font-size: .78em; text-transform: uppercase;
      letter-spacing: .04em; color: #888; margin-bottom: 2px;
    }
    .modal-envio-caja .cerrar-envio {
      position: absolute; top: 10px; right: 14px;
      background: none; border: none; font-size: 1.4em; cursor: pointer; color: #888;
    }
    .btn-envio {
      background: #1D6C90; color: #fff; border: none;
      border-radius: 6px; padding: 4px 8px; cursor: pointer; font-size: .85em;
    }
    .btn-envio:hover { background: #145570; }
  </style>
</head>

<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<body>

<nav style="background:#0F5476; padding:10px; position:relative; display:flex; align-items:center; gap:4px;">
  <h1 id="nombre">­ </h1>
  <input type="checkbox" id="check">
  <label class="bar" for="check">
    <span class="top"></span><span class="middle"></span><span class="bottom"></span>
  </label>

  <!-- Flecha izquierda -->
  <button id="nav-prev" onclick="scrollMenu(-1)" style="
    display:none; background:rgba(255,255,255,0.15); border:none; color:#fff;
    width:28px; height:36px; cursor:pointer; font-size:1.3em; border-radius:4px;
    align-items:center; justify-content:center; flex-shrink:0;
  ">&#8249;</button>

  <!-- Contenedor scrolleable -->
  <div id="menu-scroll" style="overflow:hidden; flex:1;">
    <ul id="menu" style="display:flex; flex-direction:row; flex-wrap:nowrap; margin:0; padding:0; list-style:none; gap:4px;">
      <li>
        <a href="validador.php" style="display:flex;align-items:center;gap:8px;white-space:nowrap;">
          <span style="display:inline-flex;width:40px;height:40px;background:#fff;border-radius:50%;justify-content:center;align-items:center;">
            <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>"
                 style="width:30px;height:30px;object-fit:contain;" alt="Logo">
          </span>Home
        </a>
      </li>
      <li><a href="../vendedor/garantias.php" style="white-space:nowrap;">👨🏻‍💼 Apdo. Vendedor</a></li>
      <li><a href="anotarmermassinregistrar.php" style="white-space:nowrap;">⚠️ Anot Mer.</a></li>
      <li><a href="tabla.php" style="white-space:nowrap;">📌 Mermas sin reg.</a></li>
      <li><a href="../../Evaluacion/lista_colaboradores.php" style="white-space:nowrap;">📘 Capacit.</a></li>
      <li><a href="../../compatibilidades/consultar.php" style="white-space:nowrap;">🔗 Compatibilidades</a></li>
      <li><a href="../../kpis/index.php" style="white-space:nowrap;">📈 KPIs</a></li>
      <li><a href="guardar_imagen.php" style="white-space:nowrap;">🖼️ Imagenes</a></li>
      <li><a href="sucursales.php" style="white-space:nowrap;">🏬 Sucursales</a></li>
      <li><a href="https://docs.google.com/spreadsheets/d/1QIicEhXQNDOwBXIwqZs9Y0KT1MdWluXwdPh68vwlCVc/edit?usp=sharing" style="white-space:nowrap;">📑 Bitacora</a></li>
      <li><a href="Validadores.php" style="white-space:nowrap;">🆕 Validador</a></li>
      <li><a href="../../existencias/index.php" style="white-space:nowrap;">📦 Existencias</a></li>
    </ul>
  </div>

  <!-- Flecha derecha -->
  <button id="nav-next" onclick="scrollMenu(1)" style="
    display:none; background:rgba(255,255,255,0.15); border:none; color:#fff;
    width:28px; height:36px; cursor:pointer; font-size:1.3em; border-radius:4px;
    align-items:center; justify-content:center; flex-shrink:0;
  ">&#8250;</button>
</nav>

<script>
function checkMenuOverflow() {
  const wrap = document.getElementById('menu-scroll');
  const ul   = document.getElementById('menu');
  const prev = document.getElementById('nav-prev');
  const next = document.getElementById('nav-next');
  if (!wrap || !ul) return;

  const overflows = ul.scrollWidth > wrap.clientWidth;
  prev.style.display = overflows ? 'flex' : 'none';
  next.style.display = overflows ? 'flex' : 'none';
  updateArrows();
}

function scrollMenu(dir) {
  const wrap = document.getElementById('menu-scroll');
  wrap.scrollBy({ left: dir * 160, behavior: 'smooth' });
  setTimeout(updateArrows, 350);
}

function updateArrows() {
  const wrap = document.getElementById('menu-scroll');
  const prev = document.getElementById('nav-prev');
  const next = document.getElementById('nav-next');
  if (!wrap) return;
  prev.style.opacity = wrap.scrollLeft <= 0 ? '0.35' : '1';
  next.style.opacity = wrap.scrollLeft + wrap.clientWidth >= wrap.scrollWidth - 1 ? '0.35' : '1';
}

document.getElementById('menu-scroll')?.addEventListener('scroll', updateArrows);
window.addEventListener('resize', checkMenuOverflow);
document.addEventListener('DOMContentLoaded', checkMenuOverflow);
</script>

<div class="fila-usuario">
  <header style="display:flex;justify-content:flex-end;align-items:center;gap:15px;padding:10px 20px;font-family:Arial,sans-serif;">
    <span style="font-weight:600;font-size:18px;">Bienvenido, <?= htmlspecialchars($nombre) . ' ' . htmlspecialchars($apellido) ?></span>
    <form action="logout.php" method="POST" style="margin:0;">
      <button type="submit" class="Btn" aria-label="Cerrar sesión">
        <div class="sign">
          <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
            <path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"/>
          </svg>
        </div>
        <div class="text">ㅤLogout</div>
      </button>
    </form>
  </header>
</div>

<div class="container">
  <h2>Historial de Garantías y Mermas</h2>

  <div class="filters-container">
    <h3>Filtros por Columna</h3>
    <div class="filter-row">
      <div class="filter-group">
        <label>PLOWS:</label>
        <input type="text" id="filter-plows" placeholder="Escanea o escribe el código">
      </div>
      <div class="filter-group">
        <label>Departamento:</label>
        <select id="filter-dpto">
          <option value="">Todos</option>
          <option value="Accesorios">Accesorios</option>
          <option value="Telefonia">Telefonía</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Tipo:</label>
        <select id="filter-tipo"><option value="">Todos</option></select>
      </div>
      <div class="filter-group">
        <label>Causa:</label>
        <select id="filter-causa"><option value="">Todas</option></select>
      </div>
      <div class="filter-group">
        <label>Sucursal:</label>
        <select id="filter-sucursal"><option value="">Todas</option></select>
      </div>
      <div class="filter-group">
        <label>Colaborador:</label>
        <select id="filter-colaborador"><option value="">Todos</option></select>
      </div>
    </div>
    <div class="filter-row">
      <div class="filter-group">
        <label>Estatus:</label>
        <select id="filter-estatus"><option value="">Todos</option></select>
      </div>
      <div class="filter-group">
        <label>Validador:</label>
        <select id="filter-validador"><option value="">Todos</option></select>
      </div>
      <div class="filter-group">
        <label>Fecha Desde:</label>
        <input type="date" id="filter-fecha-desde">
      </div>
      <div class="filter-group">
        <label>Fecha Hasta:</label>
        <input type="date" id="filter-fecha-hasta">
      </div>
    </div>
    <div class="filter-buttons">
      <button class="btn-clear" onclick="clearAllFilters()">🔄 Limpiar Filtros</button>
      <button type="button" id="btn-descargar">📥 Descargar</button>
    </div>
  </div>

  <div class="results-info">
    <span id="results-count">Cargando datos...</span>
  </div>

  <form method="POST" action="guardar_validaciones.php">
    <button class="action_has has_saved" aria-label="Guardar cambios" type="submit">
      <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
           stroke-linejoin="round" stroke-linecap="round" stroke-width="2"
           viewBox="0 0 24 24" stroke="currentColor" fill="none">
        <path d="m19,21H5c-1.1,0-2-.9-2-2V5c0-1.1.9-2,2-2h11l5,5v11c0,1.1-.9,2-2,2Z"/>
        <path d="M7 3L7 8L15 8"/><path d="M17 20L17 13L7 13L7 20"/>
      </svg>
    </button><br>

    <div class="table-container">
      <table border="1" cellpadding="0" cellspacing="0" id="garantias-table">
        <thead>
          <tr style="background:#ddd;">
            <th>PLOWS</th><th>Depto</th><th>Tipo</th><th>Causa</th><th>Piezas</th>
            <th>Sucursal</th><th>Colaborador</th><th>Fecha de Registro</th>
            <th>Estatus</th><th>Acciones</th><th>Validador</th>
            <th>Piezas Validadas</th><th>Número de Ajuste</th>
            <th>Anotación del Validador</th><th>Hora de Validación</th>
            <th>Fecha de Validación</th><th>Anotación del Vendedor</th>
            <th>Datos de Envío</th>
          </tr>
        </thead>
        <tbody id="table-body">
          <?php foreach ($garantias as $g): ?>
          <?php
            $bloquear   = ($g['piezas_validadas'] > 0 && $g['numero_ajuste'] > 0 && $g['id_validador'] !== null);
            $readonly   = $bloquear ? 'readonly' : '';
            $dpto       = strtolower($g['dpto'] ?? 'im');
            $dptoNombre = $g['dpto_nombre'] ?? ($dpto === 'tm' ? 'Telefonia' : 'Accesorios');
            $badgeClass = $dpto === 'tm' ? 'badge-tm' : 'badge-im';

            $fotosUrls = [];
            if (!empty($g['foto'])) {
              foreach (explode(',', $g['foto']) as $pf) {
                $pf = trim($pf);
                $fotosUrls[] = strpos($pf,'|') !== false ? explode('|',$pf,2)[1] : $pf;
              }
              $fotosUrls = array_filter($fotosUrls);
            }
            $fotosJson    = htmlspecialchars(json_encode(array_values($fotosUrls)), ENT_QUOTES);
            $tieneFoto    = count($fotosUrls) > 0;
            $fotoBtnClass = $tieneFoto ? 'btn-fotos con-foto' : 'btn-fotos sin-foto';
            $fotoOnClick  = $tieneFoto ? 'abrirFotos(this)' : 'alert(\'Este registro no tiene fotos.\')';
            $createdAt    = $g['created_at']  ?? '---';
            $dispositivo  = $g['dispositivo'] ?? '---';
          ?>
          <tr data-id="<?= $g['id'] ?>"
              data-plows="<?= htmlspecialchars($g['plows']) ?>"
              data-dpto="<?= htmlspecialchars($dptoNombre) ?>"
              data-tipo="<?= htmlspecialchars($g['tipo']) ?>"
              data-causa="<?= htmlspecialchars($g['causa']) ?>"
              data-piezas="<?= htmlspecialchars($g['piezas']) ?>"
              data-sucursal="<?= htmlspecialchars($g['sucursal']) ?>"
              data-colaborador="<?= htmlspecialchars($g['apasionado']) ?>"
              data-fecha="<?= htmlspecialchars($g['fecha']) ?>"
              data-estatus="<?= htmlspecialchars($g['estatus']) ?>"
              data-validador="<?= $g['validador_nombre'] ? htmlspecialchars($g['validador_nombre'].' '.$g['validador_apellido']) : 'No validado' ?>">

            <td><?= htmlspecialchars($g['plows']) ?></td>
            <td><span class="badge-dpto <?= $badgeClass ?>"><?= htmlspecialchars($dptoNombre) ?></span></td>
            <td><?= htmlspecialchars($g['tipo']) ?></td>
            <td><?= htmlspecialchars($g['causa']) ?></td>
            <td><?= htmlspecialchars($g['piezas']) ?></td>
            <td><?= htmlspecialchars($g['sucursal']) ?></td>
            <td><?= htmlspecialchars($g['apasionado']) ?></td>
            <td><?= htmlspecialchars($g['fecha']) ?></td>
            <td><?= htmlspecialchars($g['estatus']) ?></td>
            <td class="action-links">
              <button type="button" onclick="openEditModal(this)"
                data-id="<?= $g['id'] ?>"
                data-plows="<?= htmlspecialchars($g['plows']) ?>"
                data-piezas_validadas="<?= htmlspecialchars($g['piezas_validadas']) ?>"
                data-numero_ajuste="<?= htmlspecialchars($g['numero_ajuste']) ?>"
                data-anotaciones_validador="<?= htmlspecialchars($g['anotaciones_validador']) ?>"
                class="btn-edit">✏️ Editar</button>
              |
              <a href="eliminar.php?id=<?= $g['id'] ?>"
                 onclick="return confirm('¿Seguro que quieres eliminar esta garantía?')">🗑️ Eliminar</a>
              |
              <button type="button" class="<?= $fotoBtnClass ?>"
                onclick="<?= $fotoOnClick ?>"
                data-fotos="<?= $fotosJson ?>">🖼️ Fotos</button>
            </td>
            <td><?= $g['validador_nombre'] ? htmlspecialchars($g['validador_nombre'].' '.$g['validador_apellido']) : 'No validado' ?></td>
            <td><input type="number" name="piezas_validadas[<?= $g['id'] ?>]" value="<?= htmlspecialchars($g['piezas_validadas']) ?>" <?= $readonly ?>></td>
            <td><input type="number" name="numero_ajuste[<?= $g['id'] ?>]"    value="<?= htmlspecialchars($g['numero_ajuste']) ?>"    <?= $readonly ?>></td>
            <td><input type="text"   name="anotaciones_validador[<?= $g['id'] ?>]" value="<?= htmlspecialchars($g['anotaciones_validador']) ?>" <?= $readonly ?>></td>
            <td><?= htmlspecialchars($g['hora']) ?></td>
            <td><?= htmlspecialchars($g['fecha_validacion']) ?></td>
            <td><?= htmlspecialchars($g['anotaciones_vendedor']) ?></td>
            <td style="text-align:center;">
              <button type="button" class="btn-envio" onclick="abrirEnvio(this)"
                data-created="<?= htmlspecialchars($createdAt) ?>"
                data-dispositivo="<?= htmlspecialchars($dispositivo) ?>">📋 Ver</button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>

  <!-- ── Modal Editar ── -->
  <div id="editModal" class="modal" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;">
    <div class="modaldos">
      <h3>Editar Registro</h3>
      <form id="editForm">
        <input type="hidden" id="edit-id" name="id">
        <label>PLOWS:</label>
        <input type="text" id="edit-plows" name="plows" required><br>
        <label>Piezas Validadas:</label>
        <input type="number" id="edit-piezas-validadas" name="piezas_validadas"><br>
        <label>Número de Ajuste:</label>
        <input type="text" id="edit-numero-ajuste" name="numero_ajuste"><br>
        <label>Anotaciones del Validador:</label>
        <textarea id="edit-anotaciones-validador" name="anotaciones_validador" rows="3"></textarea><br>
        <div style="margin-top:15px;display:flex;justify-content:space-between;">
          <button type="button" onclick="closeModal()">Cancelar</button>
          <button type="submit" style="background:#1D6C90;color:#fff;">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── Modal Fotos ── -->
  <div id="modalFotos" class="modal-fotos" onclick="cerrarFotosFondo(event)">
    <div class="modal-fotos-contenido">
      <button class="cerrar-fotos" onclick="cerrarFotos()">✕</button>
      <button class="flecha" id="flechaIzq" onclick="cambiarFoto(-1)">&#8249;</button>
      <img id="fotoActual" src="" alt="Foto de merma">
      <button class="flecha" id="flechaDer" onclick="cambiarFoto(1)">&#8250;</button>
    </div>
    <div class="contador-foto" id="contadorFoto"></div>
  </div>

  <!-- ── Modal Datos de Envío ── -->
  <div id="modalEnvio" class="modal-envio" onclick="cerrarEnvioFondo(event)">
    <div class="modal-envio-caja">
      <button class="cerrar-envio" onclick="cerrarEnvio()">✕</button>
      <h3>📋 Datos de Envío</h3>
      <div class="fila"><strong>Fecha de creación</strong><span id="envioFecha"></span></div>
      <div class="fila"><strong>Dispositivo / Huella</strong><span id="envioDispositivo"></span></div>
    </div>
  </div>

</div>

<script>
let allRows = [], filteredRows = [];
// Bandera para poblar los filtros fijos solo una vez
let filtrosEstaticosCargados = false;

function initializeData() {
  allRows      = Array.from(document.querySelectorAll('#table-body tr'));
  filteredRows = [...allRows];

  // Sucursal, Colaborador, Estatus y Validador: se pueblan UNA sola vez
  if (!filtrosEstaticosCargados) {
    const uniq = key => [...new Set(allRows.map(r => r.dataset[key]))].filter(v => v).sort();
    repopulateSelect('filter-sucursal',    uniq('sucursal'));
    repopulateSelect('filter-colaborador', uniq('colaborador'));
    repopulateSelect('filter-estatus',     uniq('estatus'));
    repopulateSelect('filter-validador',   uniq('validador'));

    document.getElementById('filter-plows').addEventListener('input', applyFilters);
    ['filter-dpto','filter-tipo','filter-causa','filter-sucursal','filter-colaborador',
     'filter-estatus','filter-validador','filter-fecha-desde','filter-fecha-hasta']
      .forEach(id => document.getElementById(id).addEventListener('change', applyFilters));
    document.getElementById('filter-dpto').addEventListener('change', refreshDependentFilters);

    filtrosEstaticosCargados = true;
  }

  // Tipo y Causa siempre se recalculan según el depto activo
  refreshDependentFilters();
  updateResultsCount();
}

// Solo Tipo y Causa dependen del depto seleccionado
function refreshDependentFilters() {
  const dptoSel  = document.getElementById('filter-dpto').value;
  const baseRows = dptoSel ? allRows.filter(r => r.dataset.dpto === dptoSel) : allRows;
  const uniqFrom = key => [...new Set(baseRows.map(r => r.dataset[key]))].filter(v => v).sort();

  const tipoActual  = document.getElementById('filter-tipo').value;
  const causaActual = document.getElementById('filter-causa').value;

  repopulateSelect('filter-tipo',  uniqFrom('tipo'));
  repopulateSelect('filter-causa', uniqFrom('causa'));

  const tipoOpts  = [...document.getElementById('filter-tipo').options].map(o => o.value);
  const causaOpts = [...document.getElementById('filter-causa').options].map(o => o.value);
  document.getElementById('filter-tipo').value  = tipoOpts.includes(tipoActual)   ? tipoActual  : '';
  document.getElementById('filter-causa').value = causaOpts.includes(causaActual) ? causaActual : '';
}

// Limpia y repuebla cualquier select (manteniendo el primer option vacío)
function repopulateSelect(id, values) {
  const sel   = document.getElementById(id);
  const first = sel.options[0];
  sel.innerHTML = '';
  sel.appendChild(first);
  values.forEach(v => {
    const o = document.createElement('option');
    o.value = o.textContent = v;
    sel.appendChild(o);
  });
}

function applyFilters() {
  refreshDependentFilters();
  const f = {
    plows:       document.getElementById('filter-plows').value.toLowerCase(),
    dpto:        document.getElementById('filter-dpto').value,
    tipo:        document.getElementById('filter-tipo').value,
    causa:       document.getElementById('filter-causa').value,
    sucursal:    document.getElementById('filter-sucursal').value,
    colaborador: document.getElementById('filter-colaborador').value,
    estatus:     document.getElementById('filter-estatus').value,
    validador:   document.getElementById('filter-validador').value,
    desde:       document.getElementById('filter-fecha-desde').value,
    hasta:       document.getElementById('filter-fecha-hasta').value,
  };
  filteredRows = allRows.filter(row => {
    if (f.plows       && !row.dataset.plows.toLowerCase().includes(f.plows)) return false;
    if (f.dpto        && row.dataset.dpto        !== f.dpto)        return false;
    if (f.tipo        && row.dataset.tipo        !== f.tipo)        return false;
    if (f.causa       && row.dataset.causa       !== f.causa)       return false;
    if (f.sucursal    && row.dataset.sucursal    !== f.sucursal)    return false;
    if (f.colaborador && row.dataset.colaborador !== f.colaborador) return false;
    if (f.estatus     && row.dataset.estatus     !== f.estatus)     return false;
    if (f.validador   && row.dataset.validador   !== f.validador)   return false;
    if (f.desde       && row.dataset.fecha       <  f.desde)        return false;
    if (f.hasta       && row.dataset.fecha       >  f.hasta)        return false;
    return true;
  });
  renderFilteredTable();
}

function renderFilteredTable() {
  allRows.forEach(r => r.style.display = 'none');
  const tbody = document.getElementById('table-body');
  if (filteredRows.length === 0) {
    if (!document.getElementById('no-results-row')) {
      const tr = document.createElement('tr');
      tr.id = 'no-results-row';
      tr.innerHTML = '<td colspan="18" class="no-results">No se encontraron resultados</td>';
      tbody.appendChild(tr);
    }
    document.getElementById('no-results-row').style.display = '';
  } else {
    const nr = document.getElementById('no-results-row');
    if (nr) nr.style.display = 'none';
    filteredRows.forEach(r => r.style.display = '');
  }
  updateResultsCount();
}

function updateResultsCount() {
  if (allRows.length === 0) { document.getElementById('results-count').textContent = 'Cargando datos...'; return; }
  document.getElementById('results-count').textContent = `Mostrando ${filteredRows.length} de ${allRows.length} registros`;
}

function clearAllFilters() {
  ['filter-plows','filter-dpto','filter-tipo','filter-causa','filter-sucursal',
   'filter-colaborador','filter-estatus','filter-validador',
   'filter-fecha-desde','filter-fecha-hasta']
    .forEach(id => document.getElementById(id).value = '');
  refreshDependentFilters();
  filteredRows = [...allRows];
  renderFilteredTable();
}

document.addEventListener('DOMContentLoaded', () => {
  initializeData();
  cargarDatos();
  setInterval(cargarDatos, 1200000);
});

async function cargarDatos() {
  try {
    const resp = await fetch('get_garantias.php');
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const garantias = await resp.json();

    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';

    garantias.forEach(g => {
      const bloquear = (g.piezas_validadas > 0 && g.numero_ajuste > 0 && g.id_validador !== null);
      const ro = bloquear ? 'readonly' : '';

      const dpto       = (g.dpto || 'im').toLowerCase();
      const dptoNombre = g.dpto_nombre || (dpto === 'tm' ? 'Telefonia' : 'Accesorios');
      const badgeClass = dpto === 'tm' ? 'badge-tm' : 'badge-im';

      let fotosUrls = [];
      if (g.foto) {
        g.foto.split(',').forEach(pf => {
          pf = pf.trim();
          fotosUrls.push(pf.includes('|') ? pf.split('|')[1] : pf);
        });
        fotosUrls = fotosUrls.filter(Boolean);
      }
      const fotosJson    = JSON.stringify(fotosUrls).replace(/"/g,'&quot;');
      const tieneFoto    = fotosUrls.length > 0;
      const fotoBtnClass = tieneFoto ? 'btn-fotos con-foto' : 'btn-fotos sin-foto';
      const fotoOnClick  = tieneFoto ? 'abrirFotos(this)' : "alert('Este registro no tiene fotos.')";
      const createdAt    = (g.created_at  || '---').replace(/"/g,'');
      const dispositivo  = (g.dispositivo || '---').replace(/"/g,'&quot;');

      const tr = document.createElement('tr');
      tr.dataset.id          = g.id;
      tr.dataset.plows       = g.plows       || '';
      tr.dataset.dpto        = dptoNombre;
      tr.dataset.tipo        = g.tipo        || '';
      tr.dataset.causa       = g.causa       || '';
      tr.dataset.piezas      = g.piezas      || '';
      tr.dataset.sucursal    = g.sucursal    || '';
      tr.dataset.colaborador = g.apasionado  || '';
      tr.dataset.fecha       = g.fecha       || '';
      tr.dataset.estatus     = g.estatus     || '';
      tr.dataset.validador   = g.validador_nombre
        ? g.validador_nombre + ' ' + g.validador_apellido : 'No validado';

      tr.innerHTML = `
        <td>${g.plows   || ''}</td>
        <td><span class="badge-dpto ${badgeClass}">${dptoNombre}</span></td>
        <td>${g.tipo    || ''}</td>
        <td>${g.causa   || ''}</td>
        <td>${g.piezas  || ''}</td>
        <td>${g.sucursal|| ''}</td>
        <td>${g.apasionado || ''}</td>
        <td>${g.fecha   || ''}</td>
        <td>${g.estatus || ''}</td>
        <td class="action-links">
          <button type="button" onclick="openEditModal(this)"
            data-id="${g.id}"
            data-plows="${(g.plows||'').replace(/"/g,'&quot;')}"
            data-piezas_validadas="${g.piezas_validadas||''}"
            data-numero_ajuste="${g.numero_ajuste||''}"
            data-anotaciones_validador="${(g.anotaciones_validador||'').replace(/"/g,'&quot;')}"
            class="btn-edit">✏️</button>
          |
          <button onclick="if(confirm('¿Seguro que quieres eliminar esta garantía?')) location.href='eliminar.php?id=${g.id}'"
            class="btn-eliminar">🗑️</button>
          |
          <button type="button" class="${fotoBtnClass}" onclick="${fotoOnClick}"
            data-fotos="${fotosJson}">🖼️</button>
        </td>
        <td>${g.validador_nombre ? g.validador_nombre+' '+g.validador_apellido : 'No validado'}</td>
        <td><input type="number" name="piezas_validadas[${g.id}]" value="${g.piezas_validadas||''}" ${ro} style="width:80px;padding:4px;border:1px solid #ccc;border-radius:4px;"></td>
        <td><input type="number" name="numero_ajuste[${g.id}]"    value="${g.numero_ajuste||''}"    ${ro} style="width:100px;padding:4px;border:1px solid #ccc;border-radius:4px;"></td>
        <td><input type="text"   name="anotaciones_validador[${g.id}]" value="${(g.anotaciones_validador||'').replace(/"/g,'&quot;')}" ${ro} style="width:100px;padding:4px;border:1px solid #ccc;border-radius:4px;"></td>
        <td>${g.hora              || ''}</td>
        <td>${g.fecha_validacion  || ''}</td>
        <td>${g.anotaciones_vendedor || ''}</td>
        <td style="text-align:center;">
          <button type="button" class="btn-envio" onclick="abrirEnvio(this)"
            data-created="${createdAt}"
            data-dispositivo="${dispositivo}">📋 Ver</button>
        </td>`;
      tbody.appendChild(tr);
    });

    // Al recargar datos se resetea la bandera para que los estáticos se actualicen también
    filtrosEstaticosCargados = false;
    initializeData();
  } catch (err) {
    console.error('cargarDatos error:', err);
  }
}

/* ── Excel ── */
document.getElementById('btn-descargar').addEventListener('click', descargarExcel);
async function descargarExcel() {
  if (filteredRows.length === 0) { alert('No hay datos para descargar.'); return; }
  const wb = XLSX.utils.book_new();
  const enc = ["PLOWS","Depto","Tipo","Causa","Piezas","Sucursal","Colaborador","Fecha de Registro",
               "Estatus","Validador","Piezas Validadas","Número de Ajuste",
               "Anotación del Validador","Hora de Validación","Fecha de Validación","Anotación del Vendedor"];
  const data = [enc];
  filteredRows.forEach(row => {
    data.push([
      row.dataset.plows, row.dataset.dpto, row.dataset.tipo, row.dataset.causa, row.dataset.piezas,
      row.dataset.sucursal, row.dataset.colaborador, row.dataset.fecha, row.dataset.estatus,
      row.dataset.validador,
      row.querySelector('input[name^="piezas_validadas"]').value,
      row.querySelector('input[name^="numero_ajuste"]').value,
      row.querySelector('input[name^="anotaciones_validador"]').value,
      row.querySelector('td:nth-last-child(4)').textContent.trim(),
      row.querySelector('td:nth-last-child(3)').textContent.trim(),
      row.querySelector('td:nth-last-child(2)').textContent.trim(),
    ]);
  });
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(data), 'Tabla Completa');
  const sucursales = [...new Set(filteredRows.map(r=>r.dataset.sucursal))].sort();
  const causas     = [...new Set(filteredRows.map(r=>r.dataset.causa))].sort();
  sucursales.forEach(suc => {
    const d=[["Causa de Merma","Total Piezas"]]; let tot=0;
    causas.forEach(cau => {
      const s=filteredRows.filter(r=>r.dataset.sucursal===suc&&r.dataset.causa===cau).reduce((a,r)=>a+(parseInt(r.dataset.piezas)||0),0);
      if(s>0){d.push([cau,s]);tot+=s;}
    });
    d.push(["Total",tot]);
    XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(d),suc.substring(0,30));
  });
  const res=[["Causa de Merma","Total Piezas"]];
  causas.forEach(cau=>{
    const s=filteredRows.filter(r=>r.dataset.causa===cau).reduce((a,r)=>a+(parseInt(r.dataset.piezas)||0),0);
    if(s>0)res.push([cau,s]);
  });
  res.push(["Total",res.slice(1).reduce((a,r)=>a+r[1],0)]);
  XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(res),'Resumen Total');
  XLSX.writeFile(wb,'garantias_filtradas.xlsx');
}

/* ── Modal Fotos ── */
let fotosActuales=[], fotoIdx=0;
function abrirFotos(btn){
  try{fotosActuales=JSON.parse(btn.dataset.fotos||'[]');}catch(e){fotosActuales=[];}
  if(!fotosActuales.length){alert('Este registro no tiene fotos.');return;}
  fotoIdx=0;mostrarFoto();
  document.getElementById('modalFotos').classList.add('activo');
}
function mostrarFoto(){
  document.getElementById('fotoActual').src=fotosActuales[fotoIdx];
  document.getElementById('contadorFoto').textContent=(fotoIdx+1)+' / '+fotosActuales.length;
  document.getElementById('flechaIzq').disabled=fotoIdx===0;
  document.getElementById('flechaDer').disabled=fotoIdx===fotosActuales.length-1;
}
function cambiarFoto(d){fotoIdx=Math.max(0,Math.min(fotosActuales.length-1,fotoIdx+d));mostrarFoto();}
function cerrarFotos(){document.getElementById('modalFotos').classList.remove('activo');document.getElementById('fotoActual').src='';}
function cerrarFotosFondo(e){if(e.target===document.getElementById('modalFotos'))cerrarFotos();}

/* ── Modal Envío ── */
function abrirEnvio(btn){
  document.getElementById('envioFecha').textContent       = btn.dataset.created     || '---';
  document.getElementById('envioDispositivo').textContent = btn.dataset.dispositivo || '---';
  document.getElementById('modalEnvio').classList.add('activo');
}
function cerrarEnvio(){document.getElementById('modalEnvio').classList.remove('activo');}
function cerrarEnvioFondo(e){if(e.target===document.getElementById('modalEnvio'))cerrarEnvio();}

/* ── Modal Editar ── */
function openEditModal(btn){
  document.getElementById('edit-id').value                    = btn.dataset.id;
  document.getElementById('edit-plows').value                 = btn.dataset.plows               || '';
  document.getElementById('edit-piezas-validadas').value      = btn.dataset.piezas_validadas     || '';
  document.getElementById('edit-numero-ajuste').value         = btn.dataset.numero_ajuste        || '';
  document.getElementById('edit-anotaciones-validador').value = btn.dataset.anotaciones_validador|| '';
  document.getElementById('editModal').style.display='flex';
}
function closeModal(){document.getElementById('editModal').style.display='none';}
document.getElementById('editForm').addEventListener('submit', async function(e){
  e.preventDefault();
  try{
    const resp=await fetch('editar_garantia.php',{method:'POST',body:new FormData(this)});
    const data=await resp.json();
    if(data.success){alert('Registro actualizado correctamente');location.reload();}
    else{alert('Error al actualizar: '+data.message);}
  }catch(err){console.error(err);alert('Error de conexión con el servidor.');}
});
</script>

<br>
<footer style="text-align:center;padding:10px;font-size:.85rem;color:#777;margin-top:50px;">
  <p>&copy; <span id="year"></span> Diego Fernando Martínez Santiago</p>
</footer>
<script>document.getElementById('year').textContent=new Date().getFullYear();</script>
</body>
</html>