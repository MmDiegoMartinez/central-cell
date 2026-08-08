<?php
include_once '../funciones.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Análisis Multisemana — IM & TM</title>
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

  <!-- Hoja de estilos base del sistema de diseño (Material 3) -->
  <link rel="stylesheet" href="../styles.css">

  <!-- Estilos adicionales SOLO para componentes que no existen en la hoja base
       (navbar responsive, subida de archivo, loader, tabs, tablas de desempeño con semáforo).
       No se modifica el CSS externo. -->
  <style>
    /* ── Navbar horizontal con menú hamburguesa (idéntico al del Comparador de Series) ── */
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

    /* ── Contenedor de controles ── */
    .controls{
      display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-md);
      margin-top:var(--space-lg);
    }

    /* ── Botón de subir archivo (reemplaza la animación de carpetas) ── */
    .file-upload{display:flex;}
    .boton{
      display:inline-flex;align-items:center;gap:8px;
      padding:10px 18px;border-radius:var(--radius-lg);
      background:var(--surface-container-high);
      border:1px solid var(--outline-variant);
      color:var(--on-surface);font-size:14px;font-weight:600;
      transition:background .15s ease, border-color .15s ease;
    }
    .boton:hover{background:var(--surface-container-highest);border-color:var(--primary);}
    .boton .material-symbols-outlined{font-size:20px;color:var(--primary);}

    /* ── Mensaje / nota de estado ── */
    .note{
      margin-top:var(--space-md);
      font-size:14px;color:var(--on-surface-variant);
      min-height:20px;
    }

    /* ── Loader simple (reemplaza la animación de sol/nube) ── */
    .loader-container{
      display:flex;align-items:center;gap:var(--space-sm);
      padding:var(--space-md) 0;
      color:var(--primary);font-size:14px;font-weight:600;
    }
    .loader-container .material-symbols-outlined{
      font-size:22px;animation:spin 1s linear infinite;
    }
    @keyframes spin{ from{transform:rotate(0deg);} to{transform:rotate(360deg);} }

    /* ── Secciones IM / TM ── */
    .seccion{
      background:var(--surface-container-lowest);
      border:1px solid rgba(196,197,215,0.4);
      border-radius:var(--radius-xl);
      padding:var(--space-xl) var(--space-2xl);
      margin:var(--space-2xl) 0;
      box-shadow:0 1px 2px rgba(17,28,45,0.04);
    }
    @media (max-width:640px){ .seccion{padding:var(--space-lg);} }

    .seccion-titulo{
      display:flex;align-items:center;gap:10px;
      font-size:20px;line-height:28px;font-weight:700;color:var(--on-surface);
    }
    .seccion-titulo .material-symbols-outlined{
      font-size:26px;color:var(--primary);
    }
    .titulo-tm .material-symbols-outlined{color:var(--tertiary);}
    .seccion-subtitulo{
      margin:6px 0 var(--space-lg);
      font-size:13px;color:var(--on-surface-variant);
    }

    /* ── Tabs ── */
    .tabs{display:flex;gap:8px;border-bottom:1px solid var(--outline-variant);margin-bottom:var(--space-lg);}
    .tab{
      display:flex;align-items:center;gap:6px;
      padding:10px 16px;font-size:13px;font-weight:600;
      color:var(--on-surface-variant);cursor:pointer;
      border-bottom:2px solid transparent;
      transition:color .15s ease, border-color .15s ease;
    }
    .tab .material-symbols-outlined{font-size:18px;}
    .tab:hover{color:var(--primary);}
    .tab.active-im{color:var(--primary);border-bottom-color:var(--primary);}
    .tab.active-tm{color:var(--tertiary);border-bottom-color:var(--tertiary);}

    /* ── Tablas de desempeño (semáforo) ── */
    .tab-content{overflow-x:auto;}
    .tab-content table{width:100%;border-collapse:collapse;font-size:13px;}
    .tab-content thead th{
      text-align:left;padding:10px 12px;white-space:nowrap;
      background:var(--surface-container-high);
      color:var(--on-surface-variant);
      font-size:11px;text-transform:uppercase;letter-spacing:0.04em;
      border-bottom:1px solid var(--outline-variant);
    }
    .tab-content tbody td{
      padding:9px 12px;white-space:nowrap;
      border-bottom:1px solid var(--outline-variant);
      color:var(--on-surface);
    }
    .tab-content tbody tr.verde   { background:rgba(0,107,95,0.07); }
    .tab-content tbody tr.amarillo{ background:rgba(196,130,15,0.09); }
    .tab-content tbody tr.rojo    { background:rgba(186,26,26,0.06); }
    .tab-content tbody tr.verde    td:first-child{border-left:3px solid var(--secondary);}
    .tab-content tbody tr.amarillo td:first-child{border-left:3px solid #c4820f;}
    .tab-content tbody tr.rojo     td:first-child{border-left:3px solid var(--error);}
  </style>
</head>
<body>

<!-- ===================== NAVBAR HORIZONTAL CON MENÚ HAMBURGUESA ===================== -->
<header class="navbar">
  <a href="../garantias/validador/validador.php" class="navbar-brand">
    <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="32" height="32">
    <div class="navbar-brand-text">
      <p class="text-headline-sm">Central Cell</p>
      <p class="text-label-sm" style="color:var(--outline)">Analizador Multisemana — IM &amp; TM</p>
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
      <span class="material-symbols-outlined">apps</span>
      Panel de Herramientas
    </a>
  </nav>
</header>

<div class="main" style="margin-left:0;">
  <div class="container">

    <div class="lesson">
      <div class="lesson-body">
        <div class="eyebrow">Comparativo semanal</div>
        <h1 class="text-headline-lg" style="margin:8px 0 4px;">Analizador Multisemana — IM &amp; TM</h1>
        <p class="text-body-md" style="color:var(--on-surface-variant);margin:0;">
          Sube tu archivo Excel de ventas para comparar el desempeño semanal contra la meta de cada departamento
        </p>

        <div class="controls">
          <div class="file-upload">
            <input id="fileInput" type="file" accept=".xlsx,.xls" style="display:none;" />
            <button class="boton" id="fileButton" type="button">
              <span class="material-symbols-outlined">attach_file</span>
              <span class="text">Seleccionar Archivo</span>
            </button>
          </div>
          <button id="processBtn" class="btn btn-primary" disabled>
            <span class="material-symbols-outlined">play_arrow</span>
            Procesar
          </button>
          <button id="downloadBtn" class="btn btn-secondary" disabled>
            <span class="material-symbols-outlined">download</span>
            Descargar Excel
          </button>
        </div>

        <div id="mensajes" class="note"></div>

        <div id="loader" class="loader-container" style="display:none;">
          <span class="material-symbols-outlined">progress_activity</span>
          <span>Procesando archivo...</span>
        </div>
      </div>
    </div>

    <!-- ── INNOVACIÓN MÓVIL ── -->
    <div class="seccion" id="seccionIM" style="display:none">
      <div class="seccion-titulo titulo-im">
        <span class="material-symbols-outlined">smartphone</span>
        Innovación Móvil — Accesorios
      </div>
      <p class="seccion-subtitulo">Comparativo semanal vs meta IM · Verde ≥100% · Amarillo ≥70% · Rojo &lt;70%</p>
      <div class="tabs">
        <div class="tab active-im" id="tabIM-vend" onclick="mostrarTab('IM','vend',this)">
          <span class="material-symbols-outlined">person</span> Vendedores
        </div>
        <div class="tab" id="tabIM-suc" onclick="mostrarTab('IM','suc',this)">
          <span class="material-symbols-outlined">storefront</span> Sucursales
        </div>
      </div>
      <div class="tab-content">
        <div id="tablaVendIM"></div>
        <div id="tablaSucIM" style="display:none"></div>
      </div>
    </div>

    <!-- ── TECNOLOGÍA MÓVIL ── -->
    <div class="seccion" id="seccionTM" style="display:none">
      <div class="seccion-titulo titulo-tm">
        <span class="material-symbols-outlined">devices</span>
        Tecnología Móvil — Telefonía
      </div>
      <p class="seccion-subtitulo">Comparativo semanal vs meta TM · Almacén y días efectivos tomados de IM</p>
      <div class="tabs">
        <div class="tab active-tm" id="tabTM-vend" onclick="mostrarTab('TM','vend',this)">
          <span class="material-symbols-outlined">person</span> Vendedores
        </div>
        <div class="tab" id="tabTM-suc" onclick="mostrarTab('TM','suc',this)">
          <span class="material-symbols-outlined">storefront</span> Sucursales
        </div>
      </div>
      <div class="tab-content">
        <div id="tablaVendTM"></div>
        <div id="tablaSucTM" style="display:none"></div>
      </div>
    </div>

  </div>

  
</div>

<script>

const METAS_IM = <?php echo json_encode(obtenerMetasTiendas('IM'), JSON_PRETTY_PRINT); ?> || {};
const METAS_TM = <?php echo json_encode(obtenerMetasTiendas('TM'), JSON_PRETTY_PRINT); ?> || {};

/* ── Estado global ── */
let weeksList        = [];
let sellersByWeekIM  = {}, storesByWeekIM = {};
let sellersByWeekTM  = {}, storesByWeekTM = {};
let expVendIM=[], expSucIM=[], expVendTM=[], expSucTM=[];

/* ── DOM ── */
const fileInput   = document.getElementById('fileInput');
const processBtn  = document.getElementById('processBtn');
const downloadBtn = document.getElementById('downloadBtn');
const mensajes    = document.getElementById('mensajes');

document.getElementById('fileButton').addEventListener('click', ()=> fileInput.click());
fileInput.addEventListener('change', ()=>{
  processBtn.disabled = !fileInput.files.length;
  mensajes.innerText  = fileInput.files.length ? `Archivo listo: ${fileInput.files[0].name}` : '';
});
processBtn.addEventListener('click',  ()=>{ if(fileInput.files.length) readFile(fileInput.files[0]); });
downloadBtn.addEventListener('click', ()=>{ if(weeksList.length) exportExcel(); });

/* ── Tabs ── */
function mostrarTab(depto, panel, el){
  const prefix = `tabla${depto==='IM'?'Vend':'Vend'}`;
  document.getElementById(`tablaVend${depto}`).style.display = panel==='vend'?'':'none';
  document.getElementById(`tablaSuc${depto}`).style.display  = panel==='suc'?'':'none';
  const activeCls = depto==='IM' ? 'active-im' : 'active-tm';
  document.querySelectorAll(`#seccion${depto} .tab`).forEach(t=>{
    t.classList.remove('active-im','active-tm');
  });
  el.classList.add(activeCls);
}


function safeCell(row,i){ return (i!==null&&i!==undefined&&row[i]!==undefined&&row[i]!==null)?row[i]:""; }
function toNumber(x){ if(!x&&x!==0)return 0; const n=parseFloat(String(x).replace(/\$/g,'').replace(/,/g,'').trim()); return isNaN(n)?0:n; }
function pad(n){ return String(n).padStart(2,'0'); }
function round2(n){ return Math.round((Number(n)+Number.EPSILON)*100)/100; }
function round6(n){ return Math.round((Number(n)+Number.EPSILON)*1000000)/1000000; }
function escapeHtml(s){ return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }
function avgOfObject(obj){ const v=Object.values(obj||{}).map(Number); return v.length?v.reduce((a,b)=>a+b,0)/v.length:0; }

function detectarIndices(headerRow){
  const lc=headerRow.map(h=>String(h||"").toLowerCase());
  const find=cs=>{ for(const c of cs){ const i=lc.findIndex(h=>h.includes(c)); if(i>=0) return i; } return -1; };
  const totalP=["totalventa","total venta","total_venta","venta total","total neto","total bruto","importe total","monto total"];
  let idxT=-1;
  for(const c of totalP){ const i=lc.findIndex(h=>h.includes(c)); if(i>=0&&!lc[i].includes("sub")){idxT=i;break;} }
  if(idxT===-1) idxT=lc.findIndex(h=>h.includes("total")&&!h.includes("sub"));
  if(idxT===-1) idxT=find(["importe","monto","amount","venta"]);
  if(idxT===-1) idxT=headerRow.length>18?18:null;
  const fb=(i,f)=>i>=0?i:(headerRow.length>f?f:null);
  return {
    almacen: fb(find(["almac","store","sucursal","almacén"]),0),
    n1:      fb(find(["n1","departamento","categoria"]),1),
    fecha:   fb(find(["fecha","date","dia","time"]),7),
    vendedor:fb(find(["vendedor","seller","promotor","vended"]),9),
    total:   idxT!==null?idxT:fb(-1,18)
  };
}

function parseFecha(fechaRaw){
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

function weekStart(date){
  const daysBack=(date.getDay()+1)%7;
  const d=new Date(date.getFullYear(),date.getMonth(),date.getDate());
  d.setDate(d.getDate()-daysBack);
  return new Date(d.getFullYear(),d.getMonth(),d.getDate());
}
function weekKey(sd){ return `${sd.getFullYear()}-${pad(sd.getMonth()+1)}-${pad(sd.getDate())}`; }
function niceWeekLabel(key){
  const [y,m,d]=key.split('-').map(Number);
  const s=new Date(y,m-1,d), e=new Date(s); e.setDate(s.getDate()+6);
  return `${pad(s.getDate())}/${pad(s.getMonth()+1)}/${s.getFullYear()} — ${pad(e.getDate())}/${pad(e.getMonth()+1)}/${e.getFullYear()}`;
}


function readFile(file){
  mensajes.innerText='Leyendo archivo...';
  document.getElementById('loader').style.display='flex';
  const reader=new FileReader();
  reader.onload=(e)=>{
    const wb=XLSX.read(new Uint8Array(e.target.result),{type:'array'});
    const rows=XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]],{header:1,defval:""});
    if(!rows||rows.length<2){ mensajes.innerText='Hoja vacía.'; document.getElementById('loader').style.display='none'; return; }

    const idx=detectarIndices(rows[0].map(h=>String(h||"").trim()));
    const allIM=[], allTM=[];

    for(let r=1;r<rows.length;r++){
      const row=rows[r];
      if(row.every(c=>c===null||String(c).trim()==="")) continue;
      const n1=String(safeCell(row,idx.n1)||"").trim();
      const fecha=parseFecha(safeCell(row,idx.fecha));
      if(!fecha) continue;
      const rec={
        almacen: String(safeCell(row,idx.almacen)||"(SIN ALMACEN)").trim(),
        vendedor:String(safeCell(row,idx.vendedor)||"(SIN VENDEDOR)").trim(),
        fecha, total:toNumber(safeCell(row,idx.total))
      };
      if(n1==='INNOVACION MOVIL')   allIM.push(rec);
      else if(n1==='TECNOLOGIA MOVIL') allTM.push(rec);
    }

    if(!allIM.length&&!allTM.length){
      mensajes.innerText='No se encontraron registros IM ni TM. Verifica la columna N1.';
      document.getElementById('loader').style.display='none'; return;
    }

    /* Semanas: unión de ambos departamentos */
    const weeksSet=new Set();
    [...allIM,...allTM].forEach(r=>weeksSet.add(weekKey(weekStart(r.fecha))));
    weeksList=Array.from(weeksSet).sort((a,b)=>new Date(a)-new Date(b));

    /* Procesar IM primero → genera el mapa de vendedores por semana para TM */
    const vendMapIMporSemana={};
    const resIM = processWeeks(allIM, METAS_IM, null, vendMapIMporSemana);
    sellersByWeekIM = resIM.sellers;
    storesByWeekIM  = resIM.stores;

    /* Procesar TM usando el mapa de IM */
    const resTM = processWeeks(allTM, METAS_TM, vendMapIMporSemana, null);
    sellersByWeekTM = resTM.sellers;
    storesByWeekTM  = resTM.stores;

    buildAndRenderTables();
    document.getElementById('loader').style.display='none';
    mensajes.innerText=`Completado ✔ · Semanas: ${weeksList.length} · Vendedores IM: ${Object.keys(sellersByWeekIM).length} · Vendedores TM: ${Object.keys(sellersByWeekTM).length}`;
    downloadBtn.disabled=false;
  };
  reader.readAsArrayBuffer(file);
}


