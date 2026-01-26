<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Inventario INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<style>
.controls{
  display:flex;
  gap:10px;
  align-items:center;
  flex-wrap:wrap;
}
input[type=file], input[type=text]{
  padding:8px;
  border-radius:6px;
  border:1px solid #ccc;
}
button{
  padding:8px 14px;
  background:#2f6fa6;
  color:white;
  border:none;
  border-radius:6px;
  cursor:pointer;
}
.list{
  margin-top:20px;
  background:white;
  border-radius:8px;
  box-shadow:0 2px 6px rgba(0,0,0,.1);
  overflow:hidden;
}
.item{
  padding:10px 14px;
  border-bottom:1px solid #eee;
  cursor:pointer;
}
.item:hover{ background:#f0f6ff; }
.brand{ font-weight:bold; color:#2f6fa6; }
.model{ font-size:14px; }

.panel{
  position:fixed;
  top:0;
  right:0;
  width:420px;
  height:100%;
  background:white;
  box-shadow:-4px 0 10px rgba(0,0,0,.15);
  padding:20px;
  overflow:auto;
  display:none;
}
.store{
  padding:8px;
  border-bottom:1px solid #ddd;
  cursor:pointer;
}
.store:hover{ background:#eef4ff; }
.store.total-row{
  background:#e3f2fd;
  font-weight:bold;
  color:#1976d2;
  cursor:default;
}
.product-detail{
  margin-top:10px;
  background:#f5f7fa;
  padding:10px;
  border-radius:6px;
}
</style>
<link rel="stylesheet" href="estilos.css">
</head>

<body>
<header>
  <nav>
    <ul id="menu">
      <li>
        <a href="analisis_fundas_ventas_existencias.php">Análisis de Ventas vs Existencias</a>
        <a href="ventasfundas.php">Ventas Por Modelo Fundas</a>
        <a href="analisis_fundas.php">Ventas Por Marca Fundas</a>
      </li>
    </ul>
  </nav>
</header>

<div class="container">
  <h1>📦 Distribución Fundas — INNOVACION MOVIL</h1>

  <div class="controls">
    <div class="file-upload">
      <input id="inputFile" type="file" accept=".xlsx,.xls" hidden />
      <button class="boton" id="fileButton" type="button">
        <div class="contenedorCarpeta">
          <div class="folder folder_one"></div>
          <div class="folder folder_two"></div>
          <div class="folder folder_three"></div>
          <div class="folder folder_four"></div>
        </div>
        <div class="active_line"></div>
        <span class="text">Seleccionar Existencias</span>
      </button>
    </div>

    <input type="text" id="search" placeholder="Buscar modelo...">
  </div>

  <div id="list" class="list"></div>

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

<script>
document.addEventListener("DOMContentLoaded", () => {
  const fileButton = document.getElementById("fileButton");
  const inputFile = document.getElementById("inputFile");
  if (fileButton && inputFile) {
    fileButton.addEventListener("click", () => inputFile.click());
  }
});
</script>

<div id="panel" class="panel">
  <h3 id="panelTitle"></h3>
  <button id="downloadBtn" style="display:none;margin-bottom:10px;">📥 Descargar Distribución</button>
  <div id="stores"></div>
  <div id="productDetail" class="product-detail"></div>
</div>

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

  Object.entries(mapa)
    .sort((a,b)=>b[1]-a[1])
    .forEach(([k,total])=>{
      const [marca,modelo]=k.split("||");
      const div=document.createElement("div");
      div.className="item";
      div.innerHTML=`<div class="brand">${marca}</div>
                     <div class="model">${modelo} — ${total} pzas</div>`;
      div.onclick=()=>abrirModelo(marca,modelo);
      list.appendChild(div);
    });
}

/*  ALMACENES ORDENADOS */
function abrirModelo(marca,modelo){
  marcaActiva=marca; modeloActivo=modelo;
  panel.style.display="block";
  panelTitle.textContent=`${marca} — ${modelo}`;
  downloadBtn.style.display="block";
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
      div.innerHTML=`<b>${almacen}</b> — ${total} pzas`;
      div.onclick=()=>mostrarProductos(almacen,productos);
      storesDiv.appendChild(div);
    });

  // AGREGAR FILA DE TOTAL
  const totalDiv=document.createElement("div");
  totalDiv.className="store total-row";
  totalDiv.innerHTML=`TOTAL — ${granTotal} pzas`;
  storesDiv.appendChild(totalDiv);
}

/*  PRODUCTOS ORDENADOS */
function mostrarProductos(almacen,productos){
  productDetail.innerHTML=`<h4>${almacen}</h4>`;
  productos.sort((a,b)=>b.existencia-a.existencia).forEach(p=>{
    const d=document.createElement("div");
    d.style.borderBottom="1px solid #ddd";
    d.style.padding="6px";
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
</body>
</html>