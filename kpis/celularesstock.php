<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Distribución Celulares — TECNOLOGIA MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<link rel="stylesheet" href="../styles.css">
<style>
  
  .sidebar-brand-logo{display:flex;align-items:center;gap:10px;}
  .sidebar-brand-logo img{border-radius:6px;}

  /* ── Controles (subir archivo + buscar) ── */
  .controls{display:flex;gap:var(--space-md);align-items:center;flex-wrap:wrap;margin-bottom:var(--space-md);}
  #inputFile{display:none;}
  .file-button{
    display:inline-flex;align-items:center;gap:8px;
    padding:10px 18px;border-radius:var(--radius-lg);
    font-size:14px;font-weight:600;letter-spacing:0.02em;
    background:var(--surface-container-high);color:var(--on-surface);
    border:1px solid var(--outline-variant);
    transition:border-color .15s ease, transform .15s ease;
  }
  .file-button:hover{border-color:var(--primary);transform:translateY(-1px);}
  .file-button .material-symbols-outlined{font-size:18px;}

  #search{
    flex:1;min-width:220px;padding:10px 14px;
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    font-family:'Inter',sans-serif;font-size:14px;
    background:var(--surface-container-lowest);color:var(--on-surface);
  }
  #search:focus{outline:2px solid var(--primary);outline-offset:1px;}

  /* ── Filtros por tipo ── */
  .filtro-tipo{display:flex;gap:10px;margin-bottom:var(--space-lg);flex-wrap:wrap;}
  .filtro-btn{
    display:inline-flex;align-items:center;gap:6px;
    padding:8px 16px;border-radius:var(--radius-full);
    border:1px solid var(--outline-variant);background:var(--surface-container-lowest);
    color:var(--on-surface-variant);font-size:13px;font-weight:600;
    cursor:pointer;transition:all .15s ease;
  }
  .filtro-btn .material-symbols-outlined{font-size:16px;}
  .filtro-btn.activo{border-color:var(--primary);background:rgba(29,78,216,0.08);color:var(--primary);}
  .filtro-btn.activo.basico{border-color:var(--tertiary);background:rgba(29,78,216,0.08);color:var(--tertiary);}

  /* ── Lista de resultados ── */
  .list{
    background:var(--surface-container-lowest);
    border:1px solid rgba(196,197,215,0.4);
    border-radius:var(--radius-xl);
    overflow:hidden;
    box-shadow:0 1px 2px rgba(17,28,45,0.04);
  }
  .item{padding:var(--space-md) var(--space-lg);border-bottom:1px solid var(--outline-variant);cursor:pointer;transition:background .15s ease;}
  .item:last-child{border-bottom:none;}
  .item:hover{background:var(--surface-container-low);}
  .brand{font-weight:700;color:var(--primary);display:flex;align-items:center;gap:8px;font-size:14px;}
  .model{font-size:13px;color:var(--on-surface-variant);margin-top:2px;}

  .tipo-badge{
    display:inline-flex;align-items:center;gap:4px;
    font-size:11px;font-weight:700;padding:3px 10px;border-radius:var(--radius-full);
  }
  .tipo-badge .material-symbols-outlined{font-size:13px;}
  .tipo-badge.smartphone{background:rgba(29,78,216,0.1);color:var(--primary);}
  .tipo-badge.basico{background:rgba(29,78,216,0.08);color:var(--tertiary);}

  /* ── Loader ── */
  .loader-container{
    display:none;align-items:center;justify-content:center;padding:var(--space-2xl) 0;
  }
  .spinner-ring{
    width:44px;height:44px;border-radius:50%;
    border:4px solid var(--surface-container-high);
    border-top-color:var(--primary);
    animation:girar .9s linear infinite;
  }
  @keyframes girar{to{transform:rotate(360deg);}}

  /* ── Panel lateral de detalle ── */
  .panel{
    position:fixed;top:0;right:0;width:420px;max-width:100%;height:100%;
    background:var(--surface-container-lowest);
    box-shadow:-8px 0 24px rgba(17,28,45,0.14);
    padding:var(--space-xl);overflow-y:auto;display:none;z-index:60;
    border-left:1px solid var(--outline-variant);
  }
  #panelTitle{margin:0 0 var(--space-md);font-size:16px;font-weight:700;color:var(--on-surface);}

  #downloadBtn{
    display:none;align-items:center;gap:8px;
    padding:10px 16px;border-radius:var(--radius-lg);
    font-size:13px;font-weight:600;
    background:var(--primary);color:var(--on-primary);
    margin-bottom:var(--space-md);
    transition:opacity .15s ease, transform .15s ease;
  }
  #downloadBtn:hover{opacity:0.9;transform:translateY(-1px);}
  #downloadBtn .material-symbols-outlined{font-size:16px;}

  .store{
    padding:10px 12px;border-bottom:1px solid var(--outline-variant);
    cursor:pointer;font-size:13px;color:var(--on-surface);border-radius:var(--radius-lg);
  }
  .store:hover{background:var(--surface-container-low);}
  .store.total-row{background:rgba(29,78,216,0.08);font-weight:700;color:var(--primary);cursor:default;}

  .product-detail{margin-top:var(--space-md);}
  .product-detail h4{margin:0 0 8px;font-size:13px;color:var(--on-surface-variant);}
  .product-detail-item{
    border-bottom:1px solid var(--outline-variant);padding:8px 4px;font-size:13px;color:var(--on-surface);
  }
