<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventas Celulares por Modelo — TECNOLOGIA MOVIL</title>
<link rel="stylesheet" href="../styles.css">
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>

<style>

  #inputFile{
    position:absolute;width:1px;height:1px;padding:0;margin:-1px;
    overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;
  }

  .controls{
    display:flex;flex-wrap:wrap;gap:var(--space-md);align-items:center;
    margin-bottom:var(--space-md);
  }
  .controls .btn{margin:0;}
  .search-input{
    flex:1;min-width:220px;
    padding:12px 16px;
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    background:var(--surface-container-low);
    color:var(--on-surface);
    font-size:14px;font-family:inherit;
    transition:border-color .15s ease;
  }
  .search-input:focus{outline:none;border-color:var(--primary);}

  /* Filtros por tipo de producto */
  .filtro-tipo{
    display:flex;flex-wrap:wrap;gap:var(--space-sm);
    margin-bottom:var(--space-lg);
  }
  .filtro-btn{
    display:inline-flex;align-items:center;gap:6px;
    padding:8px 16px;
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-full,999px);
    background:var(--surface-container-low);
    color:var(--on-surface-variant);
    font-size:13px;font-weight:600;font-family:inherit;
    cursor:pointer;transition:all .15s ease;
  }
  .filtro-btn .material-symbols-outlined{font-size:18px;}
  .filtro-btn:hover{border-color:var(--primary);color:var(--primary);}
  .filtro-btn.activo{
    background:var(--primary);border-color:var(--primary);
    color:var(--on-primary,#fff);
  }
  .filtro-btn.basico.activo{
    background:#5C6BC0;border-color:#5C6BC0;color:#fff;
  }

  /* Lista de modelos */
  .model-list{
    display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
    gap:var(--space-md);margin-bottom:var(--space-lg);
  }
  .model-item{
    padding:var(--space-md);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    background:var(--surface-container-low);
    cursor:pointer;transition:all .15s ease;
  }
  .model-item:hover{
    border-color:var(--primary);
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(0,0,0,0.06);
  }
  .model-item .brand{
    display:flex;align-items:center;justify-content:space-between;gap:8px;
    font-weight:700;font-size:14px;color:var(--on-surface);margin-bottom:4px;
  }
  .model-item .model-name{
    font-size:13px;color:var(--on-surface-variant);
  }
  .tipo-badge{
    display:inline-flex;align-items:center;gap:4px;
    font-size:11px;font-weight:600;padding:3px 8px;border-radius:var(--radius-full,999px);
  }
  .tipo-badge .material-symbols-outlined{font-size:14px;}
  .tipo-badge.smartphone{background:rgba(0,131,143,0.12);color:#00838F;}
  .tipo-badge.basico{background:rgba(92,107,192,0.12);color:#5C6BC0;}

  .empty-state{
    padding:var(--space-lg);text-align:center;color:var(--on-surface-variant);
    border:1px dashed var(--outline-variant);border-radius:var(--radius-lg);
  }

  .loader{
    display:none;
    align-items:center;justify-content:center;gap:var(--space-md);
    padding:var(--space-lg);margin-bottom:var(--space-lg);
    background:var(--surface-container-low);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
  }
  .loader.active{display:flex;}
  .spinner{
    width:22px;height:22px;border-radius:50%;
    border:3px solid var(--outline-variant);
    border-top-color:var(--primary);
    animation:spin .8s linear infinite;
  }
  @keyframes spin{to{transform:rotate(360deg);}}
  .loader-text{font-size:14px;font-weight:600;color:var(--on-surface-variant);}

  /* Panel de detalle */
  .detail-panel{display:none;}
  .detail-panel.open{display:block;}
  .store-grid{
    display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
    gap:var(--space-sm);margin:var(--space-md) 0;
  }
  .store-chip{
    padding:10px 14px;border-radius:var(--radius-lg);
    background:var(--surface-container-low);
    border:1px solid var(--outline-variant);
    font-size:13px;font-weight:600;cursor:pointer;
    transition:all .15s ease;
  }
  .store-chip:hover{border-color:var(--primary);color:var(--primary);}
  .store-chip.total-row{
    background:rgba(0,131,143,0.10);border-color:var(--primary);
    color:var(--primary);cursor:default;
  }
  .detail-list{margin-top:var(--space-md);}
  .detail-row{
    display:flex;justify-content:space-between;
    padding:8px 0;border-bottom:1px solid var(--outline-variant);
    font-size:13px;
  }

  .sidebar-brand-logo{display:flex;align-items:center;gap:10px;}
  .sidebar-brand-logo img{border-radius:6px;}
</style>
</head>

<body>


<aside class="sidebar" id="sidebar">
  <div class="sidebar-head">
    <div class="sidebar-brand-logo">
      <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="32" height="32">
      <div>
        <p class="sidebar-brand text-headline-sm">Central Cell</p>
        <p class="sidebar-sub text-label-sm">Panel de Análisis de Celulares</p>
      </div>
    </div>
    <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Cerrar menú">
      <span class="material-symbols-outlined">close</span>
    </button>
  </div>

  <nav class="sidebar-nav">
    <p class="sidebar-label">Navegación</p>
    <a href="../garantias/validador/validador.php" class="sidebar-link">
      <span class="material-symbols-outlined">home</span>
      Home
    </a>
    <a href="modulos.html" class="sidebar-link">
      <span class="material-symbols-outlined">bar_chart</span>
      Panel de Herramientas
    </a>

    <a href="analisis_celulares_ventas_existencias.php" class="sidebar-link">
      <span class="material-symbols-outlined">swap_horiz</span>
      Ventas vs Existencias
    </a>

    <a href="celularesstock.php" class="sidebar-link">
      <span class="material-symbols-outlined">inventory_2</span>
      Distribución por Modelo
    </a>
    <a href="ventascelulares.php" class="sidebar-link active">
      <span class="material-symbols-outlined">storefront</span>
      Ventas por Modelo
    </a>
  </nav>

  <div class="sidebar-foot">
    <p class="text-label-sm" style="color:var(--outline)">Innovación Móvil</p>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===================== MAIN ===================== -->
<div class="main">

  <header class="topheader">
    <div class="topheader-left">
      <button class="menu-toggle" id="menuToggle" type="button" aria-label="Abrir menú">
        <span class="material-symbols-outlined">menu</span>
      </button>
      <h2 class="text-headline-sm" style="margin:0">Ventas Celulares por Modelo</h2>
    </div>
  </header>

  <div class="container">
    <div class="lesson">
      <div class="lesson-body">

        <span class="eyebrow">Reportes</span>
        <h1 class="text-headline-lg" style="margin:6px 0 0">Ventas Celulares por Modelo — TECNOLOGIA MOVIL</h1>
        <div class="lesson-meta">
          <span><span class="material-symbols-outlined" style="font-size:16px">storefront</span> Tecnología Móvil</span>
        </div>

        <div class="intro-panel">
          <div class="icon-badge"><span class="material-symbols-outlined">insights</span></div>
          <div>
            <h3 style="margin:0">Explora las ventas por modelo</h3>
            <p>Carga tu archivo de ventas, filtra por tipo de producto y da clic en un modelo para ver el detalle de ventas por almacén.</p>
          </div>
        </div>

        <!-- Paso 1: Carga de archivo -->
        <section class="step-section">
          <div class="step-head">
            <div class="step-num">1</div>
            <h3 class="step-title text-headline-sm">Carga tu archivo de ventas</h3>
          </div>

          <div class="controls">
            <input type="file" id="inputFile" accept=".xlsx,.xls">
            <button class="btn btn-outline" id="fileButton" type="button">
              <span class="material-symbols-outlined">upload_file</span>
              Seleccionar Archivo de Ventas
            </button>
            <input type="text" id="search" class="search-input" placeholder="Buscar modelo...">
          </div>

          <div class="loader" id="loader">
            <div class="spinner"></div>
            <div class="loader-text">Procesando archivo...</div>
          </div>
        </section>

        <!-- Paso 2: Filtro y listado -->
        <section class="step-section">
          <div class="step-head">
            <div class="step-num">2</div>
            <h3 class="step-title text-headline-sm">Filtra y elige un modelo</h3>
          </div>

          <div class="filtro-tipo">
            <button class="filtro-btn activo" data-tipo="TODOS" id="btn-todos">
              <span class="material-symbols-outlined">apps</span> Todos
            </button>
            <button class="filtro-btn" data-tipo="SMARTPHONE" id="btn-smartphone">
              <span class="material-symbols-outlined">smartphone</span> Smartphone
            </button>
            <button class="filtro-btn basico" data-tipo="EQUIPO_BASICO" id="btn-basico">
              <span class="material-symbols-outlined">dialpad</span> Equipo Básico
            </button>
          </div>

          <div id="list" class="model-list"></div>
        </section>

        <!-- Panel de detalle -->
        <section class="step-section detail-panel" id="panel">
          <div class="step-head">
            <div class="step-num"><span class="material-symbols-outlined" style="font-size:20px">storefront</span></div>
            <h3 class="step-title text-headline-sm" id="panelTitle"></h3>
          </div>

          <button class="btn btn-primary" id="download" type="button">
            <span class="material-symbols-outlined">download</span>
            Descargar Reporte de Ventas
          </button>

          <div class="store-grid" id="stores"></div>
          <div class="detail-list" id="detail"></div>
        </section>

      </div>
    </div>
  </div>

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
  loader.classList.add('active');
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
    loader.classList.remove('active');
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

  const items = Object.values(map).sort((a,b)=> b.total - a.total);

  if(!items.length){
    list.innerHTML = `<div class="empty-state">
      <span class="material-symbols-outlined" style="font-size:28px">search_off</span>
      <p style="margin:8px 0 0">No hay modelos que coincidan. Carga un archivo o ajusta la búsqueda.</p>
    </div>`;
    return;
  }

  items.forEach(({ marca, modelo, tipo, total })=>{
    const badgeClass = tipo === 'SMARTPHONE' ? 'smartphone' : 'basico';
    const badgeIcon  = tipo === 'SMARTPHONE' ? 'smartphone' : 'dialpad';
    const badgeLabel = tipo === 'SMARTPHONE' ? 'Smartphone' : 'Básico';

    const d = document.createElement("div");
    d.className = "model-item";
    d.innerHTML = `
      <div class="brand">
        ${marca}
        <span class="tipo-badge ${badgeClass}">
          <span class="material-symbols-outlined">${badgeIcon}</span>${badgeLabel}
        </span>
      </div>
      <div class="model-name">${modelo} — ${total} uds</div>
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
  panel.classList.add('open');
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

  const tipoLabel = tipo === 'SMARTPHONE' ? 'Smartphone' : 'Equipo Básico';
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
    d.className = "store-chip";
    d.innerHTML = `${alm} — ${total}`;
    d.onclick   = ()=> showDetail(alm, prods);
    stores.appendChild(d);
  });

  const totalDiv = document.createElement("div");
  totalDiv.className = "store-chip total-row";
  totalDiv.innerHTML = `TOTAL — ${granTotal}`;
  stores.appendChild(totalDiv);

  panel.scrollIntoView({behavior:'smooth', block:'start'});
}

/* ── Detalle por almacén ── */
function showDetail(alm, prods){
  const detail = document.getElementById("detail");
  detail.innerHTML = `<h4 style="margin:0 0 8px">${alm}</h4>`;
  const map = {};
  prods.forEach(p=>{ if(!map[p.prod]) map[p.prod]=0; map[p.prod]+=p.cantidad; });
  Object.entries(map).sort((a,b)=>b[1]-a[1]).forEach(([n,c])=>{
    detail.innerHTML += `<div class="detail-row"><span>${n}</span><span>${c}</span></div>`;
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
</script>

<script>
  // Control del sidebar en móvil (mismo patrón que el resto del panel)
  const sidebar        = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const menuToggle      = document.getElementById('menuToggle');
  const sidebarClose    = document.getElementById('sidebarClose');

  function abrirSidebar() {
    sidebar.classList.add('open');
    sidebarOverlay.classList.add('show');
  }
  function cerrarSidebar() {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('show');
  }

  menuToggle.addEventListener('click', abrirSidebar);
  sidebarClose.addEventListener('click', cerrarSidebar);
  sidebarOverlay.addEventListener('click', cerrarSidebar);
</script>

</body>
</html>