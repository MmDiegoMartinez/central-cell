<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Análisis Ventas vs Existencias — Celulares TECNOLOGIA MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>
<link rel="stylesheet" href="estilos.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f0f6ff;min-height:100vh}
.container{max-width:900px;margin:40px auto;background:white;border-radius:12px;
           box-shadow:0 4px 20px rgba(0,180,216,.15);padding:40px}
h1{color:#00b4d8;margin-bottom:30px;text-align:center;font-size:28px;font-weight:600}
.upload-section{display:grid;gap:25px;margin-bottom:30px}
.file-group{background:#f8fafc;padding:20px;border-radius:10px;border:2px solid #e0f7fa;transition:all .3s}
.file-group:hover{border-color:#00b4d8}
.file-group h3{color:#00b4d8;margin-bottom:15px;font-size:16px;font-weight:600}

/* Input file oculto — solo se usa programáticamente */
.file-group input[type="file"]{ display:none; }

.file-button{display:block;width:100%;padding:14px 20px;background:#00838f;color:white;
             border:none;border-radius:8px;cursor:pointer;font-size:15px;font-weight:500;transition:all .3s}
.file-button:hover{background:#006064;transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,180,216,.3)}
.file-button.loaded{background:#28a745}
.file-button.loaded:hover{background:#218838}
.file-status{margin-top:12px;padding:10px;background:white;border-radius:6px;font-size:14px;
             color:#6c757d;min-height:40px;display:flex;align-items:center;border:1px solid #e9ecef}
.file-status.success{color:#28a745;font-weight:600;border-color:#28a745;background:#f0fff4}
.analyze-section{text-align:center;padding:30px 0;border-top:2px solid #e0f7fa;margin-top:20px}
#analyzeBtn{padding:16px 50px;background:linear-gradient(135deg,#00838f 0%,#006064 100%);
            color:white;border:none;border-radius:50px;font-size:18px;font-weight:600;
            cursor:pointer;transition:all .3s;box-shadow:0 4px 15px rgba(0,180,216,.3)}
#analyzeBtn:hover:not(:disabled){transform:translateY(-3px);box-shadow:0 6px 25px rgba(0,180,216,.5)}
#analyzeBtn:disabled{background:#adb5bd;cursor:not-allowed;box-shadow:none;transform:none}
.loader{display:none;text-align:center;padding:20px}
.loader.active{display:block}
.spinner{border:4px solid #e0f7fa;border-top:4px solid #00b4d8;border-radius:50%;
         width:50px;height:50px;animation:spin 1s linear infinite;margin:0 auto 15px}
@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
.loader-text{color:#00838f;font-size:14px;font-weight:500}
.instructions{background:#e0f7fa;padding:20px;border-radius:10px;margin-top:25px;border-left:4px solid #00b4d8}
.instructions h3{color:#00838f;margin-bottom:12px;font-size:16px;font-weight:600}
.instructions ul{margin-left:20px;color:#495057;line-height:1.8}
</style>
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
        <li><a href="ventascelulares.php">🛍️ Ventas por Modelo</a></li>
      </ul>
    </div>
  </nav>
</header>

<div class="container">
  <h1>📊 Análisis Ventas vs Existencias — Celulares</h1>

  <div class="upload-section">
    <div class="file-group">
      <h3>1️⃣ Archivo de Existencias</h3>
      <!-- input oculto, se activa desde el botón estilizado -->
      <input type="file" id="existenciasFile" accept=".xlsx,.xls">
      <button class="file-button" id="existenciasBtn">📦 Seleccionar Archivo de Existencias</button>
      <div class="file-status" id="existenciasStatus">Esperando archivo...</div>
    </div>
    <div class="file-group">
      <h3>2️⃣ Archivo de Ventas</h3>
      <input type="file" id="ventasFile" accept=".xlsx,.xls">
      <button class="file-button" id="ventasBtn">🛍️ Seleccionar Archivo de Ventas</button>
      <div class="file-status" id="ventasStatus">Esperando archivo...</div>
    </div>
  </div>

  <div class="analyze-section">
    <button id="analyzeBtn" disabled>🚀 Generar Reporte Completo</button>
  </div>

  <div class="loader" id="loader">
    <div class="spinner"></div>
    <div class="loader-text">Generando reporte Excel...</div>
  </div>


<script>
let existenciasData = null;
let ventasData      = null;

const existenciasFile   = document.getElementById('existenciasFile');
const ventasFile        = document.getElementById('ventasFile');
const existenciasBtn    = document.getElementById('existenciasBtn');
const ventasBtn         = document.getElementById('ventasBtn');
const existenciasStatus = document.getElementById('existenciasStatus');
const ventasStatus      = document.getElementById('ventasStatus');
const analyzeBtn        = document.getElementById('analyzeBtn');
const loader            = document.getElementById('loader');

// Botones estilizados disparan el input oculto
existenciasBtn.onclick   = ()=> existenciasFile.click();
ventasBtn.onclick        = ()=> ventasFile.click();
existenciasFile.onchange = e=>{ if(e.target.files.length) cargarExistencias(e.target.files[0]); };
ventasFile.onchange      = e=>{ if(e.target.files.length) cargarVentas(e.target.files[0]); };

/* ─────────────────────────────────────────────────────────────
   CATEGORÍAS VÁLIDAS — columna N (índice 13) existencias

   SMARTPHONE  → requiere sufijo >PROPIOS o >BATYCELL
   EQUIPO BASICO → se acepta la cadena exacta sin sufijo
   ───────────────────────────────────────────────────────────── */
const CATS_SMARTPHONE = [
  "TECNOLOGIA MOVIL>SMARTPHONE>PROPIOS",
  "TECNOLOGIA MOVIL>SMARTPHONE>BATYCELL"
];
// Para básicos basta con que la categoría EMPIECE con esta cadena
const PREFIJO_BASICO = "TECNOLOGIA MOVIL>EQUIPO BASICO";

const TIPOS_VENTAS = ["PROPIOS", "BATYCELL"];

/* ── CARGAR EXISTENCIAS ── */
function cargarExistencias(file){
  const reader = new FileReader();
  reader.onload = e =>{
    const wb   = XLSX.read(new Uint8Array(e.target.result), {type:"array"});
    const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], {header:1, defval:""});
    existenciasData = [];
    for(let i=1; i<rows.length; i++){
      const r      = rows[i];
      const nombre = String(r[13]).trim().toUpperCase();

      let tipoProducto = null;
      if(CATS_SMARTPHONE.includes(nombre)){
        tipoProducto = 'SMARTPHONE';
      } else if(nombre.startsWith(PREFIJO_BASICO)){
        // acepta "TECNOLOGIA MOVIL>EQUIPO BASICO",
        // "TECNOLOGIA MOVIL>EQUIPO BASICO>PROPIOS",
        // "TECNOLOGIA MOVIL>EQUIPO BASICO>BATYCELL", etc.
        tipoProducto = 'EQUIPO_BASICO';
      } else {
        continue;
      }

      existenciasData.push({
        almacen:    r[0],
        marca:      r[5],
        modelo:     r[6],
        existencia: Number(r[7]) || 0,
        tipoProducto
      });
    }
    existenciasStatus.textContent = `✅ ${existenciasData.length} registros cargados`;
    existenciasStatus.classList.add('success');
    existenciasBtn.classList.add('loaded');
    verificarArchivos();
  };
  reader.readAsArrayBuffer(file);
}

/* ── CARGAR VENTAS ── */
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

function cargarVentas(file){
  const reader = new FileReader();
  reader.onload = e =>{
    const wb   = XLSX.read(new Uint8Array(e.target.result), {type:"array"});
    const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]], {header:1, defval:""});
    ventasData = [];
    for(let i=1; i<rows.length; i++){
      const r    = rows[i];
      const colB = String(r[1]||"").trim().toUpperCase();
      const colC = String(r[2]||"").trim().toUpperCase();
      const colD = String(r[3]||"").trim().toUpperCase();

      if(colB !== "TECNOLOGIA MOVIL") continue;

      let tipoProducto = null;
      if(colC === "SMARTPHONE"){
        if(!TIPOS_VENTAS.includes(colD)) continue;
        tipoProducto = "SMARTPHONE";
      } else if(colC === "EQUIPO BASICO"){
        tipoProducto = "EQUIPO_BASICO"; // col D puede venir vacía
      } else {
        continue;
      }

      ventasData.push({
        almacen: r[0], cantidad: Number(r[14])||0,
        marca: r[20], modelo: r[21],
        fecha: parseFechaFixed(r[7]), tipoProducto
      });
    }
    ventasStatus.textContent = `✅ ${ventasData.length} registros cargados`;
    ventasStatus.classList.add('success');
    ventasBtn.classList.add('loaded');
    verificarArchivos();
  };
  reader.readAsArrayBuffer(file);
}

function verificarArchivos(){
  if(existenciasData && ventasData) analyzeBtn.disabled = false;
}

analyzeBtn.onclick = async ()=>{
  loader.classList.add('active');
  analyzeBtn.disabled = true;
  await new Promise(r=> setTimeout(r, 100));
  try{ await generarReporte(); }
  catch(err){ alert('Error: ' + err.message); console.error(err); }
  finally{ loader.classList.remove('active'); analyzeBtn.disabled = false; }
};

/* ── HELPER: mapas ventas + existencias por tipo ── */
function construirMapas(tipo){
  const ventasPorModelo = {};
  ventasData.filter(v=> v.tipoProducto === tipo).forEach(v=>{
    const key = `${v.marca}||${v.modelo}`;
    if(!ventasPorModelo[key])
      ventasPorModelo[key] = {marca:v.marca, modelo:v.modelo, total:0, porAlmacen:{}};
    ventasPorModelo[key].total += v.cantidad;
    ventasPorModelo[key].porAlmacen[v.almacen] =
      (ventasPorModelo[key].porAlmacen[v.almacen]||0) + v.cantidad;
  });
  const existenciasPorModelo = {};
  existenciasData.filter(e=> e.tipoProducto === tipo).forEach(e=>{
    const key = `${e.marca}||${e.modelo}`;
    if(!existenciasPorModelo[key]) existenciasPorModelo[key] = {porAlmacen:{}};
    existenciasPorModelo[key].porAlmacen[e.almacen] =
      (existenciasPorModelo[key].porAlmacen[e.almacen]||0) + e.existencia;
  });
  const modelosOrdenados =
    Object.entries(ventasPorModelo).sort((a,b)=>b[1].total - a[1].total);
  const almacenesSet = new Set();
  ventasData.filter(v=> v.tipoProducto === tipo).forEach(v=> almacenesSet.add(v.almacen));
  existenciasData.filter(e=> e.tipoProducto === tipo).forEach(e=> almacenesSet.add(e.almacen));
  const almacenes = Array.from(almacenesSet).sort();
  return { existenciasPorModelo, modelosOrdenados, almacenes };
}

/* ── HELPER: escribir hoja de análisis ── */
function escribirHojaAnalisis(wb, nombreHoja, colorHeader, tipo){
  const { existenciasPorModelo, modelosOrdenados, almacenes } = construirMapas(tipo);
  const ws = wb.addWorksheet(nombreHoja);
  ws.getColumn(1).width=20; ws.getColumn(2).width=32;
  ws.getColumn(3).width=30; ws.getColumn(4).width=12; ws.getColumn(5).width=14;

  const hdr = ws.addRow(['Marca','Modelo','Almacén','Ventas','Existencias']);
  hdr.font={bold:true,color:{argb:'FFFFFFFF'},size:12};
  hdr.fill={type:'pattern',pattern:'solid',fgColor:{argb:'FF'+colorHeader}};
  hdr.alignment={horizontal:'center',vertical:'middle'}; hdr.height=25;

  modelosOrdenados.forEach(([key, vi])=>{
    const ei = existenciasPorModelo[key] || {porAlmacen:{}};
    almacenes.forEach((almacen, idx)=>{
      const ventas      = vi.porAlmacen[almacen] || 0;
      const existencias = ei.porAlmacen[almacen] || 0;
      const row = ws.addRow([
        idx===0?vi.marca:'', idx===0?vi.modelo:'',
        almacen, ventas, existencias
      ]);
      if(idx%2===0) row.fill={type:'pattern',pattern:'solid',fgColor:{argb:'FFF8FAFC'}};
      row.getCell(1).alignment={vertical:'middle'};
      row.getCell(2).alignment={vertical:'middle'};
      row.getCell(3).alignment={horizontal:'left',vertical:'middle'};
      row.getCell(4).alignment={horizontal:'center',vertical:'middle'};
      row.getCell(5).alignment={horizontal:'center',vertical:'middle'};
      if(idx===0){
        row.getCell(1).font={bold:true,color:{argb:'FF'+colorHeader}};
        row.getCell(2).font={bold:true,color:{argb:'FF'+colorHeader}};
      }
    });
    const tv = vi.total;
    const te = Object.values(ei.porAlmacen).reduce((s,v)=>s+v,0);
    const tr = ws.addRow(['','','TOTAL',tv,te]);
    tr.font={bold:true};
    tr.fill={type:'pattern',pattern:'solid',fgColor:{argb:'FFE0F7FA'}};
    tr.getCell(3).alignment={horizontal:'right',vertical:'middle'};
    tr.getCell(4).alignment={horizontal:'center',vertical:'middle'};
    tr.getCell(5).alignment={horizontal:'center',vertical:'middle'};
    ws.addRow([]);
  });

  ws.eachRow((row,rowNum)=>{
    if(rowNum>0) row.eachCell(cell=>{
      cell.border={
        top:{style:'thin',color:{argb:'FFD0D0D0'}},left:{style:'thin',color:{argb:'FFD0D0D0'}},
        bottom:{style:'thin',color:{argb:'FFD0D0D0'}},right:{style:'thin',color:{argb:'FFD0D0D0'}}
      };
    });
  });
}

/* ─────────────────────────────────────────────────────────────
   GENERAR REPORTE
   ───────────────────────────────────────────────────────────── */
async function generarReporte(){
  const wb = new ExcelJS.Workbook();

  /* Hoja 1 — Smartphones */
  escribirHojaAnalisis(wb, ' Smartphones',   '00838F', 'SMARTPHONE');

  /* Hoja 2 — Equipo Básico */
  escribirHojaAnalisis(wb, ' Equipo Básico', '5C6BC0', 'EQUIPO_BASICO');

  /* ════════════════════════════════════════════════════
     Hoja 3 — Marcas más vendidas
     Columnas: # | Tipo | Marca | Ventas | (subtotal por tipo, ordenado por ventas desc)
     Una fila por combinación marca+tipo, ordenadas por ventas desc
     Al final fila de TOTAL GENERAL
     ════════════════════════════════════════════════════ */

  // Acumular ventas por marca+tipo
  const ventasPorMarcaTipo = {};
  ventasData.forEach(v=>{
    const marca = String(v.marca||"").trim().toUpperCase() || "SIN MARCA";
    const key   = `${marca}||${v.tipoProducto}`;
    ventasPorMarcaTipo[key] = (ventasPorMarcaTipo[key]||0) + v.cantidad;
  });

  // Construir lista y ordenar por ventas desc
  const filas = Object.entries(ventasPorMarcaTipo)
    .map(([key, ventas])=>{
      const [marca, tipo] = key.split('||');
      const tipoLabel = tipo === 'SMARTPHONE' ? ' Smartphone' : ' Equipo Básico';
      return { marca, tipoLabel, ventas };
    })
    .sort((a,b)=> b.ventas - a.ventas);

  const ws3 = wb.addWorksheet(' Marcas más Vendidas');
  ws3.getColumn(1).width = 6;   // #
  ws3.getColumn(2).width = 18;  // Tipo
  ws3.getColumn(3).width = 26;  // Marca
  ws3.getColumn(4).width = 14;  // Ventas

  // Título
  ws3.addRow([]);
  const tituloRow = ws3.addRow(['',' RANKING DE MARCAS MÁS VENDIDAS','','']);
  ws3.mergeCells(`B${tituloRow.number}:D${tituloRow.number}`);
  tituloRow.getCell(2).font      = {bold:true, size:15, color:{argb:'FF006064'}};
  tituloRow.getCell(2).alignment = {horizontal:'center', vertical:'middle'};
  tituloRow.height = 32;
  ws3.addRow([]);

  // Encabezados
  const hdr3 = ws3.addRow(['#','Tipo','Marca','Ventas']);
  hdr3.font      = {bold:true, color:{argb:'FFFFFFFF'}, size:11};
  hdr3.fill      = {type:'pattern', pattern:'solid', fgColor:{argb:'FF00838F'}};
  hdr3.alignment = {horizontal:'center', vertical:'middle'};
  hdr3.height    = 26;

  const topColores = ['FFFFD700','FFC0C0C0','FFCD7F32'];

  filas.forEach(({ marca, tipoLabel, ventas }, idx)=>{
    const row = ws3.addRow([idx+1, tipoLabel, marca, ventas]);

    if(idx < 3){
      row.fill = {type:'pattern', pattern:'solid', fgColor:{argb:topColores[idx]}};
      row.font = {bold:true, size:11, color:{argb:'FF1A1A1A'}};
    } else {
      row.fill = {type:'pattern', pattern:'solid',
                  fgColor:{argb: idx%2===0 ? 'FFF8FAFC' : 'FFFFFFFF'}};
      row.font = {size:11};
    }

    row.getCell(1).alignment = {horizontal:'center', vertical:'middle'};
    row.getCell(2).alignment = {horizontal:'center', vertical:'middle'};
    row.getCell(3).alignment = {horizontal:'left',   vertical:'middle'};
    row.getCell(4).alignment = {horizontal:'center', vertical:'middle'};
    row.height = 22;
  });

  // Total general
  ws3.addRow([]);
  const grandTotal    = filas.reduce((s,f)=>s+f.ventas, 0);
  const totalFinalRow = ws3.addRow(['','','TOTAL GENERAL', grandTotal]);
  totalFinalRow.font = {bold:true, size:12, color:{argb:'FFFFFFFF'}};
  totalFinalRow.fill = {type:'pattern', pattern:'solid', fgColor:{argb:'FF006064'}};
  totalFinalRow.getCell(3).alignment = {horizontal:'right',  vertical:'middle'};
  totalFinalRow.getCell(4).alignment = {horizontal:'center', vertical:'middle'};
  totalFinalRow.height = 26;

  // Bordes hoja 3
  ws3.eachRow((row,rowNum)=>{
    if(rowNum>0) row.eachCell(cell=>{
      cell.border={
        top:{style:'thin',color:{argb:'FFD0D0D0'}},left:{style:'thin',color:{argb:'FFD0D0D0'}},
        bottom:{style:'thin',color:{argb:'FFD0D0D0'}},right:{style:'thin',color:{argb:'FFD0D0D0'}}
      };
    });
  });

  /* ── Descargar ── */
  const buffer = await wb.xlsx.writeBuffer();
  const a = document.createElement('a');
  a.href     = URL.createObjectURL(new Blob([buffer]));
  a.download = `Analisis_Celulares_${new Date().toISOString().split('T')[0]}.xlsx`;
  a.click();
}

document.getElementById('menu-check').addEventListener('change', function(){
  const m = document.getElementById('nav-menu');
  m.style.opacity       = this.checked ? '1' : '0';
  m.style.visibility    = this.checked ? 'visible' : 'hidden';
  m.style.pointerEvents = this.checked ? 'auto' : 'none';
});
</script>
</body>
</html>