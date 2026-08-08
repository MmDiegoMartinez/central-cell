<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analizador de Ventas</title>

<link rel="stylesheet" href="../styles.css">

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>

<style>
  /* ============================================================
     Estilos complementarios (no existen en styles.css)
     ============================================================ */

  /* --- Overlay tap-outside del sidebar --- */
  .sidebar-overlay{cursor:pointer;}

  /* --- Barra de controles (upload + búsqueda) --- */
  .controls{
    display:flex;flex-wrap:wrap;gap:var(--space-md);
    align-items:center;margin-bottom:var(--space-lg);
  }
  .search-input{
    flex:1;min-width:220px;
    padding:10px 14px;
    border-radius:var(--radius-lg);
    border:1px solid var(--outline-variant);
    background:var(--surface-container-lowest);
    color:var(--on-surface);
    font-family:inherit;font-size:14px;
  }
  .search-input:focus{outline:2px solid var(--primary);outline-offset:1px;}
  .search-input::placeholder{color:var(--outline);}

  /* --- Lista de modelos --- */
  .list{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
    gap:var(--space-md);
  }
  .item{
    background:var(--surface-container-lowest);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    padding:var(--space-md);
    cursor:pointer;
    transition:background .15s ease, transform .15s ease, border-color .15s ease;
  }
  .item:hover{
    background:var(--surface-container-low);
    border-color:var(--primary);
    transform:translateY(-1px);
  }
  .item b{color:var(--primary);font-size:14px;}

  /* --- Panel de detalle --- */
  .panel{
    margin-top:var(--space-2xl);
    background:var(--surface-container-lowest);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-xl);
    padding:var(--space-xl);
  }
  .panel h3{margin:0 0 var(--space-md);color:var(--on-surface);}

  .stores{
    display:flex;flex-wrap:wrap;gap:8px;
    margin:var(--space-md) 0;
  }
  .store{
    background:var(--surface-container);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-full);
    padding:8px 16px;
    font-size:13px;font-weight:600;
    cursor:pointer;color:var(--on-surface-variant);
    transition:background .15s ease,color .15s ease;
  }
  .store:hover{background:var(--primary-container);color:var(--on-primary);}
  .store.total-row{
    background:var(--secondary-container);
    color:var(--on-secondary-container);
    cursor:default;font-weight:700;
  }
  .store.total-row:hover{background:var(--secondary-container);}

  .detail{
    margin-top:var(--space-md);
    background:var(--surface-container-low);
    border-radius:var(--radius-lg);
    padding:var(--space-md) var(--space-lg);
  }
  .detail h4{margin:0 0 8px;font-size:14px;color:var(--primary);}
  .detail div{
    font-size:13px;color:var(--on-surface-variant);
    padding:4px 0;border-bottom:1px solid var(--outline-variant);
  }
  .detail div:last-child{border-bottom:none;}

  #download{margin-bottom:var(--space-sm);}

  /* --- Loader --- */
  .loader-container{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:var(--space-md);padding:var(--space-2xl) 0;
    color:var(--on-surface-variant);font-size:13px;
  }
  .spinner{
    width:40px;height:40px;border-radius:50%;
    border:4px solid var(--surface-container-high);
    border-top-color:var(--primary);
    animation:spin .8s linear infinite;
  }
  @keyframes spin{to{transform:rotate(360deg);}}

  .page-icon{color:var(--primary);}
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
        <p class="sidebar-sub text-label-sm">Panel de Análisis de Fundas</p>
      </div>
    </div>
    <button class="sidebar-close" id="sidebarClose" aria-label="Cerrar menú">
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
    <a href="fundasstock.php" class="sidebar-link">
      <span class="material-symbols-outlined">inventory_2</span>
      Distribución de fundas
    </a>
    <a href="ventasfundas.php" class="sidebar-link active">
      <span class="material-symbols-outlined">storefront</span>
      Ventas por Modelo
    </a>
    <a href="analisis_fundas.php" class="sidebar-link">
      <span class="material-symbols-outlined">sell</span>
      Ventas Por Marca
    </a>
  </nav>

  <div class="sidebar-foot">
    <p class="text-label-sm" style="color:var(--outline);">Innovación Móvil</p>
  </div>
</aside>

