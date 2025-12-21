<?php

include_once '../funciones.php'; 

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Análisis Completo de Ventas — INNOVACION MOVIL</title>
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <style>
    body { font-family: Arial, sans-serif; margin: 18px; background:#f7f7f7; color:#222; }
    h1 { margin-top:0; }
    .controls { display:flex; gap:12px; align-items:center; margin-bottom:12px; flex-wrap:wrap; }
    input[type=file] { padding:6px; }
    button.btn { background:#007bff; color:white; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; }
    button.btn:disabled { background:#999; cursor:not-allowed; }
    .tables { display:flex; flex-direction:column; gap:20px; margin-top:14px; }
    table { border-collapse:collapse; width:100%; background:white; box-shadow: 0 1px 3px rgba(0,0,0,0.07); }
    th, td { padding:8px 6px; border:1px solid #e1e1e1; text-align:center; font-size:13px; }
    th { background:#2f6fa6; color:white; position:sticky; top:0; z-index:1; }
    .rojo { background:#ffdad6; }
    .amarillo { background:#fff3cc; }
    .verde { background:#dff7df; }
    caption { text-align:left; font-weight:600; padding:8px; }
    .small { font-size:12px; color:#444; }
    .note { font-size:13px; color:#333; margin-top:6px; }
    .download-link { margin-left:8px; }
    .summary { margin-top:8px; padding:10px; background:#fff; border:1px solid #eee; }
    .nowrap { white-space:nowrap; }
    #debugBox { margin-top:8px; font-size:13px; color:#111; background:#fff; border:1px solid #eee; padding:8px; }
  
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
            <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />

          </span>
          Home
        </a>
      </li>
    </ul>
  </nav>
</header>


<br>
<div class="container">

  <h1>📈 Análisis completo — INNOVACION MOVIL</h1>

  <div class="controls">
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

    <button id="procesarBtn" class="btn" disabled>Procesar archivo</button>
    <button id="descargarBtn" class="btn" disabled>Descargar .xlsx con resultados</button>
  </div>
<div class="center-container">
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
  <div id="debugBox" style="display:none;"></div>

  <div class="tables">
    <div id="tablaVendedores"></div>
    
    <div id="tablaTiendas"></div>
  </div>
</div>



<script>
/* Modificado: detección mejorada para evitar elegir SUBTOTAL en vez de TOTAL.
   También imprime una previsualización en consola para comprobar columnas. */


const METAS = <?php echo json_encode(obtenerMetasTiendas(), JSON_PRETTY_PRINT); ?>;

const DIA_LABELS = ["Sábado","Domingo","Lunes","Martes","Miércoles","Jueves","Viernes"];

let workbookData = null;
let registros = [];
let vendedoresResumen = [];
let tiendasResumen = [];

const inputFile = document.getElementById('inputFile');
const procesarBtn = document.getElementById('procesarBtn');
const descargarBtn = document.getElementById('descargarBtn');
const mensajes = document.getElementById('mensajes');
const debugBox = document.getElementById('debugBox');
const tablaVendedoresDiv = document.getElementById('tablaVendedores');
const tablaTiendasDiv = document.getElementById('tablaTiendas');

inputFile.addEventListener('change', () => {
  procesarBtn.disabled = !inputFile.files.length;
  mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : "";
  debugBox.style.display = 'none';
});

procesarBtn.addEventListener('click', () => {
  if (!inputFile.files.length) return;
  leerExcel(inputFile.files[0]);
});

descargarBtn.addEventListener('click', descargaResultados);

function leerExcel(file) {
mensajes.innerText = 'Leyendo archivo...';
document.getElementById('loader').style.display = 'flex';
const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target.result);
    const wb = XLSX.read(data, { type: 'array' });
    workbookData = wb;
    const sheetName = wb.SheetNames[0];
    const sheet = wb.Sheets[sheetName];
    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "" });
    if (!rows || rows.length < 1) {
      mensajes.innerText = 'La hoja está vacía o no pudo leerse.';
      return;
    }
    const headerRow = rows[0].map(h => String(h || "").trim());
    const idx = detectarIndices(headerRow);

    

    // Previsualización en consola (muestra total en idx.total vs valor en Q (índice 16) para comparar)
    const preview = rows.slice(1,6).map((r, i) => ({
      fila: i+2,
      total_at_idx: safeCell(r, idx.total),
      colQ_subtotal: safeCell(r, 16) // Q = index 16
    }));
    console.log("HeaderRow:", headerRow);
    console.log("Indices detectados:", idx);
    console.log("Preview (total_at_idx vs colQ_subtotal):", preview);
    mensajes.innerText = `Encabezados detectados. Revisa la consola para una previsualización (total_at_idx vs colQ_subtotal). Procesando...`;

    // Convertir filas en objetos usando los índices detectados
    const dataObjs = [];
    for (let r = 1; r < rows.length; r++) {
      const row = rows[r];
      if (row.every(cell => (cell === null || String(cell).trim() === ""))) continue;
      const obj = {
        almacen: safeCell(row, idx.almacen),
        N1: safeCell(row, idx.n1),
        fechaRaw: safeCell(row, idx.fecha),
        vendedor: safeCell(row, idx.vendedor),
        totalRaw: safeCell(row, idx.total)
      };
      dataObjs.push(obj);
    }

    registros = dataObjs.filter(r => String(r.N1).trim() === "INNOVACION MOVIL");
    mensajes.innerText = `Filtradas ${registros.length} filas con N1="INNOVACION MOVIL". Procesando datos...`;
    procesarRegistros();
    document.getElementById('loader').style.display = 'none'; 

  };
  reader.readAsArrayBuffer(file);
}

function safeCell(row, index) {
  if (index === null || index === undefined) return "";
  return row[index] !== undefined && row[index] !== null ? row[index] : "";
}

// Detec. mejorada: prioriza "totalventa"/"total venta" y evita "subtotal"
function detectarIndices(headerRow) {
  const lowercase = headerRow.map(h => String(h || "").toLowerCase());
  const findIndexContains = (candidates) => {
    for (const cand of candidates) {
      const i = lowercase.findIndex(h => h.includes(cand));
      if (i >= 0) return i;
    }
    return -1;
  };

  // Buscar TOTAL preferente (evitar SUBTOTAL)
  const totalPriority = ["totalventa","total venta","total_venta","venta total","total neto","total bruto","importe total","monto total"];
  let idxTotal = -1;
  for (const cand of totalPriority) {
    const i = lowercase.findIndex(h => h.includes(cand));
    if (i >= 0 && !lowercase[i].includes("sub")) { idxTotal = i; break; }
  }
  // Si no encontró, buscar cualquier "total" que NO sea "subtotal"
  if (idxTotal === -1) {
    idxTotal = lowercase.findIndex(h => h.includes("total") && !h.includes("sub"));
  }
  // Si aún no, buscar "importe" o "monto" o "venta" como segunda opción
  if (idxTotal === -1) {
    idxTotal = findIndexContains(["importe","monto","amount","venta"]);
  }
  // Fallback a columna S (índice 18) si no hay coincidencia razonable
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

function toNumber(x) {
  if (x === null || x === undefined || x === "") return 0;
  const s = String(x).replace(/\$/g,'').replace(/,/g,'').trim();
  const num = parseFloat(s);
  return isNaN(num) ? 0 : num;
}

function procesarRegistros() {
  const vendedoresMap = {};
  const tiendasDiaTotales = {};
  Object.keys(METAS).forEach(t => tiendasDiaTotales[t] = Array(7).fill(0));
  registros.forEach(r => {
    const almacen = String(r.almacen || "").trim();
    if (almacen && !(almacen in tiendasDiaTotales)) tiendasDiaTotales[almacen] = Array(7).fill(0);
  });

  registros.forEach(r => {
    const vendedor = String(r.vendedor || "").trim() || "(SIN VENDEDOR)";
    const almacen = String(r.almacen || "").trim() || "(SIN ALMACEN)";
    const fecha = parseFecha(r.fechaRaw);
    const total = toNumber(r.totalRaw);

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

  // Ordenar vendedores por AlmacénAsignado (alfabético) y dentro por ventas desc
  vendedoresArray.sort((a,b)=> {
    const cmp = (a.almacenAsignado || "").localeCompare(b.almacenAsignado || "", 'es');
    if (cmp !== 0) return cmp;
    return b.totalVentas - a.totalVentas;
  });
  tiendasArray.sort((a,b)=> a.almacen.localeCompare(b.almacen, 'es'));

  vendedoresResumen = vendedoresArray.map(v => {
    const tiendaObj = tiendasArray.find(t => t.almacen === v.almacenAsignado);
    let metaReal = 0;
    if (tiendaObj) {
      const found = tiendaObj.asignadosDetalle.find(x=>x.vendedor===v.vendedor);
      if (found) metaReal = found.metaAsignada;
      else metaReal = tiendaObj.metaSemanal / Math.max(1, tiendaObj.asignadosDetalle.length || 1);
    }
    const porcentaje = metaReal ? (v.totalVentas / metaReal) * 100 : 0;
    return {
      Vendedor: v.vendedor,
      AlmacenAsignado: v.almacenAsignado || "(SIN ASIGNAR)",
      VentasTotales: round2(v.totalVentas),
      DiasVendidos: v.diasVendidos,
      MetaAsignada: round2(metaReal),
      PorcentajeCumplimiento: round2(porcentaje)
    };
  });

  tiendasResumen = tiendasArray.map(t => {
    const base = { Almacen: t.almacen, MetaDiaria: round2(t.metaDiaria) };
    for (let i=0;i<7;i++){
      const monto = round2(t.dias[i] || 0);
      const pct = t.metaDiaria ? round2( (monto / t.metaDiaria) * 100 ) : 0;
      base[ DIA_LABELS[i] + " Monto"] = monto;
      base[ DIA_LABELS[i] + " %"] = pct;
    }
    base.TotalSemana = round2(t.totalSemana);
    base["% CumplSemana"] = round2(t.porcentajeSemana);
    return base;
  });

  mostrarTablaVendedores(vendedoresResumen);
  mostrarTablaTiendas(tiendasResumen);

  mensajes.innerText = `Procesamiento completado. ${vendedoresResumen.length} vendedores analizados.`;
  descargarBtn.disabled = false;
}

function round2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; }

function mostrarTablaVendedores(arr) {
  if (!arr || !arr.length) {
    tablaVendedoresDiv.innerHTML = "<div class='note'>No hay datos para vendedores.</div>";
    return;
  }
  let html = "<table><caption>Metas y cumplimiento por vendedor</caption><thead><tr>";
  const headers = Object.keys(arr[0]);
  headers.forEach(h => html += `<th>${h}</th>`);
  html += "</tr></thead><tbody>";
  arr.forEach(row => {
    const pct = parseFloat(row.PorcentajeCumplimiento) || 0;
    const cls = pct >= 100 ? "verde" : (pct >= 70 ? "amarillo" : "rojo");
    html += `<tr class="${cls}">`;
    headers.forEach(h => {
      let val = row[h];
      if (typeof val === 'number') val = val.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      html += `<td class="nowrap">${val}</td>`;
    });
    html += `</tr>`;
  });
  html += "</tbody></table>";
  tablaVendedoresDiv.innerHTML = html;
}

function mostrarTablaTiendas(arr) {
  if (!arr || !arr.length) {
    tablaTiendasDiv.innerHTML = "<div class='note'>No hay datos por tienda.</div>";
    return;
  }
  let html = "<table><caption>Análisis semanal por tienda (Sábado → Viernes)</caption><thead><tr>";
  const fixedHeaders = ["Almacen","MetaDiaria"];
  const dayHeaders = [];
  for (let i=0;i<7;i++){
    dayHeaders.push(`${DIA_LABELS[i]} Monto`);
    dayHeaders.push(`${DIA_LABELS[i]} %`);
  }
  const footerHeaders = ["TotalSemana","% CumplSemana"];
  const allHeaders = fixedHeaders.concat(dayHeaders).concat(footerHeaders);
  allHeaders.forEach(h => html += `<th>${h}</th>`);
  html += "</tr></thead><tbody>";
  let totalGeneral = 0;
  let sumaMetasSemanales = 0;
  arr.forEach(row => {
    html += "<tr>";
    allHeaders.forEach(h => {
      let val = row[h];
      if (typeof val === 'number') val = val.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      html += `<td>${val !== undefined ? val : ''}</td>`;
    });
    html += "</tr>";
    totalGeneral += (row.TotalSemana || 0);
    sumaMetasSemanales += ((row.MetaDiaria || 0) * 7);
  });
  const pctGeneral = sumaMetasSemanales ? (totalGeneral / sumaMetasSemanales) * 100 : 0;
  html += `<tr style="font-weight:700;background:#eee;">
    <td>Total general</td><td></td>`;
  for (let i=0;i<14;i++) html += `<td></td>`;
  html += `<td>${round2(totalGeneral).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})}</td><td>${round2(pctGeneral).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2})}%</td></tr>`;

  html += "</tbody></table>";
  tablaTiendasDiv.innerHTML = html;
}

function descargaResultados() {
  if (!vendedoresResumen.length || !tiendasResumen.length) {
    alert("Aún no hay resultados para descargar. Procesa el archivo primero.");
    return;
  }
  const wb = XLSX.utils.book_new();

// Copia con porcentaje en decimal (ej. 0.85)
const vendedoresExcel = vendedoresResumen.map(v => ({
  ...v,
  PorcentajeCumplimiento: (v.PorcentajeCumplimiento / 100)
}));

// En tiendas también todos los campos que terminen en "%", convertirlos:
const tiendasExcel = tiendasResumen.map(t => {
  const nuevo = {};
  for (const [k, val] of Object.entries(t)) {
    if (typeof val === 'number' && k.includes('%')) {
      nuevo[k] = val / 100;
    } else {
      nuevo[k] = val;
    }
  }
  return nuevo;
});

const ws1 = XLSX.utils.json_to_sheet(vendedoresExcel);
XLSX.utils.book_append_sheet(wb, ws1, "Metas por Vendedor");
const ws2 = XLSX.utils.json_to_sheet(tiendasExcel);
XLSX.utils.book_append_sheet(wb, ws2, "Analisis Semanal por Tienda");

const filename = 'Resultados_Analisis_Ventas.xlsx';
XLSX.writeFile(wb, filename);
}

window.addEventListener("dragover", (e)=>e.preventDefault());
window.addEventListener("drop", (e)=>e.preventDefault());
</script>
</body>
</html>