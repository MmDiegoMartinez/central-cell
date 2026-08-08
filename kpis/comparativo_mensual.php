<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comparativo Mensual — IM & TM</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>

<!-- Hoja de estilos base del sistema de diseño (Material 3) -->
<link rel="stylesheet" href="../styles.css">

<!-- Estilos adicionales SOLO para componentes que no existen en la hoja base
     (subida de archivo, selects de mes, loader, tabs, tablas comparativas con fila total).
     No se modifica el CSS externo. -->
<style>
  /* ── Contenedor de controles ── */
  .controls{
    display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-md);
    margin-top:var(--space-lg);
  }
  .controls label{
    font-size:13px;font-weight:600;color:var(--on-surface-variant);
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

  /* ── Selects de mes ── */
  .controls select{
    padding:9px 14px;border-radius:var(--radius-lg);
    border:1px solid var(--outline-variant);
    background:var(--surface-container-lowest);
    color:var(--on-surface);font-size:14px;font-weight:500;
    cursor:pointer;
  }
  .controls select:disabled{opacity:0.5;cursor:not-allowed;}
  .controls select:focus-visible{outline:2px solid var(--primary);outline-offset:1px;}

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

  /* ── Tablas comparativas ── */
  .tab-content{overflow-x:auto;}
  .tab-content table{width:100%;border-collapse:collapse;font-size:13px;margin-bottom:var(--space-xl);}
  .tab-content caption{
    text-align:left;caption-side:top;
    font-size:14px;font-weight:700;color:var(--on-surface);
    padding:0 0 10px;
  }
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
  .tab-content tbody tr:hover{background:var(--surface-container-low);}
  .tab-content tbody tr.total-row{
    background:var(--surface-container-high);
    font-weight:700;color:var(--on-surface);
  }
  .tab-content tbody tr.total-row td{border-bottom:none;}
</style>
</head>
<body>

<!-- TOP HEADER -->
<header class="topheader">
  <div class="topheader-left">
    <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="26" height="26" style="border-radius:6px;">
    <h2 class="text-headline-sm">Comparativo Mensual — IM &amp; TM</h2>
  </div>
  <nav class="topnav">
    <a href="../garantias/validador/validador.php">Home</a>
    <a href="modulos.html">Panel de Herramientas</a>
  </nav>
</header>

<div class="main" style="margin-left:0;">
  <div class="container">

    <div class="lesson">
      <div class="lesson-body">
        <div class="eyebrow">Comparativo por categoría</div>
        <h1 class="text-headline-lg" style="margin:8px 0 4px;display:flex;align-items:center;gap:10px;">
          <span class="material-symbols-outlined" style="font-size:30px;color:var(--primary);">sync_alt</span>
          Comparativo Mensual por Categoría — IM &amp; TM
        </h1>
        <p class="text-body-md" style="color:var(--on-surface-variant);margin:0;">
          Sube tu archivo Excel y compara las ventas por categoría entre dos meses
        </p>

        <div class="controls">
          <div class="file-upload">
            <input id="inputFile" type="file" accept=".xlsx,.xls" style="display:none;"/>
            <button class="boton" id="fileButton" type="button">
              <span class="material-symbols-outlined">attach_file</span>
              <span class="text">Seleccionar Archivo</span>
            </button>
          </div>
          <button id="cargarBtn" class="btn btn-outline" disabled>
            <span class="material-symbols-outlined">upload</span>
            Cargar archivo
          </button>
          <label>Mes 1:</label>
          <select id="mes1" disabled></select>
          <label>Mes 2:</label>
          <select id="mes2" disabled></select>
          <button id="analizarBtn" class="btn btn-primary" disabled>
            <span class="material-symbols-outlined">query_stats</span>
            Analizar
          </button>
          <button id="exportBtn" class="btn btn-secondary" disabled>
            <span class="material-symbols-outlined">download</span>
            Exportar Excel
          </button>
        </div>

        <div id="loader" class="loader-container" style="display:none;">
          <span class="material-symbols-outlined">progress_activity</span>
          <span>Procesando archivo...</span>
        </div>

        <div id="mensajes" class="note">Sube un archivo Excel con al menos dos meses de ventas.</div>
      </div>
    </div>

    <!-- ── INNOVACIÓN MÓVIL ── -->
    <div class="seccion" id="seccionIM" style="display:none">
      <div class="seccion-titulo titulo-im">
        <span class="material-symbols-outlined">smartphone</span>
        Innovación Móvil — Accesorios
      </div>
      <p class="seccion-subtitulo">Comparativo mensual de categorías N3 · N1 = INNOVACION MOVIL</p>
      <div class="tabs">
        <div class="tab active-im" id="tabIM-gen" onclick="mostrarTab('IM','gen',this)">
          <span class="material-symbols-outlined">public</span> General
        </div>
        <div class="tab" id="tabIM-tienda" onclick="mostrarTab('IM','tienda',this)">
          <span class="material-symbols-outlined">storefront</span> Por Tienda
        </div>
      </div>
      <div class="tab-content">
        <div id="tabGenIM"></div>
        <div id="tabTiendaIM" style="display:none"></div>
      </div>
    </div>

    <!-- ── TECNOLOGÍA MÓVIL ── -->
    <div class="seccion" id="seccionTM" style="display:none">
      <div class="seccion-titulo titulo-tm">
        <span class="material-symbols-outlined">devices</span>
        Tecnología Móvil — Telefonía
      </div>
      <p class="seccion-subtitulo">Comparativo mensual de categorías N3 · N1 = TECNOLOGIA MOVIL</p>
      <div class="tabs">
        <div class="tab active-tm" id="tabTM-gen" onclick="mostrarTab('TM','gen',this)">
          <span class="material-symbols-outlined">public</span> General
        </div>
        <div class="tab" id="tabTM-tienda" onclick="mostrarTab('TM','tienda',this)">
          <span class="material-symbols-outlined">storefront</span> Por Tienda
        </div>
      </div>
      <div class="tab-content">
        <div id="tabGenTM"></div>
        <div id="tabTiendaTM" style="display:none"></div>
      </div>
    </div>

  </div>

  
</div>

<script>
/* ── Índices de columnas ── */
const IDX_ALMACEN = 0;
const IDX_N1      = 1;
const IDX_N2      = 2;
const IDX_N3      = 3;
const IDX_FECHA   = 7;
const IDX_SUM     = 18;

/* ── Estado ── */
let rawRows          = [];
let mesesDisponibles = [];
let resultIM = null, resultTM = null;

/* ── DOM ── */
const inputFile  = document.getElementById('inputFile');
const cargarBtn  = document.getElementById('cargarBtn');
const mes1Sel    = document.getElementById('mes1');
const mes2Sel    = document.getElementById('mes2');
const analizarBtn= document.getElementById('analizarBtn');
const exportBtn  = document.getElementById('exportBtn');
const mensajes   = document.getElementById('mensajes');

document.getElementById('fileButton').addEventListener('click', ()=> inputFile.click());
inputFile.addEventListener('change', ()=>{
  cargarBtn.disabled = !inputFile.files.length;
  mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : 'Sube un archivo.';
});
cargarBtn.addEventListener('click',   handleLoad);
analizarBtn.addEventListener('click', handleAnalizar);
exportBtn.addEventListener('click',   handleExport);

/* ── Tabs ── */
function mostrarTab(depto, panel, el){
  document.getElementById(`tabGen${depto}`).style.display    = panel==='gen'    ? '' : 'none';
  document.getElementById(`tabTienda${depto}`).style.display = panel==='tienda' ? '' : 'none';
  const cls = depto==='IM' ? 'active-im' : 'active-tm';
  document.querySelectorAll(`#seccion${depto} .tab`).forEach(t=>t.classList.remove('active-im','active-tm'));
  el.classList.add(cls);
}

/* ══════════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════════ */
function safeNumber(x){
  if(x===null||x===undefined||x==='') return 0;
  const n=parseFloat(String(x).replace(/[,\$]/g,'').trim());
  return isNaN(n)?0:n;
}
function pad2(n){ return String(n).padStart(2,'0'); }
function monthKey(d){ return `${d.getFullYear()}-${pad2(d.getMonth()+1)}`; }
function monthLabel(d){
  return `${["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"][d.getMonth()]} ${d.getFullYear()}`;
}
function getLabel(key){
  const [y,m]=key.split('-').map(Number);
  return `${["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"][m-1]} ${y}`;
}
function escapeHtml(s){ return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

function parseFecha(raw){
  if(raw===null||raw===undefined||raw==='') return null;
  if(typeof raw==='number'){
    const js=new Date((raw-25569)*86400*1000);
    return new Date(js.getFullYear(),js.getMonth(),js.getDate());
  }
  if(typeof raw==='string'){
    let s=raw.replace(/\s+/g,' ').trim();
    const monMap={jan:0,feb:1,mar:2,apr:3,may:4,jun:5,jul:6,aug:7,sep:8,oct:9,nov:10,dec:11,
                  ene:0,abr:3,ago:7,dic:11};
    const r1=s.match(/([A-Za-z]{3,})\s+(\d{1,2})\s+(\d{4})/);
    if(r1){
      const mk=r1[1].substring(0,3).toLowerCase();
      const month=(mk in monMap)?monMap[mk]:NaN;
      if(!isNaN(month)) return new Date(parseInt(r1[3],10),month,parseInt(r1[2],10));
    }
    const d=new Date(s);
    if(!isNaN(d.getTime())) return new Date(d.getFullYear(),d.getMonth(),d.getDate());
  }
  return null;
}

/* ══════════════════════════════════════════════════
   CARGA DEL ARCHIVO
══════════════════════════════════════════════════ */
function handleLoad(){
  const file=inputFile.files[0]; if(!file) return;
  mensajes.innerText='Leyendo archivo...';
  document.getElementById('loader').style.display='flex';
  const reader=new FileReader();
  reader.onload=e=>{
    try{
      const wb=XLSX.read(new Uint8Array(e.target.result),{type:'array'});
      const rows=XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]],{header:1,defval:""});
      if(!rows||rows.length<2){ mensajes.innerText='Archivo sin datos.'; return; }
      rawRows=rows;

      const monthsSet=new Map();
      for(let i=1;i<rows.length;i++){
        const d=parseFecha(rows[i][IDX_FECHA]);
        if(!d) continue;
        const k=monthKey(d);
        if(!monthsSet.has(k)) monthsSet.set(k,monthLabel(d));
      }
      const keys=Array.from(monthsSet.keys()).sort();
      mesesDisponibles=keys.map(k=>({key:k,label:monthsSet.get(k)}));
      if(mesesDisponibles.length<2){ mensajes.innerText='Necesitas al menos dos meses en el archivo.'; return; }

      mes1Sel.innerHTML=''; mes2Sel.innerHTML='';
      mesesDisponibles.forEach(m=>{
        const o1=document.createElement('option'); o1.value=m.key; o1.textContent=m.label; mes1Sel.appendChild(o1);
        const o2=document.createElement('option'); o2.value=m.key; o2.textContent=m.label; mes2Sel.appendChild(o2);
      });
      mes1Sel.selectedIndex=0; mes2Sel.selectedIndex=1;
      mes1Sel.disabled=false; mes2Sel.disabled=false; analizarBtn.disabled=false;
      mensajes.innerText=`Archivo cargado. Meses: ${mesesDisponibles.map(m=>m.label).join(', ')}. Selecciona dos meses y pulsa Analizar.`;
    }catch(err){ console.error(err); mensajes.innerText='Error leyendo el archivo.'; }
    document.getElementById('loader').style.display='none';
  };
  reader.readAsArrayBuffer(file);
}

