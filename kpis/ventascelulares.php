<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventas Celulares por Modelo — TECNOLOGIA MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>
<style>
.controls{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
input[type=file],input[type=text]{padding:8px;border-radius:6px;border:1px solid #ccc}
button{padding:8px 14px;background:#2f6fa6;color:white;border:none;border-radius:6px;cursor:pointer}
.list{margin-top:20px;background:white;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1);overflow:hidden}
.item{padding:10px 14px;border-bottom:1px solid #eee;cursor:pointer}
.item:hover{background:#f0f6ff}
.brand{font-weight:bold;color:#00b4d8}
.model{font-size:14px}

/* Badge de tipo producto */
.tipo-badge{
  display:inline-block;font-size:11px;font-weight:600;padding:2px 7px;
  border-radius:10px;margin-left:6px;vertical-align:middle;
}
.tipo-badge.smartphone{background:#e0f7fa;color:#00838f}
.tipo-badge.basico{background:#ede7f6;color:#5c6bc0}

.panel{position:fixed;top:0;right:0;width:420px;height:100%;background:white;
       box-shadow:-4px 0 10px rgba(0,0,0,.15);padding:20px;overflow:auto;display:none}
.store{padding:8px;border-bottom:1px solid #ddd;cursor:pointer}
.store:hover{background:#eef4ff}
.store.total-row{background:#e3f2fd;font-weight:bold;color:#1976d2;cursor:default}
.product-detail{margin-top:10px;background:#f5f7fa;padding:10px;border-radius:6px}

/* Filtro tipo */
.filtro-tipo{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.filtro-btn{padding:6px 14px;border-radius:20px;border:2px solid #ccc;background:white;
            cursor:pointer;font-size:13px;font-weight:500;transition:all .2s}
.filtro-btn.activo{border-color:#00838f;background:#e0f7fa;color:#00838f}
.filtro-btn.activo.basico{border-color:#5c6bc0;background:#ede7f6;color:#5c6bc0}
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
        <li><a href="index.php">Home</a></li>
        <li><a href="celularesstock.php">📦 Distribución por Modelo</a></li>
        <li><a href="analisis_celulares_ventas_existencias.php">📊 Análisis Ventas vs Existencias</a></li>
      </ul>
    </div>
  </nav>
</header>

<div class="container">
  <h1>🛍️ Ventas Celulares por Modelo — TECNOLOGIA MOVIL</h1>

  <div class="controls">
    <div class="file-upload">
      <input id="inputFile" type="file" accept=".xlsx,.xls" hidden/>
      <button class="boton" id="fileButton" type="button">
        <div class="contenedorCarpeta">
          <div class="folder folder_one"></div><div class="folder folder_two"></div>
          <div class="folder folder_three"></div><div class="folder folder_four"></div>
        </div>
        <div class="active_line"></div>
        <span class="text">Análisis de Ventas</span>
      </button>
    </div>
    <input type="text" id="search" placeholder="Buscar modelo...">
  </div>

  <!-- Filtros por tipo de producto -->
  <div class="filtro-tipo">
    <button class="filtro-btn activo" data-tipo="TODOS" id="btn-todos">📋 Todos</button>
    <button class="filtro-btn" data-tipo="SMARTPHONE" id="btn-smartphone">📱 Smartphone</button>
    <button class="filtro-btn basico" data-tipo="EQUIPO_BASICO" id="btn-basico">📟 Equipo Básico</button>
  </div>

  <div id="list" class="list"></div>
  <div id="loader" class="loader-container" style="display:none;">
    <div class="cloud front"><span class="left-front"></span><span class="right-front"></span></div>
    <span class="sun sunshine"></span><span class="sun"></span>
    <div class="cloud back"><span class="left-back"></span><span class="right-back"></span></div>
  </div>
</div>

<div id="panel" class="panel" style="display:none">
  <h3 id="panelTitle"></h3>
  <button id="download">📥 Descargar Reporte de Ventas</button>
  <div id="stores"></div>
  <div id="detail"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded", ()=>{
  document.getElementById("fileButton").addEventListener("click", ()=>
    document.getElementById("inputFile").click()
  );
});

let data = [];
let marcaActiva  = null;
let modeloActivo = null;
let tipoActivo   = null; // se guarda al abrir el panel
let rangoVentas  = "";
let filtroTipo   = "TODOS"; // TODOS | SMARTPHONE | EQUIPO_BASICO

const loader = document.getElementById("loader");

/* ── Filtros de tipo ── */
document.querySelectorAll('.filtro-btn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    document.querySelectorAll('.filtro-btn').forEach(b=> b.classList.remove('activo'));
    btn.classList.add('activo');
    filtroTipo = btn.dataset.tipo;
    renderModels();
  });
});

document.getElementById("inputFile").onchange = e=> loadExcel(e.target.files[0]);
document.getElementById("search").oninput     = renderModels;

/* ─────────────────────────────────────────────────────────────
   FILTRO DE VENTAS — misma lógica que el archivo de análisis:
     col B (índice 1) = "TECNOLOGIA MOVIL"
     col C (índice 2) = "SMARTPHONE"    → col D debe ser PROPIOS o BATYCELL
     col C (índice 2) = "EQUIPO BASICO" → col D puede venir vacía
   ───────────────────────────────────────────────────────────── */
const TIPOS_VALIDOS = ["PROPIOS", "BATYCELL"];

function parseFechaFixed(txt){
  if(!txt) return null;
  const partes = String(txt).trim().split(/\s+/);
  if(partes.length < 4) return null;
  const [mesStr, diaStr, anioStr, horaStr] = partes;
  const meses = {Jan:0,Feb:1,Mar:2,Apr:3,May:4,Jun:5,Jul:6,Aug:7,Sep:8,Oct:9,Nov:10,Dec:11};
  const mes = meses[mesStr], dia = parseInt(diaStr), anio = parseInt(anioStr);
  const match = horaStr.match(/(\d+):(\d+)(AM|PM)/);
  if(!match) return new Date(anio, mes, dia);
  let h = parseInt(match[1]);
  const min = parseInt(match[2]), ampm = match[3];
  if(ampm==="PM" && h<12) h+=12;
  if(ampm==="AM" && h===12) h=0;
  return new Date(anio, mes, dia, h, min);
}

function formatFecha(d){
  if(!d) return "";
  return d.toLocaleDateString("es-MX", {day:"2-digit", month:"short", year:"numeric"});
}

function loadExcel(file){
  loader.style.display = "flex";
  const reader = new FileReader();
  reader.onload = e =>{
    const wb   = XLSX.read(new Uint8Array(e.target.result), {type:"array"});
    const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], {header:1, defval:""});
    data = [];
    for(let i=1; i<rows.length; i++){
      const r    = rows[i];
      const colB = String(r[1]||"").trim().toUpperCase();
      const colC = String(r[2]||"").trim().toUpperCase();
      const colD = String(r[3]||"").trim().toUpperCase();

      if(colB !== "TECNOLOGIA MOVIL") continue;

      let tipoProducto = null;
      if(colC === "SMARTPHONE"){
        if(!TIPOS_VALIDOS.includes(colD)) continue;
        tipoProducto = "SMARTPHONE";
      } else if(colC === "EQUIPO BASICO"){
        tipoProducto = "EQUIPO_BASICO"; // col D puede venir vacía
      } else {
        continue;
      }

      data.push({
        almacen:      r[0],
        prod:         r[11],
        cantidad:     Number(r[14]) || 0,
        marca:        r[20],
        modelo:       r[21],
        fecha:        parseFechaFixed(r[7]),
        tipoProducto
      });
    }
    renderModels();
    loader.style.display = "none";
  };
  reader.readAsArrayBuffer(file);
}

/* ── Renderizar lista de modelos ── */
function renderModels(){
  const list = document.getElementById("list");
  list.innerHTML = "";
  const term = document.getElementById("search").value.toLowerCase();

  const map = {};
  data.forEach(p=>{
    // Filtro por tipo activo
    if(filtroTipo !== "TODOS" && p.tipoProducto !== filtroTipo) return;
    // Filtro por búsqueda de texto
    if(!(p.marca+" "+p.modelo).toLowerCase().includes(term)) return;

    // Clave única por marca + modelo + tipo (para no mezclar)
    const key = `${p.marca}||${p.modelo}||${p.tipoProducto}`;
    if(!map[key]) map[key] = {marca:p.marca, modelo:p.modelo, tipo:p.tipoProducto, total:0};
    map[key].total += p.cantidad;
  });

  Object.values(map)
    .sort((a,b)=> b.total - a.total)
    .forEach(({ marca, modelo, tipo, total })=>{
      const badgeClass = tipo === 'SMARTPHONE' ? 'smartphone' : 'basico';
      const badgeLabel = tipo === 'SMARTPHONE' ? '📱 Smartphone' : '📟 Básico';

      const d = document.createElement("div");
      d.className = "item";
      d.innerHTML = `
        <div class="brand">
          ${marca}
          <span class="tipo-badge ${badgeClass}">${badgeLabel}</span>
        </div>
        <div class="model">${modelo} — ${total} uds</div>
      `;
      d.onclick = ()=> openModel(marca, modelo, tipo);
      list.appendChild(d);
    });
}

/* ── Abrir panel de detalle ── */
function openModel(marca, modelo, tipo){
  marcaActiva  = marca;
  modeloActivo = modelo;
  tipoActivo   = tipo;

  const panel = document.getElementById("panel");
  panel.style.display = "block";
  document.getElementById("stores").innerHTML = "";
  document.getElementById("detail").innerHTML = "";

  const filtered = data.filter(p=>
    p.marca === marca && p.modelo === modelo && p.tipoProducto === tipo
  );

  const fechas = filtered.map(p=>p.fecha).filter(f=>f && !isNaN(f));
  if(fechas.length){
    const minF = new Date(Math.min(...fechas));
    const maxF = new Date(Math.max(...fechas));
    rangoVentas = `${formatFecha(minF)} al ${formatFecha(maxF)}`;
  } else {
    rangoVentas = "";
  }

  const tipoLabel = tipo === 'SMARTPHONE' ? '📱 Smartphone' : '📟 Equipo Básico';
  document.getElementById("panelTitle").textContent =
    `${marca} ${modelo} · ${tipoLabel}${rangoVentas ? " ("+rangoVentas+")" : ""}`;

  const map = {};
  filtered.forEach(p=>{
    if(!map[p.almacen]) map[p.almacen] = [];
    map[p.almacen].push(p);
  });

  const ordenado = Object.entries(map).sort((a,b)=>
    b[1].reduce((s,p)=>s+p.cantidad,0) - a[1].reduce((s,p)=>s+p.cantidad,0)
  );

  let granTotal = 0;
  const stores  = document.getElementById("stores");
  ordenado.forEach(([alm, prods])=>{
    const total = prods.reduce((s,p)=>s+p.cantidad, 0);
    granTotal  += total;
    const d = document.createElement("div");
    d.className = "store";
    d.innerHTML = `${alm} — ${total}`;
    d.onclick   = ()=> showDetail(alm, prods);
    stores.appendChild(d);
  });

  const totalDiv = document.createElement("div");
  totalDiv.className = "store total-row";
  totalDiv.innerHTML = `TOTAL — ${granTotal}`;
  stores.appendChild(totalDiv);
}

/* ── Detalle por almacén ── */
function showDetail(alm, prods){
  const detail = document.getElementById("detail");
  detail.innerHTML = `<h4>${alm}</h4>`;
  const map = {};
  prods.forEach(p=>{ if(!map[p.prod]) map[p.prod]=0; map[p.prod]+=p.cantidad; });
  Object.entries(map).sort((a,b)=>b[1]-a[1]).forEach(([n,c])=>{
    detail.innerHTML +=
      `<div style="padding:4px 0;border-bottom:1px solid #ddd">${n} — ${c}</div>`;
  });
}

/* ── Descargar reporte Excel ── */
document.getElementById("download").onclick = async ()=>{
  const filtered = data.filter(p=>
    p.marca === marcaActiva && p.modelo === modeloActivo && p.tipoProducto === tipoActivo
  );
  const wb = new ExcelJS.Workbook();

  const resumen = wb.addWorksheet("General");
  const tipoLabel = tipoActivo === 'SMARTPHONE' ? 'Smartphone' : 'Equipo Básico';
  resumen.addRow([`Modelo: ${marcaActiva} ${modeloActivo} (${tipoLabel})`]);
  if(rangoVentas) resumen.addRow([`Periodo de ventas: ${rangoVentas}`]);
  resumen.addRow([]);
  resumen.addRow(["Almacén","Total"]);

  const map = {};
  filtered.forEach(p=>{ if(!map[p.almacen]) map[p.almacen]=0; map[p.almacen]+=p.cantidad; });

  const ordenado = Object.entries(map).sort((a,b)=>b[1]-a[1]);
  let granTotal = 0;
  ordenado.forEach(([a,t])=>{ resumen.addRow([a,t]); granTotal+=t; });
  resumen.addRow(["TOTAL", granTotal]);

  const porAlm = {};
  filtered.forEach(p=>{
    if(!porAlm[p.almacen]) porAlm[p.almacen] = {};
    if(!porAlm[p.almacen][p.prod]) porAlm[p.almacen][p.prod] = 0;
    porAlm[p.almacen][p.prod] += p.cantidad;
  });
  Object.entries(porAlm).forEach(([alm, prods])=>{
    const sh = wb.addWorksheet(alm.substring(0,31));
    sh.addRow(["Producto","Cantidad"]);
    let totalAlm = 0;
    Object.entries(prods).sort((a,b)=>b[1]-a[1]).forEach(([n,c])=>{
      sh.addRow([n,c]); totalAlm+=c;
    });
    sh.addRow(["TOTAL", totalAlm]);
  });

  const buf = await wb.xlsx.writeBuffer();
  const a   = document.createElement("a");
  a.href    = URL.createObjectURL(new Blob([buf]));
  a.download= `Ventas_${marcaActiva}_${modeloActivo}.xlsx`;
  a.click();
};

document.getElementById('menu-check').addEventListener('change', function(){
  const m = document.getElementById('nav-menu');
  m.style.opacity       = this.checked ? '1' : '0';
  m.style.visibility    = this.checked ? 'visible' : 'hidden';
  m.style.pointerEvents = this.checked ? 'auto' : 'none';
});
</script>
</body>
</html>