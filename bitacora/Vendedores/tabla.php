<?php 
// tabla.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitácora de Vendedores</title>
    <link rel="stylesheet" href="../../csstabla.css?v=<?php echo time(); ?>">
    <style>
        /* Colores exactos según indicador */
        .legend {
            display: flex; gap: 10px; margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .legend div {
            padding: 5px 10px; font-weight: bold; border-radius: 4px;
        }
        .legend .visto { background-color: #42A5F5; color: #fff; }
        .legend .en-pedido { background-color: #FFEB3B; color: #000; }
        .legend .surtido { background-color: #66BB6A; color: #fff; }
        .legend .tiene-en-tienda { background-color: #E53935; color: #fff; }
        .legend .anotado { background-color: #FFFFFF; color: #000; border:1px solid #000; }
        .legend .otro { background-color: #FB8C00; color: #fff; }
    </style>
</head>
<body>

 <nav style="background:#0F5476; padding:10px;">
        <!-- Checkbox PRIMERO (importante para el CSS) -->
        <input type="checkbox" id="check">
         <h1 id="nombre">Productos Negados</h1>
        
        
        
        <!-- Menú Hamburguesa -->
        <label class="bar" for="check">
            <span class="top"></span>
            <span class="middle"></span>
            <span class="bottom"></span>
        </label>
        
        <ul id="menu">
            <li>
                <a href="index.php">
                    <span style="display: inline-flex; width: 40px; height: 40px; background: white; border-radius: 50%; justify-content: center; align-items: center; overflow: visible; position: relative;">
                        <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" alt="Logo Central Cell" style="width: 30px; height: 30px; object-fit: contain; position: relative; top: 0; left: 0;" />
                    </span>
                    Home
                </a>
            </li>

            <li>
            <a href="tabla.php" style="display: flex; align-items: center; gap: 12px;">
                <img src="../../recursos/img/merma.png" alt="Producto" style="width: 40px; height: 40px; object-fit: contain;" />
                Producto
            </a>
        </li>
        </ul>
    </nav>

<div class="container">
    <h2>Bitácora de Vendedores</h2>

    <div class="filters-container">
        <h3>Filtros</h3>
        <div class="filter-row">
            <div class="filter-group">
                <label>Sucursal:</label>
                <select id="filter-sucursal"><option value="">Todas</option></select>
            </div>
            <div class="filter-group">
                <label>Colaborador:</label>
                <select id="filter-colaborador"><option value="">Todos</option></select>
            </div>
            <div class="filter-group">
                <label>Indicador:</label>
                <select id="filter-indicador">
                    <option value="">Todos</option>
                    <option value="Anotado">Anotado</option>
                    <option value="Visto">Visto</option>
                    <option value="En pedido">En pedido</option>
                    <option value="Surtido">Surtido</option>
                    <option value="Tiene en tienda">Tiene en tienda</option>
                    <option value="Otro (Anotaciones)">Otro (Anotaciones)</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Fecha Desde:</label>
                <input type="date" id="filter-fecha-desde">
            </div>
            <div class="filter-group">
                <label>Fecha Hasta:</label>
                <input type="date" id="filter-fecha-hasta">
            </div>
            <div class="filter-buttons">
                <button onclick="clearAllFilters()">🔄 Limpiar Filtros</button>
            </div>
        </div>

        <div class="legend">
            <div class="visto">Visto</div>
            <div class="en-pedido">En pedido</div>
            <div class="surtido">Surtido</div>
            <div class="tiene-en-tienda">Tiene en tienda</div>
            <div class="anotado">Anotado</div>
            <div class="otro">Otro (Anotaciones)</div>
        </div>
    </div>

    <!-- Mensaje de cargando -->
    <div id="loading" style="font-weight:bold; margin:10px 0; display:none;">
        Cargando<span id="dots"></span>
    </div>

    <div class="table-container">
        <table border="1" cellpadding="5" cellspacing="0" id="bitacora-table">
            <thead>
                <tr style="background:#ddd;">
                    <th>Marca/Modelo</th>
                    <th>Producto</th>
                    <th>Sucursal</th>
                    <th>Colaborador</th>
                    <th>Estatus</th>
                    <th>Anotaciones</th>
                    <th>AnotacionAlmacen</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <!-- Datos se cargarán vía JS -->
            </tbody>
        </table>
    </div>

    <div id="results-count" style="margin-top:10px;"></div>
</div>

<script>
const GOOGLE_SCRIPT_URL = "https://script.google.com/macros/s/AKfycbynYy67vrk7v0GzC7gzBrjzseVPj6RrxRAxn5AssxVdith8SwcejDzHjytWUlJSTjtW/exec";

let allRows = [];
let filteredRows = [];
let dotsInterval;

function showLoading() {
    document.getElementById('loading').style.display = 'block';
    let dots = '';
    dotsInterval = setInterval(() => {
        dots = dots.length < 3 ? dots + '.' : '';
        document.getElementById('dots').textContent = dots;
    }, 500);
}

function hideLoading() {
    document.getElementById('loading').style.display = 'none';
    clearInterval(dotsInterval);
}

//  FUNCIÓN PRINCIPAL DE CARGA - SOLO UNA VEZ
async function loadData() {
    showLoading();
    try {
        console.log('Iniciando carga de datos...');
        const response = await fetch(GOOGLE_SCRIPT_URL);
        
        console.log('Respuesta recibida:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Datos parseados:', result);
        
        if (result.ok) {
            allRows = result.data.map((row, index) => ({
                ...row,
                id: index + 1
            }));
            
            console.log('Total de registros:', allRows.length);
            
            // Ordenar del más reciente al más antiguo
            allRows.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
            
            filteredRows = [...allRows];
            initializeFilters();
            renderTable();
        } else {
            alert("Error al obtener datos: " + result.error);
        }
    } catch (err) {
        console.error('Error completo:', err);
        alert("Error de conexión con Google Sheets: " + err.message);
    } finally {
        hideLoading();
    }
}

function initializeFilters() {
    const sucursalValues = [...new Set(allRows.map(r => r.sucursal).filter(v => v))].sort();
    const colaboradorValues = [...new Set(allRows.map(r => r.colaborador).filter(v => v))].sort();

    populateSelect('filter-sucursal', sucursalValues);
    populateSelect('filter-colaborador', colaboradorValues);

    document.getElementById('filter-sucursal').addEventListener('change', applyFilters);
    document.getElementById('filter-colaborador').addEventListener('change', applyFilters);
    document.getElementById('filter-indicador').addEventListener('change', applyFilters);
    document.getElementById('filter-fecha-desde').addEventListener('change', applyFilters);
    document.getElementById('filter-fecha-hasta').addEventListener('change', applyFilters);
}

function populateSelect(selectId, values) {
    const select = document.getElementById(selectId);
    const firstOption = select.options[0];
    select.innerHTML = '';
    select.appendChild(firstOption);
    values.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v;
        opt.textContent = v;
        select.appendChild(opt);
    });
}

function applyFilters() {
    const filters = {
        sucursal: document.getElementById('filter-sucursal').value,
        colaborador: document.getElementById('filter-colaborador').value,
        indicador: document.getElementById('filter-indicador').value,
        fechaDesde: document.getElementById('filter-fecha-desde').value,
        fechaHasta: document.getElementById('filter-fecha-hasta').value
    };

    filteredRows = allRows.filter(r => {
        if (filters.sucursal && r.sucursal !== filters.sucursal) return false;
        if (filters.colaborador && r.colaborador !== filters.colaborador) return false;
        if (filters.indicador && r.indicador !== filters.indicador) return false;
        if (filters.fechaDesde && new Date(r.fecha) < new Date(filters.fechaDesde)) return false;
        if (filters.fechaHasta && new Date(r.fecha) > new Date(filters.fechaHasta)) return false;
        return true;
    });

    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('table-body');
    tbody.innerHTML = '';

    if (filteredRows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px;">No hay registros para mostrar</td></tr>';
        document.getElementById('results-count').textContent = 'No hay registros';
        return;
    }

    filteredRows.forEach(row => {
        const tr = document.createElement('tr');

        // Colores según indicador
        switch(row.indicador){
            case "Anotado": tr.style.background="#FFFFFF"; tr.style.color="#000"; break;
            case "Visto": tr.style.background="#42A5F5"; tr.style.color="#fff"; break;
            case "En pedido": tr.style.background="#FFEB3B"; tr.style.color="#000"; break;
            case "Surtido": tr.style.background="#66BB6A"; tr.style.color="#fff"; break;
            case "Tiene en tienda": tr.style.background="#E53935"; tr.style.color="#fff"; break;
            case "Otro (Anotaciones)": tr.style.background="#FB8C00"; tr.style.color="#fff"; break;
            default: tr.style.background="#FFFFFF"; tr.style.color="#000";
        }

        tr.innerHTML = `
            <td>${row.marca_modelo || ''}</td>
            <td>${row.producto || ''}</td>
            <td>${row.sucursal || ''}</td>
            <td>${row.colaborador || ''}</td>
            <td>${row.estatus || ''}</td>
            <td>${row.anotaciones || ''}</td>
            <td>${row.anotacionAlmacen || ''}</td>
            <td>${row.fecha ? new Date(row.fecha).toLocaleString() : ''}</td>
        `;
        tbody.appendChild(tr);
    });

    document.getElementById('results-count').textContent = `Mostrando ${filteredRows.length} de ${allRows.length} registros`;
}

function clearAllFilters() {
    document.getElementById('filter-sucursal').value = '';
    document.getElementById('filter-colaborador').value = '';
    document.getElementById('filter-indicador').value = '';
    document.getElementById('filter-fecha-desde').value = '';
    document.getElementById('filter-fecha-hasta').value = '';
    filteredRows = [...allRows];
    renderTable();
}

//  INICIAR AL CARGAR LA PÁGINA
document.addEventListener('DOMContentLoaded', loadData);
</script>
</body>
</html>