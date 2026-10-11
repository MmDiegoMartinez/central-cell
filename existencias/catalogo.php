<?php
require_once '../funciones.php';

$smartphones        = obtenerSmartphones();
$fechaActualizacion = obtenerFechaUltimaActualizacion();

// ── Precalcular stock por teléfono UNA sola vez ──────────────
$stockPorId = [];
foreach ($smartphones as &$phone) {
    $phone['_stock'] = array_sum(array_column($phone['sucursales'], 'existencia'));
}
unset($phone);

// ── Agrupar por categoría/marca ──────────────────────────────
$porCategoria = [];
foreach ($smartphones as $phone) {
    $porCategoria[$phone['categoria']][$phone['marca']][] = $phone;
}

// ── Marcas únicas (una sola pasada) ─────────────────────────
$todasLasMarcas = array_unique(array_column($smartphones, 'marca'));
sort($todasLasMarcas);

$totalModelos = count($smartphones);
$totalStock   = array_sum(array_column($smartphones, '_stock'));

// ── Rango de precios ─────────────────────────────────────────
$precios   = array_column($smartphones, 'precio');
$precioMin = $precios ? (int)floor(min($precios)) : 0;
$precioMax = $precios ? (int)ceil(max($precios))  : 99999;

// ── Mapa de colores (búsqueda O(1) con array_key_first match) ─
const COLOR_MAP = [
    // ── Compuestos primero (más específicos) ──────────────────
    'GRIS OBSCURO' => ['#333333','#fff'],
    'AZUL MARINO'  => ['#5164c0','#fff'],
    'AZUL CIELO'   => ['#B3E5FC','#0D1B2A'],
    'VERDE MENTA'  => ['#80cbc4','#333'],
    'VERDE LIMA'   => ['#AEEA00','#0F1A00'],
    'HIELO ASTRAL' => ['#BFF6FF','#333'],
    'ARCTIC GLOW'  => ['#E8F1F7','#1A0B2E'],
    // ── Simples después ───────────────────────────────────────
    'NEGRO'        => ['#1a1a1a','#fff'],
    'BLANCO'       => ['#f5f5f5','#333'],
    'GRIS'         => ['#9e9e9e','#fff'],
    'PLATA'        => ['#c0c0c0','#333'],
    'PLATEADO'     => ['#c0c0c0','#333'],
    'DORADO'       => ['#c9a84c','#fff'],
    'ORO'          => ['#c9a84c','#fff'],
    'AZUL'         => ['#1a73e8','#fff'],
    'CELESTE'      => ['#4fc3f7','#333'],
    'VERDE'        => ['#2e7d32','#fff'],
    'ROJO'         => ['#c62828','#fff'],
    'ROSA'         => ['#f48fb1','#333'],
    'MORADO'       => ['#6a1b9a','#fff'],
    'LILA'         => ['#ce93d8','#333'],
    'NARANJA'      => ['#e65100','#fff'],
    'AMARILLO'     => ['#f9a825','#333'],
    'CAFE'         => ['#5d4037','#fff'],
    'TITANIO'      => ['#8d8d8d','#fff'],
    'OBSIDIANA'    => ['#1C1C1C','#fff'],
    'LAVANDA'      => ['#D8B4F8','#2B1B3A'],
    'VIOLETA'      => ['#A78BFA','#1A0B2E'],
];

// Caché de resultados de colorCSS para evitar recalcular el mismo color
$_colorCache = [];
function colorCSS(string $color): string {
    global $_colorCache;
    if (isset($_colorCache[$color])) return $_colorCache[$color];
    $upper = strtoupper($color);
    foreach (COLOR_MAP as $key => $val) {
        if (str_contains($upper, $key)) {
            return $_colorCache[$color] = $val[0].'|'.$val[1];
        }
    }
    return $_colorCache[$color] = '#888|#fff';
}
function colorHex(string $color): string { return explode('|', colorCSS($color))[0]; }

