<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Análisis de Protectores — INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

<link rel="stylesheet" href="../styles.css">

<style>
  /* ---- Ajustes puntuales que el CSS base no cubre (no se toca styles.css) ---- */
  #inputFile{
    position:absolute;width:1px;height:1px;padding:0;margin:-1px;
    overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;
  }

  /* Navbar horizontal */
  .navbar{
    position:sticky;top:0;z-index:50;
    display:flex;align-items:center;justify-content:space-between;
    gap:var(--space-md);
    padding:12px var(--space-lg);
    background:var(--surface);
    border-bottom:1px solid var(--outline-variant);
  }
  .navbar-brand{
    display:flex;align-items:center;gap:10px;
    text-decoration:none;color:var(--on-surface);
  }
  .navbar-brand img{border-radius:6px;}
  .navbar-brand-text p{margin:0;line-height:1.2;}
  .navbar-links{
    display:flex;align-items:center;gap:6px;flex-wrap:wrap;
  }
  .navbar-link{
    display:flex;align-items:center;gap:6px;
    padding:8px 14px;border-radius:var(--radius-lg);
    color:var(--on-surface-variant);text-decoration:none;
    font-size:14px;font-weight:600;
    transition:all .15s ease;
  }
  .navbar-link .material-symbols-outlined{font-size:20px;}
  .navbar-link:hover{background:var(--surface-container-low);color:var(--primary);}
  .navbar-link.active{background:var(--primary);color:var(--on-primary,#fff);}
  .navbar-toggle{display:none;}

  @media (max-width:720px){
    .navbar-links{
      display:none;flex-direction:column;align-items:stretch;
      position:absolute;top:100%;left:0;right:0;
      background:var(--surface);border-bottom:1px solid var(--outline-variant);
      padding:var(--space-sm);gap:4px;
    }
    .navbar-links.open{display:flex;}
    .navbar-toggle{
      display:flex;align-items:center;justify-content:center;
      background:none;border:none;cursor:pointer;color:var(--on-surface);
    }
  }

  .controls{
    display:flex;flex-wrap:wrap;gap:var(--space-md);align-items:center;
    margin-bottom:var(--space-md);
  }
  .controls .btn{margin:0;}
  .btn:disabled{opacity:.5;cursor:not-allowed;}

  .loader{
    display:none;
    align-items:center;justify-content:center;gap:var(--space-md);
    padding:var(--space-lg);margin-bottom:var(--space-lg);
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

  .file-status{
    font-size:13px;color:var(--on-surface-variant);
    margin-top:10px;display:flex;align-items:center;gap:6px;
  }

  .tables{display:flex;flex-direction:column;gap:var(--space-lg);}
  .table-wrap{overflow-x:auto;}
  .data-table{
    width:100%;border-collapse:collapse;font-size:13px;
    background:var(--surface);border-radius:var(--radius-lg);overflow:hidden;
  }
  .data-table caption{
    text-align:left;font-weight:700;font-size:15px;
    padding:0 0 10px;color:var(--on-surface);
  }
  .data-table thead th{
    background:#00838F;color:#fff;font-weight:700;
    padding:10px 12px;text-align:left;white-space:nowrap;
  }
  .data-table tbody td{
    padding:8px 12px;border-bottom:1px solid var(--outline-variant);
    white-space:nowrap;
  }
  .data-table tbody tr:nth-child(even){background:var(--surface-container-low);}
  .data-table tbody tr:last-child td{font-weight:700;background:rgba(0,131,143,0.10);}
</style>
</head>

<body>

<!-- ===================== NAVBAR HORIZONTAL ===================== -->
<header class="navbar">
  <a href="../garantias/validador/validador.php" class="navbar-brand">
    <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="32" height="32">
    <div class="navbar-brand-text">
      <p class="text-headline-sm">Central Cell</p>
      <p class="text-label-sm" style="color:var(--outline)">Innovación Móvil</p>
    </div>
  </a>

  <button class="navbar-toggle" id="navToggle" type="button" aria-label="Abrir menú">
    <span class="material-symbols-outlined">menu</span>
  </button>

  <nav class="navbar-links" id="navLinks">
    <a href="../garantias/validador/validador.php" class="navbar-link">
      <span class="material-symbols-outlined">home</span>
      Home
    </a>
    <a href="modulos.html" class="navbar-link">
      <span class="material-symbols-outlined">bar_chart</span>
      Panel de Herramientas
    </a>
  </nav>
</header>

<!-- ===================== MAIN ===================== -->
<div class="container">
  <div class="lesson">
    <div class="lesson-body">

      <span class="eyebrow">Reportes</span>
      <h1 class="text-headline-lg" style="margin:6px 0 0">Análisis de Protectores — INNOVACION MOVIL</h1>
      <div class="lesson-meta">
        <span><span class="material-symbols-outlined" style="font-size:16px">shield</span> Innovación Móvil</span>
      </div>

      <div class="intro-panel">
        <div class="icon-badge"><span class="material-symbols-outlined">insights</span></div>
        <div>
          <h3 style="margin:0">Genera tu reporte de protectores</h3>
          <p>Carga el archivo de ventas, procésalo para obtener el resumen por almacén y por vendedor, y descarga los resultados en Excel.</p>
        </div>
      </div>

      <!-- Paso 1: Carga de archivo -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">1</div>
          <h3 class="step-title text-headline-sm">Carga tu archivo</h3>
        </div>

        <div class="controls">
          <input type="file" id="inputFile" accept=".xlsx,.xls">
          <button class="btn btn-outline" id="fileButton" type="button">
            <span class="material-symbols-outlined">upload_file</span>
            Seleccionar Archivo
          </button>
          <button id="procesarBtn" class="btn btn-primary" disabled>
            <span class="material-symbols-outlined">rocket_launch</span>
            Procesar archivo
          </button>
          <button id="descargarBtn" class="btn btn-secondary" disabled>
            <span class="material-symbols-outlined">download</span>
            Descargar resultados
          </button>
        </div>

        <div class="loader" id="loader">
          <div class="spinner"></div>
          <div class="loader-text">Procesando archivo...</div>
        </div>

        <div id="mensajes" class="file-status"></div>
      </section>

      <!-- Paso 2: Resultados -->
      <section class="step-section">
        <div class="step-head">
          <div class="step-num">2</div>
          <h3 class="step-title text-headline-sm">Resultados</h3>
        </div>

        <div class="tables">
          <div id="tablaAlmacenes" class="table-wrap"></div>
          <div id="tablaVendedores" class="table-wrap"></div>
        </div>
      </section>

    </div>
  </div>
</div>

<script>
document.getElementById("fileButton").addEventListener("click", () => {
  document.getElementById("inputFile").click();
});

let registros = [];
let resumenAlmacenes = [];
let resumenVendedores = [];

const inputFile = document.getElementById('inputFile');
const procesarBtn = document.getElementById('procesarBtn');
const descargarBtn = document.getElementById('descargarBtn');
const mensajes = document.getElementById('mensajes');
const tablaAlmacenesDiv = document.getElementById('tablaAlmacenes');
const tablaVendedoresDiv = document.getElementById('tablaVendedores');

inputFile.addEventListener('change', () => {
  procesarBtn.disabled = !inputFile.files.length;
  mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : "";
});

procesarBtn.addEventListener('click', () => {
  if (!inputFile.files.length) return;
  leerExcel(inputFile.files[0]);
});

descargarBtn.addEventListener('click', descargaResultados);

function leerExcel(file) {
  mensajes.innerText = 'Leyendo archivo...';
  document.getElementById('loader').classList.add('active');
  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target.result);
    const wb = XLSX.read(data, { type: 'array' });
    const sheet = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "" });

    if (!rows || rows.length < 2) {
      mensajes.innerText = 'Archivo vacío o sin datos.';
      return;
    }

    const headerRow = rows[0].map(h => String(h || "").trim());
    const idx = {
      almacen: headerRow.findIndex(h => /almacen/i.test(h)),
      n1: headerRow.findIndex(h => /n1/i.test(h)),
      n3: headerRow.findIndex(h => /n3/i.test(h)),
      tipoProducto: headerRow.findIndex(h => /tipoproducto/i.test(h)),
      cantidad: headerRow.findIndex(h => /cantidad/i.test(h)),
      precio: headerRow.findIndex(h => /precioventa/i.test(h)),
      vendedor: headerRow.findIndex(h => /vendedor/i.test(h))
    };

    const dataObjs = [];
    for (let r = 1; r < rows.length; r++) {
      const row = rows[r];
      if (row[idx.n1] === "INNOVACION MOVIL" && row[idx.n3] === "PROTECTOR") {
        dataObjs.push({
          almacen: row[idx.almacen] || "(SIN ALMACÉN)",
          tipoProducto: row[idx.tipoProducto] || "(SIN CATEGORÍA)",
          cantidad: Number(row[idx.cantidad] || 0),
          precio: Number(row[idx.precio] || 0),
          vendedor: row[idx.vendedor] || "(SIN VENDEDOR)"
        });
      }
    }

    registros = dataObjs;
    mensajes.innerText = `Filtradas ${registros.length} filas. Procesando...`;
    procesarDatos();
    document.getElementById('loader').classList.remove('active');
  };
  reader.readAsArrayBuffer(file);
}

