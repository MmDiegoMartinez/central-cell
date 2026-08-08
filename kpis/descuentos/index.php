<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Época de Descuentos · Centralcell</title>
<link rel="stylesheet" href="../../styles.css">
<style>

  body{background:var(--background);}

  /* --- Barra de navegación superior --- */
  .dash-topbar{
    position:sticky;top:0;z-index:40;
    background:rgba(249,249,255,0.85);
    backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
    border-bottom:1px solid var(--outline-variant);
    display:flex;align-items:center;justify-content:space-between;
    padding:0 var(--space-xl);height:64px;
  }
  .dash-brand{display:flex;align-items:center;gap:10px;color:var(--primary);font-weight:700;font-size:16px;}
  .dash-brand .material-symbols-outlined{font-size:22px;}
  .dash-nav{display:flex;align-items:center;gap:var(--space-xl);}
  .dash-nav a, .dash-nav span{
    font-size:14px;font-weight:600;color:var(--on-surface-variant);
    display:flex;align-items:center;gap:6px;
    padding:8px 4px;border-bottom:2px solid transparent;
    transition:color .15s ease, border-color .15s ease;
  }
  .dash-nav a:hover{color:var(--primary);}
  .dash-nav .current{color:var(--primary);border-bottom-color:var(--primary);}
  .dash-nav-toggle{display:none;color:var(--primary);font-size:26px;background:none;}

  @media (max-width:720px){
    .dash-topbar{padding:0 var(--space-md);}
    .dash-nav{
      display:none;position:absolute;top:64px;left:0;right:0;
      background:var(--surface-container-lowest);
      border-bottom:1px solid var(--outline-variant);
      flex-direction:column;align-items:flex-start;gap:0;
      padding:var(--space-sm) var(--space-md) var(--space-md);
      box-shadow:0 8px 16px rgba(17,28,45,0.08);
    }
    .dash-nav.open{display:flex;}
    .dash-nav a, .dash-nav span{width:100%;padding:12px 4px;border-bottom:1px solid var(--outline-variant);}
    .dash-nav-toggle{display:inline-flex;}
  }

  /* --- Encabezado --- */
  .mod-hero{max-width:var(--container-max);margin:0 auto;padding:var(--space-2xl) var(--space-xl) var(--space-lg);}
  .mod-hero h1{margin:8px 0 0;}
  .mod-hero p{color:var(--on-surface-variant);margin-top:var(--space-sm);max-width:640px;}

  .mod-wrap{max-width:var(--container-max);margin:0 auto;padding:0 var(--space-xl) var(--space-3xl);}

  .section-label{
    display:flex;align-items:center;gap:10px;
    margin:var(--space-2xl) 0 var(--space-md);
    color:var(--outline);font-size:12px;font-weight:700;
    letter-spacing:0.1em;text-transform:uppercase;
  }
  .section-label:first-of-type{margin-top:0;}
  .section-label::after{content:"";flex:1;height:1px;background:var(--outline-variant);}

  /* --- Grid de módulos --- */
  .module-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(230px,1fr));
    gap:var(--space-lg);
  }
  @media (max-width:520px){
    .module-grid{grid-template-columns:repeat(2,1fr);gap:var(--space-sm);}
  }

  .module-card{
    position:relative;
    display:block;
    aspect-ratio:1/1;
    border-radius:var(--radius-xl);
    overflow:hidden;
    isolation:isolate;
    box-shadow:0 1px 2px rgba(17,28,45,0.06);
    transition:transform .35s cubic-bezier(.2,.8,.2,1), box-shadow .35s cubic-bezier(.2,.8,.2,1);
    outline:none;
  }
  .module-card:hover,
  .module-card:focus-visible{
    transform:translateY(-6px);
    box-shadow:0 20px 40px rgba(17,28,45,0.22);
  }
  .module-card:focus-visible{outline:2px solid var(--primary);outline-offset:3px;}

  .module-art{
    position:absolute;inset:0;
    display:flex;align-items:center;justify-content:center;
    transition:transform .5s cubic-bezier(.2,.8,.2,1);
  }
  .module-card:hover .module-art,
  .module-card:focus-visible .module-art{transform:scale(1.08);}

  .module-art::before{
    content:"";position:absolute;inset:0;
    background:
      radial-gradient(circle at 25% 20%, rgba(255,255,255,0.16), transparent 45%),
      radial-gradient(circle at 80% 85%, rgba(0,0,0,0.12), transparent 55%);
  }
  .module-art .material-symbols-outlined{
    position:relative;z-index:1;
    font-size:56px;color:rgba(255,255,255,0.92);
    font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 48;
    filter:drop-shadow(0 4px 10px rgba(0,0,0,0.18));
  }

  .art-1{background:linear-gradient(135deg,#1d4ed8,#004191);}
  .art-2{background:linear-gradient(135deg,#006b5f,#0037b0);}
  .art-3{background:linear-gradient(135deg,#004191,#006f64);}
  .art-4{background:linear-gradient(135deg,#0058be,#00332b);}

  .module-overlay{
    position:absolute;inset:0;z-index:2;
    display:flex;flex-direction:column;justify-content:flex-end;
    padding:var(--space-md);
    background:linear-gradient(180deg, rgba(17,28,45,0) 55%, rgba(9,14,26,0.78) 100%);
    -webkit-backdrop-filter:blur(0px);backdrop-filter:blur(0px);
    transition:background .35s ease, backdrop-filter .35s ease;
  }
  .module-card:hover .module-overlay,
  .module-card:focus-visible .module-overlay{
    background:linear-gradient(180deg, rgba(17,28,45,0) 20%, rgba(9,14,26,0.88) 100%);
    -webkit-backdrop-filter:blur(1px);backdrop-filter:blur(1px);
  }

  .module-overlay h3{
    margin:0;color:#fff;font-size:15px;font-weight:700;line-height:20px;
    text-shadow:0 2px 8px rgba(0,0,0,0.35);
  }

  .module-overlay p{
    margin:0;color:rgba(255,255,255,0.85);font-size:12.5px;line-height:17px;
    max-height:0;opacity:0;
    transform:translateY(6px);
    transition:max-height .35s ease, opacity .3s ease, transform .35s ease, margin-top .35s ease;
  }
  .module-card:hover .module-overlay p,
  .module-card:focus-visible .module-overlay p{
    max-height:100px;opacity:1;margin-top:6px;
    transform:translateY(0);
  }

  @media (max-width:520px){
    .module-art .material-symbols-outlined{font-size:38px;}
    .module-overlay h3{font-size:13px;}
    .module-overlay p{font-size:11px;line-height:15px;}
  }

  @media (prefers-reduced-motion: reduce){
    .module-card, .module-art, .module-overlay, .module-overlay p{
      transition:none !important;
    }
    .module-card:hover{transform:none;}
    .module-art{transform:none !important;}
  }
</style>
</head>
<body>

<!-- BARRA DE NAVEGACIÓN -->
<header class="dash-topbar">
  <div class="dash-brand">
    <span class="material-symbols-outlined">sell</span>
    Centralcell · Época de Descuentos
  </div>
  <button class="dash-nav-toggle material-symbols-outlined" id="navToggle" aria-label="Abrir menú">menu</button>
  <nav class="dash-nav" id="dashNav">
    <a href="../../garantias/validador/validador.php" class="navbar-link">
      <span class="material-symbols-outlined">home</span>
      Home
    </a>
    <a href="../modulos.html" class="navbar-link">
      <span class="material-symbols-outlined">apps</span>
      Panel de Herramientas
    </a>
  </nav>
</header>

<!-- ENCABEZADO -->
<section class="mod-hero">
  <span class="eyebrow">Innovación Móvil</span>
  <h1 class="text-headline-lg">Panel de Ventas · Época de Descuentos</h1>
  <p class="text-body-md">Visualiza la venta real por categoría, sucursal y vendedor de manera clara y sencilla, sin contar el código promocional como método de pago.</p>
</section>

<main class="mod-wrap">

  <div class="section-label">Módulos</div>
  <div class="module-grid">

    <a class="module-card" href="semanaventas.php">
      <div class="module-art art-1"><span class="material-symbols-outlined">bar_chart</span></div>
      <div class="module-overlay">
        <h3>Ventas Generales</h3>
        <p>Análisis de la semana de ventas reales de Innovación Móvil por sucursal y vendedor, sin que afecte el código promocional.</p>
      </div>
    </a>

    <a class="module-card" href="codigopromocional.php">
      <div class="module-art art-2"><span class="material-symbols-outlined">confirmation_number</span></div>
      <div class="module-overlay">
        <h3>Control de Promociones en Tickets 3x2</h3>
        <p>Visualiza los tickets y el estado de aquellos donde la promoción fue aplicada correctamente, e identifica los que se usaron de forma incorrecta.</p>
      </div>
    </a>

    <a class="module-card" href="comparativo_mensual.php">
      <div class="module-art art-3"><span class="material-symbols-outlined">balance</span></div>
      <div class="module-overlay">
        <h3>Comparación de Meses</h3>
        <p>Analiza dos meses y observa en qué categoría subimos o bajamos, sin contar los códigos promocionales.</p>
      </div>
    </a>

    <a class="module-card" href="descuento20por.php">
      <div class="module-art art-4"><span class="material-symbols-outlined">percent</span></div>
      <div class="module-overlay">
        <h3>Control de Promociones en Tickets 20%</h3>
        <p>Visualiza los tickets y el estado de aquellos donde la promoción fue aplicada correctamente, e identifica los que se usaron de forma incorrecta.</p>
      </div>
    </a>

    <a class="module-card" href="ahorra10.html">
      <div class="module-art art-1"><span class="material-symbols-outlined">local_offer</span></div>
      <div class="module-overlay">
        <h3>Control de Promociones en Tickets 10% Telefonía</h3>
        <p>Visualiza los tickets y el estado de aquellos donde la promoción fue aplicada correctamente, e identifica los que se usaron de forma incorrecta.</p>
      </div>
    </a>

  </div>

</main>

<footer class="site-footer">
  <div class="mod-wrap" style="max-width:var(--container-max);margin:0 auto;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;width:100%;gap:var(--space-md);padding-left:var(--space-xl);padding-right:var(--space-xl);">
    <span>© <span id="current-year"></span> Centralcell · Época de Descuentos</span>
  </div>
</footer>

<script>
  document.getElementById('current-year').textContent = new Date().getFullYear();

  // Menú móvil
  const navToggle = document.getElementById('navToggle');
  const dashNav = document.getElementById('dashNav');
  navToggle.addEventListener('click', () => {
    const open = dashNav.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
</script>

</body>
</html>