function iconoCategoria(string $cat): string {
    return match($cat) { 'Smartphones'=>'smartphone','Equipo Básico'=>'call','Smartwatch'=>'watch', default=>'inventory_2' };
}

// Precalcular stock de categoría de forma eficiente
function stockCategoria(array $marcasGrupo): int {
    $t = 0;
    foreach ($marcasGrupo as $telefonos)
        foreach ($telefonos as $p)
            $t += $p['_stock'];
    return $t;
}

// ── PayJoy: dispositivos NO compatibles (una sola consulta para todo el catálogo) ──
function pj_norm(string $s): string {
    $s = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s) ?? ''), 'UTF-8');
    return strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
}
function pj_cargar_no_compatibles(): ?array {
    try {
        $conn  = conectarBD();
        $filas = $conn->query('SELECT nombre_dispositivo FROM telefonos_no_payjoy')->fetchAll(PDO::FETCH_COLUMN);
        $set = [];
        foreach ($filas as $n) $set[pj_norm((string)$n)] = true;
        return $set;
    } catch (Throwable $e) {
        error_log('PayJoy: ' . $e->getMessage());
        return null;   // null = no se pudo verificar; no se muestra ningún indicador
    }
}
$noPayjoy = pj_cargar_no_compatibles();

?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catálogo de Smartphones</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="catalogo.css?v=<?php echo time(); ?>">
<style>
.pj-flag{position:relative;display:inline-block;vertical-align:-3px;margin-left:6px;line-height:0}
.pj-flag img{display:block;height:18px;width:auto}
.pj-flag .pj-x{position:absolute;left:50%;top:50%;width:22px;height:22px;transform:translate(-50%,-50%);pointer-events:none}
</style>
</head>
<body>


<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Imagen ampliada">
  <button class="lightbox-close" onclick="cerrarLightbox()" aria-label="Cerrar"><span class="material-symbols-outlined" style="font-size:20px;">close</span></button>
  <img id="lightboxImg" src="" alt="">
  <div class="lightbox-caption" id="lightboxCaption"></div>
</div>


<div class="aviso-overlay" id="avisoOverlay" role="dialog" aria-modal="true" aria-labelledby="avisoTitulo">
  <div class="aviso-modal">
    <div class="aviso-head">
      <div class="aviso-head-icon material-symbols-outlined">checklist</div>
      <div>
        <div class="aviso-head-title" id="avisoTitulo">Protocolo de Traspasos</div>
        <div class="aviso-head-sub">Leer antes de continuar</div>
      </div>
    </div>
    <div class="aviso-body">
      <div class="aviso-regla-main"><strong><span class="material-symbols-outlined" style="font-size:15px;">sync_alt</span> Traspaso entre tiendas</strong>Todo traspaso debe solicitarse previamente en el grupo, indicando almacén y vendedor, antes de mover el producto.</div>
      <div class="aviso-regla green"><span class="aviso-icon material-symbols-outlined">check_circle</span><span>Verificar producto y etiqueta antes de cualquier movimiento.</span></div>
      <div class="aviso-regla blue"><span class="aviso-icon material-symbols-outlined">smartphone</span><span>Validar <strong>IMEI</strong> en todos los teléfonos antes del traspaso.</span></div>
      <div class="aviso-regla green"><span class="aviso-icon material-symbols-outlined">local_shipping</span><span>Confirmar entrega con <strong>evidencia</strong> fotográfica o de recibo.</span></div>
      <div class="aviso-regla orange"><span class="aviso-icon material-symbols-outlined">warning</span><span>Todo equipo solicitado <strong>debe venderse</strong> o será retirado.</span></div>
      <div class="aviso-regla orange"><span class="aviso-icon material-symbols-outlined">block</span><span>Solo grupos autorizados — <strong>no números personales</strong>.</span></div>
      <div class="aviso-regla red"><span class="aviso-icon material-symbols-outlined">priority_high</span><span>Incumplimiento de cualquier punto será motivo de <strong>sanción</strong>.</span></div>
    </div>
    <div class="aviso-footer">
      <button class="aviso-btn" id="avisoClose" onclick="cerrarAviso()">Entendido, continuar →</button>
    </div>
  </div>