</style>
</head>
<body>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

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
      <a href="celularesstock.php" class="sidebar-link active">
        <span class="material-symbols-outlined">inventory_2</span>
        Distribución por Modelo
      </a>
      <a href="ventascelulares.php" class="sidebar-link">
        <span class="material-symbols-outlined">storefront</span>
        Ventas por Modelo
      </a>
      
    </nav>

    <div class="sidebar-foot">
      <p class="text-label-sm" style="color:var(--outline)">Innovación Móvil</p>
    </div>
  </aside>

  <div class="main">
    <header class="topheader">
      <div class="topheader-left">
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Abrir menú">
          <span class="material-symbols-outlined">menu</span>
        </button>
        <h2 class="text-headline-sm" style="margin:0">Distribución Celulares</h2>
      </div>
    </header>

    <div class="container">

      <div class="lesson">
        <div class="lesson-body">

          <span class="eyebrow">Reportes</span>
          <h1 class="text-headline-lg" style="margin:6px 0 var(--space-lg);display:flex;align-items:center;gap:10px;">
            <span class="material-symbols-outlined" style="font-size:26px;color:var(--primary);">inventory_2</span>
            Distribución Celulares — TECNOLOGIA MOVIL
          </h1>

          <div class="controls">
            <input id="inputFile" type="file" accept=".xlsx,.xls"/>
            <button class="file-button" id="fileButton" type="button">
              <span class="material-symbols-outlined">upload_file</span>
              Seleccionar Existencias
            </button>
            <input type="text" id="search" placeholder="Buscar modelo...">
          </div>

          <!-- Filtros por tipo -->
          <div class="filtro-tipo">
            <button class="filtro-btn activo" data-tipo="TODOS"><span class="material-symbols-outlined">apps</span> Todos</button>
            <button class="filtro-btn" data-tipo="SMARTPHONE"><span class="material-symbols-outlined">smartphone</span> Smartphone</button>
            <button class="filtro-btn basico" data-tipo="EQUIPO_BASICO"><span class="material-symbols-outlined">dialpad</span> Equipo Básico</button>
          </div>

          <div id="list" class="list"></div>
          <div id="loader" class="loader-container">
            <div class="spinner-ring"></div>
          </div>

        </div>
      </div>

    </div>
  </div>

  <div id="panel" class="panel">
    <h3 id="panelTitle"></h3>
    <button id="downloadBtn" type="button"><span class="material-symbols-outlined">download</span> Descargar Distribución</button>
    <div id="stores"></div>
    <div id="productDetail" class="product-detail"></div>
  </div>

<script>
document.addEventListener("DOMContentLoaded", ()=>{
  document.getElementById("fileButton").addEventListener("click", ()=>
    document.getElementById("inputFile").click()
  );
});

const search        = document.getElementById("search");
const list          = document.getElementById("list");
const panel         = document.getElementById("panel");
const panelTitle    = document.getElementById("panelTitle");
const storesDiv     = document.getElementById("stores");
const productDetail = document.getElementById("productDetail");
const downloadBtn   = document.getElementById("downloadBtn");
const loader        = document.getElementById("loader");

let inventario   = [];
let marcaActiva  = null;
let modeloActivo = null;
let tipoActivo   = null;
let filtroTipo   = "TODOS"; // TODOS | SMARTPHONE | EQUIPO_BASICO

