<?php
include_once '../funciones.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<title>Productos Más Vendidos — INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<style>
body { font-family: Arial, sans-serif; margin:18px; background:#f7f7f7; color:#222; }
h1{margin-top:0;}
.controls{display:flex; gap:12px; align-items:center; margin-bottom:12px; flex-wrap:wrap;}
input[type=file]{padding:6px;}
button.btn{background:#007bff;color:white;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;}
button.btn:disabled{background:#999; cursor:not-allowed;}
table{border-collapse:collapse;width:100%;background:white;box-shadow:0 1px 3px rgba(0,0,0,0.07);margin-bottom:20px;}
th,td{padding:8px 6px;border:1px solid #e1e1e1;text-align:center;font-size:13px;}
th{background:#2f6fa6;color:white;position:sticky;top:0;z-index:1;}
caption{text-align:left;font-weight:600;padding:8px;}
.note{font-size:13px;color:#333;margin-top:6px;}
.filter-container{margin-bottom:12px;}
.filter-container label{margin-right:6px;}

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
            <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />

          </span>
          Home
        </a>
      </li>
    </ul>
  </nav>
</header>
<div class="center-container">
<div class="container">
<h1>📊 Productos Más Vendidos — INNOVACION MOVIL</h1>

    <div class="controls">
<div class="file-upload">
  <input id="inputFile" type="file" accept=".xlsx,.xls" />

  <button class="boton" id="fileButton" type="button">
    <div class="contenedorCarpeta">
      <div class="folder folder_one"></div>
      <div class="folder folder_two"></div>
      <div class="folder folder_three"></div>
      <div class="folder folder_four"></div>
    </div>
    <div class="active_line"></div>
    <span class="text">Seleccionar Archivo</span>
  </button>
</div>

<script>
// Conectamos el botón animado con el input oculto
document.getElementById("fileButton").addEventListener("click", () => {
  document.getElementById("inputFile").click();
});
</script>
    <button id="procesarBtn" class="btn" disabled>Procesar archivo</button>
    <button id="descargarBtn" class="btn" disabled>Descargar Excel</button>
</div>
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
<div id="mensajes" class="note"></div>
<div class="center-container">
<div class="filter-container">
    <label>Almacén: <select id="filterAlmacen"><option value="">Todos</option></select></label>
    <label>Categoría: <select id="filterCategoria"><option value="">Todos</option></select></label>
    <label>Tipo de producto: <select id="filterTipo"><option value="">Todos</option></select></label>
</div>
</div>

<div id="tablesContainer"></div>
</div>

<script>
let registros = [];
let registrosFiltrados = [];
const inputFile = document.getElementById('inputFile');
const procesarBtn = document.getElementById('procesarBtn');
const descargarBtn = document.getElementById('descargarBtn');
const mensajes = document.getElementById('mensajes');
const tablesContainer = document.getElementById('tablesContainer');

const filterAlmacen = document.getElementById('filterAlmacen');
const filterCategoria = document.getElementById('filterCategoria');
const filterTipo = document.getElementById('filterTipo');

inputFile.addEventListener('change', ()=>{ procesarBtn.disabled = !inputFile.files.length; mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : ""; });

procesarBtn.addEventListener('click', ()=>{ if(inputFile.files.length) leerExcel(inputFile.files[0]); });

filterAlmacen.addEventListener('change', aplicarFiltros);
filterCategoria.addEventListener('change', aplicarFiltros);
filterTipo.addEventListener('change', aplicarFiltros);

descargarBtn.addEventListener('click', ()=>descargarExcel());

function safeCell(row,index){return row[index]!==undefined?row[index]:"";}
function parseFecha(fechaRaw){
    if(typeof fechaRaw==='number') return new Date((fechaRaw-25569)*86400*1000);
    const d = new Date(fechaRaw); if(!isNaN(d.getTime())) return d; return null;
}

function leerExcel(file){
      mensajes.innerText = 'Leyendo archivo...';
document.getElementById('loader').style.display = 'flex';
const reader = new FileReader();
    reader.onload = e=>{
        const data = new Uint8Array(e.target.result);
        const wb = XLSX.read(data,{type:'array'});
        const sheet = wb.Sheets[wb.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(sheet,{header:1,defval:""});
        if(!rows.length){mensajes.innerText="Archivo vacío"; return;}
        // Índices de columnas
        const idxAlmacen=0, idxN1=1, idxCategoria=3, idxProdConcat=11, idxTipoProducto=12, idxCantidad=14, idxTotalVenta=18;
        registros=[];
        for(let i=1;i<rows.length;i++){
            const r=rows[i];
            if(String(r[idxN1]||"").trim()==="INNOVACION MOVIL"){
                registros.push({
                    almacen: String(r[idxAlmacen]||"").trim(),
                    categoria: String(r[idxCategoria]||"").trim(),
                    producto: String(r[idxProdConcat]||"").trim(),
                    tipo: String(r[idxTipoProducto]||"").trim(),
                    cantidad: Number(r[idxCantidad]||0),
                    totalVenta: Number(r[idxTotalVenta]||0)
                });
            }
        }
        mensajes.innerText=`Filtradas ${registros.length} filas INNOVACION MOVIL. Procesando...`;
        llenarFiltros();
        aplicarFiltros();
        descargarBtn.disabled=false;
        document.getElementById('loader').style.display = 'none'; 
    };
    reader.readAsArrayBuffer(file);
}

function llenarFiltros(){
    const almacenes = Array.from(new Set(registros.map(r=>r.almacen))).sort();
    const categorias = Array.from(new Set(registros.map(r=>r.categoria))).sort();
    const tipos = Array.from(new Set(registros.map(r=>r.tipo))).sort();
    filterAlmacen.innerHTML='<option value="">Todos</option>'+almacenes.map(a=>`<option value="${a}">${a}</option>`).join('');
    filterCategoria.innerHTML='<option value="">Todos</option>'+categorias.map(a=>`<option value="${a}">${a}</option>`).join('');
    filterTipo.innerHTML='<option value="">Todos</option>'+tipos.map(a=>`<option value="${a}">${a}</option>`).join('');
}

function aplicarFiltros(){
    registrosFiltrados = registros.filter(r=>{
        return (!filterAlmacen.value || r.almacen===filterAlmacen.value) &&
               (!filterCategoria.value || r.categoria===filterCategoria.value) &&
               (!filterTipo.value || r.tipo===filterTipo.value);
    });
    generarTabla();
}

function generarTabla(){
    tablesContainer.innerHTML="";
    if(!registrosFiltrados.length){tablesContainer.innerHTML="<p>No hay registros con los filtros seleccionados.</p>"; return;}
    // Agrupar por producto
    const resumen = {};
    registrosFiltrados.forEach(r=>{
        const key = `${r.producto}||${r.categoria}||${r.tipo}`;
        if(!resumen[key]) resumen[key]={producto:r.producto,categoria:r.categoria,tipo:r.tipo,cantidad:0,totalVenta:0};
        resumen[key].cantidad+=r.cantidad;
        resumen[key].totalVenta+=r.totalVenta;
    });
    // Ordenar de mayor a menor por cantidad
    const resumenArr = Object.values(resumen).sort((a,b)=>b.cantidad - a.cantidad);

    let html=`<div class="center-table"><table><thead><tr>
        <th>Producto</th>
        <th>Categoría</th>
        <th>Tipo de producto</th>
        <th>Cantidad vendida</th>
        <th>Total vendido</th>
    </tr></thead><tbody>`;
    resumenArr.forEach(r=>{
        html+=`<tr>
            <td>${r.producto}</td>
            <td>${r.categoria}</td>
            <td>${r.tipo}</td>
            <td>${r.cantidad}</td>
            <td>${r.totalVenta.toFixed(2)}</td>
        </tr>`;
    });
    html+="</tbody></table></div>";
    tablesContainer.innerHTML=html;
}

function descargarExcel(){
    if(!registrosFiltrados.length) return;
    const resumen = {};
    registrosFiltrados.forEach(r=>{
        const key = `${r.producto}||${r.categoria}||${r.tipo}`;
        if(!resumen[key]) resumen[key]={producto:r.producto,categoria:r.categoria,tipo:r.tipo,cantidad:0,totalVenta:0};
        resumen[key].cantidad+=r.cantidad;
        resumen[key].totalVenta+=r.totalVenta;
    });
    const resumenArr = Object.values(resumen).sort((a,b)=>b.cantidad - a.cantidad);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb,XLSX.utils.json_to_sheet(resumenArr),"ProductosMasVendidos");
    XLSX.writeFile(wb,"ProductosMasVendidos.xlsx");
}
</script>
</body>
</html>