/* ══════════════════════════════════════════════════
   ANÁLISIS
══════════════════════════════════════════════════ */
function handleAnalizar(){
  const m1=mes1Sel.value, m2=mes2Sel.value;
  if(!m1||!m2||m1===m2){ alert('Selecciona dos meses distintos.'); return; }
  mensajes.innerText=`Analizando ${getLabel(m1)} vs ${getLabel(m2)}...`;

  resultIM = analizarDepto('INNOVACION MOVIL', m1, m2);
  resultTM = analizarDepto('TECNOLOGIA MOVIL', m1, m2);

  renderDepto(resultIM, 'IM', m1, m2);
  renderDepto(resultTM, 'TM', m1, m2);

  exportBtn.disabled=false;
  const cIM=Object.keys(resultIM).length, cTM=Object.keys(resultTM).length;
  mensajes.innerText=`Análisis listo ✔ · Tiendas IM: ${cIM} · Tiendas TM: ${cTM}`;
}

function analizarDepto(n1Label, m1, m2){
  const storesMap={};
  for(let i=1;i<rawRows.length;i++){
    const r=rawRows[i];
    if(String(r[IDX_N1]||'').trim()!==n1Label) continue;
    const d=parseFecha(r[IDX_FECHA]); if(!d) continue;
    const mk=monthKey(d);
    if(mk!==m1&&mk!==m2) continue;
    const alm=String(r[IDX_ALMACEN]||'(SIN ALMACEN)').trim();
    const n3=String(r[IDX_N3]||'').trim();
    const n2=String(r[IDX_N2]||'').trim();
    const cat=n3||n2||'(SIN CATEGORIA)';
    const val=safeNumber(r[IDX_SUM]);
    if(!storesMap[alm]) storesMap[alm]={};
    if(!storesMap[alm][cat]) storesMap[alm][cat]={m1:0,m2:0};
    if(mk===m1) storesMap[alm][cat].m1+=val;
    if(mk===m2) storesMap[alm][cat].m2+=val;
  }

  const result={};
  Object.keys(storesMap).sort((a,b)=>a.localeCompare(b,'es')).forEach(alm=>{
    result[alm]=Object.keys(storesMap[alm]).sort((a,b)=>a.localeCompare(b,'es')).map(cat=>{
      const m1v=storesMap[alm][cat].m1, m2v=storesMap[alm][cat].m2;
      const diff=m2v-m1v;
      const pct=m1v===0?null:(diff/m1v)*100;
      return {categoria:cat,mes1:m1v,mes2:m2v,diferencia:diff,porcentaje:pct,
              porcentajeDecimal:m1v===0?null:diff/m1v};
    });
  });
  return result;
}

