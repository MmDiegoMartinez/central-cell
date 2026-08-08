<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analizador · Smartphones Vendidos</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<style>
  :root {
    --bg: #f5f7fa;
    --surface: #ffffff;
    --surface2: #eef1f6;
    --muted: #6b7280;
    --text: #0f1724;
    --primary-600: #0f5476;
    --primary-400: #16729a;
    --accent: #1f9a8a;
    --accent2: #16729a;
    --warn: #e05c2a;
    --glass: rgba(15, 23, 36, 0.04);
    --border: rgba(15, 23, 36, 0.08);
    --radius-lg: 14px;
    --radius-md: 10px;
    --radius: 16px;
    --shadow-sm: 0 6px 18px rgba(12, 18, 26, 0.06);
    --shadow-md: 0 10px 30px rgba(12, 18, 26, 0.09);
    --transition-fast: 220ms cubic-bezier(.2, .9, .2, 1);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    padding: 0 0 80px;
  }

  /* ── NAV HEADER ── */
  .top-nav {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    position: sticky;
    top: 0;
    z-index: 100;
  }
  .nav-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0 24px;
    height: 56px;
  }
  .nav-inner ul {
    list-style: none;
    display: flex;
    gap: 4px;
    padding: 0;
    margin: 0;
  }
  .menu-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: var(--radius-md);
    font-family: 'DM Sans', sans-serif;
    font-size: .88rem;
    font-weight: 500;
    color: var(--muted);
    text-decoration: none;
    transition: background var(--transition-fast), color var(--transition-fast);
    white-space: nowrap;
  }
  .menu-link:hover {
    background: rgba(15, 84, 118, 0.07);
    color: var(--primary-600);
  }
  .menu-link.active {
    background: rgba(15, 84, 118, 0.1);
    color: var(--primary-600);
    font-weight: 600;
  }
  .logo-container {
    display: flex;
    align-items: center;
  }
  .logo { border-radius: 4px; }
  .nav-divider {
    width: 1px;
    height: 22px;
    background: var(--border);
    margin: 0 6px;
  }

  /* ── MAIN CONTENT ── */
  .main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 24px 0;
  }

  /* ── HEADER ── */
  .header {
    text-align: center;
    margin-bottom: 40px;
  }
  .header h1 {
    font-family: 'Syne', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary-600), var(--accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -1px;
  }
  .header p {
    color: var(--muted);
    margin-top: 8px;
    font-size: .95rem;
  }

  /* ── DROP ZONE ── */
  .drop-zone {
    border: 2px dashed rgba(15, 84, 118, 0.25);
    border-radius: var(--radius);
    padding: 52px 32px;
    text-align: center;
    cursor: pointer;
    transition: all .3s;
    background: var(--surface);
    box-shadow: var(--shadow-sm);
    max-width: 560px;
    margin: 0 auto 32px;
    position: relative;
  }
  .drop-zone:hover, .drop-zone.dragover {
    border-color: var(--primary-400);
    background: rgba(15, 84, 118, 0.03);
    box-shadow: var(--shadow-md);
  }
  .drop-zone input { display: none; }
  .drop-icon { font-size: 3rem; margin-bottom: 16px; }
  .drop-zone h3 {
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem;
    color: var(--text);
    margin-bottom: 6px;
  }
  .drop-zone p  { color: var(--muted); font-size: .88rem; }
  .file-name {
    margin-top: 12px;
    font-size: .85rem;
    color: var(--accent);
    font-weight: 500;
  }

  /* ── BTN GENERAR ── */
  .btn-generar {
    display: block;
    margin: 0 auto 40px;
    background: linear-gradient(135deg, var(--primary-600), var(--accent));
    color: #ffffff;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 1rem;
    border: none;
    border-radius: 50px;
    padding: 14px 48px;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(15, 84, 118, 0.25);
    transition: opacity .2s, transform .15s, box-shadow .2s;
    letter-spacing: .3px;
  }
  .btn-generar:hover   { opacity: .9; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15, 84, 118, 0.3); }
  .btn-generar:active  { transform: scale(.97); }
  .btn-generar:disabled { opacity: .35; cursor: not-allowed; box-shadow: none; }

  /* ── STATS BAR ── */
  .stats-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 36px;
  }
  .stat-chip {
    background: var(--surface);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    border-radius: 50px;
    padding: 10px 22px;
    font-size: .85rem;
    color: var(--muted);
  }
  .stat-chip strong { color: var(--text); font-weight: 600; }
  .stat-chip .accent { color: var(--accent); }

  /* ── TABLA PREVIEW ── */
  .preview-wrap {
    overflow-x: auto;
    border-radius: var(--radius);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
    margin-bottom: 48px;
    background: var(--surface);
  }
  table { width: 100%; border-collapse: collapse; font-size: .85rem; }
  thead th {
    background: var(--surface2);
    padding: 13px 18px;
    text-align: left;
    font-family: 'Syne', sans-serif;
    font-size: .78rem;
    letter-spacing: .8px;
    color: var(--muted);
    text-transform: uppercase;
    white-space: nowrap;
    border-bottom: 1px solid var(--border);
  }
  tbody tr { border-top: 1px solid var(--border); transition: background .15s; }
  tbody tr:hover { background: rgba(15, 84, 118, 0.03); }
  tbody td { padding: 11px 18px; color: var(--text); white-space: nowrap; }
  .badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 600;
  }
  .badge-contado { background: rgba(31, 154, 138, 0.12); color: var(--accent); }
  .badge-credito { background: rgba(22, 114, 154, 0.12); color: var(--accent2); }

  /* sección dentro de tarjeta */
  .vc-section-label {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .6px;
    text-transform: uppercase;
    color: var(--muted);
    margin: 14px 0 6px;
    padding-left: 4px;
  }

  /* ── GALERÍA DE TARJETAS ── */
  .gallery-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.3rem;
    font-weight: 800;
    margin-bottom: 24px;
    text-align: center;
    color: var(--primary-600);
  }
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 380px);
    gap: 24px;
    margin-bottom: 40px;
    justify-content: center;
  }
  .card-wrap {
    position: relative;
    display: flex;
    flex-direction: column;
  }
  .card-wrap .vendor-card { flex: 1; }
  .btn-dl {
    display: block;
    width: 100%;
    margin-top: 10px;
    background: var(--surface2);
    border: 1px solid var(--border);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    font-size: .85rem;
    border-radius: 10px;
    padding: 9px;
    cursor: pointer;
    transition: background .2s;
  }
  .btn-dl:hover { background: #e2e7ef; }

  /* ══════════════════════════════════════
     TARJETA VENDEDOR — paleta clara
  ══════════════════════════════════════ */
  .vendor-card {
    width: 380px;
    background: #ffffff;
    border-radius: 22px;
    overflow: hidden;
    font-family: 'DM Sans', sans-serif;
    border: 1px solid rgba(15,23,36,.09);
    box-shadow: var(--shadow-md);
    position: relative;
    display: flex;
    flex-direction: column;
  }
  .vc-body {
    padding: 26px 28px 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .vc-header { height: 6px; }
  .vc-header.contado { background: linear-gradient(90deg, #1f9a8a, #17b89e); }
  .vc-header.credito { background: linear-gradient(90deg, #16729a, #0f5476); }
  .vc-header.empate  { background: linear-gradient(90deg, #e05c2a, #f0833a); }

  .vc-seller {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
  }
  .vc-avatar {
    width: 52px; height: 52px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-size: 1.3rem;
    font-weight: 800;
    flex-shrink: 0;
  }
  .vc-avatar.contado { background: rgba(31,154,138,.15); color: #1f9a8a; }
  .vc-avatar.credito { background: rgba(22,114,154,.15); color: #16729a; }
  .vc-avatar.empate  { background: rgba(224,92,42,.15); color: #e05c2a; }

  .vc-name {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.1rem;
    color: #0f1724;
    line-height: 1.1;
    text-transform: capitalize;
  }
  .vc-subtitle { font-size: .78rem; color: #6b7280; margin-top: 3px; }

  .vc-congrats {
    border-radius: 10px;
    padding: 10px 14px;
    font-size: .82rem;
    font-weight: 500;
    margin-bottom: 18px;
    line-height: 1.45;
  }
  .vc-congrats.contado { background: rgba(31,154,138,.08); color: #167a6c; border-left: 3px solid #1f9a8a; }
  .vc-congrats.credito { background: rgba(22,114,154,.08); color: #0f5476; border-left: 3px solid #16729a; }
  .vc-congrats.empate  { background: rgba(224,92,42,.08);  color: #b84a1e; border-left: 3px solid #e05c2a; }

  .vc-sale {
    background: #f5f7fa;
    border: 1px solid rgba(15,23,36,.06);
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  .vc-model { font-size: .84rem; color: #374151; font-weight: 500; flex: 1; }
  .vc-pill {
    font-size: .72rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .vc-pill.contado { background: rgba(31,154,138,.12); color: #167a6c; }
  .vc-pill.credito { background: rgba(22,114,154,.12); color: #0f5476; }

  .vc-section-label {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .6px;
    text-transform: uppercase;
    color: #6b7280;
    margin: 14px 0 6px;
    padding-left: 4px;
  }

  .vc-footer {
    border-top: 1px solid rgba(15,23,36,.07);
    margin-top: auto;
    padding-top: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .vc-total {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem;
    font-weight: 800;
  }
  .vc-total.contado { color: #1f9a8a; }
  .vc-total.credito { color: #16729a; }
  .vc-total.empate  { color: #e05c2a; }
  .vc-total-label { font-size: .74rem; color: #6b7280; margin-top: 2px; }
  .vc-date { font-size: .78rem; color: #6b7280; text-align: right; }

  /* ── HIDDEN RENDER CONTAINER ── */
  #render-zone { position: fixed; left: -9999px; top: 0; z-index: -1; }

  /* ── EMPTY / ERROR ── */
  .empty {
    text-align: center;
    color: var(--muted);
    padding: 60px 20px;
    font-size: .95rem;
  }

  /* scrollbar */
  ::-webkit-scrollbar { width: 6px; height: 6px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: rgba(15,23,36,.15); border-radius: 3px; }
</style>
</head>
<body>

<!-- ══════════════════════ NAV HEADER ══════════════════════ -->
<nav class="top-nav">
  <div class="nav-inner">
    <ul>
      <li>
        <a href="../garantias/validador/validador.php" class="menu-link">
          <span class="logo-container">
            <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" class="logo" width="25" height="25"/>
          </span>
          Home
        </a>
      </li>
      <div class="nav-divider"></div>
      <li>
        <a href="index.php" class="menu-link">
          Panel KPIs
        </a>
      </li>
    </ul>
  </div>
</nav>

<!-- ══════════════════════ MAIN ══════════════════════ -->
<div class="main-content">

  <div class="header">
    <h1>📱 Reporte · Smartphones Vendidos</h1>
    <p>Sube tu archivo Excel de ventas para generar las tarjetas por vendedor</p>
  </div>

  <!-- DROP ZONE -->
  <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
    <div class="drop-icon">📂</div>
    <h3>Arrastra tu archivo .xlsx aquí</h3>
    <p>o haz clic para seleccionarlo</p>
    <div class="file-name" id="fileName"></div>
    <input type="file" id="fileInput" accept=".xlsx,.xls">
  </div>

  <button class="btn-generar" id="btnGenerar" disabled onclick="procesar()">
    ✨ Generar Reporte
  </button>

  <!-- STATS -->
  <div class="stats-bar" id="statsBar" style="display:none"></div>

  <!-- TABLA PREVIEW -->
  <div id="tablaWrap" style="display:none">
    <div class="preview-wrap">
      <table id="tablaPreview">
        <thead>
          <tr>
            <th>Vendedor</th>
            <th>Modelo</th>
            <th>Modalidad</th>
            <th>Fecha</th>
          </tr>
        </thead>
        <tbody id="tablaBody"></tbody>
      </table>
    </div>
  </div>

  <!-- TARJETAS -->
  <div id="galeria" style="display:none">
    <div class="gallery-title">🎉 Tarjetas por Vendedor</div>
    <button class="btn-generar" id="btnDescargarTodo" onclick="descargarTodo()" style="margin-bottom:32px;background:linear-gradient(135deg,#0f5476,#1f9a8a);display:none">
      ⬇️ Descargar imagen completa
    </button>
    <div class="cards-grid" id="cardsGrid"></div>
  </div>

</div><!-- /main-content -->

<!-- ZONA DE RENDER OCULTO PARA html2canvas -->
<div id="render-zone"></div>

<script>
// ══════════════════════════════════
//  UTILIDADES
// ══════════════════════════════════
const MESES_ES = {
  jan:'Enero', feb:'Febrero', mar:'Marzo', apr:'Abril',
  may:'Mayo', jun:'Junio', jul:'Julio', aug:'Agosto',
  sep:'Septiembre', oct:'Octubre', nov:'Noviembre', dec:'Diciembre'
};

function parsearFecha(str) {
  if (!str) return null;
  const m = String(str).match(/(\w{3})\s+(\d+)\s+(\d{4})/);
  if (!m) return null;
  const mesKey = m[1].toLowerCase();
  return { mes: MESES_ES[mesKey] || m[1], dia: parseInt(m[2]), anio: parseInt(m[3]), raw: new Date(`${m[1]} ${m[2]} ${m[3]}`) };
}

function rangoFechas(fechas) {
  const validas = fechas.filter(Boolean).map(f => f.raw).filter(d => d instanceof Date && !isNaN(d));
  if (!validas.length) return '';
  const min = new Date(Math.min(...validas));
  const max = new Date(Math.max(...validas));
  const fmt = d => {
    const m = d.toLocaleString('en-US', {month:'short'});
    const mes = MESES_ES[m.toLowerCase()] || m;
    return `${d.getDate()} de ${mes} de ${d.getFullYear()}`;
  };
  if (min.toDateString() === max.toDateString()) return fmt(min);
  return `${fmt(min)} — ${fmt(max)}`;
}

function extraerModelo(prodConcat) {
  if (!prodConcat) return 'Modelo desconocido';
  const m = prodConcat.match(/\(([^)]+)\)/);
  if (!m) return prodConcat.trim();
  const dentro = m[1];
  const antes = dentro.split('/')[0].trim();
  return antes || prodConcat.trim();
}

// ══════════════════════════════════
//  DRAG & DROP + FILE INPUT
// ══════════════════════════════════
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
let workbook = null;

dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.classList.remove('dragover');
  const f = e.dataTransfer.files[0];
  if (f) cargarArchivo(f);
});
fileInput.addEventListener('change', () => { if (fileInput.files[0]) cargarArchivo(fileInput.files[0]); });

function cargarArchivo(file) {
  document.getElementById('fileName').textContent = '📄 ' + file.name;
  const reader = new FileReader();
  reader.onload = e => {
    const data = new Uint8Array(e.target.result);
    workbook = XLSX.read(data, { type: 'array' });
    document.getElementById('btnGenerar').disabled = false;
  };
  reader.readAsArrayBuffer(file);
}

// ══════════════════════════════════
//  PROCESAR
// ══════════════════════════════════
let filasProcesadas = [];

async function procesar() {
  if (!workbook) return;

  const sheet = workbook.Sheets[workbook.SheetNames[0]];
  const rows  = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });

  if (rows.length < 2) { alert('El archivo no tiene datos.'); return; }

  const headers = rows[0].map(h => String(h).trim());
  const idx = {
    N1:       headers.indexOf('N1'),
    N2:       headers.indexOf('N2'),
    Vendedor: headers.indexOf('Vendedor'),
    Cliente:  headers.indexOf('Cliente'),
    ProdConcat: headers.indexOf('ProdConcat'),
    Fecha:    headers.indexOf('Fecha'),
  };

  if (idx.N1       < 0) idx.N1       = 1;
  if (idx.N2       < 0) idx.N2       = 2;
  if (idx.Vendedor < 0) idx.Vendedor = 9;
  if (idx.Cliente  < 0) idx.Cliente  = 8;
  if (idx.ProdConcat < 0) idx.ProdConcat = 11;
  if (idx.Fecha    < 0) idx.Fecha    = 7;

  filasProcesadas = [];

  for (let i = 1; i < rows.length; i++) {
    const r = rows[i];
    const n1 = String(r[idx.N1] || '').trim().toUpperCase();
    const n2 = String(r[idx.N2] || '').trim().toUpperCase();

    if (n1 !== 'TECNOLOGIA MOVIL') continue;
    if (n2 !== 'SMARTPHONE')       continue;

    const vendedor   = String(r[idx.Vendedor]  || '').trim();
    const clienteRaw = String(r[idx.Cliente]   || '').trim().toUpperCase();
    const prodConcat = String(r[idx.ProdConcat]|| '').trim();
    const fechaRaw   = String(r[idx.Fecha]     || '').trim();

    const modalidad = clienteRaw.includes('PUBLICO EN GENERAL') ? 'contado' : 'credito';
    const modelo    = extraerModelo(prodConcat);
    const fecha     = parsearFecha(fechaRaw);

    filasProcesadas.push({ vendedor, modalidad, modelo, fecha });
  }

  if (!filasProcesadas.length) {
    alert('No se encontraron ventas de Smartphone en Tecnología Móvil.');
    return;
  }

  await cargarFotosBD();

  renderTabla();
  renderStats();
  renderTarjetas();
}

// ══════════════════════════════════
//  TABLA PREVIEW
// ══════════════════════════════════
function renderTabla() {
  const tbody = document.getElementById('tablaBody');
  tbody.innerHTML = '';
  filasProcesadas.forEach(f => {
    const tr = document.createElement('tr');
    const fStr = f.fecha ? `${f.fecha.dia} ${f.fecha.mes} ${f.fecha.anio}` : '—';
    tr.innerHTML = `
      <td>${f.vendedor || '—'}</td>
      <td>${f.modelo}</td>
      <td><span class="badge badge-${f.modalidad}">${f.modalidad === 'contado' ? '💵 Contado' : '💳 PayJoy'}</span></td>
      <td>${fStr}</td>`;
    tbody.appendChild(tr);
  });
  document.getElementById('tablaWrap').style.display = '';
}

// ══════════════════════════════════
//  STATS
// ══════════════════════════════════
function renderStats() {
  const contado = filasProcesadas.filter(f => f.modalidad === 'contado').length;
  const credito = filasProcesadas.filter(f => f.modalidad === 'credito').length;
  const vendedores = [...new Set(filasProcesadas.map(f => f.vendedor))].length;
  const fechas = filasProcesadas.map(f => f.fecha);
  const rango = rangoFechas(fechas);

  document.getElementById('statsBar').innerHTML = `
    <div class="stat-chip">Total ventas: <strong>${filasProcesadas.length}</strong></div>
    <div class="stat-chip">Contado: <strong class="accent">${contado}</strong></div>
    <div class="stat-chip">PayJoy: <strong style="color:var(--accent2)">${credito}</strong></div>
    <div class="stat-chip">Vendedores: <strong>${vendedores}</strong></div>
    ${rango ? `<div class="stat-chip">Período: <strong>${rango}</strong></div>` : ''}
  `;
  document.getElementById('statsBar').style.display = 'flex';
}

// ══════════════════════════════════
//  AGRUPAR POR VENDEDOR
// ══════════════════════════════════
function agruparPorVendedor() {
  const map = {};
  filasProcesadas.forEach(f => {
    const k = f.vendedor || 'Sin nombre';
    if (!map[k]) map[k] = { ventas: [], contado: 0, credito: 0, fechas: [] };
    map[k].ventas.push(f);
    map[k][f.modalidad]++;
    if (f.fecha) map[k].fechas.push(f.fecha);
  });
  return map;
}

// ══════════════════════════════════════════════════════
//  IMÁGENES DE LA BD
// ══════════════════════════════════════════════════════
const IMAGENES_BD = {
  'imagen-1': 'https://i.ibb.co/GvLkyFYR/2179f406fcaa.png',
  'imagen-2': 'https://i.ibb.co/mCnBf5xb/10819118cb0e.png',
};

function elegirImagen(contado, credito) {
  if (credito > contado)  return { id: 'imagen-1', url: IMAGENES_BD['imagen-1'] };
  if (contado > credito)  return { id: 'imagen-2', url: IMAGENES_BD['imagen-2'] };
  return Math.random() > 0.5
    ? { id: 'imagen-1', url: IMAGENES_BD['imagen-1'] }
    : { id: 'imagen-2', url: IMAGENES_BD['imagen-2'] };
}

function tipoVendedor(contado, credito) {
  if (credito > contado) return 'credito';
  if (contado > credito) return 'contado';
  return 'empate';
}

function inicialAvatar(nombre) {
  const partes = nombre.split(' ').filter(Boolean);
  if (partes.length >= 2) return (partes[0][0] + partes[1][0]).toUpperCase();
  return nombre.substring(0, 2).toUpperCase();
}

// ══════════════════════════════════════════════════════
//  FRASES
// ══════════════════════════════════════════════════════
const FRASES = {
  credito: [
    (n,t,r) => `¡Arriba, ${n}! Cerraste ${t} financiamiento${t>1?'s':''} con PayJoy en ${r}. El crédito es tu idioma. 🚀`,
    (n,t,r) => `¡Qué racha, ${n}! ${t} venta${t>1?'s':''} a crédito en ${r}. Los clientes confían en ti para financiar sus sueños. 💳✨`,
    (n,t,r) => `¡Imparable, ${n}! Registraste ${t} equipo${t>1?'s':''} con PayJoy en ${r}. Tú haces que lo imposible sea accesible. 🔥`,
    (n,t,r) => `Top seller, ${n}! En ${r} lograste ${t} venta${t>1?'s':''} financiada${t>1?'s':''}. ¡Eso es convertir sueños en ventas! 🌟`,
    (n,t,r) => `¡Gran trabajo, ${n}! ${t} cliente${t>1?'s':''} se van a casa con un equipo gracias a PayJoy en ${r}. Eres clave del equipo. 💪`,
  ],
  contado: [
    (n,t,r) => `¡Increíble, ${n}! Registraste ${t} venta${t>1?'s':''} al contado en ${r}. ¡Eso es dominar las ventas! 💵`,
    (n,t,r) => `¡Efectivo puro, ${n}! ${t} equipo${t>1?'s':''} vendido${t>1?'s':''} de contado en ${r}. Los números no mienten. 💯`,
    (n,t,r) => `¡Brutal, ${n}! ${t} venta${t>1?'s':''} cerrada${t>1?'s':''} en ${r} sin rodeos. ¡Así se vende! 🏆`,
    (n,t,r) => `¡Sin filtros, ${n}! En ${r} cerraste ${t} transacción${t>1?'es':''} al contado. ¡El cliente llegó, confió y pagó! 🔑`,
    (n,t,r) => `¡Crack, ${n}! ${t} smartphone${t>1?'s':''} vendido${t>1?'s':''} al contado en ${r}. Donde otros negocian, tú cierras. ⚡`,
  ],
  empate: [
    (n,t,r) => `¡Versátil total, ${n}! Combinaste contado y PayJoy en ${t} venta${t>1?'s':''} en ${r}. ¡Adaptas tu estrategia como nadie! ⭐`,
    (n,t,r) => `¡Equilibrado y efectivo, ${n}! En ${r} vendiste ${t} equipo${t>1?'s':''} mezclando lo mejor de los dos mundos. 🎯`,
    (n,t,r) => `¡Multi-estrategia, ${n}! ${t} venta${t>1?'s':''} en ${r} entre crédito y contado. Conoces al cliente y sabes cómo cerrar. 🤝`,
    (n,t,r) => `¡Flexible y ganador, ${n}! Contado o crédito, tú mandas. ${t} equipo${t>1?'s':''} colocado${t>1?'s':''} en ${r}. ¡Eso es talento! 💫`,
    (n,t,r) => `¡Polivalente top, ${n}! En ${r} cerraste ${t} venta${t>1?'s':''} sin importar la modalidad. ¡Tu arma es la adaptación! 🚀`,
  ],
};

function mensajeFelicitacion(nombre, total, tipo, rango, contado, credito) {
  const first = nombre.split(' ')[0];
  const pool  = FRASES[tipo] || FRASES.empate;
  const fn    = pool[Math.floor(Math.random() * pool.length)];
  return fn(first, total, rango);
}

// ══════════════════════════════════════════════════════
//  CSS PARA RENDER html2canvas (tarjetas siempre oscuras)
// ══════════════════════════════════════════════════════
const CARD_CSS = `
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap');
  .vendor-card { width:380px; background:#ffffff; border-radius:22px; overflow:hidden;
    font-family:'DM Sans',sans-serif; border:1px solid rgba(15,23,36,.09);
    box-shadow:0 10px 30px rgba(12,18,26,.09); }
  .vc-header { height:6px; }
  .vc-header.contado { background:linear-gradient(90deg,#1f9a8a,#17b89e); }
  .vc-header.credito { background:linear-gradient(90deg,#16729a,#0f5476); }
  .vc-header.empate  { background:linear-gradient(90deg,#e05c2a,#f0833a); }
  .vc-body { padding:26px 28px 20px; }
  .vc-seller { display:flex; align-items:center; gap:14px; margin-bottom:14px; }
  .vc-avatar { width:52px; height:52px; border-radius:50%; display:flex; align-items:center;
    justify-content:center; font-family:'Syne',sans-serif; font-size:1.3rem; font-weight:800; }
  .vc-avatar.contado { background:rgba(31,154,138,.15); color:#1f9a8a; }
  .vc-avatar.credito { background:rgba(22,114,154,.15); color:#16729a; }
  .vc-avatar.empate  { background:rgba(224,92,42,.15); color:#e05c2a; }
  .vc-name { font-family:'Syne',sans-serif; font-weight:800; font-size:1.1rem;
    color:#0f1724; line-height:1.1; text-transform:capitalize; }
  .vc-subtitle { font-size:.78rem; color:#6b7280; margin-top:3px; }
  .vc-congrats { border-radius:10px; padding:10px 14px; font-size:.82rem;
    font-weight:500; margin-bottom:18px; line-height:1.45; }
  .vc-congrats.contado { background:rgba(31,154,138,.08); color:#167a6c; border-left:3px solid #1f9a8a; }
  .vc-congrats.credito { background:rgba(22,114,154,.08); color:#0f5476; border-left:3px solid #16729a; }
  .vc-congrats.empate  { background:rgba(224,92,42,.08);  color:#b84a1e; border-left:3px solid #e05c2a; }
  .vc-sale { background:#f5f7fa; border:1px solid rgba(15,23,36,.06); border-radius:10px;
    padding:10px 14px; margin-bottom:8px; display:flex; align-items:center;
    justify-content:space-between; gap:10px; }
  .vc-model { font-size:.84rem; color:#374151; font-weight:500; flex:1; }
  .vc-pill { font-size:.72rem; font-weight:700; padding:3px 9px; border-radius:20px; white-space:nowrap; }
  .vc-pill.contado { background:rgba(31,154,138,.12); color:#167a6c; }
  .vc-pill.credito { background:rgba(22,114,154,.12); color:#0f5476; }
  .vc-section-label { font-size:.72rem; font-weight:700; letter-spacing:.6px;
    text-transform:uppercase; color:#6b7280; margin:14px 0 6px; padding-left:4px; }
  .vc-footer { border-top:1px solid rgba(15,23,36,.07); margin-top:auto; padding-top:12px;
    display:flex; justify-content:space-between; align-items:center; }
  .vc-total { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; }
  .vc-total.contado { color:#1f9a8a; }
  .vc-total.credito { color:#16729a; }
  .vc-total.empate  { color:#e05c2a; }
  .vc-total-label { font-size:.74rem; color:#6b7280; margin-top:2px; }
  .vc-date { font-size:.78rem; color:#6b7280; text-align:right; }
`;

// ══════════════════════════════════════════════════════
//  FOTOS DE VENDEDORES
// ══════════════════════════════════════════════════════
const FOTOS_VENDEDORES = {};

function mapearFotosVendedores(vendedoresUnicos) {
  Object.keys(FOTOS_VENDEDORES).forEach(k => delete FOTOS_VENDEDORES[k]);
  for (const { descripcion, url } of IMAGENES_BD_VENDEDORES) {
    const desc = normalizar(descripcion);
    for (const v of vendedoresUnicos) {
      const vNorm = normalizar(v);
      if (vNorm === desc || vNorm.includes(desc) || desc.includes(vNorm)) {
        FOTOS_VENDEDORES[v] = url;
        break;
      }
    }
  }
}

function normalizar(str) {
  return (str || '').toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ').trim();
}

let IMAGENES_BD_VENDEDORES = [];

async function cargarFotosBD() {
  try {
    const res = await fetch('get_fotos_vendedores.php');
    if (!res.ok) throw new Error('HTTP ' + res.status);
    IMAGENES_BD_VENDEDORES = await res.json();
  } catch(e) {
    console.warn('No se pudieron cargar fotos de la BD:', e.message);
    IMAGENES_BD_VENDEDORES = [];
  }
}

// ══════════════════════════════════════════════════════
//  CONSTRUIR HTML DE TARJETA
// ══════════════════════════════════════════════════════
function buildCardHTML(vendedor, data, msgOverride) {
  const tipo  = tipoVendedor(data.contado, data.credito);
  const rango = rangoFechas(data.fechas);
  const msg   = msgOverride || mensajeFelicitacion(vendedor, data.ventas.length, tipo, rango || 'el período', data.contado, data.credito);
  const inits = inicialAvatar(vendedor);

  const fotoVendedor = FOTOS_VENDEDORES[vendedor];
  const avatarEl = fotoVendedor
    ? `<div class="vc-avatar ${tipo}" style="overflow:hidden;padding:0;">
         <img src="${fotoVendedor}" alt="${vendedor}" crossorigin="anonymous"
              style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
              onerror="this.parentElement.innerHTML='${inits}';this.parentElement.style.padding='';">
       </div>`
    : `<div class="vc-avatar ${tipo}">${inits}</div>`;

  const ventasContado = data.ventas.filter(v => v.modalidad === 'contado');
  const ventasCredito = data.ventas.filter(v => v.modalidad === 'credito');

  function ventaItems(lista, cls) {
    return lista.map(v => `
      <div class="vc-sale">
        <div class="vc-model">📱 ${v.modelo}</div>
        <div class="vc-pill ${cls}">${cls === 'contado' ? '💵 Contado' : '💳 PayJoy'}</div>
      </div>`).join('');
  }

  let ventasHTML = '';
  if (tipo === 'contado' || (tipo === 'empate' && ventasContado.length)) {
    if (ventasContado.length) ventasHTML += `<div class="vc-section-label">💵 Al contado (${ventasContado.length})</div>` + ventaItems(ventasContado, 'contado');
    if (ventasCredito.length) ventasHTML += `<div class="vc-section-label">💳 PayJoy (${ventasCredito.length})</div>`    + ventaItems(ventasCredito, 'credito');
  } else {
    if (ventasCredito.length) ventasHTML += `<div class="vc-section-label">💳 PayJoy (${ventasCredito.length})</div>`    + ventaItems(ventasCredito, 'credito');
    if (ventasContado.length) ventasHTML += `<div class="vc-section-label">💵 Al contado (${ventasContado.length})</div>` + ventaItems(ventasContado, 'contado');
  }

  return `
    <div class="vendor-card">
      <div class="vc-header ${tipo}"></div>
      <div class="vc-body">
        <div class="vc-seller">
          ${avatarEl}
          <div>
            <div class="vc-name">${vendedor.toLowerCase()}</div>
            <div class="vc-subtitle">Departamento · Telefonía</div>
          </div>
        </div>
        <div class="vc-congrats ${tipo}">${msg}</div>
        ${ventasHTML}
        <div class="vc-footer">
          <div>
            <div class="vc-total ${tipo}">${data.ventas.length}</div>
            <div class="vc-total-label">equipo${data.ventas.length>1?'s':''} vendido${data.ventas.length>1?'s':''}</div>
          </div>
          <div class="vc-date">${rango || ''}</div>
        </div>
      </div>
    </div>`;
}

// ══════════════════════════════════════════════════════
//  RENDER TARJETAS EN PANTALLA
// ══════════════════════════════════════════════════════
let tarjetasData = [];

async function renderTarjetas() {
  const map  = agruparPorVendedor();
  const grid = document.getElementById('cardsGrid');
  grid.innerHTML = '';
  document.getElementById('galeria').style.display = '';
  tarjetasData = [];

  const vendedoresUnicos = Object.keys(map);
  mapearFotosVendedores(vendedoresUnicos);

  // Ordenar de mayor a menor por total de ventas
  const entradasOrdenadas = Object.entries(map).sort((a, b) => b[1].ventas.length - a[1].ventas.length);

  for (const [vendedor, data] of entradasOrdenadas) {
    const tipo  = tipoVendedor(data.contado, data.credito);
    const rango = rangoFechas(data.fechas);
    const msg   = mensajeFelicitacion(vendedor, data.ventas.length, tipo, rango || 'el período', data.contado, data.credito);

    tarjetasData.push({ vendedor, data, msg });

    const wrap = document.createElement('div');
    wrap.className = 'card-wrap';
    wrap.innerHTML = buildCardHTML(vendedor, data, msg);
    grid.appendChild(wrap);
  }

  document.getElementById('btnDescargarTodo').style.display = '';
}

// ══════════════════════════════════════════════════════
//  DESCARGAR → UNA IMAGEN POR CADA 3 VENDEDORES
// ══════════════════════════════════════════════════════
async function descargarTodo() {
  const btn = document.getElementById('btnDescargarTodo');
  btn.disabled = true;

  const renderZone = document.getElementById('render-zone');
  const todasFechas = filasProcesadas.map(f => f.fecha);
  const rango = rangoFechas(todasFechas) || 'Sin fecha';
  const GRUPO = 3;
  const total = tarjetasData.length;
  const numImagenes = Math.ceil(total / GRUPO);

  for (let g = 0; g < numImagenes; g++) {
    const grupo = tarjetasData.slice(g * GRUPO, g * GRUPO + GRUPO);
    btn.textContent = `⏳ Generando imagen ${g+1} de ${numImagenes}...`;

    renderZone.innerHTML = '';

    const style = document.createElement('style');
    style.textContent = CARD_CSS + `
      .render-wrapper {
        background: #f5f7fa;
        padding: 40px 36px 48px;
        width: fit-content;
      }
      .render-titulo {
        font-family: 'Syne', sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 8px;
        background: linear-gradient(135deg,#0f5476,#1f9a8a);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }
      .render-subtitulo {
        font-family: 'DM Sans', sans-serif;
        font-size: .82rem;
        color: #6b7280;
        text-align: center;
        margin-bottom: 32px;
      }
      .render-pag {
        font-family: 'DM Sans', sans-serif;
        font-size: .75rem;
        color: #9ca3af;
        text-align: center;
        margin-top: 28px;
        letter-spacing: .4px;
      }
      .render-grid {
        display: grid;
        grid-template-columns: repeat(3, 380px);
        grid-auto-rows: 1fr;
        gap: 20px;
        justify-content: center;
      }
      .render-grid .vendor-card {
        display: flex;
        flex-direction: column;
        width: 380px;
        height: 100%;
        min-height: 100%;
        box-sizing: border-box;
      }
      .render-grid .vc-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100%;
      }
      .render-grid .vc-footer {
        margin-top: auto;
        padding-top: 12px;
      }
    `;
    renderZone.appendChild(style);

    const wrapper = document.createElement('div');
    wrapper.className = 'render-wrapper';

    const titulo = document.createElement('div');
    titulo.className = 'render-titulo';
    titulo.textContent = '🏆 Top Vendedores de Smartphones';
    wrapper.appendChild(titulo);

    const subtitulo = document.createElement('div');
    subtitulo.className = 'render-subtitulo';
    subtitulo.textContent = `Período: ${rango} · Telefonía`;
    wrapper.appendChild(subtitulo);

    const grid = document.createElement('div');
    grid.className = 'render-grid';

    const imgs = [];
    for (const { vendedor, data, msg } of grupo) {
      const cardEl = document.createElement('div');
      cardEl.innerHTML = buildCardHTML(vendedor, data, msg);
      const card = cardEl.firstElementChild;
      grid.appendChild(card);
      card.querySelectorAll('img').forEach(img => imgs.push(img));
    }

    wrapper.appendChild(grid);

    if (numImagenes > 1) {
      const pag = document.createElement('div');
      pag.className = 'render-pag';
      pag.textContent = `${g+1} / ${numImagenes}`;
      wrapper.appendChild(pag);
    }

    renderZone.appendChild(wrapper);

    await Promise.all(imgs.map(img => new Promise(r => {
      if (img.complete && img.naturalWidth > 0) { r(); return; }
      img.onload  = r;
      img.onerror = r;
      setTimeout(r, 5000);
    })));

    await new Promise(r => setTimeout(r, 300));

    const cards = Array.from(grid.querySelectorAll('.vendor-card'));
    cards.forEach(c => { c.style.height = ''; });
    await new Promise(r => setTimeout(r, 50));
    const maxH = Math.max(...cards.map(c => c.getBoundingClientRect().height));
    cards.forEach(c => { c.style.height = maxH + 'px'; });

    await new Promise(r => setTimeout(r, 200));

    try {
      const canvas = await html2canvas(wrapper, {
        scale: 4,
        backgroundColor: '#f5f7fa',
        useCORS: true,
        allowTaint: true,
        logging: false,
        imageTimeout: 15000,
      });

      const dataURL = canvas.toDataURL('image/png');
      const byteSize = Math.round((dataURL.length * 3) / 4);
      const finalURL = byteSize > 300_000
        ? dataURL
        : canvas.toDataURL('image/jpeg', 1.0);

      const link = document.createElement('a');
      const sufijo = numImagenes > 1 ? `-${g+1}de${numImagenes}` : '';
      const ext    = finalURL.startsWith('data:image/png') ? 'png' : 'jpg';
      link.download = `smartphones-vendedores${sufijo}.${ext}`;
      link.href = finalURL;
      link.click();

      if (g < numImagenes - 1) await new Promise(r => setTimeout(r, 600));
    } catch(e) {
      console.error(`Error en imagen ${g+1}:`, e);
    }
  }

  renderZone.innerHTML = '';
  btn.textContent = `✅ ¡${numImagenes} imagen${numImagenes>1?'es':''} descargada${numImagenes>1?'s':''}!`;
  setTimeout(() => {
    btn.disabled = false;
    btn.textContent = '⬇️ Descargar imagen completa';
  }, 3500);
}
</script>
</body>
</html>