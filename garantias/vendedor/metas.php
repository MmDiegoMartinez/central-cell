<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metas de Venta</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #16729a 0%, #0f5476 100%);
            min-height: 100vh;
            padding: 20px;
            color: #0f1724;
        }

        .container { max-width: 1200px; margin: 0 auto; }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            animation: fadeInDown 0.6s ease;
        }
        .header h1 { font-size: 2.8em; margin-bottom: 8px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        .header p  { font-size: 1.1em; opacity: 0.9; }

        .back-button {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
            margin-bottom: 20px;
        }
        .back-button:hover {
            background: rgba(255,255,255,0.35);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        /* ── Tabs de departamento ──────────────────── */
        .tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .tab-btn {
            flex: 1;
            min-width: 200px;
            max-width: 320px;
            padding: 18px 24px;
            border: none;
            border-radius: 16px;
            font-size: 1.15em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
            opacity: 0.65;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .tab-btn.im { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .tab-btn.tm { background: linear-gradient(135deg, #4facfe 0%, #00b4d8 100%); }
        .tab-btn.activo { opacity: 1; transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }

        /* ── Card selector ─────────────────────────── */
        .selector-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: fadeInUp 0.5s ease;
        }

        .depto-badge {
            display: inline-block;
            padding: 5px 16px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            color: white;
            margin-bottom: 18px;
        }
        .depto-badge.im { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .depto-badge.tm { background: linear-gradient(135deg, #4facfe, #00b4d8); }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }

        .form-group { margin-bottom: 0; }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #0f5476;
            font-size: 1em;
        }
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 13px 18px;
            border: 2px solid #d1d5db;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s;
            background: white;
        }
        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%230f5476' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 18px;
            padding-right: 42px;
        }
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #16729a;
            box-shadow: 0 0 0 3px rgba(22,114,154,0.2);
        }

        /* ── Dashboard cards ───────────────────────── */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 20px;
            animation: fadeInUp 0.6s ease;
        }

        .card {
            border-radius: 18px;
            padding: 22px;
            color: white;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        .card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(0,0,0,0.3); }

        /* Colores IM */
        .card.im-tienda-dia    { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .card.im-tienda-sem    { background: linear-gradient(135deg, #c94b4b 0%, #4b134f 100%); }
        .card.im-ind-dia       { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .card.im-ind-sem       { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

        /* Colores TM */
        .card.tm-tienda-dia    { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card.tm-tienda-sem    { background: linear-gradient(135deg, #3a1c71 0%, #d76d77 100%); }
        .card.tm-ind-dia       { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
        .card.tm-ind-sem       { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); }

        .card-icon    { font-size: 2.5em; margin-bottom: 8px; display: block; }
        .card-title   { font-size: .9em; opacity: .9; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .card-value   { font-size: 2.2em; font-weight: bold; text-shadow: 1px 1px 3px rgba(0,0,0,0.2); }
        .card-subtitle{ font-size: .82em; opacity: .8; margin-top: 4px; }

        /* ── Stats summary ─────────────────────────── */
        .stats-summary {
            background: white;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            animation: fadeInUp 0.7s ease;
        }
        .stats-summary h3 { color: #0f5476; margin-bottom: 14px; font-size: 1.3em; }
        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .stat-row:last-child { border-bottom: none; }
        .stat-label { color: #6b7280; font-weight: 600; }
        .stat-value { font-size: 1.15em; font-weight: bold; color: #0f1724; }

        /* ── Motivacional ──────────────────────────── */
        .motivacional {
            background: white;
            border-radius: 18px;
            padding: 26px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            animation: fadeInUp 0.8s ease;
        }
        .motivacional h2 { color: #0f5476; margin-bottom: 12px; font-size: 1.7em; }
        .motivacional p  { font-size: 1.15em; color: #6b7280; line-height: 1.6; }

        /* ── Placeholder vacío ─────────────────────── */
        .placeholder {
            text-align: center;
            color: rgba(255,255,255,0.75);
            padding: 50px 20px;
            font-size: 1.2em;
        }
        .placeholder .icon { font-size: 4em; margin-bottom: 16px; }

        .error {
            background: #dc2626;
            color: white;
            padding: 14px;
            border-radius: 10px;
            margin: 16px 0;
            text-align: center;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .header h1  { font-size: 1.8em; }
            .card-value { font-size: 1.7em; }
            .tab-btn    { font-size: 1em; padding: 14px 16px; }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="garantias.php" class="back-button">⬅️ Regresar al Inicio</a>

    <div class="header">
        <h1>🎯 Metas de Venta</h1>
        <p>Innovación Móvil &nbsp;·&nbsp; Tecnología Móvil</p>
        <p style="font-size:.9em;margin-top:4px;opacity:.8;">¡Alcanza tus objetivos y supera las expectativas!</p>
    </div>

    <!-- Tabs departamento -->
    <div class="tabs">
        <button class="tab-btn im activo" onclick="cambiarDepto('IM')">
            📱 Innovación Móvil<br><small style="font-weight:normal;font-size:.8em;">Accesorios</small>
        </button>
        <button class="tab-btn tm" onclick="cambiarDepto('TM')">
            📲 Tecnología Móvil<br><small style="font-weight:normal;font-size:.8em;">Telefonía</small>
        </button>
    </div>

    <!-- Selector -->
    <div class="selector-card">
        <span class="depto-badge im" id="depto-badge">📱 Innovación Móvil – Accesorios</span>
        <div class="form-row">
            <div class="form-group">
                <label for="sucursal">🏪 Sucursal:</label>
                <select id="sucursal">
                    <option value="">Cargando sucursales...</option>
                </select>
            </div>
            <div class="form-group">
                <label for="plantilla">👥 Vendedores en plantilla:</label>
                <input type="number" id="plantilla" placeholder="Ej: 5" min="1" value="1">
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div id="resultados">
        <div class="placeholder">
            <div class="icon">🎯</div>
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
        campo:  'metaIM',
        label:  '📱 Innovación Móvil – Accesorios',
        clase:  'im',
        colores: ['im-tienda-dia','im-tienda-sem','im-ind-dia','im-ind-sem'],
    },
    TM: {
        campo:  'metaTM',
        label:  '📲 Tecnología Móvil – Telefonía',
        clase:  'tm',
        colores: ['tm-tienda-dia','tm-tienda-sem','tm-ind-dia','tm-ind-sem'],
    }
};

/* ── Formatear moneda ─── */
const fmt = v => new Intl.NumberFormat('es-MX', { style:'currency', currency:'MXN' }).format(v);

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
    badge.textContent  = d.label;
    badge.className    = `depto-badge ${d.clase}`;

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
                <span class="card-icon">🏪</span>
                <div class="card-title">Meta Tienda Diaria</div>
                <div class="card-value">${fmt(metaDiariaT)}</div>
                <div class="card-subtitle">Objetivo diario de la sucursal</div>
            </div>
            <div class="card ${c2}">
                <span class="card-icon">📅</span>
                <div class="card-title">Meta Tienda Semanal</div>
                <div class="card-value">${fmt(metaSemanalT)}</div>
                <div class="card-subtitle">7 días de venta</div>
            </div>
            <div class="card ${c3}">
                <span class="card-icon">🎯</span>
                <div class="card-title">Tu Meta Diaria</div>
                <div class="card-value">${fmt(metaIndDia)}</div>
                <div class="card-subtitle">Tomando en cuenta tu día de descanso</div>
            </div>
            <div class="card ${c4}">
                <span class="card-icon">🚀</span>
                <div class="card-title">Tu Meta Semanal</div>
                <div class="card-value">${fmt(metaIndSem)}</div>
                <div class="card-subtitle">¡A por todas!</div>
            </div>
        </div>

        <div class="stats-summary">
            <h3>📊 Resumen – ${DEPTO[deptoActual].label}</h3>
            <div class="stat-row">
                <span class="stat-label">Sucursal</span>
                <span class="stat-value">${sucursalActual.nombre}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Vendedores en plantilla</span>
                <span class="stat-value">${plantilla}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Meta tienda mensual (aprox)</span>
                <span class="stat-value">${fmt(metaDiariaT * 30)}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Tu meta mensual (aprox)</span>
                <span class="stat-value">${fmt(((metaIndDia * 6) / 7) * 30)}</span>
            </div>
        </div>

        <div class="motivacional">
            <h2>💪 ${frase.titulo}</h2>
            <p>${frase.mensaje}</p>
        </div>`;
}

/* ── Event listeners ── */
document.getElementById('sucursal').addEventListener('change', function() {
    sucursalActual = sucursales.find(s => s.id == this.value) || null;
    if (sucursalActual) calcularMetas();
    else document.getElementById('resultados').innerHTML =
        `<div class="placeholder"><div class="icon">🎯</div>Selecciona una sucursal para ver las metas</div>`;
});

document.getElementById('plantilla').addEventListener('input', () => {
    if (sucursalActual) calcularMetas();
});

window.addEventListener('DOMContentLoaded', cargarSucursales);
</script>
</body>
</html>