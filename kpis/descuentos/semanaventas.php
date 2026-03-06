<?php

include_once '../../funciones.php'; 

?>
<!DOCTYPE html>
<html lang="es">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    .info-box { background:#e7f3ff; padding:12px; border-left:4px solid #007bff; margin-bottom:15px; }
    #debugBox { margin-top:8px; font-size:13px; color:#111; background:#fff; border:1px solid #eee; padding:8px; }
  </style>
  
  <link rel="stylesheet" href="../estilos.css">
  
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
            <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />

          </span>
          Atras
        </a>
      </li>
            </ul>
        </div>
    </nav>
</header>

<br>
<div class="container">

  <h1>📈 Análisis completo — INNOVACION MOVIL (Ventas Reales)</h1>

  <div class="info-box">
    <strong>ℹ️ Nota importante:</strong> Este análisis resta los descuentos de códigos promocionales para mostrar las ventas reales. Los montos mostrados son después de aplicar los descuentos.
  </div>

  <div class="controls">
   <div class="file-upload">
  <input id="inputFile" type="file" accept=".xlsx,.xls" style="display: none;" />
  
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
  
  <span id="fileName" style="margin-left: 10px; color: #666;"></span>
</div>

<script>
document.getElementById("fileButton").addEventListener("click", () => {
  document.getElementById("inputFile").click();
});

// Este código debe estar ANTES de tu listener existente
document.getElementById('inputFile').addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (file) {
    document.getElementById('fileName').textContent = `📄 ${file.name}`;
  }
});
</script>

    <button id="procesarBtn" class="btn" disabled>Procesar archivo</button>
    <button id="descargarBtn" class="btn" disabled>Descargar .xlsx con resultados</button>
  </div>
<div class="center-container">
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

