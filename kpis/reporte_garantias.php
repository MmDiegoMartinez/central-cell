<?php
// reporte_fusion.php
require_once '../funciones.php'; // ajusta la ruta si es necesario

// Endpoint AJAX para devolver garantías en JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'consultar_garantias') {
    $input = json_decode(file_get_contents('php://input'), true);
    $fechaInicio = $input['fechaInicio'] ?? '';
    $fechaFin = $input['fechaFin'] ?? '';
    $tipo = $input['tipo'] ?? '';

    header('Content-Type: application/json; charset=utf-8');

    if (!$fechaInicio || !$fechaFin || !$tipo) {
        echo json_encode(['error' => 'Parámetros incompletos']);
        exit;
    }

    $garantias = consultarGarantias($fechaInicio, $fechaFin, $tipo);
    echo json_encode(['data' => $garantias]);
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fusión Excel + Garantías — INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<link rel="stylesheet" href="../styles.css">

<style>
  /* ---- Ajustes puntuales que el CSS base no cubre (no se toca styles.css) ---- */
  #inputFile{
    position:absolute;width:1px;height:1px;padding:0;margin:-1px;
    overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;
  }

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
  .btn:disabled{opacity:.5;cursor:not-allowed;}
  .form-field{
    display:flex;flex-direction:column;gap:6px;
  }
  .form-field label{
    font-size:13px;font-weight:600;color:var(--on-surface-variant);
  }
  .form-field input[type="date"],
  .form-field select{
    padding:10px 12px;
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    background:var(--surface-container-low);
    color:var(--on-surface);
    font-size:14px;font-family:inherit;
    min-width:170px;
  }
  .form-field input[type="date"]:focus,
  .form-field select:focus{outline:none;border-color:var(--primary);}

  .loader{
    display:none;
    align-items:center;justify-content:center;gap:var(--space-md);
    padding:var(--space-lg);margin-bottom:var(--space-lg);
    background:var(--surface-container-low);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
  }
  .loader.active{display:flex;}
  .spinner{
    width:22px;height:22px;border-radius:50%;
    border:3px solid var(--outline-variant);
    border-top-color:var(--primary);
    animation:spin .8s linear infinite;
  }
  @keyframes spin{to{transform:rotate(360deg);}}
  .loader-text{font-size:14px;font-weight:600;color:var(--on-surface-variant);}

  .file-status{
    font-size:13px;color:var(--on-surface-variant);
    margin-top:10px;display:flex;align-items:center;gap:6px;
  }

  .table-wrap{overflow-x:auto;}
  .data-table{
    width:100%;border-collapse:collapse;font-size:13px;
    background:var(--surface);border-radius:var(--radius-lg);overflow:hidden;
  }
  .data-table caption{
    text-align:left;font-weight:700;font-size:15px;
    padding:0 0 10px;color:var(--on-surface);
  }
  .data-table thead th{
    background:#00838F;color:#fff;font-weight:700;
    padding:10px 12px;text-align:left;white-space:nowrap;
  }
  .data-table tbody td{
    padding:8px 12px;border-bottom:1px solid var(--outline-variant);
    white-space:nowrap;
  }
  .data-table tbody tr:nth-child(even){background:var(--surface-container-low);}
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
      <h1 class="text-headline-lg" style="margin:6px 0 0">Mermas / Ventas %</h1>
      <div class="lesson-meta">
        <span><span class="material-symbols-outlined" style="font-size:16px">percent</span> Innovación Móvil</span>
      </div>

      <div class="intro-panel">
        <div class="icon-badge"><span class="material-symbols-outlined">insights</span></div>
        <div>
          <h3 style="margin:0">Fusiona ventas y garantías</h3>
          <p>Selecciona el rango de fechas y el tipo de producto, carga el archivo de ventas y genera el resumen de mermas por sucursal.</p>
        </div>
      </div>

      <!-- Paso 1: Parámetros y archivo -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">1</div>
          <h3 class="step-title text-headline-sm">Configura y carga tu archivo</h3>
        </div>

        <form id="mainForm" onsubmit="return false;">
          <div class="controls">
            <div class="form-field">
              <label for="fechaInicio">Desde</label>
              <input type="date" id="fechaInicio" required>
            </div>

            <div class="form-field">
              <label for="fechaFin">Hasta</label>
              <input type="date" id="fechaFin" required>
            </div>

            <div class="form-field">
              <label for="tipoProducto">Tipo</label>
              <select id="tipoProducto" required>
                <option value="">-- Selecciona --</option>
                <option value="Hidrogel">Hidrogel</option>
                <option value="Protection Pro">Protection Pro</option>
              </select>
            </div>

            <input type="file" id="inputFile" accept=".xlsx,.xls">
            <button class="btn btn-outline" id="fileButton" type="button">
              <span class="material-symbols-outlined">upload_file</span>
              Seleccionar Archivo
            </button>

            <button id="procesarBtn" class="btn btn-primary" type="button" disabled>
              <span class="material-symbols-outlined">rocket_launch</span>
              Procesar
            </button>
            <button id="descargarBtn" class="btn btn-secondary" type="button" disabled>
              <span class="material-symbols-outlined">download</span>
              Generar Excel de Salida
            </button>
          </div>
        </form>

        <div class="loader" id="loader">
          <div class="spinner"></div>
          <div class="loader-text">Procesando archivo...</div>
        </div>

        <div id="mensajes" class="file-status"></div>
      </section>

      <!-- Paso 2: Vista previa -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">2</div>
          <h3 class="step-title text-headline-sm">Vista previa</h3>
        </div>

        <div id="vistaPrev" class="table-wrap"></div>
      </section>

    </div>
  </div>