<div class="main">
  <header class="topheader">
    <div class="topheader-left">
      <button class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
        <span class="material-symbols-outlined">menu</span>
      </button>
      <h2 class="text-headline-sm">
        <span class="material-symbols-outlined page-icon">shopping_bag</span>
        Ventas Fundas por Modelo — INNOVACION MOVIL
      </h2>
    </div>
  </header>

  <div class="container" style="padding-top:var(--space-2xl);padding-bottom:var(--space-3xl);">

    <div class="controls">
      <div class="file-upload">
        <input id="inputFile" type="file" accept=".xlsx,.xls" hidden />
        <button class="btn btn-primary" id="fileButton" type="button">
          <span class="material-symbols-outlined">upload_file</span>
          <span class="text">Análisis de Ventas</span>
        </button>
      </div>

      <input type="text" id="search" class="search-input" placeholder="Buscar modelo...">
    </div>

    <div id="list" class="list"></div>

    <div id="loader" class="loader-container" style="display:none;">
      <div class="spinner"></div>
      <span>Cargando datos…</span>
    </div>

    <div id="panel" class="panel" style="display:none">
      <h3 id="panelTitle"></h3>
      <button id="download" class="btn btn-secondary">
        <span class="material-symbols-outlined">download</span>
        Descargar Reporte de Ventas
      </button>
      <div id="stores" class="stores"></div>
      <div id="detail" class="detail"></div>
    </div>

  </div>

  
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  document.getElementById('current-year').textContent = new Date().getFullYear();

  const fileButton = document.getElementById("fileButton");
  const inputFile = document.getElementById("inputFile");
  if (fileButton && inputFile) {
    fileButton.addEventListener("click", () => inputFile.click());
  }

  // Sidebar toggle (hamburguesa + overlay + botón cerrar)
  const sidebar = document.getElementById("sidebar");
  const menuToggle = document.getElementById("menuToggle");
  const sidebarClose = document.getElementById("sidebarClose");
  const sidebarOverlay = document.getElementById("sidebarOverlay");

  function openSidebar(){
    sidebar.classList.add("open");
    sidebarOverlay.classList.add("show");
  }
  function closeSidebar(){
    sidebar.classList.remove("open");
    sidebarOverlay.classList.remove("show");
  }

  menuToggle && menuToggle.addEventListener("click", openSidebar);
  sidebarClose && sidebarClose.addEventListener("click", closeSidebar);
  sidebarOverlay && sidebarOverlay.addEventListener("click", closeSidebar);
});
</script>

<script>
let data=[]
let marcaActiva=null, modeloActivo=null
let rangoVentas=""
const loader = document.getElementById("loader");

inputFile.onchange=e=>loadExcel(e.target.files[0])
search.oninput=renderModels

/* ===== PARSE FECHA FIXED WIDTH ===== */
function parseFechaFixed(txt){
  if(!txt) return null
  // Ejemplo txt: "Dec  9 2025  7:05PM"
  const partes = txt.trim().split(/\s+/)
  if(partes.length < 4) return null

  const [mesStr, diaStr, anioStr, horaStr] = partes
  const meses = {Jan:0, Feb:1, Mar:2, Apr:3, May:4, Jun:5,
                 Jul:6, Aug:7, Sep:8, Oct:9, Nov:10, Dec:11}
  const mes = meses[mesStr]
  const dia = parseInt(diaStr)
  const anio = parseInt(anioStr)

  // Hora
  const match = horaStr.match(/(\d+):(\d+)(AM|PM)/)
  if(!match) return new Date(anio, mes, dia)
  let h = parseInt(match[1])
  const min = parseInt(match[2])
  const ampm = match[3]
  if(ampm==="PM" && h<12) h+=12
  if(ampm==="AM" && h===12) h=0

  return new Date(anio, mes, dia, h, min)
}

function formatFecha(d){
  if(!d) return ""
  return d.toLocaleDateString("es-MX",{day:"2-digit",month:"short",year:"numeric"})
}

function loadExcel(file){
    loader.style.display = 'flex';
 const r=new FileReader()
 r.onload=e=>{
  const wb=XLSX.read(new Uint8Array(e.target.result),{type:"array"})
  const rows=XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]],{header:1,defval:""})
  data=[]

  for(let i=1;i<rows.length;i++){
    const r=rows[i]
    if(r[1]!=="INNOVACION MOVIL") continue
    if(r[3]!=="CELULAR" && r[3]!=="TABLET") continue

    const fechaTxt=r[7]
    const fecha=parseFechaFixed(fechaTxt)

    data.push({
      almacen:r[0],
      prod:r[11],
      cantidad:Number(r[14])||0,
      marca:r[20],
      modelo:r[21],
      fecha,
      fechaTxt
    })
  }
  renderModels()
   loader.style.display = "none";
 }
 r.readAsArrayBuffer(file)
}

