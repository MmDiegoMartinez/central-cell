<?php
include_once '../funciones.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    .nowrap { white-space:nowrap; }
    #debugBox { margin-top:8px; font-size:13px; color:#111; background:#fff; border:1px solid #eee; padding:8px; }
    .seccion-titulo {
      font-size:1.2em; font-weight:700; padding:10px 14px;
      border-radius:8px; color:white; margin: 20px 0 8px;
    }
    .titulo-im { background: linear-gradient(135deg,#f093fb,#f5576c); }
    .titulo-tm { background: linear-gradient(135deg,#4facfe,#00b4d8); }
  </style>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>

<nav>
  <div class="nav-inner">
    <label class="bar-menu">
      <input type="checkbox" id="menu-check">
      <span class="top"></span><span class="middle"></span><span class="bottom"></span>
    </label>
    <ul id="nav-menu">
      <li>
        <a href="index.php" class="menu-link">
          <span class="logo-container">
            <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" class="logo" width="25" height="25"/>
          </span>
          Home
        </a>
      </li>
    </ul>
  </div>
</nav>

<br>
<div class="container">
  <h1>📈 Análisis completo — INNOVACION MOVIL & TECNOLOGIA MOVIL</h1>

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
    <button id="procesarBtn" class="btn" disabled>Procesar archivo</button>
    <button id="descargarBtn" class="btn" disabled>Descargar .xlsx con resultados</button>
  </div>

  <div class="center-container">
    <div id="loader" class="loader-container" style="display:none;">
      <div class="cloud front"><span class="left-front"></span><span class="right-front"></span></div>
      <span class="sun sunshine"></span><span class="sun"></span>
      <div class="cloud back"><span class="left-back"></span><span class="right-back"></span></div>
    </div>
  </div>

  <div id="mensajes" class="note"></div>
  <div id="debugBox" style="display:none;"></div>

  <div class="tables">
    <div class="seccion-titulo titulo-im">📱 Innovación Móvil — Accesorios</div>
    <div id="tablaVendedoresIM"></div>
    <div id="tablaTiendasIM"></div>

    <div class="seccion-titulo titulo-tm">📲 Tecnología Móvil — Telefonía</div>
    <div id="tablaVendedoresTM"></div>
    <div id="tablaTiendasTM"></div>
  </div>
</div>

<script>
/* ─── Metas desde PHP ─────────────────────────────────────────────── */
const METAS_IM = <?php echo json_encode(obtenerMetasTiendas('IM'), JSON_PRETTY_PRINT); ?>;
const METAS_TM = <?php echo json_encode(obtenerMetasTiendas('TM'), JSON_PRETTY_PRINT); ?>;

const DIA_LABELS = ["Sábado","Domingo","Lunes","Martes","Miércoles","Jueves","Viernes"];

let vendedoresResumenIM = [], tiendasResumenIM = [];
let vendedoresResumenTM = [], tiendasResumenTM = [];

const inputFile   = document.getElementById('inputFile');
const procesarBtn = document.getElementById('procesarBtn');
const descargarBtn= document.getElementById('descargarBtn');
const mensajes    = document.getElementById('mensajes');

document.getElementById("fileButton").addEventListener("click", () => inputFile.click());

inputFile.addEventListener('change', () => {
  procesarBtn.disabled = !inputFile.files.length;
  mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : "";
});

procesarBtn.addEventListener('click', () => {
  if (inputFile.files.length) leerExcel(inputFile.files[0]);
});
descargarBtn.addEventListener('click', descargaResultados);

/* ─── Leer Excel ──────────────────────────────────────────────────── */
function leerExcel(file) {
  mensajes.innerText = 'Leyendo archivo...';
  document.getElementById('loader').style.display = 'flex';
  const reader = new FileReader();
  reader.onload = (e) => {
    const wb = XLSX.read(new Uint8Array(e.target.result), { type:'array' });
    const sheet = wb.Sheets[wb.SheetNames[0]];
    const rows  = XLSX.utils.sheet_to_json(sheet, { header:1, defval:"" });
    if (!rows || rows.length < 2) { mensajes.innerText = 'Hoja vacía.'; return; }

    const headerRow = rows[0].map(h => String(h||"").trim());
    const idx = detectarIndices(headerRow);

    const todosRegistros = [];
    for (let r = 1; r < rows.length; r++) {
      const row = rows[r];
      if (row.every(c => (c===null || String(c).trim()===""))) continue;
      todosRegistros.push({
        almacen:   safeCell(row, idx.almacen),
        N1:        safeCell(row, idx.n1),
        fechaRaw:  safeCell(row, idx.fecha),
        vendedor:  safeCell(row, idx.vendedor),
        totalRaw:  safeCell(row, idx.total)
      });
    }

    const registrosIM = todosRegistros.filter(r => String(r.N1).trim() === "TECNOLOGIA MOVIL");   // ← IM usa esta etiqueta en el archivo original
    // NOTA: ajusta el string de filtro si IM tiene diferente etiqueta en el excel
    // Basado en el código original, IM filtraba "TECNOLOGIA MOVIL" — si IM tiene otra
    // etiqueta, cámbiala aquí. Por el código original ambos parecen venir del mismo archivo.
    // Se asume: IM = "ACCESORIOS" o similar, TM = "TECNOLOGIA MOVIL"
    // → ajusta los dos filtros según tus datos reales:
    const registrosFiltIM = todosRegistros.filter(r => String(r.N1).trim() === "INNOVACION MOVIL");
    const registrosFiltTM = todosRegistros.filter(r => String(r.N1).trim() === "TECNOLOGIA MOVIL");

    mensajes.innerText = `IM: ${registrosFiltIM.length} filas | TM: ${registrosFiltTM.length} filas. Procesando...`;

    /* ── Procesar IM primero para obtener diasVendidosIM por vendedor ── */
    const { vendedoresResumen: vrIM, tiendasResumen: trIM, vendedoresMap: vmIM }
      = procesarDepartamento(registrosFiltIM, METAS_IM, null);

    /* ── Procesar TM pasando los días de IM como referencia ── */
    const { vendedoresResumen: vrTM, tiendasResumen: trTM }
      = procesarDepartamento(registrosFiltTM, METAS_TM, vmIM);

    vendedoresResumenIM = vrIM;
    tiendasResumenIM    = trIM;
    vendedoresResumenTM = vrTM;
    tiendasResumenTM    = trTM;

    mostrarTablaVendedores(vrIM, 'tablaVendedoresIM', 'Metas y cumplimiento por vendedor — IM');
    mostrarTablaTiendas(trIM,   'tablaTiendasIM',     'Análisis semanal por tienda — IM (Sáb→Vie)');
    mostrarTablaVendedores(vrTM, 'tablaVendedoresTM', 'Metas y cumplimiento por vendedor — TM');
    mostrarTablaTiendas(trTM,   'tablaTiendasTM',     'Análisis semanal por tienda — TM (Sáb→Vie)');

    mensajes.innerText = `Completado. IM: ${vrIM.length} vendedores | TM: ${vrTM.length} vendedores.`;
    descargarBtn.disabled = false;
    document.getElementById('loader').style.display = 'none';
  };
  reader.readAsArrayBuffer(file);
}

/* ─── Procesar un departamento ───────────────────────────────────────
   diasIMRef: mapa {vendedor → diasVendidos} del departamento IM.
   Si se pasa (para TM), los días se toman de IM en vez de TM.
────────────────────────────────────────────────────────────────────── */
function procesarDepartamento(registros, METAS, diasIMRef) {
  const vendedoresMap = {};
  const tiendasDiaTotales = {};
  Object.keys(METAS).forEach(t => tiendasDiaTotales[t] = Array(7).fill(0));
  registros.forEach(r => {
    const a = String(r.almacen||"").trim();
    if (a && !(a in tiendasDiaTotales)) tiendasDiaTotales[a] = Array(7).fill(0);
  });

  registros.forEach(r => {
    const vendedor = String(r.vendedor||"").trim() || "(SIN VENDEDOR)";
    const almacen  = String(r.almacen||"").trim()  || "(SIN ALMACEN)";
    const fecha    = parseFecha(r.fechaRaw);
    const total    = toNumber(r.totalRaw);

    if (!vendedoresMap[vendedor]) vendedoresMap[vendedor] = { total:0, diasSet:new Set(), almacenes:{} };
    vendedoresMap[vendedor].total += total;
    if (fecha) vendedoresMap[vendedor].diasSet.add(fecha.toDateString());

    if (!vendedoresMap[vendedor].almacenes[almacen])
      vendedoresMap[vendedor].almacenes[almacen] = { total:0, diasSet:new Set() };
    vendedoresMap[vendedor].almacenes[almacen].total += total;
    if (fecha) vendedoresMap[vendedor].almacenes[almacen].diasSet.add(fecha.toDateString());

    if (fecha) {
      const mapIndex = {6:0,0:1,1:2,2:3,3:4,4:5,5:6}[fecha.getDay()];
      if (mapIndex === undefined) return;
      if (!(almacen in tiendasDiaTotales)) tiendasDiaTotales[almacen] = Array(7).fill(0);
      tiendasDiaTotales[almacen][mapIndex] += total;
    }
  });

  /* ── Construir array de vendedores ── */
  const vendedoresArray = [];

  if (diasIMRef) {
    /* ════════════════════════════════════════════════════════════════
       MODO TM: la fuente de verdad para almacén asignado y días
       efectivos es SIEMPRE el mapa de IM (diasIMRef).

       Pasos:
       1. Iterar sobre TODOS los vendedores de IM.
       2. Sumar sus ventas de TM (pueden ser 0 si no vendió nada en TM).
       3. Almacén asignado = el que IM le determinó.
       4. Días efectivos   = días en IM.
    ════════════════════════════════════════════════════════════════ */
    Object.entries(diasIMRef).forEach(([vendedor, infoIM]) => {
      /* Ventas TM de este vendedor (0 si no tiene registros en TM) */
      const totalTM     = vendedoresMap[vendedor] ? vendedoresMap[vendedor].total : 0;
      const diasTM      = vendedoresMap[vendedor] ? vendedoresMap[vendedor].diasSet.size : 0;

      /* Almacén asignado según IM */
      const almacenesIM = Object.keys(infoIM.almacenes || {});
      let almacenAsignado = null;
      if (almacenesIM.length === 1) {
        almacenAsignado = almacenesIM[0];
      } else if (almacenesIM.length > 1) {
        let maxD=-1, bestT=-1, best=null;
        almacenesIM.forEach(s => {
          const d = (infoIM.almacenes[s].diasSet || new Set()).size;
          const t = infoIM.almacenes[s].total || 0;
          if (d > maxD || (d === maxD && t > bestT)) { maxD=d; bestT=t; best=s; }
        });
        almacenAsignado = best;
      }

      /* Días efectivos = días en IM */
      const diasEfectivos = infoIM.diasSet ? infoIM.diasSet.size : 0;

      vendedoresArray.push({
        vendedor,
        totalVentas:  totalTM,
        diasVendidos: diasTM,         // días reales en TM (informativo)
        diasEfectivos,                // días en IM → determinan si entra en la división
        almacenAsignado
      });
    });

  } else {
    /* ════ MODO IM: lógica original ════ */
    Object.entries(vendedoresMap).forEach(([vendedor, info]) => {
      const almacenesKeys = Object.keys(info.almacenes);
      let almacenAsignado = null;
      if (almacenesKeys.length === 1) {
        almacenAsignado = almacenesKeys[0];
      } else {
        let maxDias=-1, bestTotal=-1, best=null;
        almacenesKeys.forEach(store => {
          const d=info.almacenes[store].diasSet.size, t=info.almacenes[store].total;
          if (d>maxDias||(d===maxDias&&t>bestTotal)){ maxDias=d; bestTotal=t; best=store; }
        });
        almacenAsignado = best;
      }
      vendedoresArray.push({
        vendedor,
        totalVentas:  info.total,
        diasVendidos: info.diasSet.size,
        diasEfectivos: info.diasSet.size,
        almacenAsignado
      });
    });
  }

  /* ── Construir array de tiendas ── */
  const tiendasArray = [];
  Object.keys(tiendasDiaTotales).forEach(almacen => {
    const metaInfo   = METAS[almacen] || { diaria:0, limite:9999 };
    const metaDiaria = metaInfo.diaria || 0;
    const metaSemanal= metaDiaria * 7;
    const limite     = metaInfo.limite || 9999;

    const asignados = vendedoresArray.filter(v => v.almacenAsignado === almacen);

    /* ── Vendedores válidos: usar diasEfectivos ≥ 4 ── */
    let validos = asignados.filter(v => v.diasEfectivos >= 4);
    validos.sort((a,b) => b.totalVentas - a.totalVentas);
    if (validos.length > limite) validos = validos.slice(0, limite);
    const contadorValidos = Math.max(1, validos.length);
    const metaPorVendedor = metaSemanal / contadorValidos;

    // Todos reciben la misma meta — válidos o no.
    // "válidos" solo determina el divisor, no quién tiene meta.
    const asignadosDetalle = asignados.map(v => ({
      vendedor:     v.vendedor,
      totalVentas:  v.totalVentas,
      diasVendidos: v.diasVendidos,
      diasEfectivos:v.diasEfectivos,
      metaAsignada: metaPorVendedor,   // ← siempre la meta calculada
      porcentaje:   metaPorVendedor ? (v.totalVentas / metaPorVendedor) * 100 : 0
    }));

    const dias = tiendasDiaTotales[almacen] || Array(7).fill(0);
    const totalSemana = dias.reduce((a,b)=>a+b, 0);
    tiendasArray.push({
      almacen, metaDiaria, metaSemanal, limite, asignadosDetalle,
      metaPorVendedor,   // ← guardamos para usarlo en vendedoresResumen
      dias, totalSemana,
      porcentajeSemana: metaSemanal ? (totalSemana/metaSemanal)*100 : 0
    });
  });

  /* ── Ordenar ── */
  vendedoresArray.sort((a,b)=>{
    const c=(a.almacenAsignado||"").localeCompare(b.almacenAsignado||"","es");
    return c!==0?c:b.totalVentas-a.totalVentas;
  });
  tiendasArray.sort((a,b)=>a.almacen.localeCompare(b.almacen,"es"));

  /* ── Resumen vendedores ── */
  const vendedoresResumen = vendedoresArray.map(v => {
    const tiendaObj = tiendasArray.find(t => t.almacen === v.almacenAsignado);
    // metaReal = la meta por vendedor que calculó la tienda (igual para todos)
    const metaReal = tiendaObj ? tiendaObj.metaPorVendedor : 0;
    const row = {
      Vendedor:              v.vendedor,
      AlmacenAsignado:       v.almacenAsignado || "(SIN ASIGNAR)",
      VentasTotales:         round2(v.totalVentas),
      DiasVendidos:          v.diasVendidos,
    };
    if (diasIMRef) row.DiasEfectivosIM = v.diasEfectivos;
    row.MetaAsignada           = round2(metaReal);
    row.PorcentajeCumplimiento = round2(metaReal ? (v.totalVentas / metaReal) * 100 : 0);
    return row;
  });

  /* ── Resumen tiendas ── */
  const tiendasResumen = tiendasArray.map(t => {
    const base = { Almacen: t.almacen, MetaDiaria: round2(t.metaDiaria) };
    for (let i=0;i<7;i++){
      const m=round2(t.dias[i]||0);
      base[DIA_LABELS[i]+" Monto"] = m;
      base[DIA_LABELS[i]+" %"]     = t.metaDiaria ? round2((m/t.metaDiaria)*100) : 0;
    }
    base.TotalSemana    = round2(t.totalSemana);
    base["% CumplSemana"] = round2(t.porcentajeSemana);
    return base;
  });

  return { vendedoresResumen, tiendasResumen, vendedoresMap };
}

/* ─── Helpers ─────────────────────────────────────────────────────── */
function safeCell(row, index) {
  if (index===null||index===undefined) return "";
  return row[index]!==undefined&&row[index]!==null ? row[index] : "";
}

function detectarIndices(headerRow) {
  const lc = headerRow.map(h=>String(h||"").toLowerCase());
  const find = (cands) => { for(const c of cands){ const i=lc.findIndex(h=>h.includes(c)); if(i>=0) return i; } return -1; };
  const totalPriority=["totalventa","total venta","total_venta","venta total","total neto","total bruto","importe total","monto total"];
  let idxTotal=-1;
  for(const c of totalPriority){ const i=lc.findIndex(h=>h.includes(c)); if(i>=0&&!lc[i].includes("sub")){idxTotal=i;break;} }
  if(idxTotal===-1) idxTotal=lc.findIndex(h=>h.includes("total")&&!h.includes("sub"));
  if(idxTotal===-1) idxTotal=find(["importe","monto","amount","venta"]);
  if(idxTotal===-1) idxTotal=headerRow.length>18?18:null;
  const fb=(i,f)=>i>=0?i:(headerRow.length>f?f:null);
  return {
    almacen: fb(find(["almac","store","sucursal","almacén"]),0),
    n1:      fb(find(["n1","departamento","categoria"]),1),
    fecha:   fb(find(["fecha","date","dia","time"]),7),
    vendedor:fb(find(["vendedor","seller","promotor","vended"]),9),
    total:   idxTotal!==null?idxTotal:fb(-1,18)
  };
}

function parseFecha(fechaRaw) {
  if(!fechaRaw&&fechaRaw!==0) return null;
  if(typeof fechaRaw==='number'){
    try{ const d=XLSX.SSF?XLSX.SSF.parse_date_code(fechaRaw):null; if(d) return new Date(d.y,d.m-1,d.d,d.H,d.M,Math.floor(d.S)); }catch(e){}
    return new Date((fechaRaw-25569)*86400*1000);
  }
  let s=String(fechaRaw).trim(); if(!s) return null;
  s=s.replace(/(AM|PM)$/i,m=>' '+m.toUpperCase()).replace(/\s+/g,' ').trim();
  let d=new Date(s); if(!isNaN(d.getTime())) return d;
  d=new Date(s.replace(/^(\d{1,2})\s+([A-Za-z]+)/,'$2 $1')); if(!isNaN(d.getTime())) return d;
  d=new Date(s.replace(/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/,'$3-$2-$1')); if(!isNaN(d.getTime())) return d;
  return null;
}

function toNumber(x){
  if(x===null||x===undefined||x==="") return 0;
  const n=parseFloat(String(x).replace(/\$/g,'').replace(/,/g,'').trim());
  return isNaN(n)?0:n;
}

function round2(n){ return Math.round((n+Number.EPSILON)*100)/100; }

/* ─── Renderizado de tablas ───────────────────────────────────────── */
function mostrarTablaVendedores(arr, divId, caption) {
  const div = document.getElementById(divId);
  if(!arr||!arr.length){ div.innerHTML="<div class='note'>Sin datos.</div>"; return; }
  let html=`<table><caption>${caption}</caption><thead><tr>`;
  const headers=Object.keys(arr[0]);
  headers.forEach(h=>html+=`<th>${h}</th>`);
  html+="</tr></thead><tbody>";
  arr.forEach(row=>{
    const pct=parseFloat(row.PorcentajeCumplimiento)||0;
    const cls=pct>=100?"verde":(pct>=70?"amarillo":"rojo");
    html+=`<tr class="${cls}">`;
    headers.forEach(h=>{
      let val=row[h];
      if(typeof val==='number') val=val.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
      html+=`<td class="nowrap">${val}</td>`;
    });
    html+="</tr>";
  });
  html+="</tbody></table>";
  div.innerHTML=html;
}

function mostrarTablaTiendas(arr, divId, caption) {
  const div=document.getElementById(divId);
  if(!arr||!arr.length){ div.innerHTML="<div class='note'>Sin datos.</div>"; return; }
  const fixedH=["Almacen","MetaDiaria"];
  const dayH=[];
  for(let i=0;i<7;i++){ dayH.push(DIA_LABELS[i]+" Monto"); dayH.push(DIA_LABELS[i]+" %"); }
  const footH=["TotalSemana","% CumplSemana"];
  const allH=fixedH.concat(dayH).concat(footH);
  let html=`<table><caption>${caption}</caption><thead><tr>`;
  allH.forEach(h=>html+=`<th>${h}</th>`);
  html+="</tr></thead><tbody>";
  let totalGen=0, sumaMetasSem=0;
  arr.forEach(row=>{
    html+="<tr>";
    allH.forEach(h=>{
      let val=row[h];
      if(typeof val==='number') val=val.toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2});
      html+=`<td>${val!==undefined?val:''}</td>`;
    });
    html+="</tr>";
    totalGen+=(row.TotalSemana||0);
    sumaMetasSem+=((row.MetaDiaria||0)*7);
  });
  const pctGen=sumaMetasSem?(totalGen/sumaMetasSem)*100:0;
  html+=`<tr style="font-weight:700;background:#eee;"><td>Total general</td><td></td>`;
  for(let i=0;i<14;i++) html+=`<td></td>`;
  html+=`<td>${round2(totalGen).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2})}</td>`;
  html+=`<td>${round2(pctGen).toLocaleString('es-MX',{minimumFractionDigits:2,maximumFractionDigits:2})}%</td></tr>`;
  html+="</tbody></table>";
  div.innerHTML=html;
}

/* ─── Descargar Excel con 4 hojas ─────────────────────────────────── */
function descargaResultados() {
  if(!vendedoresResumenIM.length&&!vendedoresResumenTM.length){
    alert("Procesa el archivo primero."); return;
  }
  const wb = XLSX.utils.book_new();

  const pctDecimal = arr => arr.map(v=>({...v, PorcentajeCumplimiento: (v.PorcentajeCumplimiento/100)}));
  const pctDecimalTiendas = arr => arr.map(t=>{
    const n={};
    for(const [k,v] of Object.entries(t)) n[k]=(typeof v==='number'&&k.includes('%'))?v/100:v;
    return n;
  });

  XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(pctDecimal(vendedoresResumenIM)), "Metas por Vendedor IM");
  XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(pctDecimalTiendas(tiendasResumenIM)), "Analisis Semanal Tienda IM");
  XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(pctDecimal(vendedoresResumenTM)), "Metas por Vendedor TM");
  XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(pctDecimalTiendas(tiendasResumenTM)), "Analisis Semanal Tienda TM");

  XLSX.writeFile(wb, 'Resultados_Analisis_Ventas.xlsx');
}

/* ─── Menú hamburguesa ────────────────────────────────────────────── */
document.getElementById('menu-check').addEventListener('change', function() {
  const menu=document.getElementById('nav-menu');
  menu.style.opacity      =this.checked?'1':'0';
  menu.style.visibility   =this.checked?'visible':'hidden';
  menu.style.pointerEvents=this.checked?'auto':'none';
});

window.addEventListener("dragover",e=>e.preventDefault());
window.addEventListener("drop",e=>e.preventDefault());
</script>
</body>
</html>