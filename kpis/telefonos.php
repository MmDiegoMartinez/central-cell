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
    --bg: #0d0f14;
    --surface: #161921;
    --surface2: #1e2230;
    --accent: #00e5a0;
    --accent2: #0099ff;
    --warn: #ff6b35;
    --text: #eef0f6;
    --muted: #7a8099;
    --border: rgba(255,255,255,0.07);
    --radius: 16px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'DM Sans', sans-serif;
    min-height: 100vh;
    padding: 40px 20px 80px;
  }

  /* ── HEADER ── */
  .header {
    text-align: center;
    margin-bottom: 48px;
  }
  .header h1 {
    font-family: 'Syne', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
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
    border: 2px dashed rgba(0,229,160,.3);
    border-radius: var(--radius);
    padding: 52px 32px;
    text-align: center;
    cursor: pointer;
    transition: all .3s;
    background: var(--surface);
    max-width: 560px;
    margin: 0 auto 40px;
    position: relative;
  }
  .drop-zone:hover, .drop-zone.dragover {
    border-color: var(--accent);
    background: rgba(0,229,160,.04);
  }
  .drop-zone input { display: none; }
  .drop-icon { font-size: 3rem; margin-bottom: 16px; }
  .drop-zone h3 { font-family: 'Syne', sans-serif; font-size: 1.1rem; margin-bottom: 6px; }
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
    margin: 0 auto 48px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #0d0f14;
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 1.05rem;
    border: none;
    border-radius: 50px;
    padding: 14px 48px;
    cursor: pointer;
    transition: opacity .2s, transform .15s;
    letter-spacing: .3px;
  }
  .btn-generar:hover   { opacity: .88; transform: translateY(-2px); }
  .btn-generar:active  { transform: scale(.97); }
  .btn-generar:disabled { opacity: .35; cursor: not-allowed; }

  /* ── STATS BAR ── */
  .stats-bar {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 40px;
  }
  .stat-chip {
    background: var(--surface);
    border: 1px solid var(--border);
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
  }
  tbody tr { border-top: 1px solid var(--border); transition: background .15s; }
  tbody tr:hover { background: rgba(255,255,255,.025); }
  tbody td { padding: 11px 18px; color: var(--text); white-space: nowrap; }
  .badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 600;
  }
  .badge-contado { background: rgba(0,229,160,.15); color: var(--accent); }
  .badge-credito { background: rgba(0,153,255,.15); color: var(--accent2); }

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

  /* ── GALERÍA DE TARJETAS DESCARGABLES ── */
  .gallery-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.3rem;
    font-weight: 800;
    margin-bottom: 24px;
    text-align: center;
    color: var(--accent);
  }
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 380px);
    gap: 24px;
    margin-bottom: 40px;
    justify-content: center;
  }
  /* Cada card-wrap ocupa toda la altura de su fila → tarjetas iguales */
  .card-wrap {
    position: relative;
    display: flex;
    flex-direction: column;
  }
  .card-wrap .vendor-card {
    flex: 1;
  }
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
  .btn-dl:hover { background: var(--surface); }

  /* ══════════════════════════════════════
     TARJETA DESCARGABLE (render interno)
  ══════════════════════════════════════ */
  .vendor-card {
    width: 380px;
    background: #0e111a;
    border-radius: 22px;
    overflow: hidden;
    font-family: 'DM Sans', sans-serif;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
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

  /* banda superior de color */
  .vc-header { height: 6px; }
  .vc-header.contado { background: linear-gradient(90deg, #00e5a0, #00c887); }
  .vc-header.credito { background: linear-gradient(90deg, #0099ff, #006fd6); }
  .vc-header.empate  { background: linear-gradient(90deg, #ff6b35, #ff9a35); }

  /* avatar + nombre */
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
  .vc-avatar.contado { background: rgba(0,229,160,.18); color: #00e5a0; }
  .vc-avatar.credito { background: rgba(0,153,255,.18); color: #0099ff; }
  .vc-avatar.empate  { background: rgba(255,107,53,.18); color: #ff6b35; }

  .vc-name {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.1rem;
    color: #eef0f6;
    line-height: 1.1;
    text-transform: capitalize;
  }
  .vc-subtitle { font-size: .78rem; color: #7a8099; margin-top: 3px; }

  /* banner felicitación */
  .vc-congrats {
    border-radius: 10px;
    padding: 10px 14px;
    font-size: .82rem;
    font-weight: 500;
    margin-bottom: 18px;
    line-height: 1.45;
  }
  .vc-congrats.contado { background: rgba(0,229,160,.09); color: #00e5a0; border-left: 3px solid #00e5a0; }
  .vc-congrats.credito { background: rgba(0,153,255,.09); color: #0099ff; border-left: 3px solid #0099ff; }
  .vc-congrats.empate  { background: rgba(255,107,53,.09); color: #ff6b35; border-left: 3px solid #ff6b35; }

  /* lista de ventas */
  .vc-sale {
    background: #161921;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }
  .vc-model { font-size: .84rem; color: #c8ccdb; font-weight: 500; flex: 1; }
  .vc-pill {
    font-size: .72rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
    white-space: nowrap;
    flex-shrink: 0;
  }
  .vc-pill.contado { background: rgba(0,229,160,.15); color: #00e5a0; }
  .vc-pill.credito { background: rgba(0,153,255,.15); color: #0099ff; }

  /* foto de BD — eliminada, ahora la foto va en el avatar */

  /* footer de tarjeta */
  .vc-footer {
    border-top: 1px solid rgba(255,255,255,.06);
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
  .vc-total.contado { color: #00e5a0; }
  .vc-total.credito { color: #0099ff; }
  .vc-total.empate  { color: #ff6b35; }
  .vc-total-label { font-size: .74rem; color: #7a8099; margin-top: 2px; }
  .vc-date { font-size: .78rem; color: #7a8099; text-align: right; }

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
  ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }
</style>
</head>
<body>

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
  <button class="btn-generar" id="btnDescargarTodo" onclick="descargarTodo()" style="margin-bottom:32px;background:linear-gradient(135deg,#ff6b35,#ff9a35);display:none">
    ⬇️ Descargar imagen completa
  </button>
  <div class="cards-grid" id="cardsGrid"></div>
</div>

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
  // "Mar 13 2026 10:54AM"  →  solo fecha
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

  // Detectar índices por encabezado (fila 0)
  const headers = rows[0].map(h => String(h).trim());
  const idx = {
    N1:       headers.indexOf('N1'),
    N2:       headers.indexOf('N2'),
    Vendedor: headers.indexOf('Vendedor'),
    Cliente:  headers.indexOf('Cliente'),
    ProdConcat: headers.indexOf('ProdConcat'),
    Fecha:    headers.indexOf('Fecha'),
  };

  // Fallback por posición si no encuentra por nombre
  // (B=1, C=2, J=9, I=8, L=11, H=7 — índice base 0)
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

  // Cargar fotos desde BD antes de renderizar
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
//  IMÁGENES DE LA BD  (tabla `imagenes`, col `direccion`)
// ══════════════════════════════════════════════════════
// imagen-1 → vendedor con más ventas PayJoy (crédito)
// imagen-2 → vendedor con más ventas al contado
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
//  BANCO DE FRASES ALEATORIAS  (3 por tipo × variedad)
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
//  CSS INLINE PARA EL RENDER  (html2canvas)
// ══════════════════════════════════════════════════════
const CARD_CSS = `
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap');
  .vendor-card { width:380px; background:#0e111a; border-radius:22px; overflow:hidden;
    font-family:'DM Sans',sans-serif; border:1px solid rgba(255,255,255,.08); }
  .vc-header { height:6px; }
  .vc-header.contado { background:linear-gradient(90deg,#00e5a0,#00c887); }
  .vc-header.credito { background:linear-gradient(90deg,#0099ff,#006fd6); }
  .vc-header.empate  { background:linear-gradient(90deg,#ff6b35,#ff9a35); }
  .vc-body { padding:26px 28px 20px; }
  .vc-seller { display:flex; align-items:center; gap:14px; margin-bottom:14px; }
  .vc-avatar { width:52px; height:52px; border-radius:50%; display:flex; align-items:center;
    justify-content:center; font-family:'Syne',sans-serif; font-size:1.3rem; font-weight:800; }
  .vc-avatar.contado { background:rgba(0,229,160,.18); color:#00e5a0; }
  .vc-avatar.credito { background:rgba(0,153,255,.18); color:#0099ff; }
  .vc-avatar.empate  { background:rgba(255,107,53,.18); color:#ff6b35; }
  .vc-name { font-family:'Syne',sans-serif; font-weight:800; font-size:1.1rem;
    color:#eef0f6; line-height:1.1; text-transform:capitalize; }
  .vc-subtitle { font-size:.78rem; color:#7a8099; margin-top:3px; }
  .vc-congrats { border-radius:10px; padding:10px 14px; font-size:.82rem;
    font-weight:500; margin-bottom:18px; line-height:1.45; }
  .vc-congrats.contado { background:rgba(0,229,160,.09); color:#00e5a0; border-left:3px solid #00e5a0; }
  .vc-congrats.credito { background:rgba(0,153,255,.09); color:#0099ff; border-left:3px solid #0099ff; }
  .vc-congrats.empate  { background:rgba(255,107,53,.09); color:#ff6b35; border-left:3px solid #ff6b35; }
  .vc-sale { background:#161921; border-radius:10px; padding:10px 14px; margin-bottom:8px;
    display:flex; align-items:center; justify-content:space-between; gap:10px; }
  .vc-model { font-size:.84rem; color:#c8ccdb; font-weight:500; flex:1; }
  .vc-pill { font-size:.72rem; font-weight:700; padding:3px 9px; border-radius:20px; white-space:nowrap; }
  .vc-pill.contado { background:rgba(0,229,160,.15); color:#00e5a0; }
  .vc-pill.credito { background:rgba(0,153,255,.15); color:#0099ff; }
  .vc-section-label { font-size:.72rem; font-weight:700; letter-spacing:.6px;
    text-transform:uppercase; color:#7a8099; margin:14px 0 6px; padding-left:4px; }
  .vc-footer { border-top:1px solid rgba(255,255,255,.06); margin-top:auto; padding-top:12px;
    display:flex; justify-content:space-between; align-items:center; }
  .vc-total { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:800; }
  .vc-total.contado { color:#00e5a0; }
  .vc-total.credito { color:#0099ff; }
  .vc-total.empate  { color:#ff6b35; }
  .vc-total-label { font-size:.74rem; color:#7a8099; margin-top:2px; }
  .vc-date { font-size:.78rem; color:#7a8099; text-align:right; }
`;

// ══════════════════════════════════════════════════════
//  MAPA DE FOTOS DE VENDEDORES  (tabla imagenes, descripcion = nombre vendedor)
//  Se llena en renderTarjetas() con los datos de IMAGENES_BD
// ══════════════════════════════════════════════════════
// Aquí guardamos: nombreVendedorNormalizado → url de foto
const FOTOS_VENDEDORES = {};

// Registra todas las imágenes cuya descripción coincida con algún vendedor
// Se llama ANTES de renderizar, pasando el mapa de vendedores ya conocidos
function mapearFotosVendedores(vendedoresUnicos) {
  // Limpia el mapa
  Object.keys(FOTOS_VENDEDORES).forEach(k => delete FOTOS_VENDEDORES[k]);

  // Las fotos disponibles en la BD están en IMAGENES_BD_VENDEDORES
  // Cada entry: { descripcion: 'nombre vendedor', url: '...' }
  for (const { descripcion, url } of IMAGENES_BD_VENDEDORES) {
    const desc = normalizar(descripcion);
    // Buscar coincidencia exacta o parcial con algún vendedor del Excel
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

// ── Fotos de vendedores: se cargan dinámicamente desde la BD ──
// El array se llena al llamar cargarFotosBD() antes de renderizar
let IMAGENES_BD_VENDEDORES = [];

async function cargarFotosBD() {
  try {
    const res = await fetch('get_fotos_vendedores.php');
    if (!res.ok) throw new Error('HTTP ' + res.status);
    IMAGENES_BD_VENDEDORES = await res.json();
  } catch(e) {
    console.warn('No se pudieron cargar fotos de la BD:', e.message);
    IMAGENES_BD_VENDEDORES = []; // si falla, todos quedan con iniciales
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

  // Avatar: foto si existe en BD, si no iniciales
  const fotoVendedor = FOTOS_VENDEDORES[vendedor];
  const avatarEl = fotoVendedor
    ? `<div class="vc-avatar ${tipo}" style="overflow:hidden;padding:0;">
         <img src="${fotoVendedor}" alt="${vendedor}" crossorigin="anonymous"
              style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
              onerror="this.parentElement.innerHTML='${inits}';this.parentElement.style.padding='';">
       </div>`
    : `<div class="vc-avatar ${tipo}">${inits}</div>`;

  // ── Separar ventas en dos grupos ──
  const ventasContado = data.ventas.filter(v => v.modalidad === 'contado');
  const ventasCredito = data.ventas.filter(v => v.modalidad === 'credito');

  function ventaItems(lista, cls) {
    return lista.map(v => `
      <div class="vc-sale">
        <div class="vc-model">📱 ${v.modelo}</div>
        <div class="vc-pill ${cls}">${cls === 'contado' ? '💵 Contado' : '💳 PayJoy'}</div>
      </div>`).join('');
  }

  // Mostrar primero el grupo dominante, luego el otro
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

  // Mapear fotos de vendedores desde la BD
  const vendedoresUnicos = Object.keys(map);
  mapearFotosVendedores(vendedoresUnicos);

  for (const [vendedor, data] of Object.entries(map)) {
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
//  FUNCIÓN INTERNA DE CAPTURA (no se usa directamente, se captura por grupo)
// ══════════════════════════════════════════════════════
async function capturarTarjeta(vendedor, data, msg) {
  const renderZone = document.getElementById('render-zone');
  renderZone.innerHTML = '';

  const style = document.createElement('style');
  style.textContent = CARD_CSS;
  renderZone.appendChild(style);

  const cardEl = document.createElement('div');
  cardEl.innerHTML = buildCardHTML(vendedor, data, msg);
  renderZone.appendChild(cardEl.firstElementChild);

  // Esperar que cargue la imagen de la BD
  const img = renderZone.querySelector('img');
  if (img) {
    await new Promise(r => {
      if (img.complete && img.naturalWidth > 0) { r(); return; }
      img.onload  = r;
      img.onerror = r;
      setTimeout(r, 4000); // timeout máximo 4s
    });
  }
  await new Promise(r => setTimeout(r, 200));

  const canvas = await html2canvas(renderZone.querySelector('.vendor-card'), {
    scale: 2,
    backgroundColor: '#0e111a',
    useCORS: true,
    allowTaint: true,
    logging: false,
    imageTimeout: 5000,
  });

  renderZone.innerHTML = '';
  return canvas;
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
        background: #0d0f14;
        padding: 40px 36px 48px;
        width: fit-content;
      }
      .render-titulo {
        font-family: 'Syne', sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 8px;
        background: linear-gradient(135deg,#00e5a0,#0099ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }
      .render-subtitulo {
        font-family: 'DM Sans', sans-serif;
        font-size: .82rem;
        color: #7a8099;
        text-align: center;
        margin-bottom: 32px;
      }
      .render-pag {
        font-family: 'DM Sans', sans-serif;
        font-size: .75rem;
        color: #3a4055;
        text-align: center;
        margin-top: 28px;
        letter-spacing: .4px;
      }
      /* ── CLAVE: grid con filas de igual altura para html2canvas ── */
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
      // Recolectar fotos de avatar si existen
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

    // Esperar avatares/fotos
    await Promise.all(imgs.map(img => new Promise(r => {
      if (img.complete && img.naturalWidth > 0) { r(); return; }
      img.onload  = r;
      img.onerror = r;
      setTimeout(r, 5000);
    })));

    // Dejar que el DOM calcule alturas naturales
    await new Promise(r => setTimeout(r, 300));

    // ── Igualar altura de todas las tarjetas al más alto del grupo ──
    const cards = Array.from(grid.querySelectorAll('.vendor-card'));
    // Resetear cualquier height previa para medir natural
    cards.forEach(c => { c.style.height = ''; });
    await new Promise(r => setTimeout(r, 50));
    const maxH = Math.max(...cards.map(c => c.getBoundingClientRect().height));
    cards.forEach(c => { c.style.height = maxH + 'px'; });

    // Pausa final para que el repaint termine
    await new Promise(r => setTimeout(r, 200));

    try {
      const canvas = await html2canvas(wrapper, {
        scale: 4,              // 4× → resolución ~5000px de ancho, suficiente para WhatsApp HD
        backgroundColor: '#0d0f14',
        useCORS: true,
        allowTaint: true,
        logging: false,
        imageTimeout: 15000,
      });

      // PNG sin pérdida (toDataURL image/png no tiene parámetro de calidad,
      // ya es lossless por spec — obtenemos el máximo posible)
      const dataURL = canvas.toDataURL('image/png');

      // Verificar tamaño: si es muy pequeño forzar JPEG alta calidad como fallback
      const byteSize = Math.round((dataURL.length * 3) / 4);
      const finalURL = byteSize > 300_000
        ? dataURL                                         // PNG ya es grande → úsalo
        : canvas.toDataURL('image/jpeg', 1.0);           // fallback JPEG 100%

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