/* ══════════════════════════════════════════════════
   RENDER
══════════════════════════════════════════════════ */
function renderDepto(data, depto, m1, m2){
  if(!Object.keys(data).length) return;
  document.getElementById(`seccion${depto}`).style.display='';

  const label1=getLabel(m1), label2=getLabel(m2);

  const global={};
  Object.values(data).forEach(rows=>{
    rows.forEach(r=>{
      if(!global[r.categoria]) global[r.categoria]={m1:0,m2:0};
      global[r.categoria].m1+=r.mes1;
      global[r.categoria].m2+=r.mes2;
    });
  });

  let hGen=buildTable(`GENERAL — ${label1} vs ${label2}`, label1, label2,
    Object.keys(global).sort((a,b)=>a.localeCompare(b,'es')).map(cat=>{
      const m1v=global[cat].m1, m2v=global[cat].m2, diff=m2v-m1v;
      return {categoria:cat,mes1:m1v,mes2:m2v,diferencia:diff,
              porcentaje:m1v===0?null:(diff/m1v)*100};
    })
  );
  document.getElementById(`tabGen${depto}`).innerHTML=hGen;

  let hTienda='';
  Object.keys(data).forEach(alm=>{
    hTienda+=buildTable(`${alm} — ${label1} vs ${label2}`, label1, label2, data[alm]);
  });
  document.getElementById(`tabTienda${depto}`).innerHTML=hTienda||'<p class="note">Sin datos.</p>';
}

