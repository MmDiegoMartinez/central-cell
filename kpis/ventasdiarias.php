<?php
include_once '../funciones.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Análisis Semanal Completo — IM & TM</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<style>
body{font-family:Arial,sans-serif;margin:18px;background:#f7f7f7;color:#222}
h1{margin-top:0}
.controls{display:flex;gap:12px;align-items:center;margin-bottom:12px;flex-wrap:wrap}
button.btn{background:#007bff;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer}
button.btn:disabled{background:#999;cursor:not-allowed}
table{border-collapse:collapse;width:100%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.07);margin-bottom:20px}
th,td{padding:8px 6px;border:1px solid #e1e1e1;text-align:center;font-size:13px}
th{background:#2f6fa6;color:#fff;position:sticky;top:0;z-index:1}
caption{text-align:left;font-weight:600;padding:8px}
.note{font-size:13px;color:#333;margin-top:6px}
.debug{font-size:12px;color:#666;margin-top:6px;background:#fff;padding:8px;border:1px solid #eee}
tr.total-row{font-weight:700;background:#eee}

/* ── Secciones IM / TM ── */
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
  <h1>📈 Análisis Semanal Completo — IM & TM</h1>

  <div class="controls">
    <div class="file-upload">
      <input id="inputFile" type="file" accept=".xlsx,.xls" style="display:none;" />
      <button class="boton" id="fileButton" type="button">
        <div class="contenedorCarpeta">
          <div class="folder folder_one"></div><div class="folder folder_two"></div>
          <div class="folder folder_three"></div><div class="folder folder_four"></div>
        </div>
        <div class="active_line"></div>
        <span class="text">Seleccionar Archivo</span>
      </button>
    </div>
    <button id="procesarBtn" class="btn" disabled>Procesar archivo</button>
    <button id="descargarBtn" class="btn" disabled>⬇ Descargar .xlsx</button>
  </div>

  <div id="mensajes" class="note"></div>
  <div id="debugBox" class="debug" style="display:none;"></div>

  <div class="center-container">
    <div id="loader" class="loader-container" style="display:none;">
      <div class="cloud front"><span class="left-front"></span><span class="right-front"></span></div>
      <span class="sun sunshine"></span><span class="sun"></span>
      <div class="cloud back"><span class="left-back"></span><span class="right-back"></span></div>
    </div>
  </div>

  <!-- ── INNOVACIÓN MÓVIL ── -->
  <div class="seccion" id="seccionIM" style="display:none">
    <div class="seccion-titulo titulo-im">📱 Innovación Móvil — Accesorios</div>
    <p class="seccion-subtitulo">Ventas por vendedor/día vs meta IM · semanas Sáb→Vie</p>
    <div class="tabs">
      <div class="tab active-im" id="tabIM-vend" onclick="mostrarTab('IM','vend',this)">👤 Por Vendedor</div>
      <div class="tab"           id="tabIM-tienda" onclick="mostrarTab('IM','tienda',this)">🏪 Por Tienda</div>
    </div>
    <div class="tab-content">
      <div id="tablaVendIM"></div>
      <div id="tablaTiendaIM" style="display:none"></div>
    </div>
  </div>

  <!-- ── TECNOLOGÍA MÓVIL ── -->
  <div class="seccion" id="seccionTM" style="display:none">
    <div class="seccion-titulo titulo-tm">📲 Tecnología Móvil — Telefonía</div>
    <p class="seccion-subtitulo">Ventas por vendedor/día vs meta TM · almacén y días efectivos según IM</p>
    <div class="tabs">
      <div class="tab active-tm" id="tabTM-vend" onclick="mostrarTab('TM','vend',this)">👤 Por Vendedor</div>
      <div class="tab"           id="tabTM-tienda" onclick="mostrarTab('TM','tienda',this)">🏪 Por Tienda</div>
    </div>
    <div class="tab-content">
      <div id="tablaVendTM"></div>
      <div id="tablaTiendaTM" style="display:none"></div>
    </div>
  </div>
</div>

<script>
/* ══════════════════════════════════════════════════
   METAS desde PHP
══════════════════════════════════════════════════ */
const METAS_IM = <?php echo json_encode(obtenerMetasTiendas('IM'), JSON_PRETTY_PRINT); ?> || {};
const METAS_TM = <?php echo json_encode(obtenerMetasTiendas('TM'), JSON_PRETTY_PRINT); ?> || {};

/* ── Estado global ── */
let storesResumenIM = {}, storesResumenTM = {};
// Mapa de almacén asignado por vendedor en IM (por semana)
// { weekKey → { vendedor → almacen } }
let almacenIMporSemana = {};
// Mapa de días efectivos en IM por vendedor
// { weekKey → { vendedor → diasCount } }
let diasIMporSemana = {};

/* ── DOM ── */
const inputFile   = document.getElementById('inputFile');
const procesarBtn = document.getElementById('procesarBtn');
const descargarBtn= document.getElementById('descargarBtn');
const mensajes    = document.getElementById('mensajes');
const debugBox    = document.getElementById('debugBox');

document.getElementById('fileButton').addEventListener('click', ()=> inputFile.click());
inputFile.addEventListener('change', ()=>{
  procesarBtn.disabled = !inputFile.files.length;
  mensajes.innerText   = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : '';
});
procesarBtn.addEventListener('click',  ()=>{ if(inputFile.files.length) leerExcel(inputFile.files[0]); });
descargarBtn.addEventListener('click', ()=> descargaResultados());

/* ── Tabs ── */
function mostrarTab(depto, panel, el){
  document.getElementById(`tablaVend${depto}`).style.display   = panel==='vend'   ? '' : 'none';
  document.getElementById(`tablaTienda${depto}`).style.display = panel==='tienda' ? '' : 'none';
  const cls = depto==='IM' ? 'active-im' : 'active-tm';
  document.querySelectorAll(`#seccion${depto} .tab`).forEach(t=>t.classList.remove('active-im','active-tm'));
  el.classList.add(cls);
}

/* ══════════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════════ */
function safeNumber(x){
  if(x===null||x===undefined||x==="") return 0;
  const n=parseFloat(String(x).replace(/\s+/g,' ').replace(/[,\$]/g,'').trim());
  return isNaN(n)?0:n;
}

function parseFecha(fechaRaw){
  if(fechaRaw===null||fechaRaw===undefined||fechaRaw==="") return null;
  if(typeof fechaRaw==='number'){
    try{
      if(typeof XLSX!=='undefined'&&XLSX.SSF&&typeof XLSX.SSF.parse_date_code==='function'){
        const d=XLSX.SSF.parse_date_code(fechaRaw);
        if(d&&d.y&&d.m&&d.d) return new Date(d.y,d.m-1,d.d);
      }
    }catch(e){}
    const jsDate=new Date((fechaRaw-25569)*86400*1000);
    return new Date(jsDate.getFullYear(),jsDate.getMonth(),jsDate.getDate());
  }
  if(typeof fechaRaw==='string'){
    let s=fechaRaw.replace(/\s+/g,' ').trim();
    const monthNames={jan:0,feb:1,mar:2,apr:3,may:4,jun:5,jul:6,aug:7,sep:8,oct:9,nov:10,dec:11,
                      ene:0,abr:3,ago:7,dic:11};
    const r1=/([A-Za-z]{3,})\s+(\d{1,2})\s+(\d{4})/,r2=/(\d{1,2})\s+([A-Za-z]{3,})\s+(\d{4})/;
    let m=s.match(r1)||s.match(r2);
    if(m){
      const isR1=!!s.match(r1);
      const monStr=isR1?m[1]:m[2],dayStr=isR1?m[2]:m[1],yearStr=m[3];
      const mk=monStr.substring(0,3).toLowerCase();
      const month=(mk in monthNames)?monthNames[mk]:NaN;
      if(!isNaN(month)) return new Date(parseInt(yearStr,10),month,parseInt(dayStr,10));
    }
    const d=new Date(s);
    if(!isNaN(d.getTime())) return new Date(d.getFullYear(),d.getMonth(),d.getDate());
    const iso=s.replace(/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/,'$3-$2-$1');
    const d2=new Date(iso);
    if(!isNaN(d2.getTime())) return new Date(d2.getFullYear(),d2.getMonth(),d2.getDate());
    return null;
  }
  return null;
}

function getDiaSemanaIndex(fecha){
  if(!fecha) return null;
  return {6:0,0:1,1:2,2:3,3:4,4:5,5:6}[fecha.getDay()];
}

function obtenerSemanas(fechaInicio, fechaFin){
  const semanas=[];
  if(!fechaInicio||!fechaFin) return semanas;
  const firstStart=new Date(fechaInicio.getFullYear(),fechaInicio.getMonth(),fechaInicio.getDate());
  const day=firstStart.getDay();
  const daysUntilFriday=(5-day+7)%7;
  const firstEnd=new Date(firstStart);
  firstEnd.setDate(firstStart.getDate()+daysUntilFriday);
  semanas.push({start:firstStart,end:firstEnd});
  const nextSat=new Date(firstEnd);
  nextSat.setDate(firstEnd.getDate()+1);
  while(nextSat<=fechaFin){
    const end=new Date(nextSat);
    end.setDate(nextSat.getDate()+6);
    semanas.push({start:new Date(nextSat),end});
    nextSat.setDate(nextSat.getDate()+7);
  }
  return semanas;
}

function wKey(s){ return `${s.getFullYear()}-${pad(s.getMonth()+1)}-${pad(s.getDate())}`; }
function pad(n){ return String(n).padStart(2,'0'); }
function escapeHtml(s){ return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }

/* ══════════════════════════════════════════════════
   LECTURA DEL ARCHIVO
══════════════════════════════════════════════════ */
function leerExcel(file){
  mensajes.innerText='Leyendo archivo...';
  document.getElementById('loader').style.display='flex';
  const reader=new FileReader();
  reader.onload=e=>{
    const wb=XLSX.read(new Uint8Array(e.target.result),{type:'array'});
    const rows=XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]],{header:1,defval:""});
    if(!rows||rows.length<2){ mensajes.innerText='Archivo vacío.'; document.getElementById('loader').style.display='none'; return; }

    // Detectar índices por encabezado
    const headerRow=rows[0].map(h=>String(h||"").toLowerCase());
    const find=cs=>{ for(const c of cs){ const i=headerRow.findIndex(h=>h.includes(c)); if(i>=0) return i; } return -1; };
    const idxAlmacen = find(["almac","store","sucursal"]) !== -1 ? find(["almac","store","sucursal"]) : 0;
    const idxN1      = find(["n1","departamento","categoria"]) !== -1 ? find(["n1","departamento","categoria"]) : 1;
    const idxFecha   = find(["fecha","date","dia"]) !== -1 ? find(["fecha","date","dia"]) : 7;
    const idxVend    = find(["vendedor","seller","promotor"]) !== -1 ? find(["vendedor","seller","promotor"]) : 9;
    const idxTotal   = (() => {
      const totalP=["totalventa","total venta","total_venta","venta total","total neto","total bruto","importe total","monto total"];
      let i=-1;
      for(const c of totalP){ i=headerRow.findIndex(h=>h.includes(c)); if(i>=0&&!headerRow[i].includes("sub")) break; }
      if(i===-1) i=headerRow.findIndex(h=>h.includes("total")&&!h.includes("sub"));
      return i>=0 ? i : 18;
    })();

    const allIM=[], allTM=[];
    const badDates=[];

    for(let r=1;r<rows.length;r++){
      const row=rows[r];
      if(row.every(c=>c===null||String(c).trim()==="")) continue;
      const n1=String(row[idxN1]||"").trim();
      if(n1!=='INNOVACION MOVIL'&&n1!=='TECNOLOGIA MOVIL') continue;
      const fecha=parseFecha(row[idxFecha]);
      if(!fecha){ badDates.push({row:r+1,raw:row[idxFecha]}); continue; }
      const rec={
        almacen:String(row[idxAlmacen]||"(SIN ALMACEN)").trim(),
        vendedor:String(row[idxVend]||"(SIN VENDEDOR)").trim(),
        fecha, total:safeNumber(row[idxTotal])
      };
      if(n1==='INNOVACION MOVIL') allIM.push(rec);
      else allTM.push(rec);
    }

    if(badDates.length){
      debugBox.style.display='block';
      debugBox.innerText=`Advertencia: ${badDates.length} filas con fecha no parseable. Ej: fila ${badDates[0].row} → "${badDates[0].raw}"`;
    } else { debugBox.style.display='none'; }

    mensajes.innerText=`IM: ${allIM.length} registros | TM: ${allTM.length} registros. Procesando...`;

    /* Procesar IM primero → genera mapa de almacén y días por vendedor por semana */
    storesResumenIM = procesarDepartamento(allIM, METAS_IM, null);

    /* Procesar TM usando el mapa de IM */
    storesResumenTM = procesarDepartamento(allTM, METAS_TM, {
      almacenMap: almacenIMporSemana,
      diasMap:    diasIMporSemana
    });

    mostrarTablas();
    document.getElementById('loader').style.display='none';
    descargarBtn.disabled=false;
    mensajes.innerText=`Completado ✔ · Tiendas IM: ${Object.keys(storesResumenIM).length} · Tiendas TM con datos: ${Object.keys(storesResumenTM).length}`;
  };
  reader.readAsArrayBuffer(file);
}

/* ══════════════════════════════════════════════════
   PROCESAR UN DEPARTAMENTO
   imRef: null (modo IM) | { almacenMap, diasMap } (modo TM)
   Retorna: { almacen → [ { start,end,rows,metaDiaria } ] }
══════════════════════════════════════════════════ */
function procesarDepartamento(allRecords, METAS, imRef){

  /* ── Modo TM: usar todos los almacenes de IM como estructura ── */
  if(imRef){
    /* Reunir todos los almacenes que aparecieron en IM */
    const almacenesIM = new Set();
    Object.values(imRef.almacenMap).forEach(semMap=>{
      Object.values(semMap).forEach(a=>{ if(a) almacenesIM.add(a); });
    });

    /* Obtener rango de fechas de TM (y de IM) para construir semanas */
    const todasFechas=[...allRecords.map(r=>r.fecha)];
    Object.values(imRef.diasMap).forEach(semMap=>{
      /* Las claves de semana ya nos dan el rango */
    });

    /* Construir mapa TM: almacen → [registros TM] */
    const storesTM={};
    allRecords.forEach(r=>{ if(!storesTM[r.almacen]) storesTM[r.almacen]=[]; storesTM[r.almacen].push(r); });

    /* Resultado final: iterar sobre almacenes de IM (fuente de verdad) */
    const result={};

    almacenesIM.forEach(almacen=>{
      /* Semanas para este almacén: basadas en IM */
      /* Necesitamos las semanas. Las construimos del mapa de IM */
      const semanasKeys=Object.keys(imRef.almacenMap)
        .filter(wk=>Object.values(imRef.almacenMap[wk]).includes(almacen))
        .sort();

      if(!semanasKeys.length) return;

      result[almacen]=semanasKeys.map(wk=>{
        const [y,m,d]=wk.split('-').map(Number);
        const start=new Date(y,m-1,d);
        const end=new Date(start); end.setDate(start.getDate()+6);

        /* Vendedores asignados a este almacén en IM esta semana */
        const vendedoresEnIM=Object.entries(imRef.almacenMap[wk])
          .filter(([,a])=>a===almacen)
          .map(([v])=>v);

        /* Ventas TM de estos vendedores en esta semana */
        const weekVend={};
        const recsTM=allRecords.filter(r=>{
          const dd=new Date(r.fecha.getFullYear(),r.fecha.getMonth(),r.fecha.getDate());
          return dd>=start&&dd<=end;
        });

        /* Inicializar todos los vendedores de IM con 0 */
        vendedoresEnIM.forEach(v=>{ weekVend[v]=Array(7).fill(0); });

        /* Sumar ventas TM */
        recsTM.forEach(r=>{
          const v=r.vendedor;
          /* Solo los vendedores asignados a este almacén según IM */
          if(!vendedoresEnIM.includes(v)) return;
          if(!weekVend[v]) weekVend[v]=Array(7).fill(0);
          const idx=getDiaSemanaIndex(r.fecha);
          if(idx!==null) weekVend[v][idx]+=r.total;
        });

        /* Calcular meta usando días efectivos de IM */
        const metaDiaria=METAS[almacen]?METAS[almacen].diaria||0:0;
        const metaSemanal=metaDiaria*7;

        /* Divisor = vendedores con ≥4 días en IM esta semana */
        const validos=vendedoresEnIM.filter(v=>{
          const dias=imRef.diasMap[wk]?imRef.diasMap[wk][v]||0:0;
          return dias>=4;
        });
        const metaPV=metaSemanal/Math.max(1,validos.length);

        const rows=vendedoresEnIM.map(v=>{
          const ventas=weekVend[v]||Array(7).fill(0);
          const total=ventas.reduce((a,b)=>a+b,0);
          return {
            Vendedor:v,
            MetaAsignada:metaPV,    // todos reciben la misma meta
            DiasEfectivosIM: imRef.diasMap[wk]?imRef.diasMap[wk][v]||0:0,
            Dias:ventas,
            TotalSemana:total,
            Diferencia:total-metaPV,
            Porcentaje:metaPV?(total/metaPV)*100:0
          };
        });

        return {start,end,rows,metaDiaria};
      });
    });

    return result;
  }

  /* ══ MODO IM: lógica original ══ */
  const stores={};
  allRecords.forEach(r=>{ if(!stores[r.almacen]) stores[r.almacen]=[]; stores[r.almacen].push(r); });

  const result={};

  for(const almacen of Object.keys(stores)){
    const arr=stores[almacen].sort((a,b)=>a.fecha-b.fecha);
    const semanas=obtenerSemanas(arr[0].fecha,arr[arr.length-1].fecha);
    result[almacen]=[];

    semanas.forEach(s=>{
      const wk=wKey(s.start);
      const weekVend={};

      arr.forEach(r=>{
        if(r.fecha>=s.start&&r.fecha<=s.end){
          if(!weekVend[r.vendedor]) weekVend[r.vendedor]=Array(7).fill(0);
          const idx=getDiaSemanaIndex(r.fecha);
          if(idx!==null) weekVend[r.vendedor][idx]+=safeNumber(r.total);
        }
      });

      /* Guardar mapa de almacén y días de IM para TM */
      if(!almacenIMporSemana[wk]) almacenIMporSemana[wk]={};
      if(!diasIMporSemana[wk])    diasIMporSemana[wk]={};
      Object.entries(weekVend).forEach(([v,dias])=>{
        almacenIMporSemana[wk][v]=almacen;
        diasIMporSemana[wk][v]=dias.filter(x=>x>0).length;
      });

      /* Calcular meta IM */
      const metaDiaria=METAS[almacen]?METAS[almacen].diaria||0:0;
      const metaSemanal=metaDiaria*7;
      const validos=Object.keys(weekVend).filter(v=>weekVend[v].filter(x=>x>0).length>=4);
      const metaPV=metaSemanal/Math.max(1,validos.length);

      const rows=Object.keys(weekVend).map(v=>{
        const ventas=weekVend[v];
        const total=ventas.reduce((a,b)=>a+b,0);
        return {
          Vendedor:v,
          MetaAsignada:metaPV,
          Dias:ventas,
          TotalSemana:total,
          Diferencia:total-metaPV,
          Porcentaje:metaPV?(total/metaPV)*100:0
        };
      });

      if(!rows.length) rows.push({Vendedor:"(SIN VENTAS)",MetaAsignada:metaPV,Dias:Array(7).fill(0),TotalSemana:0,Diferencia:-metaPV,Porcentaje:0});

      result[almacen].push({start:s.start,end:s.end,rows,metaDiaria});
    });
  }

  return result;
}

/* ══════════════════════════════════════════════════
   MOSTRAR TABLAS
══════════════════════════════════════════════════ */
const DIA_LABELS=["Sábado","Domingo","Lunes","Martes","Miércoles","Jueves","Viernes"];

function mostrarTablas(){
  renderDepto(storesResumenIM,'IM');
  renderDepto(storesResumenTM,'TM');
}

function renderDepto(resumen, depto){
  if(!Object.keys(resumen).length) return;
  document.getElementById(`seccion${depto}`).style.display='';

  /* ── Vista por vendedor: todas las semanas de todas las tiendas ── */
  let htmlVend='';
  /* ── Vista por tienda: tabla resumen ── */
  let htmlTienda='';

  for(const almacen of Object.keys(resumen).sort()){
    resumen[almacen].forEach(weekObj=>{
      const {start,end,rows,metaDiaria}=weekObj;
      const title=`${almacen} — ${pad(start.getDate())}/${pad(start.getMonth()+1)}/${start.getFullYear()} al ${pad(end.getDate())}/${pad(end.getMonth()+1)}/${end.getFullYear()}`;

      /* Tabla por vendedor */
      let hv=`<table><caption>${escapeHtml(title)}</caption><thead><tr>`;
      hv+=`<th>Vendedor</th><th>Meta Asignada</th>`;
      if(depto==='TM') hv+=`<th>Días IM</th>`;
      DIA_LABELS.forEach(d=>hv+=`<th>${d}</th>`);
      hv+=`<th>Total Semana</th><th>Diferencia</th><th>% Cumpl.</th></tr></thead><tbody>`;

      const totDias=Array(7).fill(0);
      let totSemana=0;

      rows.forEach(r=>{
        hv+=`<tr><td>${escapeHtml(r.Vendedor)}</td><td>${r.MetaAsignada.toFixed(2)}</td>`;
        if(depto==='TM') hv+=`<td>${r.DiasEfectivosIM||0}</td>`;
        r.Dias.forEach((v,i)=>{ hv+=`<td>${v.toFixed(2)}</td>`; totDias[i]+=v; });
        hv+=`<td>${r.TotalSemana.toFixed(2)}</td><td>${r.Diferencia.toFixed(2)}</td><td>${r.Porcentaje.toFixed(2)}%</td></tr>`;
        totSemana+=r.TotalSemana;
      });

      const metaTotal=metaDiaria*7;
      const pct=metaTotal?(totSemana/metaTotal)*100:0;
      hv+=`<tr class="total-row"><td>Total</td><td></td>`;
      if(depto==='TM') hv+=`<td></td>`;
      totDias.forEach(t=>hv+=`<td>${t.toFixed(2)}</td>`);
      hv+=`<td>${totSemana.toFixed(2)}</td><td>${(totSemana-metaTotal).toFixed(2)}</td><td>${pct.toFixed(2)}%</td></tr>`;
      hv+='</tbody></table>';
      htmlVend+=hv;
    });
  }

  /* Vista por tienda: una fila por semana */
  let ht=`<table><thead><tr><th>Tienda</th><th>Semana</th><th>Meta Semanal</th>`;
  DIA_LABELS.forEach(d=>ht+=`<th>${d}</th>`);
  ht+=`<th>Total</th><th>% Cumpl.</th></tr></thead><tbody>`;

  for(const almacen of Object.keys(resumen).sort()){
    resumen[almacen].forEach(weekObj=>{
      const {start,end,rows,metaDiaria}=weekObj;
      const metaSemanal=metaDiaria*7;
      const totDias=Array(7).fill(0);
      let totSemana=0;
      rows.forEach(r=>{ r.Dias.forEach((v,i)=>totDias[i]+=v); totSemana+=r.TotalSemana; });
      const pct=metaSemanal?(totSemana/metaSemanal)*100:0;
      const semLabel=`${pad(start.getDate())}/${pad(start.getMonth()+1)} — ${pad(end.getDate())}/${pad(end.getMonth()+1)}/${end.getFullYear()}`;
      ht+=`<tr><td>${escapeHtml(almacen)}</td><td>${semLabel}</td><td>${metaSemanal.toFixed(2)}</td>`;
      totDias.forEach(t=>ht+=`<td>${t.toFixed(2)}</td>`);
      ht+=`<td>${totSemana.toFixed(2)}</td><td>${pct.toFixed(2)}%</td></tr>`;
    });
  }
  ht+='</tbody></table>';
  htmlTienda=ht;

  document.getElementById(`tablaVend${depto}`).innerHTML   = htmlVend   || '<p class="note">Sin datos.</p>';
  document.getElementById(`tablaTienda${depto}`).innerHTML = htmlTienda || '<p class="note">Sin datos.</p>';
}

/* ══════════════════════════════════════════════════
   EXPORT EXCEL — una hoja por tienda (IM y TM)
══════════════════════════════════════════════════ */
function descargaResultados(){
  if(!Object.keys(storesResumenIM).length&&!Object.keys(storesResumenTM).length){
    alert("Procesa el archivo primero."); return;
  }
  const wb=XLSX.utils.book_new();
  exportDepto(wb, storesResumenIM, 'IM', false);
  exportDepto(wb, storesResumenTM, 'TM', true);
  XLSX.writeFile(wb,'Analisis_Semanal_IM_TM.xlsx');
}

function exportDepto(wb, resumen, depto, conDiasIM){
  for(const almacen of Object.keys(resumen).sort()){
    const wsData=[];
    resumen[almacen].forEach(weekObj=>{
      const {start,end,rows,metaDiaria}=weekObj;
      wsData.push([`[${depto}] ${almacen} — ${pad(start.getDate())}/${pad(start.getMonth()+1)}/${start.getFullYear()} al ${pad(end.getDate())}/${pad(end.getMonth()+1)}/${end.getFullYear()}`]);
      const header=["Vendedor","MetaAsignada",...(conDiasIM?["DíasEfectivosIM"]:[]),...DIA_LABELS,"TotalSemana","Diferencia","%Cumplimiento"];
      wsData.push(header);
      rows.forEach(r=>{
        const row=[r.Vendedor,r.MetaAsignada.toFixed(2),...(conDiasIM?[r.DiasEfectivosIM||0]:[]),
                   ...r.Dias.map(d=>d.toFixed(2)),
                   r.TotalSemana.toFixed(2),r.Diferencia.toFixed(2),
                   (r.MetaAsignada?(r.TotalSemana/r.MetaAsignada):0).toFixed(4)];
        wsData.push(row);
      });
      /* fila de totales */
      const totDias=Array(7).fill(0); let totSem=0;
      rows.forEach(r=>{ r.Dias.forEach((d,i)=>totDias[i]+=d); totSem+=r.TotalSemana; });
      const metaTotal=metaDiaria*7;
      wsData.push(["Total","", ...(conDiasIM?[""]:[]),
                   ...totDias.map(t=>t.toFixed(2)),
                   totSem.toFixed(2),(totSem-metaTotal).toFixed(2),
                   metaTotal?(totSem/metaTotal).toFixed(4):"0"]);
      wsData.push([]);
    });
    const sheetName=`${depto} ${almacen}`.substring(0,31)||`${depto}_Tienda`;
    XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(wsData), sheetName);
  }
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