function processWeeks(allRecords, METAS, diasIMRef, outMapIM){
  const sellers={}, stores={};

  weeksList.forEach(wk=>{
    const [y,m,d]=wk.split('-').map(Number);
    const wkStart=new Date(y,m-1,d), wkEnd=new Date(wkStart); wkEnd.setDate(wkStart.getDate()+6);

    const recsWeek=allRecords.filter(r=>{
      const dd=new Date(r.fecha.getFullYear(),r.fecha.getMonth(),r.fecha.getDate());
      return dd>=wkStart&&dd<=wkEnd;
    });

    /* ── Construir vendedoresMap de este departamento en esta semana ── */
    const vendedoresMap={};
    const tiendasDia={};
    Object.keys(METAS).forEach(t=>tiendasDia[t]=Array(7).fill(0));
    recsWeek.forEach(r=>{ if(!(r.almacen in tiendasDia)) tiendasDia[r.almacen]=Array(7).fill(0); });

    recsWeek.forEach(r=>{
      const v=r.vendedor, a=r.almacen, total=Number(r.total||0);
      if(!vendedoresMap[v]) vendedoresMap[v]={total:0,diasSet:new Set(),almacenes:{}};
      vendedoresMap[v].total+=total;
      vendedoresMap[v].diasSet.add(r.fecha.toDateString());
      if(!vendedoresMap[v].almacenes[a]) vendedoresMap[v].almacenes[a]={total:0,diasSet:new Set()};
      vendedoresMap[v].almacenes[a].total+=total;
      vendedoresMap[v].almacenes[a].diasSet.add(r.fecha.toDateString());
      const mi={6:0,0:1,1:2,2:3,3:4,4:5,5:6}[r.fecha.getDay()];
      if(mi!==undefined) tiendasDia[a][mi]+=total;
    });

    /* Guardar mapa IM de esta semana para TM */
    if(outMapIM) outMapIM[wk]=vendedoresMap;

    /* ── Helper: almacén principal de un mapa de almacenes ── */
    function getAlmacen(almacenesObj){
      const keys=Object.keys(almacenesObj||{});
      if(!keys.length) return null;
      if(keys.length===1) return keys[0];
      let maxD=-1,bestT=-1,best=null;
      keys.forEach(s=>{
        const dd=(almacenesObj[s].diasSet||new Set()).size;
        const tt=almacenesObj[s].total||0;
        if(dd>maxD||(dd===maxD&&tt>bestT)){maxD=dd;bestT=tt;best=s;}
      });
      return best;
    }

    /* ── Construir vendedoresArray según modo IM/TM ── */
    const vendedoresArray=[];

    if(diasIMRef){
     
      const mapIM=diasIMRef[wk]||{};
      Object.entries(mapIM).forEach(([vendedor,infoIM])=>{
        const totalTM = vendedoresMap[vendedor] ? vendedoresMap[vendedor].total : 0;
        const diasTM  = vendedoresMap[vendedor] ? vendedoresMap[vendedor].diasSet.size : 0;
        const almacenAsignado = getAlmacen(infoIM.almacenes);
        const diasEfectivos   = infoIM.diasSet ? infoIM.diasSet.size : 0;

        /* Asegurar que el almacén de IM exista en tiendasDia TM para que tenga meta */
        if(almacenAsignado && !(almacenAsignado in tiendasDia))
          tiendasDia[almacenAsignado]=Array(7).fill(0);

        vendedoresArray.push({vendedor, totalVentas:totalTM, diasVendidos:diasTM, diasEfectivos, almacenAsignado});
      });

    } else {
      /* ══════════════ MODO IM ══════════════ */
      Object.entries(vendedoresMap).forEach(([vendedor,info])=>{
        vendedoresArray.push({
          vendedor, totalVentas:info.total,
          diasVendidos:info.diasSet.size,
          diasEfectivos:info.diasSet.size,
          almacenAsignado:getAlmacen(info.almacenes)
        });
      });
    }

    /* ── Calcular meta por tienda ── */
    const tiendasArray=[];
    Object.keys(tiendasDia).forEach(almacen=>{
      const meta  = METAS[almacen]||{diaria:0,limite:9999};
      const metaD = meta.diaria||0;
      const metaS = metaD*7;
      const asignados = vendedoresArray.filter(v=>v.almacenAsignado===almacen);

      /* Divisor = vendedores con ≥4 días efectivos */
      let validos = asignados.filter(v=>v.diasEfectivos>=4);
      validos.sort((a,b)=>b.totalVentas-a.totalVentas);
      if(validos.length>(meta.limite||9999)) validos=validos.slice(0,meta.limite);
      const metaPV = metaS / Math.max(1, validos.length);
      /* Todos reciben metaPV — el divisor lo marcan los válidos */

      const dias=tiendasDia[almacen]||Array(7).fill(0);
      const totalS=dias.reduce((a,b)=>a+b,0);
      tiendasArray.push({almacen, metaD, metaS, metaPV, totalS, pctS:metaS?totalS/metaS:0});
    });

    /* ── % por vendedor ── */
    vendedoresArray.forEach(v=>{
      const tienda=tiendasArray.find(t=>t.almacen===v.almacenAsignado);
      const meta=tienda?tienda.metaPV:0;
      const pct=meta ? v.totalVentas/meta : 0;
      if(!sellers[v.vendedor]) sellers[v.vendedor]={};
      sellers[v.vendedor][wk]=round6(pct);
    });

    /* ── % por tienda ── */
    tiendasArray.forEach(t=>{
      if(!stores[t.almacen]) stores[t.almacen]={};
      stores[t.almacen][wk]=round6(t.pctS);
    });
  }); // fin forEach semana

  /* Rellenar semanas sin datos con 0 */
  [sellers, stores].forEach(map=>{
    Object.keys(map).forEach(k=>{
      weeksList.forEach(wk=>{ if(map[k][wk]===undefined) map[k][wk]=0; });
    });
  });

  return {sellers, stores};
}


