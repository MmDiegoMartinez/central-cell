<?php
include_once '../funciones.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Análisis de Fundas - INNOVACION MOVIL</title>

<link rel="stylesheet" href="../styles.css">

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<style>

#inputFile{
    position:absolute;
    width:1px;
    height:1px;
    padding:0;
    margin:-1px;
    overflow:hidden;
    clip:rect(0,0,0,0);
    white-space:nowrap;
    border:0;
}

.file-status{
    display:flex;
    align-items:center;
    gap:8px;
    margin-top:12px;
    font-size:14px;
    color:var(--on-surface-variant);
}

.method-card.loaded{
    border-color:var(--secondary);
    background:rgba(109,245,225,0.10);
}

.loader{
    display:none;
    align-items:center;
    justify-content:center;
    gap:16px;
    padding:20px;
    margin-top:20px;
    background:var(--surface-container-low);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
}

.spinner{
    width:22px;
    height:22px;
    border-radius:50%;
    border:3px solid var(--outline-variant);
    border-top-color:var(--primary);
    animation:spin .8s linear infinite;
}

@keyframes spin{
to{
transform:rotate(360deg);
}
}

.loader-text{
    font-size:14px;
    font-weight:600;
}

.filters-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:16px;
    margin-top:8px;
}

.filters-grid label{
    display:block;
    margin-bottom:6px;
    font-weight:600;
}

.filters-grid select{
    width:100%;
}

#tablesContainer{
    margin-top:20px;
    display:flex;
    flex-direction:column;
    gap:28px;
}

#tablesContainer table{
    width:100%;
    border-collapse:collapse;
    background:var(--surface);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    overflow:hidden;
}

#tablesContainer caption{
    padding:16px 18px;
    text-align:left;
    font-weight:700;
    font-size:17px;
    background:var(--surface-container-low);
}

#tablesContainer th{
    background:var(--primary);
    color:#fff;
    padding:12px;
    text-align:left;
}

#tablesContainer td{
    padding:10px 12px;
    border-bottom:1px solid var(--outline-variant);
}

#tablesContainer tr:nth-child(even){
    background:var(--surface-container-low);
}

.sidebar-brand-logo{
    display:flex;
    align-items:center;
    gap:10px;
}

.sidebar-brand-logo img{
    border-radius:8px;
}

</style>

</head>

<body>

<!-- ===================== SIDEBAR ===================== -->

<aside class="sidebar" id="sidebar">

<div class="sidebar-head">

<div class="sidebar-brand-logo">

<img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png"
alt="Logo"
width="32"
height="32">

<div>

<p class="sidebar-brand text-headline-sm">
Central Cell
</p>

<p class="sidebar-sub text-label-sm">
Panel de Análisis
</p>

</div>

</div>

<button
class="sidebar-close"
id="sidebarClose"
type="button"
aria-label="Cerrar menú">

<span class="material-symbols-outlined">
close
</span>

</button>

</div>

<nav class="sidebar-nav">

<p class="sidebar-label">
Navegación
</p>

<a href="../garantias/validador/validador.php" class="sidebar-link">

<span class="material-symbols-outlined">
home
</span>

Home

</a>

<a href="index.php" class="sidebar-link">

<span class="material-symbols-outlined">
dashboard
</span>

Panel de Herramientas

</a>

<a href="analisis_fundas_ventas_existencias.php" class="sidebar-link">

<span class="material-symbols-outlined">
analytics
</span>

Ventas vs Existencias

</a>

<a href="fundasstock.php" class="sidebar-link">

<span class="material-symbols-outlined">
inventory_2
</span>

Distribución de Fundas

</a>

<a href="ventasfundas.php" class="sidebar-link">

<span class="material-symbols-outlined">
shopping_bag
</span>

Ventas por Modelo

</a>
<a href="analisis_fundas.php" class="sidebar-link active">
      <span class="material-symbols-outlined">sell</span>
      Ventas Por Marca
    </a>
