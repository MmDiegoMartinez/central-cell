<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comparativo Mensual — IM & TM</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
<style>
body{font-family:Arial,sans-serif;margin:18px;background:#f7f7f7;color:#222}
h1{color:#234;margin-top:0}
.controls{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
select,input[type=file]{padding:6px;border-radius:6px;border:1px solid #ccc}
button.btn{background:#007bff;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer}
button.btn:disabled{background:#999;cursor:not-allowed}
.note{font-size:13px;color:#444;margin-top:8px}
table{border-collapse:collapse;width:100%;background:#fff;margin-top:12px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
th,td{padding:8px;border:1px solid #e6e6e6;text-align:center;font-size:13px}
th{background:#2f6fa6;color:#fff;position:sticky;top:0}
caption{font-weight:700;text-align:left;padding:8px;color:#123}
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
        <a href="../garantias/validador/validador.php" class="menu-link">
          <span class="logo-container">
            <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" class="logo" width="25" height="25"/>
          </span>
          Home
        </a>
      </li>
      <li>
        <a href="index.php" class="menu-link">
          
          Panel KPIs
        </a>
      </li>
      </ul>
    </div>
  </nav>
</header>

<div class="container">
  <h1>🔁 Comparativo Mensual por Categoría — IM & TM</h1>

  <div class="controls">
    <div class="file-upload">
      <input id="inputFile" type="file" accept=".xlsx,.xls" style="display:none;"/>
      <button class="boton" id="fileButton" type="button">
        <div class="contenedorCarpeta">
          <div class="folder folder_one"></div><div class="folder folder_two"></div>
          <div class="folder folder_three"></div><div class="folder folder_four"></div>
        </div>
        <div class="active_line"></div>
        <span class="text">Seleccionar Archivo</span>
      </button>
    </div>
    <button id="cargarBtn" class="btn" disabled>Cargar archivo</button>
    <label style="margin-left:8px;">Mes 1:</label>
    <select id="mes1" disabled></select>
    <label>Mes 2:</label>
    <select id="mes2" disabled></select>
    <button id="analizarBtn" class="btn" disabled>Analizar</button>
    <button id="exportBtn"   class="btn" disabled>⬇ Exportar Excel</button>
  </div>

  <div id="loader" class="loader-container" style="display:none;">
    <div class="cloud front"><span class="left-front"></span><span class="right-front"></span></div>
    <span class="sun sunshine"></span><span class="sun"></span>
    <div class="cloud back"><span class="left-back"></span><span class="right-back"></span></div>
  </div>

  <div id="mensajes" class="note">Sube un archivo Excel con al menos dos meses de ventas.</div>

  <!-- ── INNOVACIÓN MÓVIL ── -->
  <div class="seccion" id="seccionIM" style="display:none">
    <div class="seccion-titulo titulo-im">📱 Innovación Móvil — Accesorios</div>
    <p class="seccion-subtitulo">Comparativo mensual de categorías N3 · N1 = INNOVACION MOVIL</p>
    <div class="tabs">
      <div class="tab active-im" id="tabIM-gen"    onclick="mostrarTab('IM','gen',this)">🌐 General</div>
      <div class="tab"           id="tabIM-tienda" onclick="mostrarTab('IM','tienda',this)">🏪 Por Tienda</div>
    </div>
    <div class="tab-content">
      <div id="tabGenIM"></div>
      <div id="tabTiendaIM" style="display:none"></div>
    </div>
  </div>

  <!-- ── TECNOLOGÍA MÓVIL ── -->
  <div class="seccion" id="seccionTM" style="display:none">
    <div class="seccion-titulo titulo-tm">📲 Tecnología Móvil — Telefonía</div>
    <p class="seccion-subtitulo">Comparativo mensual de categorías N3 · N1 = TECNOLOGIA MOVIL</p>
    <div class="tabs">
      <div class="tab active-tm" id="tabTM-gen"    onclick="mostrarTab('TM','gen',this)">🌐 General</div>
      <div class="tab"           id="tabTM-tienda" onclick="mostrarTab('TM','tienda',this)">🏪 Por Tienda</div>
    </div>
    <div class="tab-content">
      <div id="tabGenTM"></div>
      <div id="tabTiendaTM" style="display:none"></div>
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

/* ══════════════════════════════════════════════════
   EXPORT — Solo 2 hojas: una IM y una TM
══════════════════════════════════════════════════ */
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