function buildAndRenderTables(){
  /* IM */
  document.getElementById('seccionIM').style.display='';
  const {html:hvIM, rows:rvIM} = buildTable(sellersByWeekIM);
  const {html:hsIM, rows:rsIM} = buildTable(storesByWeekIM);
  document.getElementById('tablaVendIM').innerHTML=hvIM;
  document.getElementById('tablaSucIM').innerHTML=hsIM;
  expVendIM=rvIM; expSucIM=rsIM;

  /* TM */
  document.getElementById('seccionTM').style.display='';
  const {html:hvTM, rows:rvTM} = buildTable(sellersByWeekTM);
  const {html:hsTM, rows:rsTM} = buildTable(storesByWeekTM);
  document.getElementById('tablaVendTM').innerHTML=hvTM;
  document.getElementById('tablaSucTM').innerHTML=hsTM;
  expVendTM=rvTM; expSucTM=rsTM;
}

function buildTable(byWeek){
  const keys=Object.keys(byWeek).sort((a,b)=>avgOfObject(byWeek[b])-avgOfObject(byWeek[a]));
  const exportRows=[['Nombre',...weeksList,'Promedio Decimal','Promedio %','Desempeño']];

  if(!keys.length) return {html:'<p class="note">Sin datos.</p>', rows:exportRows};

  let html='<table><thead><tr><th>Nombre</th>';
  weeksList.forEach(w=>html+=`<th>${escapeHtml(niceWeekLabel(w))}</th>`);
  html+='<th>Promedio %</th><th>Desempeño</th></tr></thead><tbody>';

  keys.forEach(k=>{
    const vals=weeksList.map(w=>Number(byWeek[k][w]||0));
    const avg=vals.reduce((a,b)=>a+b,0)/Math.max(1,vals.length);
    const avgP=round2(avg*100);
    const cls=avg>=1?'verde':avg>=0.7?'amarillo':'rojo';
    const label=avg>=1?'Excelente':avg>=0.7?'Promedio':'Bajo';
    html+=`<tr class="${cls}"><td>${escapeHtml(k)}</td>`;
    vals.forEach(v=>html+=`<td>${(v*100).toFixed(2)}%</td>`);
    html+=`<td>${avgP}%</td><td>${label}</td></tr>`;
    exportRows.push([k,...vals.map(x=>round6(x)),round6(avg),round2(avg*100)/100,label]);
  });

  return {html:html+'</tbody></table>', rows:exportRows};
}


function exportExcel(){
  const wb=XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(expVendIM), 'Vendedores IM');
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(expSucIM),  'Sucursales IM');
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(expVendTM), 'Vendedores TM');
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(expSucTM),  'Sucursales TM');
  XLSX.writeFile(wb,'Resultados_Multisemana_IM_TM.xlsx');
}
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