</div>

<script>
document.getElementById("fileButton").addEventListener("click", () => {
  document.getElementById("inputFile").click();
});

// Variables globales
const inputFile = document.getElementById('inputFile');
const procesarBtn = document.getElementById('procesarBtn');
const descargarBtn = document.getElementById('descargarBtn');
const mensajes = document.getElementById('mensajes');
const vistaPrev = document.getElementById('vistaPrev');

let ventasPorSucursal = {}; // { 'Reforma': countVentas, ... }
let garantiasFromDB = [];   // filas devueltas por PHP
let resumenPorSucursal = {}; // resultado final
let hojasWorkbook = null;   // estructura para exportar

inputFile.addEventListener('change', () => {
    procesarBtn.disabled = !inputFile.files.length;
    mensajes.innerText = inputFile.files.length ? `Archivo seleccionado: ${inputFile.files[0].name}` : '';
});

procesarBtn.addEventListener('click', async () => {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const tipo = document.getElementById('tipoProducto').value;
    if (!fechaInicio || !fechaFin || !tipo) {
        alert('Selecciona fecha inicio, fecha fin y tipo de producto.');
        return;
    }
    if (!inputFile.files.length) {
        alert('Carga primero el archivo Excel de ventas.');
        return;
    }

    mensajes.innerText = 'Leyendo archivo...';
    document.getElementById('loader').classList.add('active');

    try {
        ventasPorSucursal = await leerExcelVentas(inputFile.files[0], tipo);
    } catch (err) {
        mensajes.innerText = 'Error leyendo Excel: ' + err;
        return;
    }

    mensajes.innerText = 'Consultando merma en la base de datos...';
    const serverData = await fetchGarantiasFromServer(fechaInicio, fechaFin, tipo);
    if (serverData.error) {
        mensajes.innerText = 'Error al obtener datos del servidor: ' + serverData.error;
        return;
    }

    garantiasFromDB = serverData.data || [];
    mensajes.innerText = `Ventas leídas: ${Object.keys(ventasPorSucursal).length} sucursales. Mermas recuperadas: ${garantiasFromDB.length} registros. Procesando resumen...`;

    procesarUnion(ventasPorSucursal, garantiasFromDB);
    mensajes.innerText = 'Procesamiento completado.';
    descargarBtn.disabled = false;
    mostrarResumenPequeno();
});