</div>


<header class="site-header">
  <div class="header-brand">
    <div class="dot"></div>
    <div><div class="brand-title"><span class="material-symbols-outlined" style="font-size:19px;">smartphone</span> Catálogo</div></div>
  </div>

  <div class="search-wrap">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input type="text" id="buscador" placeholder="Buscar modelo, marca, color…" autocomplete="off">
  </div>

  <div class="header-controls">
    <div class="header-stats">
      <div class="hstat accent">
        <div class="hstat-num" id="hstatStock"><?= $totalStock ?></div>
        <div class="hstat-lbl">En stock</div>
      </div>
    </div>
    <a class="aviso-btn" href="sobrepedido.php" style="width:auto;display:inline-flex;align-items:center;gap:6px;padding:9px 16px;text-decoration:none;white-space:nowrap;"><span class="material-symbols-outlined" style="font-size:18px;">local_shipping</span> Teléfonos sobre pedido</a>
    <button class="dark-toggle" id="darkToggle" title="Cambiar tema" aria-label="Modo oscuro"><span class="material-symbols-outlined" style="font-size:16px;">dark_mode</span></button>
  </div>

  <?php if ($fechaActualizacion): ?>
  <div class="header-date">
    <strong>Última actualización</strong><?= htmlspecialchars($fechaActualizacion) ?>
  </div>
  <?php endif; ?>
</header>


