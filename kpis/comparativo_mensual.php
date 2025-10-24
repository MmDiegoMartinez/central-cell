<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Comparativo Mensual — INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<style>
  body{font-family:Arial, sans-serif; margin:18px; background:#f7f7f7; color:#222;}
  h1{color:#234; margin-top:0;}
  .controls{display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:12px;}
  select,input[type=file]{padding:6px; border-radius:6px; border:1px solid #ccc;}
  button.btn{background:#007bff;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;}
  button.btn:disabled{background:#999; cursor:not-allowed;}
  .note{font-size:13px;color:#444;margin-top:8px;}
  table{border-collapse:collapse;width:100%;background:#fff;margin-top:12px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
  th,td{padding:8px;border:1px solid #e6e6e6;text-align:center;font-size:13px;}
  th{background:#2f6fa6;color:white;position:sticky;top:0;}
  caption{font-weight:700;text-align:left;padding:8px;color:#123;}
  .small{font-size:12px;color:#666;}
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
<div class="container">
<h1>🔁 Comparativo Mensual por Categoría — INNOVACION MOVIL</h1>

<div class="controls">
<div class="file-upload">
  <input id="inputFile" type="file" accept=".xlsx,.xls" />

  <button class="boton" id="fileButton" type="button">
    <div class="contenedorCarpeta">
      <div class="folder folder_one"></div>
      <div class="folder folder_two"></div>
      <div class="folder folder_three"></div>
      <div claAlmacess="folder folder_four"></div>
    </div>
    <div class="active_line"></div>
    <span class="text">Seleccionar Archivo</span>
  </button>
</div>

<script>
document.getElementById("fileButton").addEventListener("click", () => {
  document.getElementById("inputFile").click();
});
</script>

  <button id="cargarBtn" class="btn" disabled>Cargar archivo</button>

  <label style="margin-left:12px;">Mes 1:</label>
  <select id="mes1" disabled></select>

  <label>Mes 2:</label>
  <select id="mes2" disabled></select>

  <button id="analizarBtn" class="btn" disabled>Analizar</button>
  <button id="exportBtn" class="btn" disabled>Exportar Excel</button>
</div>
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
<div id="mensajes" class="note">Sube un archivo Excel con al menos dos meses de ventas.</div>
<div id="resultado"></div>
</div>

<script>
/*
Indices fijos (0-based):
A (Almacén) = 0
B (N1) = 1
D (N3 categoria) = 3
H (Fecha) = 7
S (Cantidad a sumar según tu instrucción) = 18
*/
const IDX_ALMACEN = 0;
const IDX_N1 = 1;
const IDX_N3 = 3;
const IDX_FECHA = 7;
const IDX_SUM = 18; // columna S (Cantidad) según tu indicación

let rawRows = []; // filas del sheet
let mesesDisponibles = []; // array de {key: "2025-09", label:"Sep 2025"}
let registrosFiltrados = [];

const inputFile = document.getElementById('inputFile');
const cargarBtn = document.getElementById('cargarBtn');
const mes1Sel = document.getElementById('mes1');
const mes2Sel = document.getElementById('mes2');
const analizarBtn = document.getElementById('analizarBtn');
const exportBtn = document.getElementById('exportBtn');
const mensajes = document.getElementById('mensajes');
const resultadoDiv = document.getElementById('resultado');

inputFile.addEventListener('change', ()=>{ cargarBtn.disabled = !inputFile.files.length; mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : 'Sube un archivo.'; });
cargarBtn.addEventListener('click', handleLoad);
analizarBtn.addEventListener('click', handleAnalizar);
exportBtn.addEventListener('click', handleExport);

/* --- utilidades --- */
function safeNumber(x){
  if (x === null || x === undefined || x === '') return 0;
  const s = String(x).replace(/[,\\$]/g,'').trim();
  const n = parseFloat(s);
  return isNaN(n) ? 0 : n;
}

function pad2(n){ return String(n).padStart(2,'0'); }

function monthKeyFromDate(d){
  // devuelve "YYYY-MM"
  return `${d.getFullYear()}-${pad2(d.getMonth()+1)}`;
}
function monthLabelFromDate(d){
  const months = ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"];
  return `${months[d.getMonth()]} ${d.getFullYear()}`;
}

function parseDateRobusta(raw){
  if (raw === null || raw === undefined || raw === '') return null;
  // si es número excel
  if (typeof raw === 'number'){
    // convertir serial a JS Date
    const js = new Date((raw - 25569) * 86400 * 1000);
    return new Date(js.getFullYear(), js.getMonth(), js.getDate());
  }
  if (typeof raw === 'string'){
    let s = raw.replace(/\s+/g,' ').trim();
    
    const r1 = s.match(/([A-Za-z]{3,})\s+(\d{1,2})\s+(\d{4})/);
    if (r1){
      // map month
      const monMap = {jan:0,feb:1,mar:2,apr:3,may:4,jun:5,jul:6,aug:7,sep:8,oct:9,nov:10,dec:11,
                      ene:0,feb:1,mar:2,abr:3,may:4,jun:5,jul:6,ago:7,sep:8,oct:9,nov:10,dic:11};
      const monKey = r1[1].substring(0,3).toLowerCase();
      const month = (monKey in monMap) ? monMap[monKey] : NaN;
      const day = parseInt(r1[2],10);
      const year = parseInt(r1[3],10);
      if (!isNaN(month) && !isNaN(day) && !isNaN(year)){
        return new Date(year, month, day);
      }
    }
    // fallback Date parse: y devolver solo Y-M-D (evitar offset)
    const d = new Date(s);
    if (!isNaN(d.getTime())){
      return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    }
  }
  return null;
}

/* --- carga y extracción de meses --- */
function handleLoad(){
  const file = inputFile.files[0];
  if (!file) return;
    mensajes.innerText = 'Leyendo archivo...';
document.getElementById('loader').style.display = 'flex';
const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const data = new Uint8Array(e.target.result);
      const wb = XLSX.read(data, {type:'array'});
      const sheet = wb.Sheets[wb.SheetNames[0]];
      const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "" });
      if (!rows || rows.length < 2) {
        mensajes.innerText = 'El archivo no contiene filas útiles.';
        return;
      }
      rawRows = rows;
      // extraer meses disponibles desde columna H (idx 7)
      const monthsSet = new Map(); // key -> label
      for (let i = 1; i < rows.length; i++){
        const r = rows[i];
        const rawDate = r[IDX_FECHA];
        const d = parseDateRobusta(rawDate);
        if (!d) continue;
        const key = monthKeyFromDate(d);
        if (!monthsSet.has(key)) monthsSet.set(key, monthLabelFromDate(d));
      }
      // ordenar keys asc
      const keys = Array.from(monthsSet.keys()).sort();
      mesesDisponibles = keys.map(k => ({ key: k, label: monthsSet.get(k) }));
      if (mesesDisponibles.length < 2){
        mensajes.innerText = 'Necesitas un archivo con al menos dos meses diferentes en la columna de fecha (H).';
        return;
      }
      // poblar selects
      populateMonthSelects();
      mensajes.innerText = `Archivo cargado. Meses detectados: ${mesesDisponibles.map(m => m.label).join(', ')}. Selecciona dos meses y pulsa Analizar.`;
    } catch(err) {
      console.error(err);
      mensajes.innerText = 'Error leyendo el archivo. Revisa consola.';
    }
    document.getElementById('loader').style.display = 'none'; 
  };
  reader.readAsArrayBuffer(file);
  // habilitar selects cuando termine
}

/* llena mes1/mes2 selects */
function populateMonthSelects(){
  mes1Sel.innerHTML = '';
  mes2Sel.innerHTML = '';
  mesesDisponibles.forEach(m=>{
    const o1 = document.createElement('option'); o1.value = m.key; o1.textContent = m.label;
    mes1Sel.appendChild(o1);
    const o2 = document.createElement('option'); o2.value = m.key; o2.textContent = m.label;
    mes2Sel.appendChild(o2);
  });
  // seleccionar por defecto dos primeros distintos
  mes1Sel.selectedIndex = 0;
  mes2Sel.selectedIndex = mesesDisponibles.length > 1 ? 1 : 0;
  mes1Sel.disabled = false;
  mes2Sel.disabled = false;
  analizarBtn.disabled = false;
}

/* --- análisis --- */
function handleAnalizar(){
  const m1 = mes1Sel.value;
  const m2 = mes2Sel.value;
  if (!m1 || !m2 || m1 === m2){
    alert('Selecciona dos meses distintos para comparar.');
    return;
  }
  mensajes.innerText = `Analizando comparación entre ${getLabel(m1)} y ${getLabel(m2)}...`;
  // filtrar registros N1 == "INNOVACION MOVIL"
  registrosFiltrados = [];
  for (let i = 1; i < rawRows.length; i++){
    const r = rawRows[i];
    const n1 = String(r[IDX_N1]||'').trim();
    if (n1 !== 'INNOVACION MOVIL') continue;
    const almacen = String(r[IDX_ALMACEN]||'(SIN ALMACEN)').trim();
    const categoria = String(r[IDX_N3]||'(SIN CATEGORIA)').trim();
    const rawDate = r[IDX_FECHA];
    const d = parseDateRobusta(rawDate);
    if (!d) continue;
    const key = monthKeyFromDate(d); // YYYY-MM
    const value = safeNumber(r[IDX_SUM]); // columna S según indicas
    registrosFiltrados.push({ almacen, categoria, monthKey: key, value });
  }

  // Agrupar por almacen -> por categoria -> sumar por mes1 y mes2
  const storesMap = {}; // almacen -> { categoria -> {m1:sum,m2:sum} }
  registrosFiltrados.forEach(rec=>{
    const a = rec.almacen;
    const c = rec.categoria || '(SIN CATEGORIA)';
    if (!storesMap[a]) storesMap[a] = {};
    if (!storesMap[a][c]) storesMap[a][c] = { m1:0, m2:0 };
    if (rec.monthKey === m1) storesMap[a][c].m1 += rec.value;
    if (rec.monthKey === m2) storesMap[a][c].m2 += rec.value;
  });

  // Construir estructura para render y export
  const resultadoPorTienda = {}; // almacen -> array rows [{categoria,m1,m2,diff,pct}]
  Object.keys(storesMap).sort((a,b)=> a.localeCompare(b,'es')).forEach(alm=>{
    const catObj = storesMap[alm];
    const rows = Object.keys(catObj).sort((x,y)=> x.localeCompare(y,'es')).map(cat=>{
      const m1v = Number(catObj[cat].m1) || 0;
      const m2v = Number(catObj[cat].m2) || 0;
      const diff = m2v - m1v;
      const pct = (m1v === 0) ? null : (diff / m1v) * 100;
      return { categoria: cat, mes1: m1v, mes2: m2v, diferencia: diff, porcentaje: pct };
    });
    resultadoPorTienda[alm] = rows;
  });

  // renderizar HTML
  renderResultado(resultadoPorTienda, m1, m2);
  // guardar para export
  window._resultadoComparativo = { resultadoPorTienda, mes1: m1, mes2: m2 };
  exportBtn.disabled = false;
  mensajes.innerText = `Análisis listo. Tiendas: ${Object.keys(resultadoPorTienda).length}.`;
}

/* render HTML tables (one per tienda) */
function renderResultado(data, key1, key2){
  resultadoDiv.innerHTML = '';
  const label1 = getLabel(key1);
  const label2 = getLabel(key2);
  const storeNames = Object.keys(data);
  if (!storeNames.length){
    resultadoDiv.innerHTML = '<div class="note">No se encontraron registros para los meses seleccionados y/o para INNOVACION MOVIL.</div>';
    return;
  }
  storeNames.forEach(alm => {
    const rows = data[alm];
    let html = `<div class="center-table"><table><caption>${escapeHtml(alm)} — Comparativo ${label1} vs ${label2}</caption>`;
    html += `<thead><tr><th>Categoría</th><th>${label1} (Suma S)</th><th>${label2} (Suma S)</th><th>Diferencia (${label2}-${label1})</th><th>% Diferencia</th></tr></thead><tbody>`;
    rows.forEach(r=>{
      const pctText = (r.porcentaje === null) ? 'N/A' : (r.porcentaje.toFixed(2) + '%');
      html += `<tr>
        <td>${escapeHtml(r.categoria)}</td>
        <td>${Number(r.mes1).toFixed(2)}</td>
        <td>${Number(r.mes2).toFixed(2)}</td>
        <td>${Number(r.diferencia).toFixed(2)}</td>
        <td>${pctText}</td>
      </tr>`;
    });
    // fila total
    const total1 = rows.reduce((s,x)=>s + Number(x.mes1), 0);
    const total2 = rows.reduce((s,x)=>s + Number(x.mes2), 0);
    const totalDiff = total2 - total1;
    const totalPct = total1 === 0 ? null : (totalDiff / total1) * 100;
    const totalPctText = totalPct === null ? 'N/A' : (totalPct.toFixed(2) + '%');
    html += `<tr style="font-weight:700; background:#eee;">
      <td>Total</td><td>${total1.toFixed(2)}</td><td>${total2.toFixed(2)}</td><td>${totalDiff.toFixed(2)}</td><td>${totalPctText}</td>
    </tr>`;
    html += `</tbody></table></div>`;
    resultadoDiv.innerHTML += html;
  });
}

/* exportar: una hoja por tienda con la tabla */
function handleExport(){
  const data = window._resultadoComparativo;
  if (!data) { alert('No hay datos para exportar.'); return; }
  const { resultadoPorTienda, mes1, mes2 } = data;
  const wb = XLSX.utils.book_new();
  Object.keys(resultadoPorTienda).forEach(alm => {
    const rows = resultadoPorTienda[alm];
    const header = ["Categoría", `Ventas ${getLabel(mes1)}`, `Ventas ${getLabel(mes2)}`, "Diferencia", "% Diferencia"];
    const aoa = [ [ `${alm} — Comparativo ${getLabel(mes1)} vs ${getLabel(mes2)}` ], header ];
    rows.forEach(r=>{
      const pct = (r.porcentaje === null) ? "" : r.porcentaje;
      aoa.push([ r.categoria, r.mes1, r.mes2, r.diferencia, pct ]);
    });
    // totales
    const total1 = rows.reduce((s,x)=>s + Number(x.mes1), 0);
    const total2 = rows.reduce((s,x)=>s + Number(x.mes2), 0);
    const totalDiff = total2 - total1;
    const totalPct = total1 === 0 ? "" : (totalDiff / total1) * 100;
    aoa.push([]);
    aoa.push(["Total", total1, total2, totalDiff, totalPct]);
    const ws = XLSX.utils.aoa_to_sheet(aoa);
    // sheet name max 31 chars
    const sheetName = (alm.substring(0, 31) || 'Tienda').replace(/[\\/?*[\]:]/g,' ');
    XLSX.utils.book_append_sheet(wb, ws, sheetName);
  });
  const fname = `Comparativo_${mes1.replace('-','')}_vs_${mes2.replace('-','')}.xlsx`;
  XLSX.writeFile(wb, fname);
}

/* helpers */
function getLabel(key){
  const parts = key.split('-'); // YYYY-MM
  if (parts.length !== 2) return key;
  const y = Number(parts[0]), m = Number(parts[1]) - 1;
  const months = ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"];
  return `${months[m]} ${y}`;
}
function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

</script>
</body>
</html>