function procesarDatos() {
  const categoriasSet = new Set(registros.map(r=>r.tipoProducto));
  const categorias = Array.from(categoriasSet).sort();

  // 1️⃣ Resumen por almacén
  const almacenesMap = {};
  registros.forEach(r => {
    if (!almacenesMap[r.almacen]) almacenesMap[r.almacen] = {};
    categorias.forEach(cat => {
      if (!almacenesMap[r.almacen][cat]) almacenesMap[r.almacen][cat] = { cantidad:0, totalVenta:0 };
    });
    almacenesMap[r.almacen][r.tipoProducto].cantidad += r.cantidad;
    almacenesMap[r.almacen][r.tipoProducto].totalVenta += r.cantidad*r.precio;
  });

  resumenAlmacenes = [];
  const totalesAlmacenes = {};
  categorias.forEach(cat => totalesAlmacenes[cat] = { cantidad:0, totalVenta:0 });

  Object.entries(almacenesMap).forEach(([almacen, cats]) => {
    const obj = { Almacen: almacen };
    categorias.forEach(cat => {
      obj[cat + " Cantidad"] = cats[cat].cantidad || 0;
      obj[cat + " Venta"] = cats[cat].totalVenta || 0;
      totalesAlmacenes[cat].cantidad += cats[cat].cantidad || 0;
      totalesAlmacenes[cat].totalVenta += cats[cat].totalVenta || 0;
    });
    resumenAlmacenes.push(obj);
  });

  // Agregar fila total general de almacenes
  const totalObj = { Almacen: "TOTAL GENERAL" };
  categorias.forEach(cat => {
    totalObj[cat + " Cantidad"] = totalesAlmacenes[cat].cantidad;
    totalObj[cat + " Venta"] = totalesAlmacenes[cat].totalVenta;
  });
  resumenAlmacenes.push(totalObj);

  // 2️⃣ Resumen por vendedor
  const vendedoresMap = {};
  registros.forEach(r => {
    if (!vendedoresMap[r.vendedor]) vendedoresMap[r.vendedor] = { sucursales: {}, totalCategorias: {} };
    if (!vendedoresMap[r.vendedor].sucursales[r.almacen]) vendedoresMap[r.vendedor].sucursales[r.almacen] = 0;
    vendedoresMap[r.vendedor].sucursales[r.almacen] += r.cantidad;

    categorias.forEach(cat => {
      if (!vendedoresMap[r.vendedor].totalCategorias[cat]) vendedoresMap[r.vendedor].totalCategorias[cat] = { cantidad:0, totalVenta:0 };
    });
    vendedoresMap[r.vendedor].totalCategorias[r.tipoProducto].cantidad += r.cantidad;
    vendedoresMap[r.vendedor].totalCategorias[r.tipoProducto].totalVenta += r.cantidad*r.precio;
  });

  resumenVendedores = [];
  const totalesVendedores = {};
  categorias.forEach(cat => totalesVendedores[cat] = { cantidad:0, totalVenta:0 });

  Object.entries(vendedoresMap).forEach(([vendedor, info]) => {
    let maxSuc = null; let maxCant = -1;
    Object.entries(info.sucursales).forEach(([suc, cant]) => {
      if (cant > maxCant) { maxCant = cant; maxSuc = suc; }
    });

    const obj = { Vendedor: vendedor, SucursalAsignada: maxSuc };
    categorias.forEach(cat => {
      const datos = info.totalCategorias[cat];
      obj[cat + " Cantidad"] = datos.cantidad || 0;
      obj[cat + " Venta"] = datos.totalVenta || 0;
      totalesVendedores[cat].cantidad += datos.cantidad || 0;
      totalesVendedores[cat].totalVenta += datos.totalVenta || 0;
    });
    resumenVendedores.push(obj);
  });

  // Agregar fila total general de vendedores
  const totalVenObj = { Vendedor: "TOTAL GENERAL", SucursalAsignada:"" };
  categorias.forEach(cat => {
    totalVenObj[cat + " Cantidad"] = totalesVendedores[cat].cantidad;
    totalVenObj[cat + " Venta"] = totalesVendedores[cat].totalVenta;
  });
  resumenVendedores.push(totalVenObj);

  mostrarTabla(resumenAlmacenes, tablaAlmacenesDiv, "Resumen por Almacén");
  mostrarTabla(resumenVendedores, tablaVendedoresDiv, "Resumen por Vendedor");

  mensajes.innerText = "Procesamiento completado.";
  descargarBtn.disabled = false;
}