<div class="layout">

 
  <aside class="sidebar">

    <div class="sidebar-section">
      <div class="sidebar-title">Categoría</div>
      <div class="sidebar-btns">
        <button class="sidebar-btn active" data-filtro-cat="todas" onclick="filtrarCat(this,'todas')">
          Todas <span class="sb-badges"><span class="sb-badge stock"><?= $totalStock ?></span></span>
        </button>
        <?php foreach ($porCategoria as $cat => $marcasGrupo):
          $sCat = stockCategoria($marcasGrupo);
        ?>
        <button class="sidebar-btn" data-filtro-cat="<?= htmlspecialchars($cat) ?>" onclick="filtrarCat(this,'<?= htmlspecialchars(addslashes($cat)) ?>')">
          <span class="material-symbols-outlined" style="font-size:16px;vertical-align:-3px;"><?= iconoCategoria($cat) ?></span> <?= htmlspecialchars($cat) ?>
          <span class="sb-badges"><span class="sb-badge stock"><?= $sCat ?></span></span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Marca -->
    <div class="sidebar-section">
      <div class="sidebar-title">Marca</div>
      <div class="sidebar-btns">
        <button class="sidebar-btn active" data-filtro-marca="todas" onclick="filtrarMarca(this,'todas')">
          Todas <span class="sb-badges"><span class="sb-badge stock"><?= $totalStock ?></span></span>
        </button>
        <?php foreach ($todasLasMarcas as $marca):
          $sMarca = 0;
          foreach ($smartphones as $p) {
              if ($p['marca'] === $marca) $sMarca += $p['_stock'];
          }
        ?>
        <button class="sidebar-btn" data-filtro-marca="<?= htmlspecialchars($marca) ?>" onclick="filtrarMarca(this,'<?= htmlspecialchars(addslashes($marca)) ?>')">
          <?= htmlspecialchars($marca) ?>
          <span class="sb-badges"><span class="sb-badge stock"><?= $sMarca ?></span></span>
        </button>
        <?php endforeach; ?>
      </div>
    </div>


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


    <div style="padding:0 0 4px;">
      <div class="dots-legend">
        <span class="dots-legend-dot" style="background:#1a1a1a;"></span>
        <span class="dots-legend-dot" style="background:#1a73e8;"></span>
        <span class="dots-legend-dot" style="background:#c62828;"></span>
        <span>Cada punto = 1 pieza</span>
      </div>
    </div>

  </aside>

  <main class="main" id="mainArea">

    <div class="results-bar">
      <div class="results-info">
        <div class="rstat accent">
          <div class="rstat-num" id="visibleStock"><?= $totalStock ?></div>
          <div class="rstat-lbl">Piezas en stock</div>
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

    <div id="noResults" class="no-results">
      <div class="no-results-icon material-symbols-outlined">search_off</div>
      <h3>Sin resultados</h3>
      <p>Intenta ajustar los filtros o el rango de precio.</p>
    </div>

    <div id="catalogoContainer">
    <?php foreach ($porCategoria as $cat => $marcasGrupo):
      $stockCat = stockCategoria($marcasGrupo);
    ?>
    <section class="cat-bloque" data-cat-bloque="<?= htmlspecialchars($cat) ?>">
      <div class="cat-divider">
        <span class="cat-icon material-symbols-outlined"><?= iconoCategoria($cat) ?></span>
        <h2 class="cat-label"><?= htmlspecialchars($cat) ?></h2>
        <div class="cat-line"></div>
        <div class="cat-counts">
          <span class="cat-count s"><span class="material-symbols-outlined" style="font-size:10px;color:var(--success);font-variation-settings:'FILL' 1;">circle</span> <?= $stockCat ?> en stock</span>
        </div>
      </div>

      <?php foreach ($marcasGrupo as $marca => $telefonos):
        $stockMarcaLocal = array_sum(array_column($telefonos, '_stock'));
      ?>
      <div class="marca-grupo" data-marca-grupo="<?= htmlspecialchars($marca) ?>">
        <div class="marca-header">
          <span class="marca-label"><?= htmlspecialchars($marca) ?></span>
          <div class="marca-line"></div>
          <div class="marca-counts">
            <span class="marca-count s"><span class="material-symbols-outlined" style="font-size:10px;color:var(--success);font-variation-settings:'FILL' 1;">circle</span> <?= $stockMarcaLocal ?> pzas.</span>
          </div>
        </div>

        <div class="cards-grid">
        <?php foreach ($telefonos as $idx => $phone):
          $coloresStr = implode(' ', array_map('strtolower', $phone['colores']));
          $stockPhone = $phone['_stock'];
          $delay = min($idx * 60, 800);
        ?>
          <article class="card"
                   style="animation-delay:<?= $delay ?>ms"
                   data-modelo="<?= htmlspecialchars(strtolower($phone['descripcionModelo'])) ?>"
                   data-marca="<?= htmlspecialchars(strtolower($phone['marca'])) ?>"
                   data-colores="<?= htmlspecialchars($coloresStr) ?>"
                   data-cat="<?= htmlspecialchars($phone['categoria']) ?>"
                   data-stock="<?= $stockPhone ?>"
                   data-precio="<?= $phone['precio'] ?>"
                   data-nombre="<?= htmlspecialchars(strtolower($phone['descripcionModelo'])) ?>">

            <div class="card-img-wrap"
                 onclick="abrirLightbox('<?= !empty($phone['imagen']) ? htmlspecialchars($phone['imagen']) : 'https://placehold.co/400x400?text=📱' ?>','<?= htmlspecialchars(addslashes($phone['descripcionModelo'])) ?>')"
                 title="Ver imagen ampliada">
              <img src="<?= !empty($phone['imagen']) ? htmlspecialchars($phone['imagen']) : 'https://placehold.co/300x200?text=📱' ?>"
                   alt="<?= htmlspecialchars($phone['descripcionModelo']) ?>"
                   loading="lazy"
                   onerror="this.src='https://placehold.co/300x200?text=Sin+imagen'">
              <div class="card-stock-badge">
                <span class="sb-dot"></span><?= $stockPhone ?> pzas.
              </div>
              <div class="card-badge">En stock</div>
            </div>

            <div class="card-body">
              <?php $pjOk = ($noPayjoy === null) ? null : !isset($noPayjoy[pj_norm($phone['descripcionModelo'])]); ?>