// ---------- LECTURA EXCEL CORREGIDA ----------
async function leerExcelVentas(file, tipoSeleccionado) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const wb = XLSX.read(data, { type: 'array' });
                const sheet = wb.Sheets[wb.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "" });

                if (!rows || rows.length < 2) {
                    reject('Archivo vacío o sin datos');
                    return;
                }

                const headerRow = rows[0].map(h => String(h || "").trim());

                // Buscar índices claves
                const idx = {
                    almacen: headerRow.findIndex(h => /almacen/i.test(h)),
                    n1: headerRow.findIndex(h => /n1/i.test(h)),
                    n3: headerRow.findIndex(h => /n3/i.test(h)),
                    tipoProducto: headerRow.findIndex(h => /tipo\s*producto|tipoproducto/i.test(h)),
                    cantidad: headerRow.findIndex(h => /cantidad|venta|ventas|qty/i.test(h))
                };

                const ventasMap = {};

                for (let r = 1; r < rows.length; r++) {
                    const row = rows[r];
                    const valN1 = String(row[idx.n1] || "").trim();
                    const valN3 = String(row[idx.n3] || "").trim();
                    if (valN1 !== 'INNOVACION MOVIL' || valN3 !== 'PROTECTOR') continue;

                    const rawAlmacen = String(row[idx.almacen] || "").trim();
                    if (!rawAlmacen) continue;
                    let nombreSuc = rawAlmacen.replace(/^Central\s*Cell\s*/i, '').trim();
                    if (!nombreSuc) nombreSuc = rawAlmacen;

                    const tipoProd = String(row[idx.tipoProducto] || "").trim().toUpperCase();
                    let cantidad = Number(row[idx.cantidad] || 0);

                    // CORRECCIÓN: Filtrado exacto tipo de producto
                    if (tipoSeleccionado === 'Hidrogel') {
                        if (!tipoProd.includes("HIDROGEL")) continue;
                    } else if (tipoSeleccionado === 'Protection Pro') {
                        if (!tipoProd.includes("POLIMERO")) continue;
                    }

                    ventasMap[nombreSuc] = (ventasMap[nombreSuc] || 0) + cantidad;
                }

                resolve(ventasMap);
            } catch (err) {
                reject(err.message || err);
            }
            document.getElementById('loader').classList.remove('active');
        };
        reader.readAsArrayBuffer(file);
    });
}

// ---------- AJAX: obtener garantias ----------
async function fetchGarantiasFromServer(fechaInicio, fechaFin, tipo) {
    try {
        const resp = await fetch(window.location.pathname + '?action=consultar_garantias', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ fechaInicio, fechaFin, tipo })
        });
        return await resp.json();
    } catch (err) {
        return { error: err.message || 'Error de red' };
    }
}

// ---------- PROCESAR UNIÓN Y HOJAS ----------
function procesarUnion(ventasMap, garantias) {
    const causasCanon = [
        "Cambio de producto (Garantia)",
        "Defecto de fabrica",
        "Mala instalacion de producto (garantia)",
        "Error (Nuevo Ingreso)",
        "Se encontro roto o descompuesto",
        "Mala instalacion del producto (merma)",
        "Fallo de la maquina"
    ];

    function normaliza(s) {
        if (!s) return '';
        return s.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    }
    const causasNorm = causasCanon.map(c => normaliza(c));

    resumenPorSucursal = {};

    // Inicializar con ventas
    Object.keys(ventasMap).forEach(suc => {
        resumenPorSucursal[suc] = { ventas: Number(ventasMap[suc] || 0), mermasPorCausa: {}, totalMermas: 0 };
        causasCanon.forEach(c => resumenPorSucursal[suc].mermasPorCausa[c] = 0);
    });

    // Incluir sucursales solo en garantias
    garantias.forEach(g => {
        let suc = (g.sucursal || '').toString().replace(/^Central\s*Cell\s*/i, '').trim() || 'SIN_SUCURSAL';
        if (!resumenPorSucursal[suc]) {
            resumenPorSucursal[suc] = { ventas: 0, mermasPorCausa: {}, totalMermas: 0 };
            causasCanon.forEach(c => resumenPorSucursal[suc].mermasPorCausa[c] = 0);
        }
    });

    // Contabilizar garantias
    garantias.forEach(g => {
        let suc = (g.sucursal || '').toString().replace(/^Central\s*Cell\s*/i, '').trim() || 'SIN_SUCURSAL';
        const piezas = Number(g.piezas || 0);
        const causaRaw = normaliza(g.causa || '');
        let idx = causasNorm.indexOf(causaRaw);
        if (idx === -1) idx = causasNorm.findIndex(cn => causaRaw.includes(cn) || cn.includes(causaRaw) || causaRaw.includes(cn.split(' ')[0]));
        let causaCanon = idx !== -1 ? causasCanon[idx] : ('OTRO: ' + (g.causa || 'SIN_CAUSA'));

        if (!resumenPorSucursal[suc].mermasPorCausa[causaCanon]) resumenPorSucursal[suc].mermasPorCausa[causaCanon] = 0;

        resumenPorSucursal[suc].mermasPorCausa[causaCanon] += piezas;
        resumenPorSucursal[suc].totalMermas += piezas;
    });

    // Preparar hojasWorkbook
    hojasWorkbook = { sheets: {} };

    Object.entries(resumenPorSucursal).forEach(([suc, info]) => {
        const filas = [];
        filas.push(['Sucursal', suc]);
        filas.push([]);
        filas.push(['Causa', 'Cantidad']);
        Object.keys(info.mermasPorCausa).forEach(c => filas.push([c, info.mermasPorCausa[c]]));
        filas.push([]);
        filas.push(['Total Mermas', info.totalMermas]);
        hojasWorkbook.sheets[suc] = filas;
    });

    // Hoja Resumen
    const resumenRows = [];
    resumenRows.push(['Sucursal', 'VentasProducto', 'CantidadMermas', 'PorcentajeMerma']);
    let totalVentasGlobal = 0, totalMermasGlobal = 0;
    Object.entries(resumenPorSucursal).forEach(([suc, info]) => {
        const ventas = Number(info.ventas || 0);
        const mermas = Number(info.totalMermas || 0);
        const ratio = ventas > 0 ? (mermas / ventas) : (mermas > 0 ? 1 : 0);
        resumenRows.push([suc, ventas, mermas, Number(ratio.toFixed(6))]);
        totalVentasGlobal += ventas;
        totalMermasGlobal += mermas;
    });
    const totalRatio = totalVentasGlobal > 0 ? (totalMermasGlobal / totalVentasGlobal) : (totalMermasGlobal > 0 ? 1 : 0);
    resumenRows.push([]);
    resumenRows.push(['TOTAL GENERAL', totalVentasGlobal, totalMermasGlobal, Number(totalRatio.toFixed(6))]);
    hojasWorkbook.sheets['Resumen'] = resumenRows;
    // ======== HOJA GENERAL (por causa global) ========
const globalCausas = {};
let totalMermasGlobal2 = 0;

// Sumar todas las mermas por causa (de todas las sucursales)
Object.values(resumenPorSucursal).forEach(info => {
  Object.entries(info.mermasPorCausa).forEach(([causa, cant]) => {
    globalCausas[causa] = (globalCausas[causa] || 0) + Number(cant || 0);
    totalMermasGlobal2 += Number(cant || 0);
  });
});

const generalRows = [];
generalRows.push(['Causa', 'CantidadTotal', 'Porcentaje']);
Object.entries(globalCausas)
  .sort((a,b)=>a[0].localeCompare(b[0],'es'))
  .forEach(([causa, cant]) => {
    const pct = totalMermasGlobal2 > 0 ? (cant / totalMermasGlobal2) : 0; // ← decimal
    generalRows.push([causa, cant, Number(pct.toFixed(6))]);
});

generalRows.push([]);
generalRows.push(['TOTAL GENERAL', totalMermasGlobal2, '1.000000']); // el 100% en decimal
hojasWorkbook.sheets['GENERAL'] = generalRows;
}

