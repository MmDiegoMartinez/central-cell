<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analizador de Ventas</title>

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>

<style>.controls{
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
        <div class="nav-inner">
            <!-- Botón hamburguesa -->
            <label class="bar-menu">
                <input type="checkbox" id="menu-check">
                <span class="top"></span>
                <span class="middle"></span>
                <span class="bottom"></span>
            </label>

            <ul id="nav-menu">
                 <li> <a href="index.php">
          
          Home
        </a></li>
            <li><a href="analisis_fundas_ventas_existencias.php">Análisis de Ventas vs Existencias</a></li>
       
        <li><a href="fundasstock.php">Distribucion Por Modelo Fundas</a></li>
        <li><a href="analisis_fundas.php">Ventas Por Marca Fundas</a></li>
            </ul>
        </div>
    </nav>
</header>

<div class="container">
  <h1>🛍️ Ventas Fundas por modelo — INNOVACION MOVIL</h1>

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
        <span class="text">Análisis de Ventas</span>
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

<div id="panel" class="panel" style="display:none">
  <h3 id="panelTitle"></h3>
  <button id="download">📥 Descargar Reporte de Ventas</button>
  <div id="stores"></div>
  <div id="detail"></div>
</div>

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
  <script>
    // Controlar menú hamburguesa
    document.getElementById('menu-check').addEventListener('change', function() {
        const menu = document.getElementById('nav-menu');
        if (this.checked) {
            menu.style.opacity = '1';
            menu.style.visibility = 'visible';
            menu.style.pointerEvents = 'auto';
        } else {
            menu.style.opacity = '0';
            menu.style.visibility = 'hidden';
            menu.style.pointerEvents = 'none';
        }
    });
</script>
</body>
</html>