<div class="card-title"><?= htmlspecialchars($phone['descripcionModelo']) ?><?php if ($pjOk !== null): ?>
  <span class="pj-flag" title="<?= $pjOk ? 'Compatible con PayJoy' : 'No compatible con PayJoy' ?>"><img src="https://i.ibb.co/ynd9kH94/716eb091156c.png" alt="<?= $pjOk ? 'Compatible con PayJoy' : 'No compatible con PayJoy' ?>" loading="lazy"><?php if (!$pjOk): ?><svg class="pj-x" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4l16 16M20 4L4 20" stroke="#fff" stroke-width="5" stroke-linecap="round"/><path d="M4 4l16 16M20 4L4 20" stroke="#D32F2F" stroke-width="3" stroke-linecap="round"/></svg><?php endif; ?></span>
<?php endif; ?></div>

              <?php if (!empty($phone['colores'])): ?>
              <div class="color-row">
                <span class="color-label">Colores:</span>
                <?php foreach ($phone['colores'] as $color):
                  $css = colorCSS($color); [$bg,$fg] = explode('|',$css);
                ?>
                <span class="color-chip" style="background:<?= $bg ?>;color:<?= $fg ?>;">
                  <span class="color-swatch" style="background:<?= $bg ?>;border-color:rgba(0,0,0,.2);"></span>
                  <?= htmlspecialchars(ucfirst(strtolower($color))) ?>
                </span>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <?php if ($phone['precio_inconsistente']): ?>
              <div class="price-alert">
                <div class="price-alert-title">Precios diferentes por color</div>
                <div class="price-alert-rows">
                  <?php foreach ($phone['precios_por_color'] as $c => $p):
                    $css2 = colorCSS($c); [$bg2] = explode('|',$css2);
                  ?>
                  <div class="price-alert-row">
                    <span class="price-alert-color"><span class="color-swatch" style="background:<?= $bg2 ?>;width:8px;height:8px;"></span><?= htmlspecialchars(ucfirst(strtolower($c))) ?></span>
                    <span class="price-alert-price">$<?= number_format($p,2) ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>

              <table class="suc-table">
                <thead><tr><th>Tienda</th><th>Color/stock</th><th>Pzas.</th></tr></thead>
                <tbody>
                <?php foreach ($phone['sucursales'] as $suc):
                  $dotItems = [];
                  if (!empty($suc['stock_por_color'])) {
                    foreach ($suc['stock_por_color'] as $sc => $qty)
                      for ($i=0;$i<(int)$qty;$i++) $dotItems[]=[$sc,(int)$qty];
                  } elseif (!empty($suc['colores'])) {
                    $nC = count($suc['colores']); $ex = (int)$suc['existencia'];
                    $base = $nC>0?(int)floor($ex/$nC):0; $resto = $nC>0?$ex%$nC:0;
                    foreach ($suc['colores'] as $ix => $sc) {
                      $qty = $base+($ix<$resto?1:0);
                      for ($i=0;$i<$qty;$i++) $dotItems[]=[$sc,$qty];
                    }
                  } else {
                    for ($i=0;$i<(int)$suc['existencia'];$i++) $dotItems[]=['GRIS',$suc['existencia']];
                  }
                  $MAX=30; $total=count($dotItems); $over=$total>$MAX;
                  $show=$over?array_slice($dotItems,0,$MAX):$dotItems;
                ?>
                <tr>
                  <td><div class="suc-name"><span class="suc-dot"></span><?= htmlspecialchars($suc['nombre']) ?></div></td>
                  <td>
                    <div class="suc-stock-dots">
                      <?php foreach ($show as [$sc,$qty]):
                        $hex=colorHex($sc); $lbl=ucfirst(strtolower($sc));
                      ?>
                      <span class="suc-sdot" style="background:<?= $hex ?>;" title="<?= htmlspecialchars($lbl) ?>"></span>
                      <?php endforeach; ?>
                      <?php if ($over): ?><span style="font-size:9px;color:var(--ink3);align-self:center;">+<?= $total-$MAX ?></span><?php endif; ?>
                    </div>
                  </td>
                  <td><span class="suc-qty"><?= $suc['existencia'] ?></span><span class="suc-unit">pz</span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>

              <div class="card-price">
                <span class="price-label"><?= $phone['precio_inconsistente']?'Precio desde':'Precio público' ?></span>
                <span class="price-value"><span>$</span><?= number_format($phone['precio'],2) ?></span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
    </div>

  </main>
