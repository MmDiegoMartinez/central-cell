<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Análisis Ventas vs Existencias - INNOVACION MOVIL</title>
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

    <a href="analisis_fundas_ventas_existencias.php" class="sidebar-link active">
      <span class="material-symbols-outlined">swap_horiz</span>
      Ventas vs Existencias
    </a>

    <a href="fundasstock.php" class="sidebar-link">
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
      <h2 class="text-headline-sm" style="margin:0">Análisis Ventas vs Existencias</h2>
    </div>
  </header>

  <div class="container">
    <div class="lesson">
      <div class="lesson-body">

        <span class="eyebrow">Reportes</span>
        <h1 class="text-headline-lg" style="margin:6px 0 0">Análisis de Ventas vs Existencias</h1>
        <div class="lesson-meta">
          <span><span class="material-symbols-outlined" style="font-size:16px">storefront</span> Innovación Móvil</span>
        </div>

        <div class="intro-panel">
          <div class="icon-badge"><span class="material-symbols-outlined">insights</span></div>
          <div>
            <h3 style="margin:0">Genera tu reporte comparativo</h3>
            <p>Carga los archivos de existencias y ventas para construir automáticamente el Excel comparativo por modelo y almacén.</p>
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
              <h4><span class="material-symbols-outlined">shopping_bag</span> Archivo de Ventas por Ticket</h4>
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
              <li>Carga el archivo de <strong>Ventas por Ticket</strong></li>
              <li>Presiona el botón para generar el reporte automáticamente</li>
              <li>El Excel mostrará una tabla con columnas: Modelo | Almacén | Ventas | Existencias</li>
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
let ventasData = null;

const existenciasFile = document.getElementById('existenciasFile');
const ventasFile = document.getElementById('ventasFile');
const existenciasBtn = document.getElementById('existenciasBtn');
const ventasBtn = document.getElementById('ventasBtn');
const existenciasStatus = document.getElementById('existenciasStatus');
const ventasStatus = document.getElementById('ventasStatus');
const analyzeBtn = document.getElementById('analyzeBtn');
const loader = document.getElementById('loader');

existenciasBtn.onclick = () => existenciasFile.click();
ventasBtn.onclick = () => ventasFile.click();

existenciasFile.onchange = (e) => {
  if (e.target.files.length) {
    cargarExistencias(e.target.files[0]);
  }
};

ventasFile.onchange = (e) => {
  if (e.target.files.length) {
    cargarVentas(e.target.files[0]);
  }
};

