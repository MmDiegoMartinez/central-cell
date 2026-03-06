<?php
include_once '../funciones.php';
?>
<!DOCTYPE html>
<html lang="es">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
<meta charset="utf-8" />
<title>Análisis Multisemana — INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<style>
  body{font-family:Arial,Helvetica,sans-serif;margin:18px;background:#f7f7f7;color:#222}
  h1{margin-top:0}
  .controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
  input[type=file]{padding:6px}
  button.btn{background:#007bff;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer}
  button.btn:disabled{background:#999;cursor:not-allowed}
  table{border-collapse:collapse;width:100%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.07);margin-top:12px}
  th,td{padding:8px 6px;border:1px solid #e6e6e6;text-align:center;font-size:13px}
  th{background:#2f6fa6;color:#fff;position:sticky;top:0}
  .verde{background:#dff7df}
  .amarillo{background:#fff3cc}
  .rojo{background:#ffdad6}
  .note{margin-top:8px;color:#333}
  .debug{font-size:12px;color:#444;margin-top:8px;background:#fff;padding:8px;border:1px solid #eee}
</style>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header>
  <nav>
        <div class="nav-inner">
            <!-- Botón hamburguesa -->
            <label class="bar-menu">
                <input type="checkbox" id="menu-check">
                <span class="top"></span>
                <span class="middle"></span>
                <span class="bottom"></span>
            </label>

            <ul id="nav-menu">
                <li>
        <a href="index.php" class="menu-link">
          <span class="logo-container">
            <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />

          </span>
          Home
        </a>
      </li>
            </ul>
        </div>
    </nav>
</header>
<div class="container">
  <h1>Analizador Multisemana — INNOVACION MOVIL</h1>
  <div class="controls">
<div class="file-upload">
  <input id="fileInput" type="file" accept=".xlsx,.xls" style="display:none;" />

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
  document.getElementById("fileInput").click();
});
</script>
    <button id="processBtn" class="btn" disabled>Procesar</button>
    <button id="downloadBtn" class="btn" disabled>Descargar Excel de resultados</button>
  </div>
  <div id="mensajes" class="note"></div>
  <div id="debug" class="debug" style="display:none;"></div>
  <div id="tables"></div>
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
</div>
<script>
// --- Metas  ---
const METAS = <?php echo json_encode(obtenerMetasTiendas(), JSON_PRETTY_PRINT); ?> || {};
const DIA_LABELS = ["Sábado","Domingo","Lunes","Martes","Miércoles","Jueves","Viernes"];

/* DOM */
const fileInput = document.getElementById('fileInput');
const processBtn = document.getElementById('processBtn');
const downloadBtn = document.getElementById('downloadBtn');
const mensajes = document.getElementById('mensajes');
const debugDiv = document.getElementById('debug');
const tablesDiv = document.getElementById('tables');

let weeksList = []; // matriz de cadenas weekKey (AAAA-MM-DD: sábado)
let sellersByWeek = {}; // vendedor -> { weekKey: decimal, ... }
let storesByWeek = {}; // tienda -> { claveSemana: decimal, ... }

/* UI eventos */
fileInput.addEventListener('change', ()=>{ processBtn.disabled = !fileInput.files.length; mensajes.innerText = fileInput.files.length ? `Archivo listo: ${fileInput.files[0].name}` : ''; debugDiv.style.display='none'; });
processBtn.addEventListener('click', ()=>{ if(fileInput.files.length) readFile(fileInput.files[0]); });
downloadBtn.addEventListener('click', ()=>{ if(weeksList.length) exportExcel(); });

/* helpers */
function safeCell(row, index) {
  if (index === null || index === undefined) return "";
  return row[index] !== undefined && row[index] !== null ? row[index] : "";
}
function toNumber(x) {
  if (x === null || x === undefined || x === "") return 0;
  const s = String(x).replace(/\$/g,'').replace(/,/g,'').trim();
  const num = parseFloat(s);
  return isNaN(num) ? 0 : num;
}
function pad(n){ return String(n).padStart(2,'0'); }
function round2(n) { return Math.round((Number(n) + Number.EPSILON) * 100) / 100; }
function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]; }); }

/* detectar índices  */
function detectarIndices(headerRow) {
  const lowercase = headerRow.map(h => String(h || "").toLowerCase());
  const findIndexContains = (candidates) => {
    for (const cand of candidates) {
      const i = lowercase.findIndex(h => h.includes(cand));
      if (i >= 0) return i;
    }
    return -1;
  };

  const totalPriority = ["totalventa","total venta","total_venta","venta total","total neto","total bruto","importe total","monto total"];
  let idxTotal = -1;
  for (const cand of totalPriority) {
    const i = lowercase.findIndex(h => h.includes(cand));
    if (i >= 0 && !lowercase[i].includes("sub")) { idxTotal = i; break; }
  }
  if (idxTotal === -1) {
    idxTotal = lowercase.findIndex(h => h.includes("total") && !h.includes("sub"));
  }
  if (idxTotal === -1) {
    idxTotal = findIndexContains(["importe","monto","amount","venta"]);
  }
  if (idxTotal === -1) idxTotal = headerRow.length > 18 ? 18 : null;

  const idxAlmacen = findIndexContains(["almac","store","sucursal","almacén"]);
  const idxN1 = findIndexContains(["n1","departamento","categoria"]);
  const idxFecha = findIndexContains(["fecha","date","dia","time"]);
  const idxVendedor = findIndexContains(["vendedor","seller","promotor","vended"]);

  const fallback = (i, fallbackIndex) => i >= 0 ? i : (headerRow.length > fallbackIndex ? fallbackIndex : null);

  return {
    almacen: fallback(idxAlmacen, 0),
    n1: fallback(idxN1, 1),
    fecha: fallback(idxFecha, 7),
    vendedor: fallback(idxVendedor, 9),
    total: (idxTotal !== null && idxTotal !== undefined) ? idxTotal : fallback(-1, 18)
  };
}

/* parseFecha  */
function parseFecha(fechaRaw) {
  if (!fechaRaw && fechaRaw !== 0) return null;
  if (typeof fechaRaw === 'number') {
    try {
      const d = XLSX.SSF ? XLSX.SSF.parse_date_code(fechaRaw) : null;
      if (d) return new Date(d.y, d.m - 1, d.d, d.H, d.M, Math.floor(d.S));
    } catch (e) {}
    return new Date((fechaRaw - 25569) * 86400 * 1000);
  }
  let s = String(fechaRaw).trim();
  if (!s) return null;
  s = s.replace(/(AM|PM)$/i, match => ' ' + match.toUpperCase());
  s = s.replace(/\s+/g, ' ').trim();
  let d = new Date(s);
  if (!isNaN(d.getTime())) return d;
  d = new Date(s.replace(/^(\d{1,2})\s+([A-Za-z]+)/, '$2 $1'));
  if (!isNaN(d.getTime())) return d;
  const iso = s.replace(/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/, '$3-$2-$1');
  d = new Date(iso);
  if (!isNaN(d.getTime())) return d;
  return null;
}

/* weekStart: sábado de la semana  */
function weekStart(date){
  const jsDay = date.getDay();
  const daysBack = (jsDay + 1) % 7; // domingo->1, lunes->2,... sábado->0
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  d.setDate(d.getDate() - daysBack);
  return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}
function weekKey(startDate){ return `${startDate.getFullYear()}-${String(startDate.getMonth()+1).padStart(2,'0')}-${String(startDate.getDate()).padStart(2,'0')}`; }
function niceWeekLabelFromKey(key){ const parts = key.split('-'); const d = new Date(Number(parts[0]), Number(parts[1])-1, Number(parts[2])); const end = new Date(d); end.setDate(d.getDate()+6); return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} - ${pad(end.getDate())}/${pad(end.getMonth()+1)}/${end.getFullYear()}`; }

/* ----------------- LECTURA DEL ARCHIVO ---ve-------------- */
function readFile(file){
  mensajes.innerText = 'Leyendo archivo...';
  document.getElementById('loader').style.display = 'flex';
  debugDiv.style.display = 'none';
  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target.result);
    const wb = XLSX.read(data, { type: 'array' });
    const sheet = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "" });
    if (!rows || rows.length < 2) { mensajes.innerText = 'La hoja está vacía o no pudo leerse.'; return; }

    const headerRow = rows[0].map(h => String(h || "").trim());
    const idx = detectarIndices(headerRow);

    
    // construir registros (solo filas con N1 == INNOVACION MOVIL, )
    const allRecords = [];
    for (let r=1; r<rows.length; r++){
      const row = rows[r];
      if (row.every(c => c === null || String(c).trim() === "")) continue;
      const n1 = String(safeCell(row, idx.n1) || "").trim();
      if (n1 !== 'INNOVACION MOVIL') continue;
      const fechaRaw = safeCell(row, idx.fecha);
      const fecha = parseFecha(fechaRaw);
      if (!fecha) continue;
      const almacen = String(safeCell(row, idx.almacen) || "(SIN ALMACEN)").trim();
      const vendedor = String(safeCell(row, idx.vendedor) || "(SIN VENDEDOR)").trim();
      const total = toNumber(safeCell(row, idx.total));
      allRecords.push({ almacen, vendedor, fecha, total });
    }

    if (!allRecords.length) { mensajes.innerText = 'No se encontraron registros válidos (N1=INNOVACION MOVIL). Revisa encabezados.'; return; }

    mensajes.innerText = `Registros válidos: ${allRecords.length}. Detectando semanas...`;
    processAllWeeks(allRecords);
     document.getElementById('loader').style.display = 'none'; 
  };
  reader.readAsArrayBuffer(file);
}


function processAllWeeks(records){
  // detectar semanas 
  const weeksSet = new Set();
  records.forEach(r => {
    const wkStart = weekStart(r.fecha);
    weeksSet.add( weekKey(wkStart) );
  });
  weeksList = Array.from(weeksSet).sort((a,b)=> new Date(a) - new Date(b));

  // init containers
  sellersByWeek = {}; // seller -> { weekKey: decimal }
  storesByWeek = {}; // store -> { weekKey: decimal }

  // para cada semana construimos los registros de esa semana y aplicamos la lógica de una semana
  weeksList.forEach(wk => {
    // calcular range saturday->friday
    const parts = wk.split('-');
    const wkStartDate = new Date(Number(parts[0]), Number(parts[1])-1, Number(parts[2]));
    const wkEndDate = new Date(wkStartDate); wkEndDate.setDate(wkStartDate.getDate() + 6);

    // filtrar records de la semana
    const recsWeek = records.filter(r => {
      const d = new Date(r.fecha.getFullYear(), r.fecha.getMonth(), r.fecha.getDate());
      return d >= wkStartDate && d <= wkEndDate;
    });

    // 1) Construir vendedoresMapWeek y tiendasDiaTotalesWeek (por día Sáb..Vie)
    const vendedoresMap = {};
    const tiendasDiaTotales = {}; // store -> [7]
    // inicializar tiendas desde METAS
    Object.keys(METAS).forEach(t => tiendasDiaTotales[t] = Array(7).fill(0));

    // asegurar que todas las tiendas encontradas tengan entrada
    recsWeek.forEach(r => {
      const almacen = String(r.almacen || "").trim();
      if (almacen && !(almacen in tiendasDiaTotales)) tiendasDiaTotales[almacen] = Array(7).fill(0);
    });

    // poblar maps
    recsWeek.forEach(r => {
      const vendedor = String(r.vendedor || "").trim() || "(SIN VENDEDOR)";
      const almacen = String(r.almacen || "").trim() || "(SIN ALMACEN)";
      const fecha = r.fecha;
      const total = Number(r.total || 0);

      if (!vendedoresMap[vendedor]) vendedoresMap[vendedor] = { total:0, diasSet: new Set(), almacenes: {} };
      vendedoresMap[vendedor].total += total;
      if (fecha) vendedoresMap[vendedor].diasSet.add(fecha.toDateString());

      if (!vendedoresMap[vendedor].almacenes[almacen]) vendedoresMap[vendedor].almacenes[almacen] = { total:0, diasSet: new Set() };
      vendedoresMap[vendedor].almacenes[almacen].total += total;
      if (fecha) vendedoresMap[vendedor].almacenes[almacen].diasSet.add(fecha.toDateString());

      if (fecha) {
        const jsDay = fecha.getDay();
        const mapIndex = {6:0,0:1,1:2,2:3,3:4,4:5,5:6}[jsDay];
        if (mapIndex === undefined) return;
        if (! (almacen in tiendasDiaTotales) ) tiendasDiaTotales[almacen] = Array(7).fill(0);
        tiendasDiaTotales[almacen][mapIndex] += total;
      }
    });

    // 2) Construir vendedoresArray (asignación de almacenPorVendedor) usando SOLO datos de la semana
    const vendedoresArray = [];
    Object.entries(vendedoresMap).forEach(([vendedor, info]) => {
      const almacenesKeys = Object.keys(info.almacenes);
      let almacenAsignado = null;
      if (almacenesKeys.length === 1) almacenAsignado = almacenesKeys[0];
      else {
        let maxDias = -1, bestTotal = -1, bestStore = null;
        almacenesKeys.forEach(store => {
          const dias = info.almacenes[store].diasSet.size;
          const tot = info.almacenes[store].total;
          if (dias > maxDias || (dias === maxDias && tot > bestTotal)) {
            maxDias = dias; bestTotal = tot; bestStore = store;
          }
        });
        almacenAsignado = bestStore;
      }
      vendedoresArray.push({
        vendedor,
        totalVentas: info.total,
        diasVendidos: info.diasSet.size,
        almacenesDetalle: (() => {
          const arr = [];
          Object.entries(info.almacenes).forEach(([k,v]) => arr.push({almacen:k, total:v.total, dias:v.diasSet.size}));
          return arr;
        })(),
        almacenAsignado
      });
    });

    // 3) Calcular tiendasArray para la semana (meta, metaSemanal, asignados, validos, metaPorVendedor, porcentajes)
    const tiendasArray = [];
    Object.keys(tiendasDiaTotales).forEach(almacen => {
      const metaInfo = METAS[almacen] || { diaria: 0, limite: 9999 };
      const metaDiaria = metaInfo.diaria || 0;
      const metaSemanal = metaDiaria * 7;
      const limite = metaInfo.limite || 9999;
      const asignados = vendedoresArray.filter(v => v.almacenAsignado === almacen);
      let validos = asignados.filter(v => v.diasVendidos >= 4);
      validos.sort((a,b) => b.totalVentas - a.totalVentas);
      if (validos.length > limite) validos = validos.slice(0, limite);
      const contadorValidos = Math.max(1, validos.length);
      const metaPorVendedor = metaSemanal / contadorValidos;

      const asignadosDetalle = asignados.map(v => ({
        vendedor: v.vendedor,
        totalVentas: v.totalVentas,
        diasVendidos: v.diasVendidos,
        metaAsignada: metaPorVendedor,
        porcentaje: metaPorVendedor ? (v.totalVentas / metaPorVendedor) * 100 : 0
      }));

      const dias = tiendasDiaTotales[almacen] || Array(7).fill(0);
      const totalSemana = dias.reduce((a,b) => a+b, 0);
      const porcentajeSemana = metaSemanal ? (totalSemana / metaSemanal) * 100 : 0;

      tiendasArray.push({ almacen, metaDiaria, metaSemanal, limite, asignadosDetalle, dias, totalSemana, porcentajeSemana });
    });

    vendedoresArray.forEach(v => {
      const tiendaObj = tiendasArray.find(t => t.almacen === v.almacenAsignado);
      let metaReal = 0;
      if (tiendaObj) {
        const found = tiendaObj.asignadosDetalle.find(x => x.vendedor === v.vendedor);
        if (found) metaReal = found.metaAsignada;
        else metaReal = tiendaObj.metaSemanal / Math.max(1, tiendaObj.asignadosDetalle.length || 1);
      }
      const porcentajeDecimal = metaReal ? (v.totalVentas / metaReal) : 0;
      // guardar en sellersByWeek
      if (!sellersByWeek[v.vendedor]) sellersByWeek[v.vendedor] = {};
      sellersByWeek[v.vendedor][wk] = round2(porcentajeDecimal * 1e6) / 1e6; // guardamos decimal con 6 decimales
    });

    // garantizar que vendedores que no vendieron esta semana tengan 0 en ese wk (se rellenará más abajo)
    // guardar storesByWeek: porcentaje de tienda (decimal)
    tiendasArray.forEach(t => {
      if (!storesByWeek[t.almacen]) storesByWeek[t.almacen] = {};
      storesByWeek[t.almacen][wk] = round2((t.porcentajeSemana/100) * 1e6) / 1e6; // porcentajeSemana es % en 0-100, lo convertimos a decimal
    });
  }); // fin weeksList.forEach

  // 5) Asegurar que todos los vendedores y tiendas tengan una entrada para cada semana (0 si no vendieron)
  const allSellers = new Set(Object.keys(sellersByWeek));
  const allStores = new Set(Object.keys(storesByWeek));
  // also include sellers who appeared in any week: (already keys)
  weeksList.forEach(wk => {
    allSellers.forEach(s => { if (!sellersByWeek[s][wk]) sellersByWeek[s][wk] = 0; });
    allStores.forEach(st => { if (!storesByWeek[st][wk]) storesByWeek[st][wk] = 0; });
  });

  // 6) Construir tablas finales: vendedores y sucursales (visual y export)
  buildAndRenderTables();
}

/* ----------------- Construir UI y export ----------------- */
function buildAndRenderTables(){
  tablesDiv.innerHTML = '';
  // Vendedores: crear filas con columnas dinámicas por week
  const sellers = Object.keys(sellersByWeek).sort((a,b) => {
    // ordenar por promedio desc para visual
    const avga = avgOfObject(sellersByWeek[a]);
    const avgb = avgOfObject(sellersByWeek[b]);
    return avgb - avga;
  });

  // construir sellersFinal array para export
  const sellersFinalRows = [];
  // header
  const header = ['Vendedor', ...weeksList.map(w=> w), 'PromedioDecimal', 'Promedio %', 'Desempeño'];
  sellersFinalRows.push(header);

  // HTML table visual (weeks formatted as labels)
  let html = '<h2>Resumen por Vendedor</h2>';
  html += '<table><thead><tr><th>Vendedor</th>';
  weeksList.forEach(w => html += `<th>${escapeHtml(niceWeekLabelFromKey(w))}</th>`);
  html += '<th>Promedio %</th><th>Desempeño</th></tr></thead><tbody>';

  sellers.forEach(s => {
    const rowObj = sellersByWeek[s];
    const percsDecimals = weeksList.map(w => Number(rowObj[w] || 0)); // decimal (e.g., 1.055)
    const avgDecimal = percsDecimals.reduce((a,b)=>a+b,0) / Math.max(1, percsDecimals.length);
    const avgPercent = round2(avgDecimal * 100);
    let label = 'Desempeño Bajo';
    if (avgDecimal >= 1.00) label = 'Desempeño Excelente';
    else if (avgDecimal >= 0.70) label = 'Desempeño Promedio';
    const cls = avgDecimal >= 1 ? 'verde' : (avgDecimal >= 0.7 ? 'amarillo' : 'rojo');

    html += `<tr class="${cls}"><td>${escapeHtml(s)}</td>`;
    percsDecimals.forEach(p => html += `<td>${(p*100).toFixed(2)}%</td>`);
    html += `<td>${avgPercent}%</td><td>${label}</td></tr>`;

    // fila para export: decimals
    sellersFinalRows.push([s, ...percsDecimals.map(x=> round6(x)), round6(avgDecimal), avgPercent/100, label]);
  });
  html += '</tbody></table>';
  tablesDiv.insertAdjacentHTML('beforeend', html);

  // Sucursales table
  const stores = Object.keys(storesByWeek).sort((a,b) => {
    const avga = avgOfObject(storesByWeek[a]);
    const avgb = avgOfObject(storesByWeek[b]);
    return avgb - avga;
  });

  // export stores header
  const storesFinalRows = [];
  const headerS = ['Sucursal', ...weeksList.map(w=> w), 'PromedioDecimal', 'Promedio %', 'Desempeño'];
  storesFinalRows.push(headerS);

  let htmlS = '<h2>Resumen por Sucursal</h2>';
  htmlS += '<table><thead><tr><th>Sucursal</th>';
  weeksList.forEach(w => htmlS += `<th>${escapeHtml(niceWeekLabelFromKey(w))}</th>`);
  htmlS += '<th>Promedio %</th><th>Desempeño</th></tr></thead><tbody>';

  stores.forEach(st => {
    const rowObj = storesByWeek[st];
    const percsDecimals = weeksList.map(w => Number(rowObj[w] || 0)); // decimal
    const avgDecimal = percsDecimals.reduce((a,b)=>a+b,0) / Math.max(1, percsDecimals.length);
    const avgPercent = round2(avgDecimal * 100);
    const label = avgDecimal >= 1 ? 'Desempeño Excelente' : (avgDecimal >= 0.7 ? 'Desempeño Promedio' : 'Desempeño Bajo');
    const cls = avgDecimal >= 1 ? 'verde' : (avgDecimal >= 0.7 ? 'amarillo' : 'rojo');

    htmlS += `<tr class="${cls}"><td>${escapeHtml(st)}</td>`;
    percsDecimals.forEach(p => htmlS += `<td>${(p*100).toFixed(2)}%</td>`);
    htmlS += `<td>${avgPercent}%</td><td>${label}</td></tr>`;

    storesFinalRows.push([st, ...percsDecimals.map(x=> round6(x)), round6(avgDecimal), avgPercent/100, label]);
  });

  htmlS += '</tbody></table>';
  tablesDiv.insertAdjacentHTML('beforeend', htmlS);

  mensajes.innerText = `Procesado. Semanas: ${weeksList.length}. Vendedores: ${sellers.length}. Sucursales: ${stores.length}.`;
  downloadBtn.disabled = false;

  // Guardar para export (XLSX)
  window.__EXPORT_VENDEDORES = sellersFinalRows;
  window.__EXPORT_SUCURSALES = storesFinalRows;
}

/* ----------------- Export: hojas con DECIMALES (ej. 1.055) ----------------- */
function exportExcel(){
  const wb = XLSX.utils.book_new();
  const ws1 = XLSX.utils.aoa_to_sheet(window.__EXPORT_VENDEDORES || []);
  XLSX.utils.book_append_sheet(wb, ws1, 'Vendedores');
  const ws2 = XLSX.utils.aoa_to_sheet(window.__EXPORT_SUCURSALES || []);
  XLSX.utils.book_append_sheet(wb, ws2, 'Sucursales');
  XLSX.writeFile(wb, 'Resultados_Analisis_Multisemana_Exacto.xlsx');
}

/* ----------------- Utilities ----------------- */
function round6(n){ return Math.round((Number(n) + Number.EPSILON) * 1000000) / 1000000; }
function avgOfObject(obj){
  const vals = Object.values(obj||{}).map(Number);
  if (!vals.length) return 0;
  return vals.reduce((a,b)=>a+b,0) / vals.length;
}

</script>

  <script>
    // Controlar menú hamburguesa
    document.getElementById('menu-check').addEventListener('change', function() {
        const menu = document.getElementById('nav-menu');
        if (this.checked) {
            menu.style.opacity = '1';
            menu.style.visibility = 'visible';
            menu.style.pointerEvents = 'auto';
        } else {
            menu.style.opacity = '0';
            menu.style.visibility = 'hidden';
            menu.style.pointerEvents = 'none';
        }
    });
</script>
</body>
</html>
