<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metas de Venta Accesorios</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            animation: fadeInDown 0.6s ease;
        }

        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .back-button {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.3);
            margin-bottom: 20px;
        }

        .back-button:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .selector-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: fadeInUp 0.6s ease;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 10px;
            color: #667eea;
            font-size: 1.1em;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1.1em;
            transition: all 0.3s ease;
            background: white;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 20px;
            padding-right: 45px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .form-group select:hover,
        .form-group input:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102,126,234,0.15);
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102,126,234,0.2);
            transform: translateY(-2px);
        }

        .form-group input {
            cursor: text;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 25px;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease;
        }

        .card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .card.tienda {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .card.individual {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .card.semanal {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .card-icon {
            font-size: 3em;
            margin-bottom: 10px;
            display: block;
        }

        .card-title {
            font-size: 1.1em;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .card-value {
            font-size: 2.5em;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .card-subtitle {
            font-size: 0.9em;
            opacity: 0.8;
            margin-top: 5px;
        }

        .motivational-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: fadeInUp 0.8s ease;
        }

        .motivational-section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 2em;
        }

        .motivational-section p {
            font-size: 1.3em;
            color: #666;
            line-height: 1.6;
        }

        .stats-summary {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border-radius: 20px;
            padding: 25px;
            margin-top: 20px;
            animation: fadeInUp 0.7s ease;
        }

        .stats-summary h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-weight: bold;
            color: #555;
        }

        .stat-value {
            font-size: 1.2em;
            color: #333;
            font-weight: bold;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: white;
            font-size: 1.5em;
        }

        .loading::after {
            content: '...';
            animation: dots 1.5s steps(4, end) infinite;
        }

        .error {
            background: #ff6b6b;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1) rotate(0deg);
            }
            50% {
                transform: scale(1.05) rotate(180deg);
            }
        }

        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header h1 {
                font-size: 1.8em;
            }

            .header p {
                font-size: 1em;
            }

            .back-button {
                padding: 10px 20px;
                font-size: 0.9em;
            }

            .selector-card {
                padding: 20px;
            }

            .form-group label {
                font-size: 1em;
            }

            .form-group select,
            .form-group input {
                padding: 12px 15px;
                font-size: 1em;
            }

            .form-group select {
                background-size: 18px;
                padding-right: 40px;
            }

            .card {
                padding: 20px;
            }

            .card-icon {
                font-size: 2.5em;
            }

            .card-title {
                font-size: 0.95em;
            }

            .card-value {
                font-size: 1.8em;
            }

            .card-subtitle {
                font-size: 0.85em;
            }

            .motivational-section {
                padding: 20px;
            }

            .motivational-section h2 {
                font-size: 1.5em;
            }

            .motivational-section p {
                font-size: 1.1em;
            }

            .stats-summary {
                padding: 20px;
            }

            .stats-summary h3 {
                font-size: 1.3em;
            }

            .stat-row {
                flex-direction: column;
                gap: 5px;
                padding: 12px 0;
            }

            .stat-label,
            .stat-value {
                text-align: left;
            }

            .stat-value {
                font-size: 1.3em;
            }

            .dashboard {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.5em;
            }

            .card-value {
                font-size: 1.6em;
            }

            .motivational-section h2 {
                font-size: 1.3em;
            }

            .motivational-section p {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="garantias.php" class="back-button">⬅️ Regresar al Inicio</a>

        <div class="header">
            <h1>🎯 Metas de Venta Accesorios</h1>
            <p>Departamento de Innovación Móvil</p>
            <p style="font-size: 1em; margin-top: 5px; opacity: 0.85;">¡Alcanza tus objetivos y supera las expectativas!</p>
        </div>

        <div class="selector-card">
            <div class="form-group">
                <label for="sucursal">🏪 Selecciona tu Sucursal:</label>
                <select id="sucursal">
                    <option value="">Cargando sucursales...</option>
                </select>
            </div>

            <div class="form-group">
                <label for="plantilla">👥 Número de Vendedores en la Plantilla:</label>
                <input type="number" id="plantilla" placeholder="Ej: 5" min="1" value="1">
            </div>
        </div>

        <div id="resultados"></div>
    </div>

    <script>
        // Configuración de la API
        const API_URL = 'api_metas.php';

        // Variables globales
        let sucursales = [];
        let sucursalActual = null;

        // Formatear moneda
        function formatearMoneda(valor) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(valor);
        }

        // Cargar sucursales al iniciar
        async function cargarSucursales() {
            try {
                const response = await fetch(`${API_URL}?accion=obtener_sucursales`);
                
                // Verificar si la respuesta es OK
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error del servidor:', errorText);
                    mostrarError(`Error del servidor (${response.status}). Verifica que api_metas.php exista y funciones.php esté configurado correctamente.`);
                    return;
                }

                // Intentar parsear JSON
                const data = await response.json();

                if (data.success) {
                    sucursales = data.data;
                    const selectSucursal = document.getElementById('sucursal');
                    
                    selectSucursal.innerHTML = '<option value="">-- Selecciona una sucursal --</option>';
                    
                    if (sucursales.length === 0) {
                        selectSucursal.innerHTML = '<option value="">No hay sucursales disponibles</option>';
                        mostrarError('No se encontraron sucursales activas en la base de datos.');
                        return;
                    }
                    
                    sucursales.forEach(sucursal => {
                        const option = document.createElement('option');
                        option.value = sucursal.id;
                        option.textContent = `${sucursal.nombre} - Meta: ${formatearMoneda(sucursal.metaIM)}`;
                        selectSucursal.appendChild(option);
                    });
                } else {
                    mostrarError('Error al cargar sucursales: ' + (data.error || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error completo:', error);
                mostrarError('Error de conexión. Verifica que api_metas.php exista en la misma carpeta que este archivo.');
            }
        }

        // Calcular y mostrar metas
        async function calcularMetas() {
            if (!sucursalActual) return;

            const plantilla = parseInt(document.getElementById('plantilla').value) || 1;
            const metaDiaria = parseFloat(sucursalActual.metaIM);

            // Cálculos locales (sin necesidad de API para esto)
            const metaSemanal = metaDiaria * 7;
            const metaIndividualDiaria = metaDiaria / plantilla;
            const metaIndividualSemanal = metaSemanal / plantilla;

            mostrarResultados({
                tienda: {
                    diaria: metaDiaria,
                    semanal: metaSemanal
                },
                individual: {
                    diaria: metaIndividualDiaria,
                    semanal: metaIndividualSemanal
                },
                plantilla: plantilla
            });
        }

        // Mostrar resultados en el dashboard
        function mostrarResultados(datos) {
            const resultadosDiv = document.getElementById('resultados');
            
            const fraseMotivacional = obtenerFraseMotivacional(datos.individual.diaria);
            
            resultadosDiv.innerHTML = `
                <div class="dashboard">
                    <div class="card tienda">
                        <span class="card-icon">🏪</span>
                        <div class="card-title">Meta Tienda Diaria</div>
                        <div class="card-value">${formatearMoneda(datos.tienda.diaria)}</div>
                        <div class="card-subtitle">Objetivo diario de la sucursal</div>
                    </div>

                    <div class="card tienda semanal">
                        <span class="card-icon">📅</span>
                        <div class="card-title">Meta Tienda Semanal</div>
                        <div class="card-value">${formatearMoneda(datos.tienda.semanal)}</div>
                        <div class="card-subtitle">7 días de venta</div>
                    </div>

                    <div class="card individual">
                        <span class="card-icon">🎯</span>
                        <div class="card-title">Tu Meta Diaria</div>
                        <div class="card-value">${formatearMoneda(datos.individual.diaria)}</div>
                        <div class="card-subtitle">Con ${datos.plantilla} vendedor${datos.plantilla > 1 ? 'es' : ''}</div>
                    </div>

                    <div class="card individual semanal">
                        <span class="card-icon">🚀</span>
                        <div class="card-title">Tu Meta Semanal</div>
                        <div class="card-value">${formatearMoneda(datos.individual.semanal)}</div>
                        <div class="card-subtitle">¡A por todas!</div>
                    </div>
                </div>

                <div class="stats-summary">
                    <h3>📊 Resumen de Metas</h3>
                    <div class="stat-row">
                        <span class="stat-label">Sucursal:</span>
                        <span class="stat-value">${sucursalActual.nombre}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Vendedores en Plantilla:</span>
                        <span class="stat-value">${datos.plantilla}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Meta Total Mensual (aprox):</span>
                        <span class="stat-value">${formatearMoneda(datos.tienda.diaria * 30)}</span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Tu Meta Mensual (aprox):</span>
                        <span class="stat-value">${formatearMoneda(datos.individual.diaria * 30)}</span>
                    </div>
                </div>

                <div class="motivational-section">
                    <h2>💪 ${fraseMotivacional.titulo}</h2>
                    <p>${fraseMotivacional.mensaje}</p>
                </div>
            `;
        }

        // Obtener frase motivacional basada en la meta
        function obtenerFraseMotivacional(metaIndividual) {
            const frases = [
                {
                    titulo: "¡Tú Puedes Lograrlo!",
                    mensaje: "Cada venta te acerca más a tu meta. ¡Mantén el enfoque y la actitud positiva!"
                },
                {
                    titulo: "Eres un Campeón",
                    mensaje: "Tu determinación es tu mejor herramienta. ¡Supera tu meta y sorprende a todos!"
                },
                {
                    titulo: "El Éxito es Tuyo",
                    mensaje: "Cada día es una nueva oportunidad para brillar. ¡Da lo mejor de ti!"
                },
                {
                    titulo: "Imparable",
                    mensaje: "Tu esfuerzo de hoy es el éxito de mañana. ¡Sigue adelante sin parar!"
                },
                {
                    titulo: "Haz que Suceda",
                    mensaje: "Las metas no se alcanzan solas, pero con tu talento y dedicación, todo es posible."
                }
            ];

            return frases[Math.floor(Math.random() * frases.length)];
        }

        // Mostrar error
        function mostrarError(mensaje) {
            const resultadosDiv = document.getElementById('resultados');
            resultadosDiv.innerHTML = `<div class="error">⚠️ ${mensaje}</div>`;
        }

        // Event Listeners
        document.getElementById('sucursal').addEventListener('change', async function() {
            const idSucursal = this.value;
            
            if (!idSucursal) {
                document.getElementById('resultados').innerHTML = '';
                return;
            }

            sucursalActual = sucursales.find(s => s.id == idSucursal);
            await calcularMetas();
        });

        document.getElementById('plantilla').addEventListener('input', function() {
            if (sucursalActual) {
                calcularMetas();
            }
        });

        // Cargar datos al iniciar
        window.addEventListener('DOMContentLoaded', cargarSucursales);
    </script>
</body>
</html>