</nav>

<div class="sidebar-foot">

<p class="text-label-sm"
style="color:var(--outline);">

Innovación Móvil

</p>

</div>

</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===================== MAIN ===================== -->

<div class="main">

<header class="topheader">

<div class="topheader-left">

<button
class="menu-toggle"
id="menuToggle"
type="button"
aria-label="Abrir menú">

<span class="material-symbols-outlined">
menu
</span>

</button>

<h2 class="text-headline-sm" style="margin:0;">
Análisis de Fundas
</h2>

</div>

</header>

<div class="container">

<div class="lesson">

<div class="lesson-body">

<span class="eyebrow">
Reportes
</span>

<h1 class="text-headline-lg" style="margin:6px 0 0;">
Análisis de Fundas
</h1>

<div class="lesson-meta">

<span>

<span class="material-symbols-outlined" style="font-size:16px">
sell
</span>

Innovación Móvil

</span>

</div>

<div class="intro-panel">

<div class="icon-badge">

<span class="material-symbols-outlined">
insights
</span>

</div>

<div>

<h3 style="margin:0">
Genera el ranking de ventas
</h3>

<p>
Carga el archivo de ventas para obtener automáticamente el ranking
de fundas por marca, modelo y los totales agrupados por marca.
</p>

</div>

</div>

<!-- Paso 1: Selección de archivo -->

<section class="step-section">

<div class="step-head">

<div class="step-num">
1
</div>

<h3 class="step-title text-headline-sm">
Selecciona el archivo
</h3>

</div>

<div class="method-grid">

<div class="method-card" id="fileCard">

<h4>

<span class="material-symbols-outlined">
upload_file
</span>

Archivo Excel

</h4>

<input id="inputFile" type="file" accept=".xlsx,.xls">

<button class="btn btn-outline btn-block" id="fileButton" type="button">

<span class="material-symbols-outlined">
upload_file
</span>

Seleccionar Archivo

</button>

</div>

</div>

</section>

<!-- Paso 2: Procesamiento -->

<section class="step-section">

<div class="step-head">

<div class="step-num">
2
</div>

<h3 class="step-title text-headline-sm">
Procesamiento
</h3>

</div>

<button id="procesarBtn" class="btn btn-primary btn-block" disabled>

<span class="material-symbols-outlined">
play_arrow
</span>

Procesar Archivo

</button>

<br>

<button id="descargarBtn" class="btn btn-outline btn-block" disabled>

<span class="material-symbols-outlined">
download
</span>

Descargar Excel

</button>

<div class="loader" id="loader">

<div class="spinner"></div>

<div class="loader-text">
Procesando información...
</div>

</div>

<div id="mensajes" class="file-status"></div>

</section>

<!-- Paso 3: Filtros -->

<section class="step-section">

<div class="step-head">

<div class="step-num">
3
</div>

<h3 class="step-title text-headline-sm">
Filtrar información
</h3>

</div>

<div id="filtros" class="filters-grid" style="display:none;">

<div>

<label for="tipoFiltro">
Tipo
</label>

<select id="tipoFiltro" class="input">
<option value="TODOS">Todos</option>
<option value="CELULAR">Solo CELULAR</option>
<option value="TABLET">Solo TABLET</option>
</select>

</div>

<div>

<label for="almacenFiltro">
Almacén
</label>

<select id="almacenFiltro" class="input"></select>

</div>

<div>

<label for="marcaFiltro">
Marca
</label>

<select id="marcaFiltro" class="input"></select>

</div>

</div>

</section>

<!-- Paso 4: Resultados -->

<section class="step-section">

<div class="step-head">

<div class="step-num">
4
</div>

<h3 class="step-title text-headline-sm">
Resultados
</h3>

</div>

<div id="tablesContainer"></div>

</section>

<div class="takeaway">

<div class="icon-badge">

<span class="material-symbols-outlined">
tips_and_updates
</span>

