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
    'NEGRO'        => ['#1a1a1a','#fff'],
    'BLANCO'       => ['#f5f5f5','#333'],
    'GRIS OBSCURO' => ['#333333','#fff'],
    'GRIS'         => ['#9e9e9e','#fff'],
    'PLATA'        => ['#c0c0c0','#333'],
    'PLATEADO'     => ['#c0c0c0','#333'],
    'DORADO'       => ['#c9a84c','#fff'],
    'ORO'          => ['#c9a84c','#fff'],
    'AZUL MARINO'  => ['#5164c0','#fff'],
    'AZUL CIELO'   => ['#B3E5FC','#0D1B2A'],
    'AZUL'         => ['#1a73e8','#fff'],
    'CELESTE'      => ['#4fc3f7','#333'],
    'VERDE MENTA'  => ['#80cbc4','#333'],
    'VERDE LIMA'   => ['#AEEA00','#0F1A00'],
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
    'HIELO ASTRAL' => ['#BFF6FF','#333'],
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
    return match($cat) { 'Smartphones'=>'📱','Equipo Básico'=>'📞','Smartwatch'=>'⌚', default=>'📦' };
}

// Precalcular stock de categoría de forma eficiente
function stockCategoria(array $marcasGrupo): int {
    $t = 0;
    foreach ($marcasGrupo as $telefonos)
        foreach ($telefonos as $p)
            $t += $p['_stock'];
    return $t;
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>📱 Catálogo de Smartphones</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Instrument+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════════════════
   VARIABLES — LIGHT & DARK
   ══════════════════════════════════════════════════════════════ */
:root {
  --bg:        #F5F3EE;
  --surface:   #FFFFFF;
  --surface2:  #EDEBE5;
  --ink:       #1A1815;
  --ink2:      #5C5750;
  --ink3:      #9B9690;
  --accent:    #E8440A;
  --accent2:   #FF7A45;
  --success:   #1A7A4A;
  --warning:   #b45309;
  --warning-bg:#fef3c7;
  --warning-bd:#fcd34d;
  --border:    #DDD9D0;
  --radius:    16px;
  --sans:      'Instrument Sans', sans-serif;
  --display:   'Syne', sans-serif;
  --shadow:    0 2px 16px rgba(26,24,21,.08);
  --shadow-lg: 0 8px 40px rgba(26,24,21,.14);
  --header-bg: #1A1815;
}
[data-theme="dark"] {
  --bg:        #111110;
  --surface:   #1C1B1A;
  --surface2:  #252422;
  --ink:       #F0EDE8;
  --ink2:      #A8A39C;
  --ink3:      #6B6660;
  --border:    #2E2C29;
  --shadow:    0 2px 16px rgba(0,0,0,.3);
  --shadow-lg: 0 8px 40px rgba(0,0,0,.5);
  --warning-bg:#2a1f00;
  --warning-bd:#7c5600;
  --header-bg: #0D0C0B;
}
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
html { scroll-behavior: smooth; }
body { font-family:var(--sans); background:var(--bg); color:var(--ink); min-height:100vh; transition: background .3s, color .3s; }

/* ══ MODAL AVISO ═══════════════════════════════════════════════ */
.aviso-overlay {
  position:fixed; inset:0; z-index:2000;
  background:rgba(10,8,6,.85); backdrop-filter:blur(6px);
  display:flex; align-items:center; justify-content:center; padding:16px;
  animation:avisoIn .3s ease;
}
@keyframes avisoIn { from{opacity:0} to{opacity:1} }
.aviso-modal { background:var(--surface); border-radius:22px; max-width:460px; width:100%; box-shadow:0 28px 80px rgba(0,0,0,.5); overflow:hidden; animation:avisoUp .35s cubic-bezier(.34,1.4,.64,1); }
@keyframes avisoUp { from{opacity:0;transform:translateY(30px) scale(.96)} to{opacity:1;transform:none} }
.aviso-head { background:var(--header-bg); border-bottom:3px solid var(--accent); padding:22px 24px 18px; display:flex; align-items:flex-start; gap:14px; }
.aviso-head-icon { font-size:34px; flex-shrink:0; line-height:1; }
.aviso-head-title { font-family:var(--display); font-size:18px; font-weight:800; color:#fff; letter-spacing:-.4px; line-height:1.25; }
.aviso-head-sub { font-size:11px; color:rgba(255,255,255,.45); text-transform:uppercase; letter-spacing:1.2px; margin-top:4px; }
.aviso-body { padding:20px 24px 16px; display:flex; flex-direction:column; gap:9px; max-height:58vh; overflow-y:auto; }
.aviso-regla-main { background:#FFFBEB; border:1.5px solid #FCD34D; border-radius:14px; padding:14px 16px; font-size:13.5px; line-height:1.6; color:#78350f; }
.aviso-regla-main strong { font-family:var(--display); font-size:15px; font-weight:800; display:block; margin-bottom:4px; color:#92400e; }
.aviso-regla { display:flex; align-items:flex-start; gap:12px; padding:11px 14px; border-radius:12px; font-size:13px; font-weight:500; line-height:1.5; }
.aviso-icon { font-size:17px; flex-shrink:0; margin-top:1px; }
.aviso-regla.green  { background:#F0FDF4; color:#14532d; }
.aviso-regla.blue   { background:#EFF6FF; color:#1e3a8a; }
.aviso-regla.orange { background:#FFF7ED; color:#7c2d12; }
.aviso-regla.red    { background:#FEF2F2; color:#7f1d1d; }
.aviso-regla strong { font-weight:700; }
.aviso-footer { padding:6px 24px 24px; }
.aviso-btn { width:100%; padding:15px 20px; background:var(--accent); color:#fff; border:none; border-radius:13px; font-family:var(--display); font-size:15px; font-weight:800; cursor:pointer; transition:background .15s,transform .1s; }
.aviso-btn:hover  { background:var(--accent2); }
.aviso-btn:active { transform:scale(.98); }
.aviso-overlay.closing             { animation:avisoOut .22s ease forwards; }
.aviso-overlay.closing .aviso-modal { animation:avisoDown .22s ease forwards; }
@keyframes avisoOut  { to{opacity:0} }
@keyframes avisoDown { to{opacity:0;transform:translateY(20px) scale(.97)} }
@media(max-width:480px){
  .aviso-overlay{padding:0;align-items:flex-end;}
  .aviso-modal{border-radius:22px 22px 0 0;max-width:100%;max-height:90vh;display:flex;flex-direction:column;}
  .aviso-body{flex:1;overflow-y:auto;}
}

/* ══ LIGHTBOX ══════════════════════════════════════════════════ */
.lightbox {
  position:fixed; inset:0; z-index:1500;
  background:rgba(0,0,0,.92); backdrop-filter:blur(12px);
  display:flex; align-items:center; justify-content:center; padding:24px;
  opacity:0; pointer-events:none; transition:opacity .25s;
}
.lightbox.open { opacity:1; pointer-events:all; }
.lightbox img {
  max-width:min(500px,90vw); max-height:80vh;
  object-fit:contain; border-radius:20px;
  transform:scale(.88); transition:transform .3s cubic-bezier(.34,1.3,.64,1);
  box-shadow:0 40px 100px rgba(0,0,0,.7);
}
.lightbox.open img { transform:scale(1); }
.lightbox-close {
  position:absolute; top:20px; right:24px;
  background:rgba(255,255,255,.12); border:none; color:#fff;
  border-radius:50%; width:40px; height:40px; font-size:18px; cursor:pointer;
  display:flex; align-items:center; justify-content:center;
  transition:background .15s, transform .15s;
}
.lightbox-close:hover { background:rgba(255,255,255,.22); transform:scale(1.1); }
.lightbox-caption {
  position:absolute; bottom:28px; left:50%; transform:translateX(-50%);
  font-family:var(--display); font-size:14px; font-weight:700; color:#fff;
  background:rgba(255,255,255,.1); backdrop-filter:blur(8px);
  padding:8px 20px; border-radius:99px; white-space:nowrap; max-width:90vw; overflow:hidden; text-overflow:ellipsis;
}
.card-img-wrap img { cursor:zoom-in; }

/* ══ HEADER ════════════════════════════════════════════════════ */
.site-header {
  background:var(--header-bg); color:#fff; padding:0 28px;
  position:sticky; top:0; z-index:100;
  display:flex; align-items:center; justify-content:space-between;
  gap:16px; height:68px; border-bottom:3px solid var(--accent);
  transition:background .3s;
}
.header-brand { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.header-brand .dot { width:9px; height:9px; background:var(--accent); border-radius:50%; animation:pulse 2s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.4)} }
.brand-title { font-family:var(--display); font-size:19px; font-weight:800; letter-spacing:-.5px; }

.header-controls { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.header-stats { display:flex; gap:10px; }
.hstat { display:flex; flex-direction:column; align-items:center; background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.11); border-radius:9px; padding:5px 12px; min-width:64px; }
.hstat-num { font-family:var(--display); font-size:18px; font-weight:800; line-height:1; color:#fff; }
.hstat-lbl { font-size:9px; text-transform:uppercase; letter-spacing:1.2px; color:rgba(255,255,255,.4); margin-top:2px; }
.hstat.accent .hstat-num { color:var(--accent2); }

/* Dark mode toggle */
.dark-toggle {
  background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15);
  color:#fff; border-radius:9px; padding:7px 10px; cursor:pointer;
  font-size:16px; line-height:1; transition:background .2s;
  display:flex; align-items:center; justify-content:center;
}
.dark-toggle:hover { background:rgba(255,255,255,.18); }

.search-wrap { flex:1; max-width:360px; position:relative; }
.search-wrap svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:rgba(255,255,255,.4); pointer-events:none; }
#buscador { width:100%; padding:9px 14px 9px 38px; background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:9px; color:#fff; font-family:var(--sans); font-size:13px; outline:none; transition:background .2s,border-color .2s; }
#buscador::placeholder { color:rgba(255,255,255,.35); }
#buscador:focus { background:rgba(255,255,255,.15); border-color:var(--accent); }

.header-date { font-size:10px; color:rgba(255,255,255,.38); text-align:right; flex-shrink:0; line-height:1.5; }
.header-date strong { display:block; color:rgba(255,255,255,.65); font-size:9px; text-transform:uppercase; letter-spacing:1px; margin-bottom:2px; }

/* ══ LAYOUT ════════════════════════════════════════════════════ */
.layout { display:grid; grid-template-columns:240px 1fr; min-height:calc(100vh - 68px); }

/* ══ SIDEBAR ═══════════════════════════════════════════════════ */
.sidebar { background:var(--surface); border-right:1px solid var(--border); padding:20px 0; position:sticky; top:68px; height:calc(100vh - 68px); overflow-y:auto; transition:background .3s,border-color .3s; }
.sidebar-section { margin-bottom:18px; }
.sidebar-title { font-family:var(--display); font-size:9.5px; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:var(--ink3); padding:0 18px 8px; border-bottom:1px solid var(--border); margin-bottom:6px; }
.sidebar-btn { display:block; width:100%; text-align:left; padding:9px 18px; background:none; border:none; font-family:var(--sans); font-size:12.5px; font-weight:500; color:var(--ink2); cursor:pointer; transition:all .15s; border-left:3px solid transparent; }
.sidebar-btn:hover,.sidebar-btn.active { background:var(--surface2); color:var(--ink); border-left-color:var(--accent); }
.sb-badges { float:right; display:flex; gap:3px; align-items:center; }
.sb-badge { font-size:9.5px; border-radius:99px; padding:1px 5px; font-weight:600; line-height:1.6; }
.sb-badge.stock { background:rgba(26,122,74,.14); color:var(--success); }

/* Slider de precio */
.price-range-wrap { padding:0 18px 4px; }
.price-range-labels { display:flex; justify-content:space-between; font-size:11px; color:var(--ink3); margin-bottom:10px; }
.price-range-labels strong { font-family:var(--display); font-weight:700; color:var(--ink); font-size:12px; }
.range-track { position:relative; height:4px; background:var(--border); border-radius:99px; margin:8px 9px 16px; }
.range-fill  { position:absolute; height:4px; background:var(--accent); border-radius:99px; pointer-events:none; }
.range-thumb {
  position:absolute; width:20px; height:20px; border-radius:50%;
  background:var(--surface); border:2.5px solid var(--accent);
  top:50%; transform:translate(-50%,-50%);
  box-shadow:0 2px 6px rgba(0,0,0,.2);
  cursor:grab; touch-action:none; user-select:none;
  transition:box-shadow .15s, border-color .15s;
  z-index:1;
}
.range-thumb:active { cursor:grabbing; box-shadow:0 0 0 6px rgba(232,68,10,.15); }
.range-thumb:focus  { outline:none; box-shadow:0 0 0 4px rgba(232,68,10,.25); }

/* Leyenda puntos */
.dots-legend { display:flex; align-items:center; gap:5px; font-size:10px; color:var(--ink3); padding:6px 10px; background:var(--surface2); border-radius:8px; margin:0 18px; }
.dots-legend-dot { width:8px; height:8px; border-radius:50%; border:1px solid rgba(0,0,0,.12); flex-shrink:0; }

/* ══ MAIN ══════════════════════════════════════════════════════ */
.main { padding:28px 28px; overflow-x:hidden; }

/* ── Barra de resultados y controles ── */
.results-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.results-info { display:flex; gap:16px; align-items:center; }
.rstat { display:flex; flex-direction:column; }
.rstat-num { font-family:var(--display); font-size:24px; font-weight:800; line-height:1; color:var(--ink); }
.rstat-lbl { font-size:10px; text-transform:uppercase; letter-spacing:1.2px; color:var(--ink3); margin-top:2px; }
.rstat.accent .rstat-num { color:var(--accent); }
.results-actions { display:flex; align-items:center; gap:8px; }
.clear-btn { display:none; font-size:12px; color:var(--accent); background:none; border:none; cursor:pointer; font-family:var(--sans); font-weight:600; padding:5px 10px; border-radius:7px; transition:background .15s; }
.clear-btn:hover { background:rgba(232,68,10,.08); }
.clear-btn.show  { display:block; }

/* Vista toggle */
.view-toggle { display:flex; gap:2px; background:var(--surface2); border-radius:8px; padding:3px; }
.view-btn { background:none; border:none; padding:6px 8px; border-radius:6px; cursor:pointer; color:var(--ink3); transition:all .15s; display:flex; align-items:center; }
.view-btn.active { background:var(--surface); color:var(--accent); box-shadow:var(--shadow); }
.view-btn svg { display:block; }

/* ── Categoría ── */
.cat-bloque  { margin-bottom:48px; }
.cat-divider { display:flex; align-items:center; gap:12px; margin-bottom:20px; }
.cat-icon    { font-size:26px; flex-shrink:0; }
.cat-label   { font-family:var(--display); font-size:26px; font-weight:800; letter-spacing:-1.5px; }
.cat-line    { flex:1; height:2px; background:var(--border); border-radius:2px; }
.cat-counts  { display:flex; flex-direction:column; align-items:flex-end; flex-shrink:0; gap:2px; }
.cat-count   { font-size:10.5px; font-weight:600; white-space:nowrap; }
.cat-count.s { color:var(--success); }

/* ── Marca ── */
.marca-grupo  { margin-bottom:32px; }
.marca-header { display:flex; align-items:center; gap:10px; margin-bottom:14px; padding-left:4px; }
.marca-label  { font-family:var(--display); font-size:15px; font-weight:700; letter-spacing:-.3px; color:var(--ink2); background:var(--surface2); border-radius:8px; padding:4px 13px; transition:background .3s; }
.marca-line   { flex:1; height:1px; background:var(--border); }
.marca-counts { display:flex; gap:7px; align-items:center; }
.marca-count  { font-size:10px; font-weight:600; padding:2px 7px; border-radius:99px; white-space:nowrap; }
.marca-count.s { background:rgba(26,122,74,.1); color:var(--success); }

/* ══ GRID VIEW ═════════════════════════════════════════════════ */
.cards-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(285px,1fr)); gap:18px; }

/* ══ LIST VIEW ═════════════════════════════════════════════════ */
.view-list .cards-grid { grid-template-columns:1fr; gap:10px; }
.view-list .card { flex-direction:row; height:110px; }
.view-list .card-img-wrap { width:100px; height:110px; flex-shrink:0; border-radius:var(--radius) 0 0 var(--radius); }
.view-list .card-body { padding:12px 16px; flex-direction:row; align-items:center; gap:16px; flex-wrap:wrap; overflow:hidden; }
.view-list .card-title { font-size:14px; flex:1; min-width:120px; }
.view-list .color-row  { flex-shrink:0; }
.view-list .suc-table  { display:none; }
.view-list .price-alert { display:none; }
.view-list .card-price { margin-top:0; padding-top:0; border-top:none; flex-shrink:0; }
.view-list .price-value { font-size:18px; }
.view-list .card-stock-badge { font-size:9px; padding:2px 8px; }
.view-list .card-badge { font-size:9px; padding:2px 8px; }
.view-list .card:hover { transform:translateX(3px); }

/* ══ TARJETA ═══════════════════════════════════════════════════ */
.card {
  background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
  overflow:hidden; box-shadow:var(--shadow); display:flex; flex-direction:column;
  transition:transform .22s, box-shadow .22s, background .3s, border-color .3s;
  opacity:0; transform:translateY(22px);
  animation:cardIn .5s ease forwards;
}
@keyframes cardIn { to { opacity:1; transform:none; } }
.card:hover { transform:translateY(-4px); box-shadow:var(--shadow-lg); }

.card-img-wrap { position:relative; background:var(--surface2); height:190px; display:flex; align-items:center; justify-content:center; overflow:hidden; transition:background .3s; }
.card-img-wrap img { width:100%; height:100%; object-fit:contain; padding:14px; transition:transform .3s ease; }
.card:hover .card-img-wrap img { transform:scale(1.05); }
.card-badge { position:absolute; top:10px; right:10px; background:var(--accent); color:#fff; font-size:9.5px; font-weight:700; font-family:var(--display); letter-spacing:.5px; padding:3px 9px; border-radius:99px; text-transform:uppercase; }
.card-stock-badge { position:absolute; top:10px; left:10px; background:rgba(26,24,21,.78); backdrop-filter:blur(4px); color:#fff; font-size:10px; font-weight:700; font-family:var(--display); padding:3px 9px; border-radius:99px; display:flex; align-items:center; gap:4px; }
.card-stock-badge .sb-dot { width:6px; height:6px; background:var(--success); border-radius:50%; animation:pulse 2s ease-in-out infinite; }

.card-body { padding:16px 18px; flex:1; display:flex; flex-direction:column; gap:10px; }
.card-title { font-family:var(--display); font-size:14.5px; font-weight:700; line-height:1.3; }

.color-row   { display:flex; flex-wrap:wrap; gap:5px; align-items:center; }
.color-label { font-size:9.5px; color:var(--ink3); text-transform:uppercase; letter-spacing:1px; font-weight:600; margin-right:2px; }
.color-chip  { display:inline-flex; align-items:center; gap:4px; padding:2px 8px 2px 5px; border-radius:99px; font-size:10.5px; font-weight:600; border:1px solid rgba(0,0,0,.1); white-space:nowrap; }
.color-swatch { width:9px; height:9px; border-radius:50%; border:1px solid rgba(0,0,0,.15); flex-shrink:0; }

.price-alert { background:var(--warning-bg); border:1px solid var(--warning-bd); border-radius:8px; padding:8px 11px; font-size:11.5px; color:var(--warning); display:flex; flex-direction:column; gap:4px; }
.price-alert-title { font-weight:700; display:flex; align-items:center; gap:5px; font-size:10.5px; text-transform:uppercase; letter-spacing:.5px; }
.price-alert-rows  { display:flex; flex-direction:column; gap:3px; }
.price-alert-row   { display:flex; justify-content:space-between; align-items:center; gap:8px; }
.price-alert-color { display:flex; align-items:center; gap:5px; }
.price-alert-price { font-family:var(--display); font-weight:700; }

/* Tabla sucursales */
.suc-table { width:100%; border-collapse:collapse; font-size:12px; }
.suc-table thead th { text-align:left; font-weight:600; color:var(--ink3); text-transform:uppercase; font-size:9.5px; letter-spacing:1px; padding:0 0 5px; border-bottom:1px solid var(--border); }
.suc-table thead th:last-child { text-align:right; }
.suc-table tbody tr { border-bottom:1px solid var(--surface2); }
.suc-table tbody tr:last-child { border-bottom:none; }
.suc-table td { padding:6px 0; color:var(--ink2); vertical-align:middle; }
.suc-table td:last-child { text-align:right; }
.suc-name { display:flex; align-items:center; gap:6px; }
.suc-dot  { width:6px; height:6px; background:var(--success); border-radius:50%; flex-shrink:0; }
.suc-qty  { font-family:var(--display); font-weight:700; font-size:12.5px; color:var(--ink); }
.suc-unit { font-size:9.5px; color:var(--ink3); margin-left:1px; }
.suc-stock-dots { display:flex; flex-wrap:wrap; gap:3px; align-items:center; max-width:150px; }
.suc-sdot { width:8px; height:8px; border-radius:50%; border:1px solid rgba(0,0,0,.15); flex-shrink:0; transition:transform .15s; cursor:default; }
.suc-sdot:hover { transform:scale(1.6); z-index:1; }

.card-price { margin-top:auto; padding-top:12px; border-top:1px solid var(--border); display:flex; align-items:baseline; justify-content:space-between; }
.price-label { font-size:10px; text-transform:uppercase; letter-spacing:1px; color:var(--ink3); font-weight:600; }
.price-value { font-family:var(--display); font-size:21px; font-weight:800; color:var(--accent); letter-spacing:-.5px; }
.price-value span { font-size:12px; font-weight:400; color:var(--ink3); margin-right:2px; }

.no-results { display:none; flex-direction:column; align-items:center; justify-content:center; padding:80px 20px; color:var(--ink3); text-align:center; }
.no-results.show { display:flex; }
.no-results-icon { font-size:52px; margin-bottom:14px; opacity:.4; }
.no-results h3   { font-family:var(--display); font-size:20px; color:var(--ink2); margin-bottom:6px; }

/* ══ RESPONSIVE ════════════════════════════════════════════════ */
@media(max-width:1060px){ .header-stats{display:none} }
@media(max-width:900px){
  .layout{grid-template-columns:1fr;}
  .sidebar{position:static;height:auto;border-right:none;border-bottom:1px solid var(--border);padding:14px 0;}
  .sidebar-btns{display:flex;flex-wrap:wrap;gap:5px;padding:0 14px;}
  .sidebar-btn{width:auto;border-left:none;border-radius:99px;padding:5px 12px;font-size:11.5px;border:1px solid var(--border);}
  .sidebar-btn.active,.sidebar-btn:hover{border-color:var(--accent);border-left-color:var(--accent);background:rgba(232,68,10,.06);}
  .sb-badges{display:none;}
  .price-range-wrap{padding:8px 14px 0;}
}
@media(max-width:640px){
  .site-header{padding:10px 14px;height:auto;flex-wrap:wrap;gap:8px;}
  .header-date{display:none;}
  .search-wrap{max-width:100%;order:3;width:100%;}
  .main{padding:16px 14px;}
  .cards-grid{grid-template-columns:1fr;}
  .cat-label{font-size:22px;}
  .view-list .card{height:auto;flex-direction:column;}
  .view-list .card-img-wrap{width:100%;height:160px;border-radius:var(--radius) var(--radius) 0 0;}
  .view-list .card-body{flex-direction:column;align-items:flex-start;}
  .view-list .suc-table{display:table;}
}
</style>
</head>
<body>

<!-- ══ LIGHTBOX ════════════════════════════════════════════════ -->
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Imagen ampliada">
  <button class="lightbox-close" onclick="cerrarLightbox()" aria-label="Cerrar">✕</button>
  <img id="lightboxImg" src="" alt="">
  <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<!-- ══ MODAL AVISO ═════════════════════════════════════════════ -->
<div class="aviso-overlay" id="avisoOverlay" role="dialog" aria-modal="true" aria-labelledby="avisoTitulo">
  <div class="aviso-modal">
    <div class="aviso-head">
      <div class="aviso-head-icon">📋</div>
      <div>
        <div class="aviso-head-title" id="avisoTitulo">Protocolo de Traspasos</div>
        <div class="aviso-head-sub">Leer antes de continuar</div>
      </div>
    </div>
    <div class="aviso-body">
      <div class="aviso-regla-main"><strong>🔁 Traspaso entre tiendas</strong>Todo traspaso debe solicitarse previamente en el grupo, indicando almacén y vendedor, antes de mover el producto.</div>
      <div class="aviso-regla green"><span class="aviso-icon">✔</span><span>Verificar producto y etiqueta antes de cualquier movimiento.</span></div>
      <div class="aviso-regla blue"><span class="aviso-icon">📱</span><span>Validar <strong>IMEI</strong> en todos los teléfonos antes del traspaso.</span></div>
      <div class="aviso-regla green"><span class="aviso-icon">🚚</span><span>Confirmar entrega con <strong>evidencia</strong> fotográfica o de recibo.</span></div>
      <div class="aviso-regla orange"><span class="aviso-icon">⚠️</span><span>Todo equipo solicitado <strong>debe venderse</strong> o será retirado.</span></div>
      <div class="aviso-regla orange"><span class="aviso-icon">🚫</span><span>Solo grupos autorizados — <strong>no números personales</strong>.</span></div>
      <div class="aviso-regla red"><span class="aviso-icon">❗</span><span>Incumplimiento de cualquier punto será motivo de <strong>sanción</strong>.</span></div>
    </div>
    <div class="aviso-footer">
      <button class="aviso-btn" id="avisoClose" onclick="cerrarAviso()">Entendido, continuar →</button>
    </div>
  </div>
</div>

<!-- ══ HEADER ══════════════════════════════════════════════════ -->
<header class="site-header">
  <div class="header-brand">
    <div class="dot"></div>
    <div><div class="brand-title">📱 Catálogo</div></div>
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
    <button class="dark-toggle" id="darkToggle" title="Cambiar tema" aria-label="Modo oscuro">🌙</button>
  </div>

  <?php if ($fechaActualizacion): ?>
  <div class="header-date">
    <strong>Última actualización</strong><?= htmlspecialchars($fechaActualizacion) ?>
  </div>
  <?php endif; ?>
</header>

<!-- ══ LAYOUT ══════════════════════════════════════════════════ -->
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">

    <!-- Categoría -->
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
          <?= iconoCategoria($cat) ?> <?= htmlspecialchars($cat) ?>
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

    <!-- Rango de precio -->
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

    <!-- Leyenda -->
    <div style="padding:0 0 4px;">
      <div class="dots-legend">
        <span class="dots-legend-dot" style="background:#1a1a1a;"></span>
        <span class="dots-legend-dot" style="background:#1a73e8;"></span>
        <span class="dots-legend-dot" style="background:#c62828;"></span>
        <span>Cada punto = 1 pieza</span>
      </div>
    </div>

  </aside>

  <!-- Main -->
  <main class="main" id="mainArea">

    <div class="results-bar">
      <div class="results-info">
        <div class="rstat accent">
          <div class="rstat-num" id="visibleStock"><?= $totalStock ?></div>
          <div class="rstat-lbl">Piezas en stock</div>
        </div>
      </div>
      <div class="results-actions">
        <button class="clear-btn" id="clearBtn" onclick="limpiarFiltros()">✕ Limpiar</button>
        <!-- Vista toggle -->
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
      <div class="no-results-icon">🔍</div>
      <h3>Sin resultados</h3>
      <p>Intenta ajustar los filtros o el rango de precio.</p>
    </div>

    <div id="catalogoContainer">
    <?php foreach ($porCategoria as $cat => $marcasGrupo):
      $stockCat = stockCategoria($marcasGrupo);
    ?>
    <section class="cat-bloque" data-cat-bloque="<?= htmlspecialchars($cat) ?>">
      <div class="cat-divider">
        <span class="cat-icon"><?= iconoCategoria($cat) ?></span>
        <h2 class="cat-label"><?= htmlspecialchars($cat) ?></h2>
        <div class="cat-line"></div>
        <div class="cat-counts">
          <span class="cat-count s">🟢 <?= $stockCat ?> en stock</span>
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
            <span class="marca-count s">🟢 <?= $stockMarcaLocal ?> pzas.</span>
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
              <div class="card-title"><?= htmlspecialchars($phone['descripcionModelo']) ?></div>

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
                <div class="price-alert-title">⚠ Precios inconsistentes</div>
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
/* ══════════════════════════════════════════════════════════════
   CONSTANTES PHP → JS
   ══════════════════════════════════════════════════════════════ */
const PRECIO_MIN_GLOBAL = <?= $precioMin ?>;
const PRECIO_MAX_GLOBAL = <?= $precioMax ?>;

/* ══ DARK MODE ═════════════════════════════════════════════════ */
const htmlEl   = document.documentElement;
const darkBtn  = document.getElementById('darkToggle');
setTheme(localStorage.getItem('tema') || 'light');

function setTheme(t) {
  htmlEl.setAttribute('data-theme', t);
  darkBtn.textContent = t === 'dark' ? '☀️' : '🌙';
  localStorage.setItem('tema', t);
}
darkBtn.addEventListener('click', () =>
  setTheme(htmlEl.getAttribute('data-theme') === 'dark' ? 'light' : 'dark')
);

/* ══ MODAL ══════════════════════════════════════════════════════ */
function cerrarAviso() {
  const el = document.getElementById('avisoOverlay');
  el.classList.add('closing');
  setTimeout(() => el.remove(), 230);
}
setTimeout(() => document.getElementById('avisoClose')?.focus(), 350);

/* ══ LIGHTBOX ══════════════════════════════════════════════════ */
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

/* ══ VISTA GRID / LIST ═════════════════════════════════════════ */
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

/* ══ FILTROS + ORDEN — ALGORITMO OPTIMIZADO ════════════════════
   - CARDS se cachea al inicio con datos pre-parseados (0 conversiones
     en caliente). Cada card guarda su índice original para restaurar
     el orden "por defecto" sin necesidad de re-consultar el DOM.
   - aplicarFiltros() nunca llama querySelectorAll dentro del loop.
   - El sort usa DocumentFragment → un solo reflow por grid.
   - actualizarRango() solo actualiza el UI del slider; llama
     aplicarFiltros al final (definida antes que el slider).
   ══════════════════════════════════════════════════════════════ */

// ── Caché de tarjetas con datos pre-parseados ─────────────────
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

// ── Caché de contenedores ─────────────────────────────────────
const catBloques  = Array.from(document.querySelectorAll('.cat-bloque'));
const marcaGrupos = Array.from(document.querySelectorAll('.marca-grupo'));

const buscador     = document.getElementById('buscador');
const clearBtn     = document.getElementById('clearBtn');
const noResults    = document.getElementById('noResults');
const visibleStock = document.getElementById('visibleStock');
const hstatStock   = document.getElementById('hstatStock');

let catActiva   = 'todas';
let marcaActiva = 'todas';

// Sets reutilizables (evita crear new Set() en cada llamada)
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
  actualizarRango(); // llama aplicarFiltros al final
}

/* ══ SLIDER DE PRECIO — custom pointer drag ════════════════════
   Sin inputs nativos superpuestos: cada thumb es un <span>
   que captura pointermove. Funciona en mouse y touch por igual.
   ══════════════════════════════════════════════════════════════ */
const lblMin    = document.getElementById('lblMin');
const lblMax    = document.getElementById('lblMax');
const rangeFill = document.getElementById('rangeFill');
const thumbMin  = document.getElementById('thumbMin');
const thumbMax  = document.getElementById('thumbMax');
const track     = document.getElementById('rangeTrack');
const STEP      = 100;
const RANGO_TOTAL = PRECIO_MAX_GLOBAL - PRECIO_MIN_GLOBAL;

// Estado actual del slider
let sliderMin = PRECIO_MIN_GLOBAL;
let sliderMax = PRECIO_MAX_GLOBAL;

// Exponer como "rangeMin/rangeMax" para que limpiarFiltros() pueda resetear
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

  // Teclado: flechas para ajuste fino
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

// También permitir click directo en el track para mover el thumb más cercano
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
  // Alias para compatibilidad con limpiarFiltros()
  renderSlider();
  aplicarFiltros();
}

// Debounce para el buscador
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