</div>

<script>

const PRECIO_MIN_GLOBAL = <?= $precioMin ?>;
const PRECIO_MAX_GLOBAL = <?= $precioMax ?>;


const htmlEl   = document.documentElement;
const darkBtn  = document.getElementById('darkToggle');
setTheme(localStorage.getItem('tema') || 'light');

function setTheme(t) {
  htmlEl.setAttribute('data-theme', t);
  darkBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">' + (t === 'dark' ? 'light_mode' : 'dark_mode') + '</span>';
  localStorage.setItem('tema', t);
}
darkBtn.addEventListener('click', () =>
  setTheme(htmlEl.getAttribute('data-theme') === 'dark' ? 'light' : 'dark')
);


function cerrarAviso() {
  const el = document.getElementById('avisoOverlay');
  el.classList.add('closing');
  setTimeout(() => el.remove(), 230);
}
setTimeout(() => document.getElementById('avisoClose')?.focus(), 350);


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
  localStorage.setItem('vista', v);
}
const vistaGuardada = localStorage.getItem('vista');
if (vistaGuardada) setVista(vistaGuardada);


const CARDS = Array.from(document.querySelectorAll('.card')).map(el => ({
  el,
  modelo : el.dataset.modelo,
  marca  : el.dataset.marca,
  colores: el.dataset.colores,
  cat    : el.dataset.cat,
  stock  : parseInt(el.dataset.stock, 10),
  precio : parseFloat(el.dataset.precio),
  grid   : el.closest('.cards-grid'),
  mgEl   : el.closest('.marca-grupo'),
  cbEl   : el.closest('.cat-bloque'),
}));


const catBloques  = Array.from(document.querySelectorAll('.cat-bloque'));
const marcaGrupos = Array.from(document.querySelectorAll('.marca-grupo'));

const buscador     = document.getElementById('buscador');
const clearBtn     = document.getElementById('clearBtn');
const noResults    = document.getElementById('noResults');
const visibleStock = document.getElementById('visibleStock');
const hstatStock   = document.getElementById('hstatStock');

let catActiva   = 'todas';
let marcaActiva = 'todas';


const _visibleMarcas = new Set();
const _visibleCats   = new Set();

function aplicarFiltros() {
  const q    = buscador.value.toLowerCase().trim();
  const pMin = +rangeMin.value;
  const pMax = +rangeMax.value;

  const gridMap = new Map();

  let totalStock = 0;
  _visibleMarcas.clear();
  _visibleCats.clear();

  for (const c of CARDS) {
    const visible =
      (catActiva   === 'todas' || c.cat   === catActiva) &&
      (marcaActiva === 'todas' || c.marca === marcaActiva.toLowerCase()) &&
      (!q || c.modelo.includes(q) || c.marca.includes(q) || c.colores.includes(q)) &&
      c.precio >= pMin && c.precio <= pMax;

    c.el.style.display = visible ? '' : 'none';

    if (visible) {
      totalStock += c.stock;
      _visibleMarcas.add(c.mgEl);
      _visibleCats.add(c.cbEl);
    }
  }

  for (const mg of marcaGrupos) mg.style.display = _visibleMarcas.has(mg) ? '' : 'none';
  for (const cb of catBloques)  cb.style.display  = _visibleCats.has(cb)  ? '' : 'none';

  visibleStock.textContent = totalStock;
  if (hstatStock) hstatStock.textContent = totalStock;

  const hayFiltro = q.length > 0 || pMin > PRECIO_MIN_GLOBAL || pMax < PRECIO_MAX_GLOBAL;
  noResults.classList.toggle('show', _visibleCats.size === 0);
  clearBtn.classList.toggle('show', hayFiltro);
}

