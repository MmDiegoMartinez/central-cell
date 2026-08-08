<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventario INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<link rel="stylesheet" href="../styles.css">

<style>
  /* ---- Ajustes puntuales que estilos.css no cubre (no se toca el archivo CSS) ---- */
  #inputFile{
    position:absolute;width:1px;height:1px;padding:0;margin:-1px;
    overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;
  }

  .search-input{
    width:100%;
    padding:10px 14px 10px 40px;
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    background:var(--surface-container-lowest) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 -960 960 960' height='20' width='20' fill='%23747686'%3E%3Cpath d='M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z'/%3E%3C/svg%3E") no-repeat 12px center;
    background-size:18px;
    font-family:'Inter',sans-serif;
    font-size:14px;color:var(--on-surface);
  }
  .search-input:focus{outline:2px solid var(--primary);outline-offset:1px;}

  .controls-row{display:flex;flex-wrap:wrap;gap:var(--space-md);align-items:stretch;margin-top:var(--space-md);}
  .controls-row .file-upload{flex:0 0 auto;}
  .controls-row .search-wrap{flex:1;min-width:220px;}

  .inventory-list{
    display:flex;flex-direction:column;gap:8px;
    margin-top:var(--space-lg);
    max-height:420px;overflow-y:auto;
    padding-right:4px;
  }
  .inventory-item{
    display:flex;align-items:center;justify-content:space-between;gap:var(--space-md);
    padding:12px var(--space-md);
    background:var(--surface-container-low);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    cursor:pointer;
    transition:background .15s ease, border-color .15s ease;
  }
  .inventory-item:hover{background:var(--surface-container-high);border-color:var(--primary);}
  .inventory-item .brand{
    font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--primary);
  }
  .inventory-item .model{font-size:14px;color:var(--on-surface);margin-top:2px;}
  .inventory-item .qty-badge{
    flex:0 0 auto;padding:4px 10px;border-radius:var(--radius-full);
    background:var(--secondary-container);color:var(--on-secondary-container);
    font-size:12px;font-weight:700;white-space:nowrap;
  }
  .empty-state{
    text-align:center;padding:var(--space-xl);color:var(--outline);font-size:14px;
  }

  /* Panel de detalle */
  .panel{
    display:none;
    margin-top:var(--space-2xl);
    padding-top:var(--space-2xl);
    border-top:1px solid var(--outline-variant);
  }
  .panel-title{margin:0 0 var(--space-md);}

  .store-list{display:flex;flex-direction:column;gap:8px;}
  .store{
    display:flex;align-items:center;justify-content:space-between;
    padding:10px var(--space-md);
    background:var(--surface-container-low);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    font-size:14px;cursor:pointer;
  }
  .store:hover{background:var(--surface-container-high);}
  .store.total-row{
    background:var(--primary-fixed);color:var(--on-primary-fixed);
    font-weight:700;cursor:default;border-color:transparent;
  }

  .product-detail{margin-top:var(--space-md);display:flex;flex-direction:column;gap:8px;}
  .product-row{
    background:var(--surface-container-lowest);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    padding:10px 14px;font-size:13px;color:var(--on-surface-variant);
  }
  .product-row b{color:var(--on-surface);font-size:14px;}

  /* Loader simple con spinner (reemplaza animación de nubes/sol) */
  .loader-container{
    align-items:center;justify-content:center;gap:var(--space-md);
    padding:var(--space-lg);margin-top:var(--space-lg);
    background:var(--surface-container-low);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
  }
  .spinner{
    width:22px;height:22px;border-radius:50%;
    border:3px solid var(--outline-variant);
    border-top-color:var(--primary);
    animation:spin .8s linear infinite;
  }
  @keyframes spin{to{transform:rotate(360deg);}}
  .loader-text{font-size:14px;font-weight:600;color:var(--on-surface-variant);}

  .sidebar-brand-logo{display:flex;align-items:center;gap:10px;}
  .sidebar-brand-logo img{border-radius:6px;}
</style>
</head>

<body>

