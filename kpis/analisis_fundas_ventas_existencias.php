<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Análisis Ventas vs Existencias - INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #f0f6ff;
  min-height: 100vh;
}

header {
  background: linear-gradient(135deg, #306F94 0%, #306F94 100%);
  padding: 15px 0;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

nav ul {
  list-style: none;
  display: flex;
  justify-content: center;
  gap: 30px;
  padding: 0 20px;
}

nav a {
  color: white;
  text-decoration: none;
  padding: 10px 20px;
  border-radius: 6px;
  transition: all 0.3s;
  font-weight: 500;
}

nav a:hover {
  background: rgba(255,255,255,0.2);
  transform: translateY(-2px);
}

.container {
  max-width: 900px;
  margin: 40px auto;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(47,111,166,0.15);
  padding: 40px;
}

h1 {
  color: #2f6fa6;
  margin-bottom: 30px;
  text-align: center;
  font-size: 28px;
  font-weight: 600;
}

.upload-section {
  display: grid;
  gap: 25px;
  margin-bottom: 30px;
}

.file-group {
  background: #f8fafc;
  padding: 20px;
  border-radius: 10px;
  border: 2px solid #e3f2fd;
  transition: all 0.3s;
}

.file-group:hover {
  border-color: #2f6fa6;
}

.file-group h3 {
  color: #2f6fa6;
  margin-bottom: 15px;
  font-size: 16px;
  font-weight: 600;
}

.file-upload {
  position: relative;
}

input[type=file] {
  display: none;
}

.file-button {
  display: block;
  width: 100%;
  padding: 14px 20px;
  background: #306F94;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 15px;
  font-weight: 500;
  transition: all 0.3s;
}

.file-button:hover {
  background: #25527a;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(47,111,166,0.3);
}

.file-button.loaded {
  background: #28a745;
}

.file-button.loaded:hover {
  background: #218838;
}

.file-status {
  margin-top: 12px;
  padding: 10px;
  background: white;
  border-radius: 6px;
  font-size: 14px;
  color: #6c757d;
  min-height: 40px;
  display: flex;
  align-items: center;
  border: 1px solid #e9ecef;
}

.file-status.success {
  color: #28a745;
  font-weight: 600;
  border-color: #28a745;
  background: #f0fff4;
}

.analyze-section {
  text-align: center;
  padding: 30px 0;
  border-top: 2px solid #e3f2fd;
  margin-top: 20px;
}

#analyzeBtn {
  padding: 16px 50px;
  background: linear-gradient(135deg, #2f6fa6 0%, #1e4d7a 100%);
  color: white;
  border: none;
  border-radius: 50px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(47,111,166,0.3);
}

#analyzeBtn:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 6px 25px rgba(47,111,166,0.5);
}

#analyzeBtn:disabled {
  background: #adb5bd;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.loader {
  display: none;
  text-align: center;
  padding: 20px;
}

.loader.active {
  display: block;
}

.spinner {
  border: 4px solid #e3f2fd;
  border-top: 4px solid #2f6fa6;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loader-text {
  color: #2f6fa6;
  font-size: 14px;
  font-weight: 500;
}

.instructions {
  background: #e3f2fd;
  padding: 20px;
  border-radius: 10px;
  margin-top: 25px;
  border-left: 4px solid #2f6fa6;
}

.instructions h3 {
  color: #2f6fa6;
  margin-bottom: 12px;
  font-size: 16px;
  font-weight: 600;
}

.instructions ul {
  margin-left: 20px;
  color: #495057;
  line-height: 1.8;
}

.instructions li {
  margin-bottom: 6px;
}
</style>
</head>
<body>

<header>
  <nav>
    <ul>
        <li> <a href="index.php">
          
          Home
        </a></li>
      <li><a href="fundasstock.php">📦 Distribución Fundas</a></li>
      <li><a href="ventasfundas.php">🛍️ Ventas por Modelo</a></li>
    </ul>
  </nav>
</header>

<div class="container">
  <h1>📊 Análisis de Ventas vs Existencias</h1>
  
  <div class="upload-section">
    <div class="file-group">
      <h3>1️⃣ Archivo de Existencias</h3>
      <div class="file-upload">
        <input type="file" id="existenciasFile" accept=".xlsx,.xls">
        <button class="file-button" id="existenciasBtn">
          📦 Seleccionar Archivo de Existencias
        </button>
        <div class="file-status" id="existenciasStatus">
          Esperando archivo...
        </div>
      </div>
    </div>

    <div class="file-group">
      <h3>2️⃣ Archivo de Ventas por Ticket</h3>
      <div class="file-upload">
        <input type="file" id="ventasFile" accept=".xlsx,.xls">
        <button class="file-button" id="ventasBtn">
          🛍️ Seleccionar Archivo de Ventas
        </button>
        <div class="file-status" id="ventasStatus">
          Esperando archivo...
        </div>
      </div>
    </div>
  </div>

  <div class="analyze-section">
    <button id="analyzeBtn" disabled>
      🚀 Generar Reporte Completo
    </button>
  </div>

  <div class="loader" id="loader">
    <div class="spinner"></div>
    <div class="loader-text">Generando reporte Excel...</div>
  </div>

  <div class="instructions">
    <h3>📋 Instrucciones:</h3>
    <ul>
      <li>Carga el archivo de <strong>Existencias</strong> (inventario actual)</li>
      <li>Carga el archivo de <strong>Ventas por Ticket</strong></li>
      <li>Presiona el botón para generar el reporte automáticamente</li>
      <li>El Excel mostrará una tabla con columnas: Modelo | Almacén | Ventas | Existencias</li>
      <li>Los modelos están ordenados por ventas (mayor a menor)</li>
      <li>Si un almacén no vendió, aparecerá 0 en ventas</li>
    </ul>
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

    existenciasStatus.textContent = `✅ ${existenciasData.length} registros cargados`;
    existenciasStatus.classList.add('success');
    existenciasBtn.classList.add('loaded');
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

    ventasStatus.textContent = `✅ ${ventasData.length} registros cargados`;
    ventasStatus.classList.add('success');
    ventasBtn.classList.add('loaded');
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

</body>
</html>