// ---------- VISTA PREVIA ----------
function mostrarResumenPequeno() {
    let html = '<strong>Resumen:</strong><br>';
    html += '<table class="data-table"><thead><tr><th>Sucursal</th><th>Ventas</th><th>Mermas</th><th>Porc.</th></tr></thead><tbody>';
    Object.entries(resumenPorSucursal).forEach(([suc, info]) => {
        const ventas = Number(info.ventas || 0);
        const mermas = Number(info.totalMermas || 0);
        const ratio = ventas > 0 ? (mermas / ventas) : (mermas > 0 ? 1 : 0);
        const pctStr = (Number(ratio.toFixed(4)) * 100).toFixed(2) + '%';
        html += `<tr><td>${escapeHtml(suc)}</td><td>${ventas}</td><td>${mermas}</td><td>${pctStr}</td></tr>`;
    });
    html += '</tbody></table>';
    vistaPrev.innerHTML = html;
}

// ---------- EXPORTAR XLSX ----------
descargarBtn.addEventListener('click', () => {
    if (!hojasWorkbook || !hojasWorkbook.sheets) {
        alert('No hay datos para exportar. Procesa primero.');
        return;
    }
    const wb = XLSX.utils.book_new();
    Object.entries(hojasWorkbook.sheets).forEach(([name, rows]) => {
        const ws = XLSX.utils.aoa_to_sheet(rows);
        const maxCols = rows.reduce((m, r) => Math.max(m, r.length), 0);
        ws['!cols'] = Array.from({length: maxCols}, ()=>({wch:25}));
        XLSX.utils.book_append_sheet(wb, ws, sanitizeSheetName(name));
    });
    const now = new Date();
    XLSX.writeFile(wb, `Resumen_Mermas_${now.toISOString().slice(0,10)}.xlsx`);
});

// Helpers
function sanitizeSheetName(name){ return name.toString().slice(0,31).replace(/[\/\\\*\?\:\[\]]/g,'_'); }
function escapeHtml(unsafe){ return String(unsafe||'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;"); }
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