<!-- ===================== SIDEBAR ===================== -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-head">
    <div class="sidebar-brand-logo">
      <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="32" height="32">
      <div>
        <p class="sidebar-brand text-headline-sm">Central Cell</p>
        <p class="sidebar-sub text-label-sm">Panel de Análisis de Fundas</p>
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
      <span class="material-symbols-outlined">dashboard</span>
      Panel de Herramientas
    </a>
    <a href="analisis_fundas_ventas_existencias.php" class="sidebar-link">
      <span class="material-symbols-outlined">swap_horiz</span>
      Ventas vs Existencias
    </a>
    <a href="fundasstock.php" class="sidebar-link active">
      <span class="material-symbols-outlined">inventory_2</span>
      Distribución Fundas
    </a>
    <a href="ventasfundas.php" class="sidebar-link">
      <span class="material-symbols-outlined">storefront</span>
      Ventas por Modelo
    </a>
    <a href="analisis_fundas.php" class="sidebar-link">
      <span class="material-symbols-outlined">sell</span>
      Ventas Por Marca
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
      <h2 class="text-headline-sm" style="margin:0">Distribución Fundas</h2>
    </div>
  </header>

  <div class="container">
    <div class="lesson">
      <div class="lesson-body">

        <span class="eyebrow">Inventario</span>
        <h1 class="text-headline-lg" style="margin:6px 0 0">
          Distribución Fundas — INNOVACION MOVIL
        </h1>
        <div class="lesson-meta">
          <span><span class="material-symbols-outlined" style="font-size:16px">inventory_2</span> Existencias por almacén</span>
        </div>

        <!-- Paso 1: Cargar archivo y buscar -->
        <section class="step-section">
          <div class="step-head">
            <div class="step-num">1</div>
            <h3 class="step-title text-headline-sm">Carga y busca tu inventario</h3>
          </div>

          <div class="controls-row">
            <div class="file-upload">
              <input id="inputFile" type="file" accept=".xlsx,.xls" hidden />
              <button class="btn btn-outline" id="fileButton" type="button">
                <span class="material-symbols-outlined">upload_file</span>
                Seleccionar Existencias
              </button>
            </div>

            <div class="search-wrap">
              <input type="text" id="search" class="search-input" placeholder="Buscar modelo...">
            </div>
          </div>

          <div id="list" class="inventory-list"></div>

          <div id="loader" class="loader-container" style="display:none;">
            <div class="spinner"></div>
            <div class="loader-text">Procesando inventario...</div>
          </div>
        </section>

        <!-- Panel de detalle por modelo -->
        <section id="panel" class="panel">
          <div class="step-head">
            <div class="step-num">2</div>
            <h3 id="panelTitle" class="panel-title text-headline-sm"></h3>
          </div>

          <button id="downloadBtn" class="btn btn-primary" style="display:none;margin-bottom:var(--space-md);" type="button">
            <span class="material-symbols-outlined">download</span>
            Descargar Distribución
          </button>

          <div id="stores" class="store-list"></div>
          <div id="productDetail" class="product-detail"></div>
        </section>

      </div>
    </div>
  </div>

  
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const fileButton = document.getElementById("fileButton");
  const inputFile = document.getElementById("inputFile");
  if (fileButton && inputFile) {
    fileButton.addEventListener("click", () => inputFile.click());
  }
  if (typeof renderList === "function") renderList();
});
</script>

<script>
const inputFile = document.getElementById("inputFile");
const search = document.getElementById("search");
const list = document.getElementById("list");
const panel = document.getElementById("panel");
const panelTitle = document.getElementById("panelTitle");
const storesDiv = document.getElementById("stores");
const productDetail = document.getElementById("productDetail");
const downloadBtn = document.getElementById("downloadBtn");
const loader = document.getElementById("loader");

let inventario = [];
let marcaActiva=null, modeloActivo=null;

inputFile.addEventListener("change", () => {
  if (inputFile.files.length) leerExcel(inputFile.files[0]);
});
search.addEventListener("input", renderList);

function leerExcel(file){
  loader.style.display = 'flex';
  const reader = new FileReader();
  reader.onload = e =>{
    const data = new Uint8Array(e.target.result);
    const wb = XLSX.read(data,{type:"array"});
    const sheet = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(sheet,{header:1, defval:""});

    inventario=[];
    for(let i=1;i<rows.length;i++){
      const r=rows[i];
      const nombre=String(r[13]).trim().toUpperCase();
      if(nombre!=="INNOVACION MOVIL>CASE>CELULAR" && nombre!=="INNOVACION MOVIL>CASE>TABLET") continue;
      inventario.push({
        almacen:r[0], prodConcat:r[2], marca:r[5], modelo:r[6],
        existencia:Number(r[7])||0, barcode:r[12]
      });
    }
    renderList();
    loader.style.display = "none";
  };
  reader.readAsArrayBuffer(file);
}

/* MODELOS ORDENADOS POR STOCK */
function renderList(){
  list.innerHTML="";
  const term=search.value.toLowerCase();
  const mapa={};

inventario.forEach(p=>{
  const texto = (p.marca + " " + p.modelo).toLowerCase();
  if(!texto.includes(term)) return;
  const k = p.marca + "||" + p.modelo;
  if(!mapa[k]) mapa[k] = 0;
  mapa[k] += p.existencia;
});

  const entradas = Object.entries(mapa).sort((a,b)=>b[1]-a[1]);

  if(!entradas.length){
    const vacio = document.createElement("div");
    vacio.className = "empty-state";
    vacio.innerHTML = `<span class="material-symbols-outlined" style="font-size:32px;display:block;margin-bottom:8px">inventory_2</span>
                        ${inventario.length ? "Sin resultados para tu búsqueda" : "Carga un archivo de existencias para comenzar"}`;
    list.appendChild(vacio);
    return;
  }

  entradas.forEach(([k,total])=>{
      const [marca,modelo]=k.split("||");
      const div=document.createElement("div");
      div.className="inventory-item";
      div.innerHTML=`<div>
                       <div class="brand">${marca}</div>
                       <div class="model">${modelo}</div>
                     </div>
                     <span class="qty-badge">${total} pzas</span>`;
      div.onclick=()=>abrirModelo(marca,modelo);
      list.appendChild(div);
    });
}