/* ─────────────────────────────────────────────────────────────
   CATEGORÍAS VÁLIDAS — columna N (índice 13)

   SMARTPHONE   → cadena exacta con sufijo >PROPIOS o >BATYCELL
   EQUIPO BASICO → cualquier cadena que empiece con el prefijo
                   (cubre sin sufijo, >PROPIOS, >BATYCELL, etc.)
   ───────────────────────────────────────────────────────────── */
const CATS_SMARTPHONE = [
  "TECNOLOGIA MOVIL>SMARTPHONE>PROPIOS",
  "TECNOLOGIA MOVIL>SMARTPHONE>BATYCELL"
];
const PREFIJO_BASICO = "TECNOLOGIA MOVIL>EQUIPO BASICO";

/* ── Filtros de tipo ── */
document.querySelectorAll('.filtro-btn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    document.querySelectorAll('.filtro-btn').forEach(b=> b.classList.remove('activo'));
    btn.classList.add('activo');
    filtroTipo = btn.dataset.tipo;
    renderList();
  });
});

document.getElementById("inputFile").addEventListener("change", e=>{
  if(e.target.files.length) leerExcel(e.target.files[0]);
});
search.addEventListener("input", renderList);

/* ── Leer Excel ── */
function leerExcel(file){
  loader.style.display = "flex";
  const reader = new FileReader();
  reader.onload = e =>{
    const wb   = XLSX.read(new Uint8Array(e.target.result), {type:"array"});
    const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], {header:1, defval:""});
    inventario = [];
    for(let i=1; i<rows.length; i++){
      const r      = rows[i];
      const nombre = String(r[13]).trim().toUpperCase();

      let tipoProducto = null;
      if(CATS_SMARTPHONE.includes(nombre)){
        tipoProducto = 'SMARTPHONE';
      } else if(nombre.startsWith(PREFIJO_BASICO)){
        tipoProducto = 'EQUIPO_BASICO';
      } else {
        continue;
      }

      inventario.push({
        almacen:      r[0],
        prodConcat:   r[2],
        marca:        r[5],
        modelo:       r[6],
        existencia:   Number(r[7]) || 0,
        barcode:      r[12],
        tipoProducto
      });
    }
    renderList();
    loader.style.display = "none";
  };
  reader.readAsArrayBuffer(file);
}

/* ── Renderizar lista ── */
function renderList(){
  list.innerHTML = "";
  const term = search.value.toLowerCase();
  const mapa = {};

  inventario.forEach(p=>{
    // Filtro por tipo activo
    if(filtroTipo !== "TODOS" && p.tipoProducto !== filtroTipo) return;
    // Filtro por búsqueda
    if(!(p.marca+" "+p.modelo).toLowerCase().includes(term)) return;

    // Clave única por marca + modelo + tipo
    const k = `${p.marca}||${p.modelo}||${p.tipoProducto}`;
    if(!mapa[k]) mapa[k] = {marca:p.marca, modelo:p.modelo, tipo:p.tipoProducto, total:0};
    mapa[k].total += p.existencia;
  });

  Object.values(mapa)
    .sort((a,b)=> b.total - a.total)
    .forEach(({ marca, modelo, tipo, total })=>{
      const badgeClass = tipo === 'SMARTPHONE' ? 'smartphone' : 'basico';
      const badgeIcon  = tipo === 'SMARTPHONE' ? 'smartphone' : 'dialpad';
      const badgeLabel = tipo === 'SMARTPHONE' ? 'Smartphone' : 'Básico';

      const div = document.createElement("div");
      div.className = "item";
      div.innerHTML = `
        <div class="brand">
          ${marca}
          <span class="tipo-badge ${badgeClass}"><span class="material-symbols-outlined">${badgeIcon}</span>${badgeLabel}</span>
        </div>
        <div class="model">${modelo} — ${total} pzas</div>
      `;
      div.onclick = ()=> abrirModelo(marca, modelo, tipo);
      list.appendChild(div);
    });
}