function buildTable(title, label1, label2, rows){
  let html=`<table><caption>${escapeHtml(title)}</caption>
  <thead><tr>
    <th>Categoría</th>
    <th>${escapeHtml(label1)}</th>
    <th>${escapeHtml(label2)}</th>
    <th>Diferencia</th>
    <th>% Diferencia</th>
  </tr></thead><tbody>`;

  let tot1=0,tot2=0;
  rows.forEach(r=>{
    const pctText=r.porcentaje===null?'N/A':r.porcentaje.toFixed(2)+'%';
    html+=`<tr>
      <td>${escapeHtml(r.categoria)}</td>
      <td>${r.mes1.toFixed(2)}</td>
      <td>${r.mes2.toFixed(2)}</td>
      <td>${r.diferencia.toFixed(2)}</td>
      <td>${pctText}</td>
    </tr>`;
    tot1+=r.mes1; tot2+=r.mes2;
  });

  const totDiff=tot2-tot1;
  const totPct=tot1===0?'N/A':(totDiff/tot1*100).toFixed(2)+'%';
  html+=`<tr class="total-row">
    <td>Total</td>
    <td>${tot1.toFixed(2)}</td>
    <td>${tot2.toFixed(2)}</td>
    <td>${totDiff.toFixed(2)}</td>
    <td>${totPct}</td>
  </tr></tbody></table>`;
  return html;
}