// NUEVA FUNCIÓN: Extrae monto de código promocional
// Ejemplos que debe manejar:
// "CÓDIGO PROMOCIONAL $180.00, EFECTIVO $380.00"
// "CÓDIGO PROMOCIONAL $180.00, EFECTIVO $1000.00, CLIP MX $204.00"
// "EFECTIVO $500.00, CÓDIGO PROMOCIONAL $180.00"
function extractPromoAmount(text){
  if(!text) return 0;
  
  // Convertir a string y buscar "CÓDIGO PROMOCIONAL" (con o sin acento)
  const str = String(text).toUpperCase();
  
  // Si no contiene "CÓDIGO PROMOCIONAL" o "CODIGO PROMOCIONAL", retornar 0
  if(!str.includes('CÓDIGO PROMOCIONAL') && !str.includes('CODIGO PROMOCIONAL')) return 0;
  
  // Buscar el patrón: CÓDIGO PROMOCIONAL seguido de $monto
  // La regex captura: C[OÓ]DIGO PROMOCIONAL (espacios opcionales) $ (número con puntos/comas)
  const regex = /C[OÓ]DIGO\s+PROMOCIONAL\s*\$\s*([\d,]+\.?\d*)/i;
  const match = str.match(regex);
  
  if(!match) return 0;
  
  // Extraer el número y limpiar
  let numStr = match[1];
  
  // Remover comas (separadores de miles) y convertir
  numStr = numStr.replace(/,/g, '');
  
  const parsed = parseFloat(numStr);
  return isNaN(parsed) ? 0 : parsed;
}

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

    const preview = rows.slice(1,6).map((r, i) => ({
      fila: i+2,
      total_at_idx: safeCell(r, idx.total),
      metodos: safeCell(r, idx.metodos)
    }));
    console.log("HeaderRow:", headerRow);
    console.log("Indices detectados:", idx);
    console.log("Preview:", preview);
    mensajes.innerText = `Encabezados detectados. Procesando...`;

    const dataObjs = [];
    for (let r = 1; r < rows.length; r++) {
      const row = rows[r];
      if (row.every(cell => (cell === null || String(cell).trim() === ""))) continue;
      const obj = {
        almacen: safeCell(row, idx.almacen),
        N1: safeCell(row, idx.n1),
        fechaRaw: safeCell(row, idx.fecha),
        vendedor: safeCell(row, idx.vendedor),
        totalRaw: safeCell(row, idx.total),
        metodosRaw: safeCell(row, idx.metodos),  // NUEVA COLUMNA
        ticketRaw: safeCell(row, idx.ticket)     // COLUMNA NoMov
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
  const idxMetodos = findIndexContains(["métodos de pago","metodos de pago","metodosdepago","metodos"]);
  const idxTicket = findIndexContains(["nomov","no mov","ticket","folio"]);  // COLUMNA E

  const fallback = (i, fallbackIndex) => i >= 0 ? i : (headerRow.length > fallbackIndex ? fallbackIndex : null);

  return {
    almacen: fallback(idxAlmacen, 0),
    n1: fallback(idxN1, 1),
    fecha: fallback(idxFecha, 7),
    vendedor: fallback(idxVendedor, 9),
    total: (idxTotal !== null && idxTotal !== undefined) ? idxTotal : fallback(-1, 18),
    metodos: idxMetodos,
    ticket: fallback(idxTicket, 4)  // Columna E normalmente es índice 4
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

  // PASO 1: AGRUPAR POR TICKET PARA DETECTAR DESCUENTOS UNA SOLA VEZ
  const ticketsMap = {};
  
  registros.forEach(r => {
    const ticket = String(r.ticketRaw || "").trim();
    const almacen = String(r.almacen || "").trim() || "(SIN ALMACEN)";
    const vendedor = String(r.vendedor || "").trim() || "(SIN VENDEDOR)";
    const key = `${ticket}__${almacen}`;
    
    if (!ticketsMap[key]) {
      ticketsMap[key] = {
        ticket,
        almacen,
        vendedor,
        descuento: extractPromoAmount(r.metodosRaw),  // Solo extraer una vez por ticket
        metodoPago: r.metodosRaw,
        productos: []
      };
    }
    
    // Agregar producto al ticket
    ticketsMap[key].productos.push({
      totalBruto: toNumber(r.totalRaw),
      fechaRaw: r.fechaRaw
    });
  });

  // PASO 2: CALCULAR TOTALES POR TICKET
  let totalDescuentosGlobal = 0;
  let registrosConDescuento = [];
  
  Object.values(ticketsMap).forEach(tk => {
    const totalBrutoTicket = tk.productos.reduce((sum, p) => sum + p.totalBruto, 0);
    const descuento = tk.descuento;  // Ya está calculado una vez
    const totalRealTicket = totalBrutoTicket - descuento;
    
    if (descuento > 0) {
      totalDescuentosGlobal += descuento;
      registrosConDescuento.push({
        ticket: tk.ticket,
        vendedor: tk.vendedor,
        almacen: tk.almacen,
        metodoPago: tk.metodoPago,
        descuento,
        totalBruto: totalBrutoTicket,
        totalReal: totalRealTicket,
        numProductos: tk.productos.length
      });
    }
    
    // PASO 3: ASIGNAR VENTAS REALES A VENDEDORES Y TIENDAS
    const vendedor = tk.vendedor;
    const almacen = tk.almacen;
    
    if (!vendedoresMap[vendedor]) {
      vendedoresMap[vendedor] = { 
        total: 0, 
        descuentos: 0,
        diasSet: new Set(), 
        almacenes: {} 
      };
    }
    
    vendedoresMap[vendedor].total += totalRealTicket;
    vendedoresMap[vendedor].descuentos += descuento;
    
    // Registrar días únicos de todos los productos del ticket
    tk.productos.forEach(p => {
      const fecha = parseFecha(p.fechaRaw);
      if (fecha) vendedoresMap[vendedor].diasSet.add(fecha.toDateString());
    });

    if (!vendedoresMap[vendedor].almacenes[almacen]) {
      vendedoresMap[vendedor].almacenes[almacen] = { 
        total: 0, 
        descuentos: 0,
        diasSet: new Set() 
      };
    }
    vendedoresMap[vendedor].almacenes[almacen].total += totalRealTicket;
    vendedoresMap[vendedor].almacenes[almacen].descuentos += descuento;
    
    tk.productos.forEach(p => {
      const fecha = parseFecha(p.fechaRaw);
      if (fecha) {
        vendedoresMap[vendedor].almacenes[almacen].diasSet.add(fecha.toDateString());
        
        const jsDay = fecha.getDay();
        const mapIndex = {6:0,0:1,1:2,2:3,3:4,4:5,5:6}[jsDay];
        if (mapIndex !== undefined) {
          if (!(almacen in tiendasDiaTotales)) tiendasDiaTotales[almacen] = Array(7).fill(0);
          // Distribuir el total real proporcionalmente entre los productos
          const proporcion = p.totalBruto / totalBrutoTicket;
          tiendasDiaTotales[almacen][mapIndex] += totalRealTicket * proporcion;
        }
      }
    });
  });

  console.log(`💰 Total de descuentos aplicados: ${totalDescuentosGlobal.toFixed(2)}`);
  console.log(`📝 Registros con descuento encontrados: ${registrosConDescuento.length}`);
  
  // Mostrar primeros 5 ejemplos para verificar
  if(registrosConDescuento.length > 0) {
    console.log("🔍 Primeros 5 ejemplos de descuentos detectados:");
    registrosConDescuento.slice(0, 5).forEach((r, i) => {
      console.log(`${i+1}. ${r.vendedor} - ${r.almacen}`);
      console.log(`   Método Pago: ${r.metodoPago}`);
      console.log(`   Descuento: ${r.descuento.toFixed(2)} | Total Bruto: ${r.totalBruto.toFixed(2)} | Total Real: ${r.totalReal.toFixed(2)}`);
    });
  }

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
      descuentosAplicados: info.descuentos,  // NUEVO CAMPO
      diasVendidos: info.diasSet.size,
      almacenesDetalle: (() => {
        const arr = [];
        Object.entries(info.almacenes).forEach(([k,v]) => 
          arr.push({almacen:k, total:v.total, descuentos:v.descuentos, dias:v.diasSet.size})
        );
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
      descuentos: v.descuentosAplicados,
      diasVendidos: v.diasVendidos,
      metaAsignada: metaPorVendedor,
      porcentaje: metaPorVendedor ? (v.totalVentas / metaPorVendedor) * 100 : 0
    }));

    const dias = tiendasDiaTotales[almacen] || Array(7).fill(0);
    const totalSemana = dias.reduce((a,b) => a+b, 0);
    const porcentajeSemana = metaSemanal ? (totalSemana / metaSemanal) * 100 : 0;

    tiendasArray.push({ almacen, metaDiaria, metaSemanal, limite, asignadosDetalle, dias, totalSemana, porcentajeSemana });
  });

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
      VentasReales: round2(v.totalVentas),
      DescuentosAplicados: round2(v.descuentosAplicados),  // NUEVA COLUMNA
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

  mensajes.innerText = `✅ Procesamiento completado. ${vendedoresResumen.length} vendedores analizados. Total descuentos: ${totalDescuentosGlobal.toFixed(2)} (${registrosConDescuento.length} tickets con descuento)`;
  descargarBtn.disabled = false;
}

function round2(n) { return Math.round((n + Number.EPSILON) * 100) / 100; }

function mostrarTablaVendedores(arr) {
  if (!arr || !arr.length) {
    tablaVendedoresDiv.innerHTML = "<div class='note'>No hay datos para vendedores.</div>";
    return;
  }
  let html = "<table><caption>Metas y cumplimiento por vendedor (Ventas Reales)</caption><thead><tr>";
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
  let html = "<table><caption>Análisis semanal por tienda (Sábado → Viernes) - Ventas Reales</caption><thead><tr>";
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

  const vendedoresExcel = vendedoresResumen.map(v => ({
    ...v,
    PorcentajeCumplimiento: (v.PorcentajeCumplimiento / 100)
  }));

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

  const filename = 'Resultados_Analisis_Ventas_Reales.xlsx';
  XLSX.writeFile(wb, filename);
}

window.addEventListener("dragover", (e)=>e.preventDefault());
window.addEventListener("drop", (e)=>e.preventDefault());
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