</div>

<div>

<h4>
Instrucciones
</h4>

<ul class="step-list check">

<li>
Selecciona el archivo Excel de ventas.
</li>

<li>
Presiona <strong>Procesar Archivo</strong>.
</li>

<li>
Utiliza los filtros para limitar la información por tipo, almacén o marca.
</li>

<li>
Descarga el reporte en Excel cuando el procesamiento finalice.
</li>

</ul>

</div>

</div>

</div>

</div>

</div>



</div>

<script>
const inputFile = document.getElementById("inputFile");
const procesarBtn = document.getElementById("procesarBtn");
const descargarBtn = document.getElementById("descargarBtn");
const mensajes = document.getElementById("mensajes");
const tablesContainer = document.getElementById("tablesContainer");
const tipoFiltro = document.getElementById("tipoFiltro");
const almacenFiltro = document.getElementById("almacenFiltro");
const marcaFiltro = document.getElementById("marcaFiltro");
const filtros = document.getElementById("filtros");

let dataFiltradaOriginal = [];
let resultados = [];
let totalesPorMarca = [];

inputFile.addEventListener('change', ()=>{
  procesarBtn.disabled = !inputFile.files.length;
  mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : "";
  document.getElementById('fileCard').classList.toggle('loaded', !!inputFile.files.length);
});

procesarBtn.addEventListener("click", () => {
  if (inputFile.files.length) leerExcel(inputFile.files[0]);
});

descargarBtn.addEventListener("click", () => descargarExcel());

tipoFiltro.addEventListener("change", aplicarFiltro);
almacenFiltro.addEventListener("change", aplicarFiltro);
marcaFiltro.addEventListener("change", aplicarFiltro);

function leerExcel(file) {
  mensajes.innerText = 'Leyendo archivo...';
  document.getElementById('loader').style.display = 'flex';
  const reader = new FileReader();
  reader.onload = e => {
    const data = new Uint8Array(e.target.result);
    const wb = XLSX.read(data, { type: "array" });
    const sheet = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "" });
    if (!rows.length) {
      mensajes.innerText = "El archivo está vacío.";
      document.getElementById('loader').style.display = 'none';
      return;
    }

    const idxAlmacen = 0;  // A
    const idxN1 = 1;       // B
    const idxN3 = 3;       // D
    const idxCantidad = 14;// O
    const idxMarca = 20;   // U
    const idxModelo = 21;  // V

    let filtradas = [];
    for (let i = 1; i < rows.length; i++) {
      const r = rows[i];
      const n1 = String(r[idxN1]).trim().toUpperCase();
      const n3 = String(r[idxN3]).trim().toUpperCase();
      if (n1 === "INNOVACION MOVIL" && (n3 === "CELULAR" || n3 === "TABLET")) {
        const almacen = (r[idxAlmacen] && r[idxAlmacen].toString().trim()) || "SIN ALMACEN";
        const marca = (r[idxMarca] && r[idxMarca].toString().trim()) || "SIN MARCA";
        const modelo = (r[idxModelo] && r[idxModelo].toString().trim()) || "SIN MODELO";
        const cantidad = Number(r[idxCantidad]) || 0;
        filtradas.push({ almacen, tipo: n3, marca, modelo, cantidad });
      }
    }

    dataFiltradaOriginal = filtradas;

    // Construir listas de filtros únicos
    const almacenes = [...new Set(filtradas.map(f => f.almacen))].sort();
    const marcas = [...new Set(filtradas.map(f => f.marca))].sort();

    almacenFiltro.innerHTML = `<option value="TODOS">Todos</option>` +
      almacenes.map(a => `<option value="${a}">${a}</option>`).join("");
    marcaFiltro.innerHTML = `<option value="TODAS">Todas</option>` +
      marcas.map(m => `<option value="${m}">${m}</option>`).join("");

    filtros.style.display = "grid";
    aplicarFiltro();
    descargarBtn.disabled = false;
    document.getElementById('loader').style.display = 'none';
  };
  reader.readAsArrayBuffer(file);
}