function cargarExistencias(file) {
  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target.result);
    const wb = XLSX.read(data, {type: "array"});
    const sheet = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(sheet, {header: 1, defval: ""});

    existenciasData = [];
    for (let i = 1; i < rows.length; i++) {
      const r = rows[i];
      const nombre = String(r[13]).trim().toUpperCase();
      if (nombre !== "INNOVACION MOVIL>CASE>CELULAR" && nombre !== "INNOVACION MOVIL>CASE>TABLET") continue;
      
      existenciasData.push({
        almacen: r[0],
        marca: r[5],
        modelo: r[6],
        existencia: Number(r[7]) || 0
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

function parseFechaFixed(txt) {
  if (!txt) return null;
  const partes = txt.trim().split(/\s+/);
  if (partes.length < 4) return null;

  const [mesStr, diaStr, anioStr, horaStr] = partes;
  const meses = {Jan:0, Feb:1, Mar:2, Apr:3, May:4, Jun:5,
                 Jul:6, Aug:7, Sep:8, Oct:9, Nov:10, Dec:11};
  const mes = meses[mesStr];
  const dia = parseInt(diaStr);
  const anio = parseInt(anioStr);

  const match = horaStr.match(/(\d+):(\d+)(AM|PM)/);
  if (!match) return new Date(anio, mes, dia);
  
  let h = parseInt(match[1]);
  const min = parseInt(match[2]);
  const ampm = match[3];
  if (ampm === "PM" && h < 12) h += 12;
  if (ampm === "AM" && h === 12) h = 0;

  return new Date(anio, mes, dia, h, min);
}

function cargarVentas(file) {
  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target.result);
    const wb = XLSX.read(data, {type: "array"});
    const sheet = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(sheet, {header: 1, defval: ""});

    ventasData = [];
    for (let i = 1; i < rows.length; i++) {
      const r = rows[i];
      if (r[1] !== "INNOVACION MOVIL") continue;
      if (r[3] !== "CELULAR" && r[3] !== "TABLET") continue;

      const fechaTxt = r[7];
      const fecha = parseFechaFixed(fechaTxt);

      ventasData.push({
        almacen: r[0],
        cantidad: Number(r[14]) || 0,
        marca: r[20],
        modelo: r[21],
        fecha
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

function verificarArchivos() {
  if (existenciasData && ventasData) {
    analyzeBtn.disabled = false;
  }
}

analyzeBtn.onclick = async () => {
  loader.classList.add('active');
  analyzeBtn.disabled = true;

  await new Promise(resolve => setTimeout(resolve, 100));

  try {
    await generarReporte();
  } catch (error) {
    alert('Error al generar el reporte: ' + error.message);
  } finally {
    loader.classList.remove('active');
    analyzeBtn.disabled = false;
  }
};

async function generarReporte() {
  // 1. Calcular ventas por modelo y almacén
  const ventasPorModelo = {};
  ventasData.forEach(v => {
    const key = `${v.marca}||${v.modelo}`;
    if (!ventasPorModelo[key]) {
      ventasPorModelo[key] = {
        marca: v.marca,
        modelo: v.modelo,
        total: 0,
        porAlmacen: {}
      };
    }
    ventasPorModelo[key].total += v.cantidad;
    
    if (!ventasPorModelo[key].porAlmacen[v.almacen]) {
      ventasPorModelo[key].porAlmacen[v.almacen] = 0;
    }
    ventasPorModelo[key].porAlmacen[v.almacen] += v.cantidad;
  });

  // 2. Calcular existencias por modelo y almacén
  const existenciasPorModelo = {};
  existenciasData.forEach(e => {
    const key = `${e.marca}||${e.modelo}`;
    if (!existenciasPorModelo[key]) {
      existenciasPorModelo[key] = {
        porAlmacen: {}
      };
    }
    if (!existenciasPorModelo[key].porAlmacen[e.almacen]) {
      existenciasPorModelo[key].porAlmacen[e.almacen] = 0;
    }
    existenciasPorModelo[key].porAlmacen[e.almacen] += e.existencia;
  });

  // 3. Ordenar modelos por ventas totales (mayor a menor)
  const modelosOrdenados = Object.entries(ventasPorModelo)
    .sort((a, b) => b[1].total - a[1].total);

  // 4. Obtener lista única de almacenes
  const almacenesSet = new Set();
  ventasData.forEach(v => almacenesSet.add(v.almacen));
  existenciasData.forEach(e => almacenesSet.add(e.almacen));
  const almacenes = Array.from(almacenesSet).sort();

  // 5. Crear Excel
  const wb = new ExcelJS.Workbook();
  const ws = wb.addWorksheet('Análisis Ventas vs Existencias');

  // 6. Configurar anchos de columna
  ws.getColumn(1).width = 20; // Marca
  ws.getColumn(2).width = 30; // Modelo
  ws.getColumn(3).width = 30; // Almacén
  ws.getColumn(4).width = 12; // Ventas
  ws.getColumn(5).width = 14; // Existencias

  // 7. Crear encabezado
  const headerRow = ws.addRow(['Marca', 'Modelo', 'Almacén', 'Ventas', 'Existencias']);
  headerRow.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 12 };
  headerRow.fill = {
    type: 'pattern',
    pattern: 'solid',
    fgColor: { argb: 'FF2F6FA6' }
  };
  headerRow.alignment = { horizontal: 'center', vertical: 'middle' };
  headerRow.height = 25;

  // 8. Agregar datos
  modelosOrdenados.forEach(([key, ventasInfo]) => {
    const existenciasInfo = existenciasPorModelo[key] || { porAlmacen: {} };

    // Por cada almacén, agregar una fila
    almacenes.forEach((almacen, idx) => {
      const ventas = ventasInfo.porAlmacen[almacen] || 0;
      const existencias = existenciasInfo.porAlmacen[almacen] || 0;

      const row = ws.addRow([
        idx === 0 ? ventasInfo.marca : '', // Solo mostrar marca en primera fila del modelo
        idx === 0 ? ventasInfo.modelo : '', // Solo mostrar modelo en primera fila
        almacen,
        ventas,
        existencias
      ]);

      // Alternar colores de fondo para mejor lectura
      if (idx % 2 === 0) {
        row.fill = {
          type: 'pattern',
          pattern: 'solid',
          fgColor: { argb: 'FFF8FAFC' }
        };
      }

      // Alineación
      row.getCell(1).alignment = { vertical: 'middle' };
      row.getCell(2).alignment = { vertical: 'middle' };
      row.getCell(3).alignment = { horizontal: 'left', vertical: 'middle' };
      row.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
      row.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };

      // Si la primera fila del modelo, poner en negrita
      if (idx === 0) {
        row.getCell(1).font = { bold: true, color: { argb: 'FF2F6FA6' } };
        row.getCell(2).font = { bold: true, color: { argb: 'FF2F6FA6' } };
      }
    });

    // Fila de totales por modelo
    const totalVentas = ventasInfo.total;
    const totalExistencias = Object.values(existenciasInfo.porAlmacen).reduce((sum, val) => sum + val, 0);

    const totalRow = ws.addRow([
      '',
      '',
      'TOTAL',
      totalVentas,
      totalExistencias
    ]);

    totalRow.font = { bold: true };
    totalRow.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FFE3F2FD' }
    };
    totalRow.getCell(3).alignment = { horizontal: 'right', vertical: 'middle' };
    totalRow.getCell(4).alignment = { horizontal: 'center', vertical: 'middle' };
    totalRow.getCell(5).alignment = { horizontal: 'center', vertical: 'middle' };

    // Espacio entre modelos
    ws.addRow([]);
  });

  // 9. Aplicar bordes a todas las celdas con datos
  ws.eachRow((row, rowNumber) => {
    if (rowNumber > 0) {
      row.eachCell((cell) => {
        cell.border = {
          top: { style: 'thin', color: { argb: 'FFD0D0D0' } },
          left: { style: 'thin', color: { argb: 'FFD0D0D0' } },
          bottom: { style: 'thin', color: { argb: 'FFD0D0D0' } },
          right: { style: 'thin', color: { argb: 'FFD0D0D0' } }
        };
      });
    }
  });

  // 10. Descargar archivo
  const buffer = await wb.xlsx.writeBuffer();
  const blob = new Blob([buffer]);
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  
  const fecha = new Date().toISOString().split('T')[0];
  a.download = `Analisis_Ventas_Existencias_${fecha}.xlsx`;
  a.click();
  URL.revokeObjectURL(url);
}
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