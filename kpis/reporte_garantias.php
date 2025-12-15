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
<title>Fusión Excel + Garantías — INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<style>
    body { font-family: Arial, sans-serif; margin: 18px; background:#f7f7f7; color:#222; }
    h1 { margin-top:0; }
    .controls { display:flex; gap:12px; align-items:center; margin-bottom:12px; flex-wrap:wrap; }
    input[type=file], input[type=date], select { padding:6px; }
    button.btn { background:#007bff; color:white; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; }
    button.btn:disabled { background:#999; cursor:not-allowed; }
    .note { font-size:13px; color:#333; margin-top:6px; }
    table { border-collapse:collapse; width:100%; background:white; box-shadow: 0 1px 3px rgba(0,0,0,0.07); margin-top:10px; }
    th, td { padding:8px 6px; border:1px solid #e1e1e1; text-align:center; font-size:13px; }
    th { background:#2f6fa6; color:white; }
</style>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header>
  <nav>
    <ul id="menu">
      <li>
        <a href="index.php" class="menu-link">
          <span class="logo-container">
            <img src="../Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />

          </span>
          Home
        </a>
      </li>
    </ul>
  </nav>
</header>
<div class="center-container">
<h1>📊 Mermas / ventas %</h1>

<form id="mainForm" onsubmit="return false;">
    <div class="controls">
        <label>Desde: <input type="date" id="fechaInicio" required></label>
        <label>Hasta: <input type="date" id="fechaFin" required></label>

        <label>Tipo:
            <select id="tipoProducto" required>
                <option value="">-- Selecciona --</option>
                <option value="Hidrogel">Hidrogel</option>
                <option value="Protection Pro">Protection Pro</option>
            </select>
        </label>

       
<div class="file-upload">
  <input id="inputFile" type="file" accept=".xlsx,.xls" />

  <button class="boton" id="fileButton" type="button">
    <div class="contenedorCarpeta">
      <div class="folder folder_one"></div>
      <div class="folder folder_two"></div>
      <div class="folder folder_three"></div>
      <div class="folder folder_four"></div>
    </div>
    <div class="active_line"></div>
    <span class="text">Seleccionar Archivo</span>
  </button>
</div>

<script>
// Conectamos el botón animado con el input oculto
document.getElementById("fileButton").addEventListener("click", () => {
  document.getElementById("inputFile").click();
});
</script>

        <button id="procesarBtn" class="btn" type="button" disabled>Procesar</button>
        <button id="descargarBtn" class="btn" type="button" disabled>Generar Excel de Salida</button>
    </div>
</form>
<!-- Loader animado mientras se procesa el archivo -->
  <div id="loader" class="loader-container" style="display:none;">
    <div class="cloud front">
      <span class="left-front"></span>
      <span class="right-front"></span>
    </div>
    <span class="sun sunshine"></span>
    <span class="sun"></span>
    <div class="cloud back">
      <span class="left-back"></span>
      <span class="right-back"></span>
    </div>
  </div>
</div>

<div id="mensajes" class="note"></div>
<div id="vistaPrev" class="note"></div>
</div>
<script>
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
document.getElementById('loader').style.display = 'flex';

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
            document.getElementById('loader').style.display = 'none'; 
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
    html += '<div class="center-table"><table><thead><tr><th>Sucursal</th><th>Ventas</th><th>Mermas</th><th>Porc.</th></tr></thead><tbody>';
    Object.entries(resumenPorSucursal).forEach(([suc, info]) => {
        const ventas = Number(info.ventas || 0);
        const mermas = Number(info.totalMermas || 0);
        const ratio = ventas > 0 ? (mermas / ventas) : (mermas > 0 ? 1 : 0);
        const pctStr = (Number(ratio.toFixed(4)) * 100).toFixed(2) + '%';
        html += `<tr><td>${escapeHtml(suc)}</td><td>${ventas}</td><td>${mermas}</td><td>${pctStr}</td></tr>`;
    });
    html += '</tbody></table></div>';
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
</body>
</html>