/*  ALMACENES ORDENADOS */
function abrirModelo(marca,modelo){
  marcaActiva=marca; modeloActivo=modelo;
  panel.style.display="block";
  panelTitle.textContent=`${marca} — ${modelo}`;
  downloadBtn.style.display="inline-flex";
  storesDiv.innerHTML=""; productDetail.innerHTML="";

  const data=inventario.filter(p=>p.marca===marca&&p.modelo===modelo&&p.existencia>0);
  const map={};
  data.forEach(p=>{
    if(!map[p.almacen]) map[p.almacen]=[];
    map[p.almacen].push(p);
  });

  let granTotal = 0;

  Object.entries(map)
    .sort((a,b)=>b[1].reduce((s,p)=>s+p.existencia,0)-a[1].reduce((s,p)=>s+p.existencia,0))
    .forEach(([almacen,productos])=>{
      const total=productos.reduce((s,p)=>s+p.existencia,0);
      granTotal += total;
      const div=document.createElement("div");
      div.className="store";
      div.innerHTML=`<b>${almacen}</b><span>${total} pzas</span>`;
      div.onclick=()=>mostrarProductos(almacen,productos);
      storesDiv.appendChild(div);
    });

  // AGREGAR FILA DE TOTAL
  const totalDiv=document.createElement("div");
  totalDiv.className="store total-row";
  totalDiv.innerHTML=`<span>TOTAL</span><span>${granTotal} pzas</span>`;
  storesDiv.appendChild(totalDiv);
}

/*  PRODUCTOS ORDENADOS */
function mostrarProductos(almacen,productos){
  productDetail.innerHTML=`<p class="sub-note">${almacen}</p>`;
  productos.sort((a,b)=>b.existencia-a.existencia).forEach(p=>{
    const d=document.createElement("div");
    d.className="product-row";
    d.innerHTML=`<b>${p.prodConcat}</b><br>
                 Barcode: ${p.barcode}<br>
                 Existencia: ${p.existencia}`;
    productDetail.appendChild(d);
  });
}

downloadBtn.onclick = () => {
  if (!marcaActiva || !modeloActivo) return;

  const data = inventario.filter(p =>
    p.marca === marcaActiva &&
    p.modelo === modeloActivo &&
    p.existencia > 0
  );

  if (!data.length) {
    alert("Este modelo no tiene existencias.");
    return;
  }

  const wb = XLSX.utils.book_new();

  /* ===== HOJA 1: RESUMEN POR ALMACÉN ===== */
  const resumenMap = {};
  data.forEach(p => {
    if (!resumenMap[p.almacen]) resumenMap[p.almacen] = 0;
    resumenMap[p.almacen] += p.existencia;
  });

  const resumenArray = Object.entries(resumenMap)
    .sort((a,b)=>b[1]-a[1])
    .map(([almacen, total]) => ({
      Almacén: almacen,
      Existencias: total
    }));

  // Agregar fila de total al resumen
  const totalResumen = resumenArray.reduce((sum, row) => sum + row.Existencias, 0);
  resumenArray.push({
    Almacén: "TOTAL",
    Existencias: totalResumen
  });

  const resumenSheet = XLSX.utils.json_to_sheet(resumenArray);
  XLSX.utils.book_append_sheet(wb, resumenSheet, "Resumen");

  /* ===== HOJAS POR CADA ALMACÉN ===== */
  const porAlmacen = {};
  data.forEach(p => {
    if (!porAlmacen[p.almacen]) porAlmacen[p.almacen] = [];
    porAlmacen[p.almacen].push({
      BarcodeId: p.barcode,
      Nombre: p.prodConcat,
      Cantidad: p.existencia
    });
  });

  Object.entries(porAlmacen)
    .sort((a,b)=>b[1].reduce((s,p)=>s+p.Cantidad,0)-a[1].reduce((s,p)=>s+p.Cantidad,0))
    .forEach(([almacen, productos]) => {
      productos.sort((a,b)=>b.Cantidad - a.Cantidad);
      
      // Calcular total del almacén
      const totalAlmacen = productos.reduce((sum, p) => sum + p.Cantidad, 0);
      
      // Agregar fila de total
      productos.push({
        BarcodeId: "",
        Nombre: "TOTAL",
        Cantidad: totalAlmacen
      });
      
      const sheet = XLSX.utils.json_to_sheet(productos);
      XLSX.utils.book_append_sheet(wb, sheet, almacen.substring(0,31));
    });

  /* ===== DESCARGAR ===== */
  const safeMarca = marcaActiva.replace(/[^a-z0-9]/gi,"_");
  const safeModelo = modeloActivo.replace(/[^a-z0-9]/gi,"_");

  XLSX.writeFile(wb, `Distribucion_${safeMarca}_${safeModelo}.xlsx`);
};
</script>

<script>
  // Control del sidebar en móvil (equivalente visual al menú hamburguesa anterior)
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const menuToggle = document.getElementById('menuToggle');
  const sidebarClose = document.getElementById('sidebarClose');

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

  document.getElementById('anioActual').textContent = new Date().getFullYear();
</script>

</body>
</html>