function renderModels(){
 list.innerHTML=""
 const term=search.value.toLowerCase()
 const map={}

 data.forEach(p=>{
   const key=p.marca+"||"+p.modelo
   const text=(p.marca+" "+p.modelo).toLowerCase()
   if(!text.includes(term)) return
   if(!map[key]) map[key]=0
   map[key]+=p.cantidad
 })

 Object.entries(map).sort((a,b)=>b[1]-a[1]).forEach(([k,t])=>{
   const [m,mo]=k.split("||")
   const d=document.createElement("div")
   d.className="item"
   d.innerHTML=`<b>${m}</b><br>${mo} — ${t}`
   d.onclick=()=>openModel(m,mo)
   list.appendChild(d)
 })
}

function openModel(marca,modelo){
 marcaActiva=marca;modeloActivo=modelo
 panel.style.display="block"
 stores.innerHTML="";detail.innerHTML=""

 const filtered=data.filter(p=>p.marca===marca&&p.modelo===modelo)

 /* ==== RANGO DE FECHAS ==== */
 const fechas=filtered.map(p=>p.fecha).filter(f=>!isNaN(f))
 const minF=new Date(Math.min(...fechas))
 const maxF=new Date(Math.max(...fechas))
 rangoVentas=`${formatFecha(minF)} al ${formatFecha(maxF)}`

 panelTitle.textContent=`${marca} ${modelo} (${rangoVentas})`

 const map={}
 filtered.forEach(p=>{
   if(!map[p.almacen]) map[p.almacen]=[]
   map[p.almacen].push(p)
 })

 // ORDENAR POR TOTAL DE VENTAS (MAYOR A MENOR)
 const ordenado = Object.entries(map).sort((a,b)=>{
   const totalA = a[1].reduce((s,p)=>s+p.cantidad,0)
   const totalB = b[1].reduce((s,p)=>s+p.cantidad,0)
   return totalB - totalA
 })

 let granTotal = 0

 ordenado.forEach(([alm,prods])=>{
   const total=prods.reduce((s,p)=>s+p.cantidad,0)
   granTotal += total
   const d=document.createElement("div")
   d.className="store"
   d.innerHTML=`${alm} — ${total}`
   d.onclick=()=>showDetail(alm,prods)
   stores.appendChild(d)
 })

 // AGREGAR FILA DE TOTAL
 const totalDiv=document.createElement("div")
 totalDiv.className="store total-row"
 totalDiv.innerHTML=`TOTAL — ${granTotal}`
 stores.appendChild(totalDiv)
}

function showDetail(alm,prods){
 detail.innerHTML="<h4>"+alm+"</h4>"
 const map={}
 prods.forEach(p=>{
   if(!map[p.prod]) map[p.prod]=0
   map[p.prod]+=p.cantidad
 })
 Object.entries(map).forEach(([n,c])=>{
   detail.innerHTML+=`<div>${n} — ${c}</div>`
 })
}

/* ===== EXPORTAR ===== */
download.onclick=async ()=>{
 const filtered=data.filter(p=>p.marca===marcaActiva&&p.modelo===modeloActivo)
 const wb=new ExcelJS.Workbook()

 const resumen=wb.addWorksheet("General")
 resumen.addRow([`Modelo: ${marcaActiva} ${modeloActivo}`])
 resumen.addRow([`Periodo de ventas: ${rangoVentas}`])
 resumen.addRow([])
 resumen.addRow(["Almacén","Total"])

 const map={}
 filtered.forEach(p=>{
  if(!map[p.almacen]) map[p.almacen]=0
  map[p.almacen]+=p.cantidad
 })

 // ORDENAR POR TOTAL (MAYOR A MENOR)
 const ordenado = Object.entries(map).sort((a,b)=>b[1]-a[1])
 let granTotal = 0

 ordenado.forEach(([a,t])=>{
   resumen.addRow([a,t])
   granTotal += t
 })

 // AGREGAR FILA DE TOTAL
 resumen.addRow(["TOTAL", granTotal])

 const porAlm={}
 filtered.forEach(p=>{
  if(!porAlm[p.almacen]) porAlm[p.almacen]={}
  if(!porAlm[p.almacen][p.prod]) porAlm[p.almacen][p.prod]=0
  porAlm[p.almacen][p.prod]+=p.cantidad
 })

 Object.entries(porAlm).forEach(([alm,prods])=>{
   const sh=wb.addWorksheet(alm.substring(0,31))
   sh.addRow(["Producto","Cantidad"])

   let totalAlmacen = 0
   Object.entries(prods).forEach(([n,c])=>{
     sh.addRow([n,c])
     totalAlmacen += c
   })

   // AGREGAR TOTAL DEL ALMACÉN
   sh.addRow(["TOTAL", totalAlmacen])
 })

 const buf=await wb.xlsx.writeBuffer()
 const blob=new Blob([buf])
 const a=document.createElement("a")
 a.href=URL.createObjectURL(blob)
 a.download=`Ventas_${marcaActiva}_${modeloActivo}.xlsx`
 a.click()
}
</script>

</body>
</html>