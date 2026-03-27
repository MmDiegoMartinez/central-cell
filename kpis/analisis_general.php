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
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:18px;background:#f7f7f7;color:#222}
    h1{margin-top:0}
    .controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
    button.btn{background:#007bff;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer}
    button.btn:disabled{background:#999;cursor:not-allowed}
    table{border-collapse:collapse;width:100%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.07);margin-top:12px}
    th,td{padding:8px 6px;border:1px solid #e6e6e6;text-align:center;font-size:13px}
    th{background:#2f6fa6;color:#fff;position:sticky;top:0;z-index:1}
    .verde{background:#dff7df}.amarillo{background:#fff3cc}.rojo{background:#ffdad6}
    .note{margin-top:8px;color:#333}

    /* ── Secciones ── */
    .seccion{margin-top:32px}
    .seccion-titulo{
      display:flex;align-items:center;gap:10px;
      font-size:1.18em;font-weight:700;padding:12px 18px;
      border-radius:10px;color:#fff;margin-bottom:4px;
    }
    .titulo-im{background:linear-gradient(135deg,#f5576c,#f093fb)}
    .titulo-tm{background:linear-gradient(135deg,#4facfe,#00b4d8)}
    .seccion-subtitulo{font-size:.9em;color:#666;margin:2px 0 10px 4px}

    /* ── Tabs ── */
    .tabs{display:flex;gap:6px;margin-bottom:-1px;flex-wrap:wrap}
    .tab{padding:7px 16px;border-radius:8px 8px 0 0;border:1px solid #ddd;border-bottom:none;
         cursor:pointer;background:#e9eef5;font-weight:600;font-size:13px;color:#444;transition:background .2s}
    .tab:hover{filter:brightness(.95)}
    .tab.active-im{background:#f5576c;color:#fff;border-color:#f5576c}
    .tab.active-tm{background:#4facfe;color:#fff;border-color:#4facfe}
    .tab-content{border:1px solid #ddd;border-radius:0 8px 8px 8px;padding:10px;background:#fff}
  </style>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header>
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
              <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25"/>
            </span>
            Home
          </a>
        </li>
      </ul>
    </div>
  </nav>
</header>

<div class="container">
  <h1>Analizador Multisemana — IM & TM</h1>

  <div class="controls">
    <div class="file-upload">
      <input id="fileInput" type="file" accept=".xlsx,.xls" style="display:none;" />
      <button class="boton" id="fileButton" type="button">
        <div class="contenedorCarpeta">
          <div class="folder folder_one"></div><div class="folder folder_two"></div>
          <div class="folder folder_three"></div><div class="folder folder_four"></div>
        </div>
        <div class="active_line"></div>
        <span class="text">Seleccionar Archivo</span>
      </button>
    </div>
    <button id="processBtn" class="btn" disabled>Procesar</button>
    <button id="downloadBtn" class="btn" disabled>⬇ Descargar Excel</button>
  </div>

  <div id="mensajes" class="note"></div>

  <div id="loader" class="loader-container" style="display:none;">
    <div class="cloud front"><span class="left-front"></span><span class="right-front"></span></div>
    <span class="sun sunshine"></span><span class="sun"></span>
    <div class="cloud back"><span class="left-back"></span><span class="right-back"></span></div>
  </div>

  <!-- ── INNOVACIÓN MÓVIL ── -->
  <div class="seccion" id="seccionIM" style="display:none">
    <div class="seccion-titulo titulo-im">📱 Innovación Móvil — Accesorios</div>
    <p class="seccion-subtitulo">Comparativo semanal vs meta IM · Verde ≥100% · Amarillo ≥70% · Rojo &lt;70%</p>
    <div class="tabs">
      <div class="tab active-im" id="tabIM-vend" onclick="mostrarTab('IM','vend',this)">👤 Vendedores</div>
      <div class="tab"           id="tabIM-suc"  onclick="mostrarTab('IM','suc',this)">🏪 Sucursales</div>
    </div>
    <div class="tab-content">
      <div id="tablaVendIM"></div>
      <div id="tablaSucIM"  style="display:none"></div>
    </div>
  </div>

  <!-- ── TECNOLOGÍA MÓVIL ── -->
  <div class="seccion" id="seccionTM" style="display:none">
    <div class="seccion-titulo titulo-tm">📲 Tecnología Móvil — Telefonía</div>
    <p class="seccion-subtitulo">Comparativo semanal vs meta TM · Almacén y días efectivos tomados de IM</p>
    <div class="tabs">
      <div class="tab active-tm" id="tabTM-vend" onclick="mostrarTab('TM','vend',this)">👤 Vendedores</div>
      <div class="tab"           id="tabTM-suc"  onclick="mostrarTab('TM','suc',this)">🏪 Sucursales</div>
    </div>
    <div class="tab-content">
      <div id="tablaVendTM"></div>
      <div id="tablaSucTM"  style="display:none"></div>
    </div>
  </div>
</div>

<script>
/* ══════════════════════════════════════════════════
   METAS desde PHP (BD)
══════════════════════════════════════════════════ */
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

/* ══════════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════════ */
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

/* ══════════════════════════════════════════════════
   LECTURA DEL ARCHIVO
══════════════════════════════════════════════════ */
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

/* ══════════════════════════════════════════════════
   PROCESAR TODAS LAS SEMANAS DE UN DEPARTAMENTO
   diasIMRef  : { weekKey → vendedoresMap }  → modo TM
   outMapIM   : objeto donde se escribe el mapa IM  → modo IM
══════════════════════════════════════════════════ */
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
      /* ══════════════ MODO TM ══════════════
         Fuente de verdad = mapa de IM en esta semana.
         · Almacén asignado → el que IM determinó.
         · Días efectivos   → días en IM.
         · Ventas           → lo que vendió en TM (0 si no vendió).
         Todos los vendedores de IM aparecen en TM.          */
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

/* ══════════════════════════════════════════════════
   RENDER TABLAS
══════════════════════════════════════════════════ */
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

/* ══════════════════════════════════════════════════
   EXPORT — 4 hojas Excel
══════════════════════════════════════════════════ */
function exportExcel(){
  const wb=XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(expVendIM), 'Vendedores IM');
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(expSucIM),  'Sucursales IM');
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(expVendTM), 'Vendedores TM');
  XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(expSucTM),  'Sucursales TM');
  XLSX.writeFile(wb,'Resultados_Multisemana_IM_TM.xlsx');
}

/* ── Menú hamburguesa ── */
document.getElementById('menu-check').addEventListener('change',function(){
  const m=document.getElementById('nav-menu');
  m.style.opacity=this.checked?'1':'0';
  m.style.visibility=this.checked?'visible':'hidden';
  m.style.pointerEvents=this.checked?'auto':'none';
});
</script>
</body>
</html>