function mostrarTabla(arr, contenedor, titulo) {
  if (!arr.length) {
    contenedor.innerHTML = "<div class='file-status'>No hay datos.</div>";
    return;
  }
  let html = `<table class="data-table"><caption>${titulo}</caption><thead><tr>`;
  Object.keys(arr[0]).forEach(k => html += `<th>${k}</th>`);
  html += "</tr></thead><tbody>";
  arr.forEach(row => {
    html += "<tr>";
    Object.keys(row).forEach(k => {
      let val = row[k] || 0;
      if (typeof val === 'number') val = val.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      html += `<td>${val}</td>`;
    });
    html += "</tr>";
  });
  html += "</tbody></table>";
  contenedor.innerHTML = html;
}

function descargaResultados() {
  if (!resumenAlmacenes.length && !resumenVendedores.length) {
    alert("No hay resultados para descargar.");
    return;
  }
  const wb = XLSX.utils.book_new();

  if (resumenAlmacenes.length) {
    const ws1 = XLSX.utils.json_to_sheet(resumenAlmacenes);
    // Ajustar ancho de columnas
    const wscols = Object.keys(resumenAlmacenes[0]).map(k => ({ wch: Math.max(k.length + 2, 12) }));
    ws1['!cols'] = wscols;
    XLSX.utils.book_append_sheet(wb, ws1, "Almacenes");
  }

  if (resumenVendedores.length) {
    const ws2 = XLSX.utils.json_to_sheet(resumenVendedores);
    const wscols2 = Object.keys(resumenVendedores[0]).map(k => ({ wch: Math.max(k.length + 2, 12) }));
    ws2['!cols'] = wscols2;
    XLSX.utils.book_append_sheet(wb, ws2, "Vendedores");
  }

  XLSX.writeFile(wb, "Resultados_Protectores.xlsx");
}
</script>

<script>
  // Control del menú horizontal en móvil
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
</script>
</body>
</html>