/* ── Abrir panel de detalle ── */
function abrirModelo(marca, modelo, tipo){
  marcaActiva  = marca;
  modeloActivo = modelo;
  tipoActivo   = tipo;

  panel.style.display = "block";
  const tipoLabel = tipo === 'SMARTPHONE'
    ? '<span class="material-symbols-outlined" style="font-size:16px;vertical-align:-3px;">smartphone</span> Smartphone'
    : '<span class="material-symbols-outlined" style="font-size:16px;vertical-align:-3px;">dialpad</span> Equipo Básico';
  panelTitle.innerHTML = `${marca} — ${modelo} · ${tipoLabel}`;
  downloadBtn.style.display = "inline-flex";
  storesDiv.innerHTML    = "";
  productDetail.innerHTML= "";

  const data = inventario.filter(p=>
    p.marca === marca && p.modelo === modelo &&
    p.tipoProducto === tipo && p.existencia > 0
  );

  const map = {};
  data.forEach(p=>{
    if(!map[p.almacen]) map[p.almacen] = [];
    map[p.almacen].push(p);
  });

  let granTotal = 0;
  Object.entries(map)
    .sort((a,b)=>
      b[1].reduce((s,p)=>s+p.existencia,0) - a[1].reduce((s,p)=>s+p.existencia,0)
    )
    .forEach(([almacen, productos])=>{
      const total = productos.reduce((s,p)=>s+p.existencia, 0);
      granTotal  += total;
      const div = document.createElement("div");
      div.className = "store";
      div.innerHTML = `<b>${almacen}</b> — ${total} pzas`;
      div.onclick   = ()=> mostrarProductos(almacen, productos);
      storesDiv.appendChild(div);
    });

  const totalDiv = document.createElement("div");
  totalDiv.className = "store total-row";
  totalDiv.innerHTML = `TOTAL — ${granTotal} pzas`;
  storesDiv.appendChild(totalDiv);
}

/* ── Detalle de productos por almacén ── */
function mostrarProductos(almacen, productos){
  productDetail.innerHTML = `<h4>${almacen}</h4>`;
  productos.sort((a,b)=>b.existencia-a.existencia).forEach(p=>{
    const d = document.createElement("div");
    d.className = "product-detail-item";
    d.innerHTML = `<b>${p.prodConcat}</b><br>Barcode: ${p.barcode}<br>Existencia: ${p.existencia}`;
    productDetail.appendChild(d);
  });
}

/* ── Descargar Excel ── */
downloadBtn.onclick = ()=>{
  if(!marcaActiva || !modeloActivo) return;
  const data = inventario.filter(p=>
    p.marca === marcaActiva && p.modelo === modeloActivo &&
    p.tipoProducto === tipoActivo && p.existencia > 0
  );
  if(!data.length){ alert("Sin existencias."); return; }

  const wb = XLSX.utils.book_new();

  // Hoja resumen
  const resumenMap = {};
  data.forEach(p=>{
    if(!resumenMap[p.almacen]) resumenMap[p.almacen] = 0;
    resumenMap[p.almacen] += p.existencia;
  });
  const resumenArr = Object.entries(resumenMap)
    .sort((a,b)=>b[1]-a[1])
    .map(([a,t])=>({Almacén:a, Existencias:t}));
  resumenArr.push({
    Almacén:"TOTAL",
    Existencias: resumenArr.reduce((s,r)=>s+r.Existencias, 0)
  });
  XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(resumenArr), "Resumen");

  // Hoja por almacén
  const porAlm = {};
  data.forEach(p=>{
    if(!porAlm[p.almacen]) porAlm[p.almacen] = [];
    porAlm[p.almacen].push({BarcodeId:p.barcode, Nombre:p.prodConcat, Cantidad:p.existencia});
  });
  Object.entries(porAlm)
    .sort((a,b)=>
      b[1].reduce((s,p)=>s+p.Cantidad,0) - a[1].reduce((s,p)=>s+p.Cantidad,0)
    )
    .forEach(([alm, prods])=>{
      prods.sort((a,b)=>b.Cantidad-a.Cantidad);
      prods.push({BarcodeId:"", Nombre:"TOTAL", Cantidad:prods.reduce((s,p)=>s+p.Cantidad,0)});
      XLSX.utils.book_append_sheet(
        wb, XLSX.utils.json_to_sheet(prods), alm.substring(0,31)
      );
    });

  XLSX.writeFile(wb,
    `Distribucion_${marcaActiva.replace(/[^a-z0-9]/gi,"_")}_${modeloActivo.replace(/[^a-z0-9]/gi,"_")}.xlsx`
  );
};

/* ── Control del sidebar en móvil ── */
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const menuToggle      = document.getElementById('menuToggle');
const sidebarClose    = document.getElementById('sidebarClose');

function abrirSidebar(){
  sidebar.classList.add('open');
  sidebarOverlay.classList.add('show');
}
function cerrarSidebar(){
  sidebar.classList.remove('open');
  sidebarOverlay.classList.remove('show');
}

menuToggle.addEventListener('click', abrirSidebar);
sidebarClose.addEventListener('click', cerrarSidebar);
sidebarOverlay.addEventListener('click', cerrarSidebar);
</script>
</body>
</html>