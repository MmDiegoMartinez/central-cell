<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metas de Venta</title>

    <link rel="stylesheet" href="../../styles.css">
    <style>
        
        .back-button{
            display:inline-flex;align-items:center;gap:6px;
            margin:var(--space-lg) 0 0;
            padding:8px 16px;
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            background:var(--surface-container-lowest);
            color:var(--on-surface);
            font-size:14px;font-weight:600;
            transition:border-color .15s ease, transform .15s ease;
        }
        .back-button:hover{border-color:var(--primary);transform:translateX(-2px);}

        .topbar{
            display:flex;flex-direction:column;gap:4px;
            margin:0 0 var(--space-lg);
            padding:var(--space-lg) 0;
            position:sticky;
            top:0;
            z-index:30;
            background:rgba(249,249,255,0.9);
            backdrop-filter:blur(10px);
            -webkit-backdrop-filter:blur(10px);
            border-bottom:1px solid var(--outline-variant);
        }
        .topbar h1{margin:0;}
        .topbar p{margin:0;color:var(--on-surface-variant);font-size:14px;}
        .topbar .sub{font-size:13px;opacity:.85;}

        /* ---------------------------------------------------
           Animaciones sutiles al mostrar resultados
           --------------------------------------------------- */
        @keyframes fadeSlideUp{
            from{opacity:0;transform:translateY(10px);}
            to{opacity:1;transform:translateY(0);}
        }
        .dashboard .card{
            animation:fadeSlideUp .45s ease both;
        }
        .dashboard .card:nth-child(1){animation-delay:.02s;}
        .dashboard .card:nth-child(2){animation-delay:.08s;}
        .dashboard .card:nth-child(3){animation-delay:.14s;}
        .dashboard .card:nth-child(4){animation-delay:.2s;}

        .stats-summary{
            animation:fadeSlideUp .45s ease both;
            animation-delay:.26s;
        }
        .motivacional{
            animation:fadeSlideUp .45s ease both;
            animation-delay:.32s;
        }
        .placeholder, .error{
            animation:fadeSlideUp .35s ease both;
        }

        /* Tabs de departamento */
        .tabs{
            display:flex;gap:var(--space-md);flex-wrap:wrap;
            margin-bottom:var(--space-lg);
        }
        .tab-btn{
            flex:1;min-width:180px;
            padding:14px var(--space-lg);
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            background:var(--surface-container-lowest);
            color:var(--on-surface-variant);
            font-weight:700;font-size:15px;text-align:center;
            cursor:pointer;transition:all .15s ease;
        }
        .tab-btn:hover{border-color:var(--primary);}
        .tab-btn.activo.im{background:var(--primary);color:var(--on-primary);border-color:var(--primary);}
        .tab-btn.activo.tm{background:var(--tertiary);color:var(--on-tertiary);border-color:var(--tertiary);}

        /* Selector card */
        .selector-card{
            background:var(--surface-container-lowest);
            border:1px solid rgba(196,197,215,0.4);
            border-radius:var(--radius-xl);
            padding:var(--space-xl);
            margin-bottom:var(--space-xl);
            box-shadow:0 1px 2px rgba(17,28,45,0.04);
        }
        .depto-badge{
            display:inline-flex;align-items:center;gap:6px;
            padding:6px 14px;border-radius:var(--radius-full);
            font-size:12px;font-weight:700;letter-spacing:0.03em;
            margin-bottom:var(--space-lg);
        }
        .depto-badge.im{background:var(--primary-container);color:var(--on-primary);}
        .depto-badge.tm{background:var(--tertiary-container);color:var(--on-tertiary);}

        .form-row{display:flex;gap:var(--space-lg);flex-wrap:wrap;}
        .form-group{flex:1;min-width:220px;}
        .form-group label{
            display:block;font-size:14px;font-weight:700;
            color:var(--on-surface);margin-bottom:6px;
        }
        .form-group select, .form-group input[type="number"]{
            width:100%;padding:12px 14px;
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            font-family:'Inter',sans-serif;font-size:15px;
            background:var(--surface-container-lowest);color:var(--on-surface);
        }
        .form-group select:focus, .form-group input:focus{
            outline:2px solid var(--primary);outline-offset:1px;
        }

        /* Placeholder / error */
        .placeholder{
            text-align:center;padding:var(--space-3xl) var(--space-md);
            color:var(--on-surface-variant);
        }
        .placeholder .icon{font-size:40px;margin-bottom:var(--space-md);}
        .error{
            background:var(--error-container);color:var(--on-error-container);
            border-left:4px solid var(--error);
            border-radius:0 var(--radius-lg) var(--radius-lg) 0;
            padding:var(--space-md) var(--space-lg);
        }

        /* Dashboard de tarjetas */
        .dashboard{
            display:grid;grid-template-columns:repeat(4,1fr);gap:var(--space-lg);
            margin-bottom:var(--space-xl);
        }
        @media (max-width:900px){.dashboard{grid-template-columns:repeat(2,1fr);}}
        @media (max-width:520px){.dashboard{grid-template-columns:1fr;}}
        .card{
            border-radius:var(--radius-xl);
            padding:var(--space-lg);
            color:#fff;
            box-shadow:0 1px 2px rgba(17,28,45,0.08);
        }
        .card-icon{font-size:24px;display:block;margin-bottom:8px;}
        .card-title{font-size:13px;font-weight:600;opacity:.9;margin-bottom:4px;}
        .card-value{font-size:22px;font-weight:700;margin-bottom:4px;}
        .card-subtitle{font-size:12px;opacity:.85;}

        .im-tienda-dia{background:linear-gradient(135deg, var(--primary), var(--tertiary));}
        .im-tienda-sem{background:linear-gradient(135deg, var(--tertiary), var(--primary-container));}
        .im-ind-dia{background:linear-gradient(135deg, var(--secondary), #00897b);}
        .im-ind-sem{background:linear-gradient(135deg, #00897b, var(--secondary));}

        .tm-tienda-dia{background:linear-gradient(135deg, var(--tertiary), var(--tertiary-container));}
        .tm-tienda-sem{background:linear-gradient(135deg, var(--tertiary-container), var(--tertiary));}
        .tm-ind-dia{background:linear-gradient(135deg, var(--secondary), #00695c);}
        .tm-ind-sem{background:linear-gradient(135deg, #00695c, var(--secondary));}

        /* Resumen */
        .stats-summary{
            background:var(--surface-container-low);
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-xl);
            padding:var(--space-lg) var(--space-xl);
            margin-bottom:var(--space-xl);
        }
        .stats-summary h3{margin:0 0 var(--space-md);font-size:16px;}
        .stat-row{
            display:flex;justify-content:space-between;
            padding:8px 0;border-bottom:1px solid var(--outline-variant);
            font-size:14px;
        }
        .stat-row:last-child{border-bottom:none;}
        .stat-label{color:var(--on-surface-variant);}
        .stat-value{font-weight:700;color:var(--on-surface);}

        /* Motivacional */
        .motivacional{
            background:linear-gradient(135deg, var(--primary), var(--tertiary));
            color:var(--on-primary);
            border-radius:var(--radius-xl);
            padding:var(--space-xl);
            text-align:center;
            margin-bottom:var(--space-2xl);
        }
        .motivacional h2{margin:0 0 8px;font-size:20px;}
        .motivacional p{margin:0;opacity:.9;font-size:14px;}
    </style>
</head>
<body>

    <div class="container">

       <a href="garantias.php" class="back-button">
            <span class="material-symbols-outlined">home</span>
            Regresar al Inicio
        </a>

        <div class="topbar">
            <h1 class="text-headline-md">
                <span class="material-symbols-outlined">target</span>
                Metas de Venta
            </h1>
            <p>Innovación Móvil &nbsp;·&nbsp; Tecnología Móvil</p>
            <p class="sub">¡Alcanza tus objetivos y supera las expectativas!</p>
        </div>

            <!-- Tabs departamento -->
            <div class="tabs">
                <button class="tab-btn im activo" onclick="cambiarDepto('IM')">
                    <span class="material-symbols-outlined">phone_iphone</span>
                    Innovación Móvil<br>
                    <small style="font-weight:normal;font-size:.8em;">Accesorios</small>
                </button>

                <button class="tab-btn tm" onclick="cambiarDepto('TM')">
                    <span class="material-symbols-outlined">smartphone</span>
                    Tecnología Móvil<br>
                    <small style="font-weight:normal;font-size:.8em;">Telefonía</small>
                </button>
            </div>

            <!-- Selector -->
            <div class="selector-card">
               <span class="depto-badge im" id="depto-badge">
                    <span class="material-symbols-outlined">phone_iphone</span>
                    Innovación Móvil – Accesorios
                </span>
                <div class="form-row">
                    <div class="form-group">
                        <label for="sucursal">
                            <span class="material-symbols-outlined">store</span>
                            Sucursal:
                        </label>
                        <select id="sucursal">
                            <option value="">Cargando sucursales...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="plantilla">
                            <span class="material-symbols-outlined">groups</span>
                            Vendedores en plantilla:
                        </label>
                        <input type="number" id="plantilla" placeholder="Ej: 5" min="1" value="1">
                    </div>
                </div>
            </div>

            <!-- Resultados -->
            <div id="resultados">
                <div class="placeholder">
                    <div class="icon">
                        <span class="material-symbols-outlined">target</span>
                    </div>
                    Selecciona una sucursal para ver las metas
                </div>
            </div>

    </div>

<script>
const API_URL = 'api_metas.php';
let sucursales     = [];
let sucursalActual = null;
let deptoActual    = 'IM';

const DEPTO = {
    IM: {
        campo: 'metaIM',
        label: '<span class="material-symbols-outlined">phone_iphone</span> Innovación Móvil – Accesorios',
        clase: 'im',
        colores: ['im-tienda-dia','im-tienda-sem','im-ind-dia','im-ind-sem'],
    },
    TM: {
        campo: 'metaTM',
        label: '<span class="material-symbols-outlined">smartphone</span> Tecnología Móvil – Telefonía',
        clase: 'tm',
        colores: ['tm-tienda-dia','tm-tienda-sem','tm-ind-dia','tm-ind-sem'],
    }
};
/* ── Formatear moneda ─── */
const fmt = v => new Intl.NumberFormat('es-MX', { style:'currency', currency:'MXN' }).format(v);

/* ── Obtener info del mes actual (días reales, no promedio) ── */
function obtenerInfoMes() {
    const hoy = new Date();
    const anio = hoy.getFullYear();
    const mes  = hoy.getMonth(); // 0-11
    const diasEnMes = new Date(anio, mes + 1, 0).getDate(); // último día del mes actual
    const nombreMes = hoy.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
    return { diasEnMes, nombreMes };
}

/* ── Cargar sucursales ── */
async function cargarSucursales() {
    try {
        const resp = await fetch(`${API_URL}?accion=obtener_sucursales`);
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const data = await resp.json();
        if (!data.success) throw new Error(data.error || 'Error desconocido');

        sucursales = data.data;
        const sel = document.getElementById('sucursal');
        sel.innerHTML = '<option value="">-- Selecciona una sucursal --</option>';
        sucursales.forEach(s => {
            const o = document.createElement('option');
            o.value = s.id;
            o.textContent = s.nombre;
            sel.appendChild(o);
        });
    } catch(e) {
        document.getElementById('resultados').innerHTML =
            `<div class="error">⚠️ ${e.message}</div>`;
    }
}

/* ── Cambiar departamento ── */
function cambiarDepto(depto) {
    deptoActual = depto;
    const d = DEPTO[depto];

    // Tabs
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('activo'));
    document.querySelector(`.tab-btn.${d.clase}`).classList.add('activo');

    // Badge
    const badge = document.getElementById('depto-badge');
    badge.innerHTML = d.label;   
    badge.className = `depto-badge ${d.clase}`;

    if (sucursalActual) calcularMetas();
}
/* ── Calcular y mostrar ── */
function calcularMetas() {
    if (!sucursalActual) return;

    const d         = DEPTO[deptoActual];
    const plantilla = parseInt(document.getElementById('plantilla').value) || 1;

    const metaDiariaT  = parseFloat(sucursalActual[d.campo]) || 0;
    const metaSemanalT = metaDiariaT * 7;
    const metaIndSem   = metaSemanalT / plantilla;
    const metaIndDia   = metaIndSem / 6;   // 6 días laborales

    // Días reales del mes en curso (en vez de un promedio fijo de 30)
    const { diasEnMes, nombreMes } = obtenerInfoMes();
    const metaTiendaMensual = metaDiariaT * diasEnMes;
    const metaIndMensual    = ((metaIndDia * 6) / 7) * diasEnMes;

    const frases = [
        { titulo: "¡Tú Puedes Lograrlo!",   mensaje: "Cada venta te acerca más a tu meta. ¡Mantén el enfoque!" },
        { titulo: "Eres un Campeón",          mensaje: "Tu determinación es tu mejor herramienta. ¡Supera tu meta!" },
        { titulo: "El Éxito es Tuyo",         mensaje: "Cada día es una nueva oportunidad para brillar." },
        { titulo: "Imparable",                mensaje: "Tu esfuerzo de hoy es el éxito de mañana." },
        { titulo: "Haz que Suceda",           mensaje: "Con tu talento y dedicación, todo es posible." },
    ];
    const frase = frases[Math.floor(Math.random() * frases.length)];
    const [c1,c2,c3,c4] = d.colores;

    document.getElementById('resultados').innerHTML = `
        <div class="dashboard">
            <div class="card ${c1}">
                <span class="card-icon material-symbols-outlined">store</span>
                <div class="card-title">Meta Tienda Diaria</div>
                <div class="card-value">${fmt(metaDiariaT)}</div>
                <div class="card-subtitle">Objetivo diario de la sucursal</div>
            </div>

            <div class="card ${c2}">
                <span class="card-icon material-symbols-outlined">calendar_month</span>
                <div class="card-title">Meta Tienda Semanal</div>
                <div class="card-value">${fmt(metaSemanalT)}</div>
                <div class="card-subtitle">7 días de venta</div>
            </div>

            <div class="card ${c3}">
                <span class="card-icon material-symbols-outlined">target</span>
                <div class="card-title">Tu Meta Diaria</div>
                <div class="card-value">${fmt(metaIndDia)}</div>
                <div class="card-subtitle">Tomando en cuenta tu día de descanso</div>
            </div>

            <div class="card ${c4}">
                <span class="card-icon material-symbols-outlined">trending_up</span>
                <div class="card-title">Tu Meta Semanal</div>
                <div class="card-value">${fmt(metaIndSem)}</div>
                <div class="card-subtitle">¡A por todas!</div>
            </div>
        </div>

        <div class="stats-summary">
            <h3>
                <span class="material-symbols-outlined">analytics</span>
                Resumen – ${DEPTO[deptoActual].label}
            </h3>
            <div class="stat-row">
                <span class="stat-label">Sucursal</span>
                <span class="stat-value">${sucursalActual.nombre}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Vendedores en plantilla</span>
                <span class="stat-value">${plantilla}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Meta tienda mensual (${nombreMes})</span>
                <span class="stat-value">${fmt(metaTiendaMensual)}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Tu meta mensual (${nombreMes})</span>
                <span class="stat-value">${fmt(metaIndMensual)}</span>
            </div>
        </div>

        <div class="motivacional">
           <h2>
                <span class="material-symbols-outlined">emoji_events</span>
                ${frase.titulo}
            </h2>
            <p>${frase.mensaje}</p>
        </div>`;
}

/* ── Event listeners ── */
document.getElementById('sucursal').addEventListener('change', function() {
    sucursalActual = sucursales.find(s => s.id == this.value) || null;
    if (sucursalActual) calcularMetas();
    else document.getElementById('resultados').innerHTML =
       `<div class="placeholder">
            <div class="icon">
                <span class="material-symbols-outlined">target</span>
            </div>
            Selecciona una sucursal para ver las metas
        </div>`;
        });

document.getElementById('plantilla').addEventListener('input', () => {
    if (sucursalActual) calcularMetas();
});

window.addEventListener('DOMContentLoaded', cargarSucursales);
</script>
</body>
</html>