<?php
include_once '../funciones.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<title>📊 Análisis de Fundas — INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<style>
body {
  font-family: 'Segoe UI', Arial, sans-serif;
  margin: 20px;
  background: #f5f7fa;
  color: #333;
}
h1 { margin-top: 0; color: #2f6fa6; }
.controls {
  display: flex; gap: 12px; align-items: center; margin-bottom: 15px; flex-wrap: wrap;
}
input[type=file] {
  padding: 6px; border: 1px solid #ccc; border-radius: 4px;
}
button.btn {
  background: #2f6fa6; color: white; border: none; padding: 8px 14px;
  border-radius: 6px; cursor: pointer; font-weight: 600;
}
button.btn:disabled {
  background: #999; cursor: not-allowed;
}
table {
  border-collapse: collapse;
  width: 100%;
  background: white;
  box-shadow: 0 1px 4px rgba(0,0,0,0.1);
  margin-top: 15px;
}
th, td {
  padding: 8px 10px;
  border: 1px solid #ddd;
  text-align: left;
}
th {
  background: #2f6fa6;
  color: white;
  text-align: center;
}
caption {
  text-align: left;
  font-weight: bold;
  padding: 8px;
  color: #2f6fa6;
}
.note {
  font-size: 13px;
  color: #444;
  margin-top: 8px;
}
select {
  padding: 6px;
  border-radius: 6px;
  border: 1px solid #bbb;
  font-weight: 500;
}
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 10px;
  background: #fff;
  padding: 10px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
</style>
<link rel="stylesheet" href="estilos.css">
</head>
<body>
  <header>
  <nav>
    <ul id="menu">
       <li><a href="analisis_fundas_ventas_existencias.php">Análisis de Ventas vs Existencias</a></li>
        <li><a href="fundasstock.php">Distribucion Por Modelo Fundas</a></li>
        <li><a href="ventasfundas.php">Ventas Por Modelo Fundas</a></li>
    </ul>
  </nav>
</header>
<div class="container">
<h1>📊 Análisis de Fundas — INNOVACION MOVIL</h1>

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
<div class="center-container">
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

<div id="filtros" class="filters" style="display:none;">
  <label><b>Tipo:</b></label>
  <select id="tipoFiltro">
    <option value="TODOS">Todos</option>
    <option value="CELULAR">Solo CELULAR</option>
    <option value="TABLET">Solo TABLET</option>
  </select>

  <label><b>Almacén:</b></label>
  <select id="almacenFiltro"></select>

  <label><b>Marca:</b></label>
  <select id="marcaFiltro"></select>
</div>

<div id="tablesContainer"></div>
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

inputFile.addEventListener('change', ()=>{ procesarBtn.disabled = !inputFile.files.length; mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : ""; });

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
      mensajes.innerText = "⚠️ El archivo está vacío.";
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

    filtros.style.display = "flex";
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

</body>
</html>