function aplicarFiltro() {
  let tipo = tipoFiltro.value;
  let alm = almacenFiltro.value;
  let marcaSel = marcaFiltro.value;

  let data = dataFiltradaOriginal.filter(f =>
    (tipo === "TODOS" || f.tipo === tipo) &&
    (alm === "TODOS" || f.almacen === alm) &&
    (marcaSel === "TODAS" || f.marca === marcaSel)
  );

  mensajes.innerText = `Filtradas ${data.length} filas (${tipo}) — ${alm}`;

  // Agrupar por marca + modelo
  const mapa = {};
  data.forEach(f => {
    const clave = `${f.marca}||${f.modelo}`;
    if (!mapa[clave]) mapa[clave] = 0;
    mapa[clave] += f.cantidad;
  });
  resultados = Object.entries(mapa).map(([clave, total]) => {
    const [marca, modelo] = clave.split("||");
    return { Marca: marca, Modelo: modelo, TotalVendidas: total };
  }).sort((a, b) => b.TotalVendidas - a.TotalVendidas);

  // Agrupar por marca
  const mapaMarca = {};
  data.forEach(f => {
    if (!mapaMarca[f.marca]) mapaMarca[f.marca] = 0;
    mapaMarca[f.marca] += f.cantidad;
  });
  totalesPorMarca = Object.entries(mapaMarca).map(([marca, total]) => ({
    Marca: marca,
    TotalVendidas: total
  })).sort((a, b) => b.TotalVendidas - a.TotalVendidas);

  mostrarTablas();
}

function mostrarTablas() {
  let html = "";

  html += `
  <table>
    <caption>Ranking de Fundas por Marca y Modelo</caption>
    <thead>
      <tr><th>#</th><th>Marca</th><th>Modelo</th><th>Total Vendidas</th></tr>
    </thead><tbody>`;
  resultados.forEach((r, i) => {
    html += `<tr><td>${i + 1}</td><td>${r.Marca}</td><td>${r.Modelo}</td><td>${r.TotalVendidas}</td></tr>`;
  });
  html += `<tr style="font-weight:bold;background:#eef;">
      <td colspan="3">TOTAL GENERAL</td>
      <td>${resultados.reduce((sum, r) => sum + r.TotalVendidas, 0)}</td>
  </tr></tbody></table>`;

  html += `
  <table>
    <caption>Totales por Marca</caption>
    <thead><tr><th>#</th><th>Marca</th><th>Total Vendidas</th></tr></thead><tbody>`;
  totalesPorMarca.forEach((r, i) => {
    html += `<tr><td>${i + 1}</td><td>${r.Marca}</td><td>${r.TotalVendidas}</td></tr>`;
  });
  html += `<tr style="font-weight:bold;background:#eef;">
      <td colspan="2">TOTAL GENERAL</td>
      <td>${totalesPorMarca.reduce((sum, r) => sum + r.TotalVendidas, 0)}</td>
  </tr></tbody></table>`;

  tablesContainer.innerHTML = html;
}

function descargarExcel() {
  if (!resultados.length) return;
  const wb = XLSX.utils.book_new();
  const ws1 = XLSX.utils.json_to_sheet(resultados);
  const ws2 = XLSX.utils.json_to_sheet(totalesPorMarca);
  XLSX.utils.book_append_sheet(wb, ws1, "Fundas_Marca_Modelo");
  XLSX.utils.book_append_sheet(wb, ws2, "Totales_Por_Marca");
  XLSX.writeFile(wb, "Analisis_Fundas_Completo.xlsx");
}
</script>

<script>
/* Botón seleccionar archivo */
document.getElementById("fileButton").addEventListener("click", () => {
  document.getElementById("inputFile").click();
});

/* Control del sidebar */
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