function filtrarCat(btn, cat) {
  document.querySelectorAll('[data-filtro-cat]').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  catActiva = cat;
  aplicarFiltros();
}
function filtrarMarca(btn, marca) {
  document.querySelectorAll('[data-filtro-marca]').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  marcaActiva = marca;
  aplicarFiltros();
}
function limpiarFiltros() {
  buscador.value = '';
  rangeMin.value = PRECIO_MIN_GLOBAL;
  rangeMax.value = PRECIO_MAX_GLOBAL;
  actualizarRango(); 
                      }

const lblMin    = document.getElementById('lblMin');
const lblMax    = document.getElementById('lblMax');
const rangeFill = document.getElementById('rangeFill');
const thumbMin  = document.getElementById('thumbMin');
const thumbMax  = document.getElementById('thumbMax');
const track     = document.getElementById('rangeTrack');
const STEP      = 100;
const RANGO_TOTAL = PRECIO_MAX_GLOBAL - PRECIO_MIN_GLOBAL;


let sliderMin = PRECIO_MIN_GLOBAL;
let sliderMax = PRECIO_MAX_GLOBAL;


const rangeMin = { get value() { return sliderMin; }, set value(v) { sliderMin = +v; } };
const rangeMax = { get value() { return sliderMax; }, set value(v) { sliderMax = +v; } };

function renderSlider() {
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

function snapToStep(val) {
  return Math.round(val / STEP) * STEP;
}

function pxToValue(clientX) {
  const rect = track.getBoundingClientRect();
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
    if (isMin) {
      sliderMin = Math.min(val, sliderMax - STEP);
    } else {
      sliderMax = Math.max(val, sliderMin + STEP);
    }
    renderSlider();
    aplicarFiltros();
  });

  thumb.addEventListener('pointerup', () => {
    dragging = false;
    thumb.style.zIndex = '';
  });

 
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

makeDraggable(thumbMin, true);
makeDraggable(thumbMax, false);


track.addEventListener('pointerdown', e => {
  if (e.target === thumbMin || e.target === thumbMax) return;
  const val = pxToValue(e.clientX);
  const distMin = Math.abs(val - sliderMin);
  const distMax = Math.abs(val - sliderMax);
  if (distMin <= distMax) sliderMin = Math.min(val, sliderMax - STEP);
  else                    sliderMax = Math.max(val, sliderMin + STEP);
  renderSlider();
  aplicarFiltros();
});

// Inicializar
renderSlider();
aplicarFiltros();

function actualizarRango() {
  renderSlider();
  aplicarFiltros();
}


let debounceTimer;
buscador.addEventListener('input', () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(aplicarFiltros, 120);
});

document.addEventListener('keydown', e => {
  const modalActivo = !!document.getElementById('avisoOverlay');
  const lbActivo    = lightbox.classList.contains('open');
  if (e.key === 'Escape') {
    if (modalActivo) cerrarAviso();
    else if (lbActivo) cerrarLightbox();
    else limpiarFiltros();
  }
  if (e.key === '/' && !modalActivo && !lbActivo && document.activeElement !== buscador) {
    e.preventDefault(); buscador.focus();
  }
});
</script>
</body>
</html>