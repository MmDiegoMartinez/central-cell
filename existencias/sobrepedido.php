<?php
require_once '../funciones.php';

/* ════════════════════════════════════════════════════════════════
   CATÁLOGO BAJO PEDIDO (página pública)
   - Teléfonos: tabla telefonos_sobrepedido (solo estatus = 1)
   - Imágenes: se obtienen con obtenerSmartphones() (mismo mecanismo
     que catalogo.php) y se vinculan por nombre de modelo.
   ═══════════════════════════════════════════════════════════════ */

if (!function_exists('sp_h')) {
    function sp_h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * Devuelve la conexión PDO que ya usa el proyecto.
 * Busca una variable global PDO o una función de conexión sin parámetros.
 */
if (!function_exists('sp_pdo')) {
    function sp_pdo(): PDO {
        if (function_exists('conectarBD')) {
            $c = conectarBD();
            $c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $c;
        }
        foreach (['pdo', 'conn', 'conexion', 'db', 'dbh'] as $n) {
            if (isset($GLOBALS[$n]) && $GLOBALS[$n] instanceof PDO) {
                $GLOBALS[$n]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $GLOBALS[$n];
            }
        }
        foreach (['conectarDB', 'conectarBD', 'conectar', 'getConexion', 'obtenerConexion', 'getPDO', 'conexion'] as $fn) {
            if (function_exists($fn) && (new ReflectionFunction($fn))->getNumberOfRequiredParameters() === 0) {
                $c = $fn();
                if ($c instanceof PDO) {
                    $c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return $c;
                }
            }
        }
        throw new RuntimeException('No se encontró una conexión PDO en funciones.php');
    }
}

// ── Teléfonos activos (filtrado directamente en la base de datos) ──
$telefonos = [];
$errorCarga = null;
try {
    $st = sp_pdo()->prepare('SELECT id, nombre, precio FROM telefonos_sobrepedido WHERE estatus = 1 ORDER BY nombre ASC');
    $st->execute();
    $telefonos = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[sobrepedido] ' . $e->getMessage());
    $errorCarga = 'No se pudo cargar el catálogo. Intenta de nuevo en unos minutos.';
}

// ── Imágenes: misma función que usa obtenerSmartphones() en catalogo.php ──
$imagenes = [];
if ($telefonos) {
    try {
        $connImg = conectarBD();
        foreach ($telefonos as $t) {
            $imagenes[$t['nombre']] = obtenerImagenTelefono($connImg, $t['nombre']);
        }
    } catch (Throwable $e) {
        error_log('[sobrepedido] imágenes: ' . $e->getMessage());
    }
}

$totalModelos = count($telefonos);

// ── Rango de precios (redondeado a 100 para alinear con el paso del slider) ──
$precios    = array_map('floatval', array_column($telefonos, 'precio'));
$hayPrecios = (bool)$precios;
$precioMin  = $hayPrecios ? (int)(floor(min($precios) / 100) * 100) : 0;
$precioMax  = $hayPrecios ? (int)(ceil(max($precios) / 100) * 100)  : 100;
if ($precioMax <= $precioMin) $precioMax = $precioMin + 100;

$msgVacio = $errorCarga ? 'No se pudo cargar el catálogo' : ($totalModelos === 0 ? 'Aún no hay teléfonos bajo pedido' : 'Sin resultados');
$msgVacioSub = $errorCarga ?? ($totalModelos === 0 ? 'Vuelve pronto: estamos actualizando el catálogo.' : 'Intenta ajustar los filtros o el rango de precio.');
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Teléfonos bajo pedido</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="catalogo.css?v=<?php echo time(); ?>">
<style>
/* Solo componentes nuevos de esta página; todo lo demás viene de catalogo.css */
.sp-link-btn { width: auto; display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; text-decoration: none; white-space: nowrap; }
.sp-link-btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
.card-badge.sp-pedido { background: var(--info-bg); color: var(--info); }
.card-img-wrap { cursor: zoom-in; }
.sp-anticipo { order: 3; font-size: 12.5px; color: var(--ink3); }
.sp-anticipo strong { color: var(--ink2); font-weight: 600; font-variant-numeric: tabular-nums; }
.view-list .sp-anticipo { display: none; }

.sp-consulta {
  background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius);
  padding: 20px 22px; margin-bottom: 28px;
  display: flex; flex-wrap: wrap; align-items: center; gap: 14px 28px;
}
.sp-consulta-text { flex: 1 1 280px; }
.sp-consulta-text h2 { font-size: 16px; font-weight: 700; letter-spacing: -.01em; line-height: 1.3; }
.sp-consulta-text p  { font-size: 13px; color: var(--ink3); margin-top: 2px; }
.sp-consulta-form { flex: 1 1 380px; display: flex; flex-wrap: wrap; gap: 10px; }
.sp-input {
  flex: 1 1 220px; min-width: 0; height: 40px; padding: 0 14px;
  background: var(--surface2); border: 1px solid transparent; border-radius: 8px;
  color: var(--ink); font-size: 14px; outline: none;
  transition: background .15s, border-color .15s;
}
.sp-input::placeholder { color: var(--ink3); }
.sp-input:focus { background: var(--surface); border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
.sp-go { width: auto; display: inline-flex; align-items: center; gap: 6px; padding: 0 18px; height: 40px; white-space: nowrap; }
.sp-go:disabled { background: var(--surface2); color: var(--ink3); cursor: not-allowed; }
.sp-hint { flex-basis: 100%; font-size: 12.5px; color: var(--warning); min-height: 0; }
.sp-hint:empty { display: none; }
.sp-sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

@media (max-width: 640px) {
  .site-header .sp-link-btn { padding: 8px 12px; font-size: 13px; }
  .view-list .sp-anticipo { display: block; }
  .sp-go { flex: 1 1 100%; justify-content: center; }
}
</style>
</head>
<body>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Imagen ampliada">
  <button class="lightbox-close" onclick="cerrarLightbox()" aria-label="Cerrar"><span class="material-symbols-outlined" style="font-size:20px;">close</span></button>
  <img id="lightboxImg" src="" alt="">
  <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<!-- Aviso informativo -->
<div class="aviso-overlay" id="avisoOverlay" role="dialog" aria-modal="true" aria-labelledby="avisoTitulo">
  <div class="aviso-modal">
    <div class="aviso-head">
      <div class="aviso-head-icon material-symbols-outlined">local_shipping</div>
      <div>
        <div class="aviso-head-title" id="avisoTitulo">Teléfonos bajo pedido</div>
        <div class="aviso-head-sub">Leer antes de continuar</div>
      </div>
    </div>
    <div class="aviso-body">
      <div class="aviso-regla-main"><strong><span class="material-symbols-outlined" style="font-size:15px;">schedule</span> Tiempo de llegada</strong>Los teléfonos bajo pedido tardan aproximadamente entre <b>2 y 7 días</b> en llegar.</div>
      <div class="aviso-regla orange"><span class="aviso-icon material-symbols-outlined">payments</span><span>Para realizar un pedido se requiere un anticipo mínimo del <strong>50 % del precio</strong> del teléfono.</span></div>
      <div class="aviso-regla blue"><span class="aviso-icon material-symbols-outlined">receipt_long</span><span>El resto del importe deberá liquidarse conforme a las <strong>condiciones de compra</strong> que se establezcan.</span></div>
    </div>
    <div class="aviso-footer">
      <button class="aviso-btn" id="avisoClose" onclick="cerrarAviso()">Entendido, continuar →</button>
    </div>
  </div>
</div>

<header class="site-header">
  <div class="header-brand">
    <div class="dot"></div>
    <div><div class="brand-title"><span class="material-symbols-outlined" style="font-size:19px;">local_shipping</span> Bajo pedido</div></div>
  </div>

  <div class="search-wrap">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input type="text" id="buscador" placeholder="Buscar modelo…" autocomplete="off">
  </div>

  <div class="header-controls">
    <a class="aviso-btn sp-link-btn" href="catalogo.php"><span class="material-symbols-outlined" style="font-size:18px;">smartphone</span> Teléfonos en stock</a>
    <button class="dark-toggle" id="darkToggle" title="Cambiar tema" aria-label="Modo oscuro"><span class="material-symbols-outlined" style="font-size:16px;">dark_mode</span></button>
  </div>
</header>

<div class="layout">

  <aside class="sidebar">
    <?php if ($hayPrecios): ?>
    <div class="sidebar-section">
      <div class="sidebar-title">Rango de precio</div>
      <div class="price-range-wrap">
        <div class="price-range-labels">
          <span>Desde <strong id="lblMin">$<?= number_format($precioMin) ?></strong></span>
          <span>Hasta <strong id="lblMax">$<?= number_format($precioMax) ?></strong></span>
        </div>
        <div class="range-track" id="rangeTrack">
          <div class="range-fill" id="rangeFill"></div>
          <span class="range-thumb" id="thumbMin" role="slider" tabindex="0" aria-label="Precio mínimo"></span>
          <span class="range-thumb" id="thumbMax" role="slider" tabindex="0" aria-label="Precio máximo"></span>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </aside>

  <main class="main" id="mainArea">

    <?php if ($totalModelos > 0): ?>
    <!-- Consulta de especificaciones en Google -->
    <section class="sp-consulta" aria-labelledby="spConsultaTitulo">
      <div class="sp-consulta-text">
        <h2 id="spConsultaTitulo">¿Tienes dudas sobre las características del equipo?</h2>
        <p>Escribe o elige un modelo y abre sus especificaciones en Google.</p>
      </div>
      <div class="sp-consulta-form">
        <label class="sp-sr" for="spModelo">Modelo del teléfono</label>
        <input class="sp-input" id="spModelo" list="spModelos" placeholder="Buscar o seleccionar modelo…" autocomplete="off">
        <datalist id="spModelos">
          <?php foreach ($telefonos as $t): ?>
          <option value="<?= sp_h($t['nombre']) ?>"></option>
          <?php endforeach; ?>
        </datalist>
        <button type="button" class="aviso-btn sp-go" id="spBuscar" disabled>
          Ver especificaciones <span class="material-symbols-outlined" style="font-size:18px;">open_in_new</span>
        </button>
        <p class="sp-hint" id="spHint" role="status"></p>
      </div>
    </section>
    <?php endif; ?>

    <div class="results-bar">
      <div class="results-info">
        <div class="rstat accent">
          <div class="rstat-num" id="visibleCount"><?= $totalModelos ?></div>
          <div class="rstat-lbl">Modelos disponibles bajo pedido</div>
        </div>
      </div>
      <div class="results-actions">
        <button class="clear-btn" id="clearBtn" onclick="limpiarFiltros()"><span class="material-symbols-outlined" style="font-size:14px;">close</span> Limpiar</button>

        <div class="view-toggle" role="group" aria-label="Vista">
          <button class="view-btn active" id="btnGrid" onclick="setVista('grid')" title="Cuadrícula" aria-pressed="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
          </button>
          <button class="view-btn" id="btnList" onclick="setVista('list')" title="Lista" aria-pressed="false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>
          </button>
        </div>
      </div>
    </div>

    <div id="noResults" class="no-results<?= $totalModelos === 0 ? ' show' : '' ?>">
      <div class="no-results-icon material-symbols-outlined">search_off</div>
      <h3 id="noResultsTitle"><?= sp_h($msgVacio) ?></h3>
      <p id="noResultsSub"><?= sp_h($msgVacioSub) ?></p>
    </div>

    <div id="catalogoContainer">
    <?php if ($totalModelos > 0): ?>
    <section class="cat-bloque" id="bloquePedido">
      <div class="cat-divider">
        <span class="cat-icon material-symbols-outlined">local_shipping</span>
        <h2 class="cat-label">Bajo pedido</h2>
        <div class="cat-line"></div>
        <div class="cat-counts">
          <span class="cat-count" id="bloqueCount"><?= $totalModelos ?> modelos</span>
        </div>
      </div>

      <div class="cards-grid">
      <?php foreach ($telefonos as $idx => $t):
        $nombre = $t['nombre'];
        $precio = (float)$t['precio'];
        $img    = (string)($imagenes[$nombre] ?? '');
        $thumb  = $img !== '' ? $img : 'https://placehold.co/300x200?text=Sin+imagen';
        $full   = $img !== '' ? $img : 'https://placehold.co/400x400?text=Sin+imagen';
        $delay  = min($idx * 60, 800);
      ?>
        <article class="card"
                 style="animation-delay:<?= $delay ?>ms"
                 data-modelo="<?= sp_h(mb_strtolower($nombre, 'UTF-8')) ?>"
                 data-precio="<?= sp_h($precio) ?>">

          <div class="card-img-wrap" data-img="<?= sp_h($full) ?>" data-titulo="<?= sp_h($nombre) ?>" title="Ver imagen ampliada">
            <img src="<?= sp_h($thumb) ?>"
                 alt="<?= sp_h($nombre) ?>"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='https://placehold.co/300x200?text=Sin+imagen'">
            <div class="card-badge sp-pedido">Bajo pedido</div>
          </div>

          <div class="card-body">
            <div class="card-title"><?= sp_h($nombre) ?></div>

            <div class="card-price">
              <span class="price-label">Precio público</span>
              <span class="price-value"><span>$</span><?= number_format($precio, 2) ?></span>
            </div>

            <div class="sp-anticipo">Anticipo (50 %): <strong>$<?= number_format($precio / 2, 2) ?></strong></div>
          </div>
        </article>
      <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
    </div>

  </main>
</div>

<script>
const PRECIO_MIN_GLOBAL = <?= (int)$precioMin ?>;
const PRECIO_MAX_GLOBAL = <?= (int)$precioMax ?>;
const TOTAL_MODELOS     = <?= (int)$totalModelos ?>;

/* ── Tema ── */
const htmlEl  = document.documentElement;
const darkBtn = document.getElementById('darkToggle');
function setTheme(t) {
  htmlEl.setAttribute('data-theme', t);
  darkBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">' + (t === 'dark' ? 'light_mode' : 'dark_mode') + '</span>';
  try { localStorage.setItem('tema', t); } catch (e) {}
}
let temaGuardado = 'light';
try { temaGuardado = localStorage.getItem('tema') || 'light'; } catch (e) {}
setTheme(temaGuardado);
darkBtn.addEventListener('click', () =>
  setTheme(htmlEl.getAttribute('data-theme') === 'dark' ? 'light' : 'dark')
);

/* ── Aviso ── */
function cerrarAviso() {
  const el = document.getElementById('avisoOverlay');
  if (!el) return;
  el.classList.add('closing');
  setTimeout(() => el.remove(), 230);
}
setTimeout(() => document.getElementById('avisoClose')?.focus(), 350);

/* ── Lightbox ── */
const lightbox        = document.getElementById('lightbox');
const lightboxImg     = document.getElementById('lightboxImg');
const lightboxCaption = document.getElementById('lightboxCaption');

function abrirLightbox(src, titulo) {
  lightboxImg.src = src;
  lightboxImg.alt = titulo;
  lightboxCaption.textContent = titulo;
  lightbox.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function cerrarLightbox() {
  lightbox.classList.remove('open');
  document.body.style.overflow = '';
}
lightbox.addEventListener('click', e => { if (e.target === lightbox) cerrarLightbox(); });
document.querySelectorAll('.card-img-wrap').forEach(w =>
  w.addEventListener('click', () => abrirLightbox(w.dataset.img, w.dataset.titulo))
);

/* ── Vista cuadrícula / lista ── */
const mainArea = document.getElementById('mainArea');
const btnGrid  = document.getElementById('btnGrid');
const btnList  = document.getElementById('btnList');

function setVista(v) {
  const esList = v === 'list';
  mainArea.classList.toggle('view-list', esList);
  btnList.classList.toggle('active', esList);
  btnList.setAttribute('aria-pressed', String(esList));
  btnGrid.classList.toggle('active', !esList);
  btnGrid.setAttribute('aria-pressed', String(!esList));
  try { localStorage.setItem('vista', v); } catch (e) {}
}
try { const vg = localStorage.getItem('vista'); if (vg) setVista(vg); } catch (e) {}

/* ── Slider de precio (estado) ── */
let sliderMin = PRECIO_MIN_GLOBAL;
let sliderMax = PRECIO_MAX_GLOBAL;
const STEP        = 100;
const RANGO_TOTAL = PRECIO_MAX_GLOBAL - PRECIO_MIN_GLOBAL;

/* ── Filtros ── */
const CARDS = Array.from(document.querySelectorAll('.card')).map(el => ({
  el,
  modelo: el.dataset.modelo,
  precio: parseFloat(el.dataset.precio),
}));

const buscador      = document.getElementById('buscador');
const clearBtn      = document.getElementById('clearBtn');
const noResults     = document.getElementById('noResults');
const noResultsTit  = document.getElementById('noResultsTitle');
const noResultsSub  = document.getElementById('noResultsSub');
const visibleCount  = document.getElementById('visibleCount');
const bloquePedido  = document.getElementById('bloquePedido');
const bloqueCount   = document.getElementById('bloqueCount');

function aplicarFiltros() {
  const q = buscador.value.toLowerCase().trim();
  let visibles = 0;

  for (const c of CARDS) {
    const ok = (!q || c.modelo.includes(q)) && c.precio >= sliderMin && c.precio <= sliderMax;
    c.el.style.display = ok ? '' : 'none';
    if (ok) visibles++;
  }

  visibleCount.textContent = visibles;
  if (bloqueCount)  bloqueCount.textContent = visibles + (visibles === 1 ? ' modelo' : ' modelos');
  if (bloquePedido) bloquePedido.style.display = visibles ? '' : 'none';

  if (TOTAL_MODELOS > 0) {
    noResultsTit.textContent = 'Sin resultados';
    noResultsSub.textContent = 'Intenta ajustar los filtros o el rango de precio.';
    noResults.classList.toggle('show', visibles === 0);
  }

  const hayFiltro = q.length > 0 || sliderMin > PRECIO_MIN_GLOBAL || sliderMax < PRECIO_MAX_GLOBAL;
  clearBtn.classList.toggle('show', hayFiltro);
}

function limpiarFiltros() {
  buscador.value = '';
  sliderMin = PRECIO_MIN_GLOBAL;
  sliderMax = PRECIO_MAX_GLOBAL;
  renderSlider();
  aplicarFiltros();
}

/* ── Slider de precio (interfaz) ── */
const lblMin    = document.getElementById('lblMin');
const lblMax    = document.getElementById('lblMax');
const rangeFill = document.getElementById('rangeFill');
const thumbMin  = document.getElementById('thumbMin');
const thumbMax  = document.getElementById('thumbMax');
const track     = document.getElementById('rangeTrack');

function renderSlider() {
  if (!track) return;
  const pMin = ((sliderMin - PRECIO_MIN_GLOBAL) / RANGO_TOTAL) * 100;
  const pMax = ((sliderMax - PRECIO_MIN_GLOBAL) / RANGO_TOTAL) * 100;
  rangeFill.style.cssText = `left:${pMin}%;width:${pMax - pMin}%`;
  thumbMin.style.left = pMin + '%';
  thumbMax.style.left = pMax + '%';
  thumbMin.setAttribute('aria-valuenow', sliderMin);
  thumbMax.setAttribute('aria-valuenow', sliderMax);
  lblMin.textContent = '$' + sliderMin.toLocaleString('es-MX');
  lblMax.textContent = '$' + sliderMax.toLocaleString('es-MX');
}

function snapToStep(val) { return Math.round(val / STEP) * STEP; }

function pxToValue(clientX) {
  const rect  = track.getBoundingClientRect();
  const ratio = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
  return snapToStep(PRECIO_MIN_GLOBAL + ratio * RANGO_TOTAL);
}

function makeDraggable(thumb, isMin) {
  let dragging = false;

  thumb.addEventListener('pointerdown', e => {
    dragging = true;
    thumb.setPointerCapture(e.pointerId);
    thumb.style.zIndex = 2;
    e.preventDefault();
  });
  thumb.addEventListener('pointermove', e => {
    if (!dragging) return;
    const val = pxToValue(e.clientX);
    if (isMin) sliderMin = Math.max(PRECIO_MIN_GLOBAL, Math.min(val, sliderMax - STEP));
    else       sliderMax = Math.min(PRECIO_MAX_GLOBAL, Math.max(val, sliderMin + STEP));
    renderSlider();
    aplicarFiltros();
  });
  thumb.addEventListener('pointerup', () => { dragging = false; thumb.style.zIndex = ''; });
  thumb.addEventListener('keydown', e => {
    const delta = (e.key === 'ArrowRight' || e.key === 'ArrowUp') ? STEP : (e.key === 'ArrowLeft' || e.key === 'ArrowDown') ? -STEP : 0;
    if (!delta) return;
    e.preventDefault();
    if (isMin) sliderMin = Math.max(PRECIO_MIN_GLOBAL, Math.min(sliderMin + delta, sliderMax - STEP));
    else       sliderMax = Math.min(PRECIO_MAX_GLOBAL, Math.max(sliderMax + delta, sliderMin + STEP));
    renderSlider();
    aplicarFiltros();
  });
}

if (track) {
  makeDraggable(thumbMin, true);
  makeDraggable(thumbMax, false);

  track.addEventListener('pointerdown', e => {
    if (e.target === thumbMin || e.target === thumbMax) return;
    const val = pxToValue(e.clientX);
    if (Math.abs(val - sliderMin) <= Math.abs(val - sliderMax)) sliderMin = Math.max(PRECIO_MIN_GLOBAL, Math.min(val, sliderMax - STEP));
    else                                                        sliderMax = Math.min(PRECIO_MAX_GLOBAL, Math.max(val, sliderMin + STEP));
    renderSlider();
    aplicarFiltros();
  });
}

renderSlider();
aplicarFiltros();

let debounceTimer;
buscador.addEventListener('input', () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(aplicarFiltros, 120);
});

/* ── Consulta de especificaciones en Google ── */
const spInput = document.getElementById('spModelo');
if (spInput) {
  const spBtn  = document.getElementById('spBuscar');
  const spHint = document.getElementById('spHint');
  const modelos = Array.from(document.getElementById('spModelos').options).map(o => o.value);
  const clave = s => s.trim().replace(/\s+/g, ' ').toLowerCase();
  const buscarModelo = v => modelos.find(m => clave(m) === clave(v)) || null;

  spInput.addEventListener('input', () => {
    spBtn.disabled = !buscarModelo(spInput.value);
    spHint.textContent = '';
  });
  spInput.addEventListener('change', () => {
    const v = spInput.value.trim();
    spHint.textContent = (v && !buscarModelo(v)) ? 'Elige un modelo de la lista para continuar.' : '';
  });
  function abrirGoogle() {
    const modelo = buscarModelo(spInput.value);
    if (!modelo) return;
    const url = 'https://www.google.com/search?q=' + encodeURIComponent(modelo + ' especificaciones');
    window.open(url, '_blank', 'noopener,noreferrer');
  }
  spBtn.addEventListener('click', abrirGoogle);
  spInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); abrirGoogle(); } });
}

/* ── Teclado ── */
document.addEventListener('keydown', e => {
  const modalActivo = !!document.getElementById('avisoOverlay');
  const lbActivo    = lightbox.classList.contains('open');
  if (e.key === 'Escape') {
    if (modalActivo) cerrarAviso();
    else if (lbActivo) cerrarLightbox();
    else limpiarFiltros();
  }
  if (e.key === '/' && !modalActivo && !lbActivo && document.activeElement !== buscador && document.activeElement !== spInput) {
    e.preventDefault(); buscador.focus();
  }
});
</script>
</body>
</html>