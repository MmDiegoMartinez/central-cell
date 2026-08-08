<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Análisis Ventas vs Existencias — Celulares TECNOLOGIA MOVIL</title>
<link rel="stylesheet" href="../styles.css">
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>

<style>
  /* ---- Ajustes puntuales que el CSS base no cubre (no se toca styles.css) ---- */
  #existenciasFile,
  #ventasFile{
    position:absolute;width:1px;height:1px;padding:0;margin:-1px;
    overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;
  }

  .file-status{
    font-size:13px;
    color:var(--on-surface-variant);
    margin-top:10px;
    display:flex;align-items:center;gap:6px;
  }
  .file-status.success{color:var(--secondary);font-weight:600;}
  .method-card .btn{margin-top:2px;}
  .method-card.loaded{border-color:var(--secondary);background:rgba(109,245,225,0.10);}

  .loader{
    display:none;
    align-items:center;justify-content:center;gap:var(--space-md);
    padding:var(--space-lg);margin-top:var(--space-lg);
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

  #analyzeBtn:disabled{opacity:.5;cursor:not-allowed;transform:none;}

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

    <a href="analisis_general.php" class="sidebar-link active">
      <span class="material-symbols-outlined">swap_horiz</span>
      Ventas vs Existencias
    </a>

    <a href="celularesstock.php" class="sidebar-link">
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

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===================== MAIN ===================== -->
<div class="main">

  <header class="topheader">
    <div class="topheader-left">
      <button class="menu-toggle" id="menuToggle" type="button" aria-label="Abrir menú">
        <span class="material-symbols-outlined">menu</span>
      </button>
      <h2 class="text-headline-sm" style="margin:0">Análisis Ventas vs Existencias</h2>
    </div>
  </header>

  <div class="container">
    <div class="lesson">
      <div class="lesson-body">

        <span class="eyebrow">Reportes</span>
        <h1 class="text-headline-lg" style="margin:6px 0 0">Análisis Ventas vs Existencias — Celulares</h1>
        <div class="lesson-meta">
          <span><span class="material-symbols-outlined" style="font-size:16px">smartphone</span> Tecnología Móvil</span>
        </div>

        <div class="intro-panel">
          <div class="icon-badge"><span class="material-symbols-outlined">insights</span></div>
          <div>
            <h3 style="margin:0">Genera tu reporte comparativo</h3>
            <p>Carga los archivos de existencias y ventas para construir automáticamente el Excel comparativo de Smartphones y Equipo Básico por modelo y almacén, además del ranking de marcas más vendidas.</p>
          </div>
        </div>

        <!-- Paso 1: Carga de archivos -->
        <section class="step-section">
          <div class="step-head">
            <div class="step-num">1</div>
            <h3 class="step-title text-headline-sm">Carga tus archivos</h3>
          </div>

          <div class="method-grid">
            <div class="method-card" id="existenciasCard">
              <h4><span class="material-symbols-outlined">inventory_2</span> Archivo de Existencias</h4>
              <input type="file" id="existenciasFile" accept=".xlsx,.xls">
              <button class="btn btn-outline btn-block" id="existenciasBtn" type="button">
                <span class="material-symbols-outlined">upload_file</span>
                Seleccionar Archivo de Existencias
              </button>
              <div class="file-status" id="existenciasStatus">
                <span class="material-symbols-outlined" style="font-size:16px">schedule</span>
                Esperando archivo...
              </div>
            </div>

            <div class="method-card" id="ventasCard">
              <h4><span class="material-symbols-outlined">shopping_bag</span> Archivo de Ventas</h4>
              <input type="file" id="ventasFile" accept=".xlsx,.xls">
              <button class="btn btn-outline btn-block" id="ventasBtn" type="button">
                <span class="material-symbols-outlined">upload_file</span>
                Seleccionar Archivo de Ventas
              </button>
              <div class="file-status" id="ventasStatus">
                <span class="material-symbols-outlined" style="font-size:16px">schedule</span>
                Esperando archivo...
              </div>
            </div>
          </div>
        </section>

        <!-- Paso 2: Generar reporte -->
        <section class="step-section">
          <div class="step-head">
            <div class="step-num">2</div>
            <h3 class="step-title text-headline-sm">Genera el reporte</h3>
          </div>

          <button class="btn btn-primary btn-block" id="analyzeBtn" disabled type="button">
            <span class="material-symbols-outlined">rocket_launch</span>
            Generar Reporte Completo
          </button>

          <div class="loader" id="loader">
            <div class="spinner"></div>
            <div class="loader-text">Generando reporte Excel...</div>
          </div>
        </section>

        <!-- Instrucciones -->
        <div class="takeaway">
          <div class="icon-badge"><span class="material-symbols-outlined">checklist</span></div>
          <div>
            <h4>Instrucciones</h4>
            <ul class="step-list check">
              <li>Carga el archivo de <strong>Existencias</strong> (inventario actual)</li>
              <li>Carga el archivo de <strong>Ventas</strong></li>
              <li>Presiona el botón para generar el reporte automáticamente</li>
              <li>El Excel incluirá 3 hojas: Smartphones, Equipo Básico y Ranking de marcas más vendidas</li>
              <li>Los modelos están ordenados por ventas (mayor a menor)</li>
              <li>Si un almacén no vendió, aparecerá 0 en ventas</li>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </div>

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
    existenciasStatus.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px">check_circle</span> ' + existenciasData.length + ' registros cargados';
    existenciasStatus.classList.add('success');
    existenciasBtn.classList.add('loaded');
    document.getElementById('existenciasCard').classList.add('loaded');
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
    ventasStatus.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px">check_circle</span> ' + ventasData.length + ' registros cargados';
    ventasStatus.classList.add('success');
    ventasBtn.classList.add('loaded');
    document.getElementById('ventasCard').classList.add('loaded');
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
</script>

<script>
  // Control del sidebar en móvil (equivalente visual al menú hamburguesa anterior)
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