/*
   EXPORT — Solo 2 hojas: una IM y una TM
 */
function handleExport(){
  if(!resultIM&&!resultTM){ alert('Analiza primero.'); return; }
  const m1=mes1Sel.value, m2=mes2Sel.value;
  const wb=XLSX.utils.book_new();

  exportDeptoExcel(wb, resultIM, 'IM', m1, m2);
  exportDeptoExcel(wb, resultTM, 'TM', m1, m2);

  XLSX.writeFile(wb,`Comparativo_IM_TM_${m1.replace('-','')}_vs_${m2.replace('-','')}.xlsx`);
}

function exportDeptoExcel(wb, data, depto, m1, m2){
  if(!data||!Object.keys(data).length) return;
  const label1=getLabel(m1), label2=getLabel(m2);
  const header=["Categoría",`Ventas ${label1}`,`Ventas ${label2}`,"Diferencia","% Diferencia"];

  const aoa=[];

  /* ── 1. Tabla GENERAL ── */
  const global={};
  Object.values(data).forEach(rows=>{
    rows.forEach(r=>{
      if(!global[r.categoria]) global[r.categoria]={m1:0,m2:0};
      global[r.categoria].m1+=r.mes1;
      global[r.categoria].m2+=r.mes2;
    });
  });

  aoa.push([`[${depto}] GENERAL — ${label1} vs ${label2}`]);
  aoa.push(header);
  let tot1g=0, tot2g=0;
  Object.keys(global).sort((a,b)=>a.localeCompare(b,'es')).forEach(cat=>{
    const m1v=global[cat].m1, m2v=global[cat].m2, diff=m2v-m1v;
    aoa.push([cat, m1v, m2v, diff, m1v===0 ? "" : diff/m1v]);
    tot1g+=m1v; tot2g+=m2v;
  });
  aoa.push(["Total", tot1g, tot2g, tot2g-tot1g, tot1g===0 ? "" : (tot2g-tot1g)/tot1g]);

  /* ── 2. Tablas por almacén ── */
  Object.keys(data).sort((a,b)=>a.localeCompare(b,'es')).forEach(alm=>{
    const rows=data[alm];
    aoa.push([]);
    aoa.push([]);
    aoa.push([`[${depto}] ${alm} — ${label1} vs ${label2}`]);
    aoa.push(header);
    let tot1=0, tot2=0;
    rows.forEach(r=>{
      aoa.push([r.categoria, r.mes1, r.mes2, r.diferencia,
                r.porcentajeDecimal===null ? "" : r.porcentajeDecimal]);
      tot1+=r.mes1; tot2+=r.mes2;
    });
    aoa.push(["Total", tot1, tot2, tot2-tot1, tot1===0 ? "" : (tot2-tot1)/tot1]);
  });

  /* ── Crear hoja, colores y formato ── */
  const ws = XLSX.utils.aoa_to_sheet(aoa);

  function colorCell(cell, val){
    if(!cell || typeof val !== 'number') return;
    const rgb = val > 0 ? 'FF7DFA6B' : val < 0 ? 'FFFF4545' : 'FFA8A8A8';
    cell.s = { fill: { patternType: 'solid', fgColor: { rgb } } };
  }

  const range = XLSX.utils.decode_range(ws['!ref']);
  for(let R = range.s.r; R <= range.e.r; R++){
    // col 3 = Diferencia
    const cDif = ws[XLSX.utils.encode_cell({r:R, c:3})];
    if(cDif && typeof cDif.v === 'number') colorCell(cDif, cDif.v);
    // col 4 = % Diferencia
    const cPct = ws[XLSX.utils.encode_cell({r:R, c:4})];
    if(cPct && typeof cPct.v === 'number'){
      cPct.z = '0.00%';
      colorCell(cPct, cPct.v);
    }
  }

  ws['!cols'] = [
    {wch:35},
    {wch:16},
    {wch:16},
    {wch:14},
    {wch:14},
  ];

  const sheetName = depto === 'IM' ? 'Innovación Móvil' : 'Tecnología Móvil';
  XLSX.utils.book_append_sheet(wb, ws, sheetName);
}
</script>
</body>
</html>