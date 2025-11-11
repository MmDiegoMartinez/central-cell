<?php 
include_once '../../funciones.php'; 
$bitacora = obtenerBitacora(); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de Vendedores</title>
    <link rel="stylesheet" href="../../csstabla.css?v=<?php echo time(); ?>">
    <style>
        /* Colores según indicador */
        .indicador-1 { background-color: transparent; }
        .indicador-2 { background-color: #42A5F5; color: #fff; }  /* Visto */
        .indicador-3 { background-color: #FFEB3B; color: #000; }  /* En pedido */
        .indicador-4 { background-color: #66BB6A; color: #fff; }  /* Surtido */
        .indicador-5 { background-color: #E53935; color: #fff; }  /* Tiene en tienda */

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
        .legend .anotado { border: 1px solid #000000ff; }
    </style>
</head>
<body>
<nav style="background:#0F5476; padding:10px;">
    <ul id="menu">
        <li>
            <a href="index.php" style="display: flex; align-items: center; gap: 12px;">
                <span style="display: inline-flex; width: 40px; height: 40px; background: white; border-radius: 50%; justify-content: center; align-items: center;">
                    <img src="../../Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" style="width: 30px; height: 30px; object-fit: contain;" />
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
                    <option value="1">Anotado</option>
                    <option value="2">Visto</option>
                    <option value="3">En pedido</option>
                    <option value="4">Surtido</option>
                    <option value="5">Tiene en tienda</option>
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
        </div>
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
                    <th>Fecha</th>
                    <th>Eliminar</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <?php foreach ($bitacora as $b): ?>
                    <tr 
                        data-id="<?= $b['id'] ?>"
                        data-sucursal="<?= htmlspecialchars($b['sucursal']) ?>"
                        data-colaborador="<?= htmlspecialchars($b['nombre_colaborador']) ?>"
                        data-fecha="<?= htmlspecialchars($b['fecha']) ?>"
                        data-indicador="<?= htmlspecialchars($b['indicador']) ?>"
                        class="indicador-<?= $b['indicador'] ?>"
                    >
                        <td><?= htmlspecialchars($b['Marca_Modelo']) ?></td>
                        <td><?= htmlspecialchars($b['producto']) ?></td>
                        <td><?= htmlspecialchars($b['sucursal']) ?></td>
                        <td><?= htmlspecialchars($b['nombre_colaborador']) ?></td>
                        <td><?= htmlspecialchars($b['Estatus']) ?></td>
                        <td><?= htmlspecialchars($b['Anotaciones']) ?></td>
                        <td><?= htmlspecialchars($b['fecha']) ?></td>
                        <td style="text-align:center;">
                            <button class="btn-eliminar" data-id="<?= $b['id'] ?>" title="Eliminar registro" style="cursor:pointer; background:none; border:none; font-size:18px;">
                                🗑️
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="results-count" style="margin-top:10px;"></div>
</div>

<script>
let allRows = [];
let filteredRows = [];

document.addEventListener('DOMContentLoaded', () => {
    initializeData();
    agregarEventosEliminar();
});

function initializeData() {
    const tableRows = document.querySelectorAll('#table-body tr');
    allRows = Array.from(tableRows);
    filteredRows = [...allRows];

    initializeFilters();
    renderTable();
}

function initializeFilters() {
   
    const sucursalValues = [...new Set(allRows.map(row => row.dataset.sucursal))].filter(v => v && v.trim() !== '').sort();
    const colaboradorValues = [...new Set(allRows.map(row => row.dataset.colaborador))].filter(v => v && v.trim() !== '').sort();

   
    populateSelect('filter-sucursal', sucursalValues);
    populateSelect('filter-colaborador', colaboradorValues);

    
    document.getElementById('filter-sucursal').addEventListener('change', applyFilters);
    document.getElementById('filter-colaborador').addEventListener('change', applyFilters);
    document.getElementById('filter-indicador').addEventListener('change', applyFilters);
    document.getElementById('filter-fecha-desde').addEventListener('change', applyFilters);
    document.getElementById('filter-fecha-hasta').addEventListener('change', applyFilters);
}

// Evita duplicar valores en los selects
function populateSelect(selectId, values) {
    const select = document.getElementById(selectId);

    // Guarda la primera opción (Todos/Todas)
    const firstOption = select.options[0];
    select.innerHTML = '';
    select.appendChild(firstOption);

    // Agrega solo valores únicos
    const uniqueValues = [...new Set(values.filter(v => v && v.trim() !== ''))];
    uniqueValues.forEach(v => {
        const option = document.createElement('option');
        option.value = v;
        option.textContent = v;
        select.appendChild(option);
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

    filteredRows = allRows.filter(row => {
        if (filters.sucursal && row.dataset.sucursal !== filters.sucursal) return false;
        if (filters.colaborador && row.dataset.colaborador !== filters.colaborador) return false;
        if (filters.indicador && row.dataset.indicador !== filters.indicador) return false;
        if (filters.fechaDesde && row.dataset.fecha < filters.fechaDesde) return false;
        if (filters.fechaHasta && row.dataset.fecha > filters.fechaHasta) return false;
        return true;
    });

    renderTable();
}

function renderTable() {
    allRows.forEach(row => row.style.display = 'none');
    filteredRows.forEach(row => {
        row.style.display = '';

        const indicador = row.dataset.indicador;
        switch (indicador) {
            case '1': row.style.backgroundColor = 'transparent'; row.style.color = '#000'; break;
            case '2': row.style.backgroundColor = '#42A5F5'; row.style.color = '#fff'; break;
            case '3': row.style.backgroundColor = '#FFEB3B'; row.style.color = '#000'; break;
            case '4': row.style.backgroundColor = '#66BB6A'; row.style.color = '#fff'; break;
            case '5': row.style.backgroundColor = '#E53935'; row.style.color = '#fff'; break;
            default: row.style.backgroundColor = 'transparent'; row.style.color = '#000';
        }
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

// --- Eliminar registros ---
function agregarEventosEliminar() {
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', async () => {
            const fila = btn.closest('tr');
            const id = btn.dataset.id;
            const indicador = fila.dataset.indicador;

            if (indicador !== '1' && indicador !== '2') {
                alert('❌ Solo se pueden eliminar registros con estatus "Anotado" o "Visto".');
                return;
            }

            if (!confirm('¿Seguro que deseas eliminar este registro?')) return;

            try {
                const response = await fetch('../../funciones.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({ accion: 'eliminar_bitacora', id })
                });
                const result = await response.json();

                if (result.success) {
                    alert('✅ Registro eliminado correctamente');
                    fila.remove();
                    allRows = Array.from(document.querySelectorAll('#table-body tr'));
                    filteredRows = [...allRows];
                    renderTable();
                } else {
                    alert('⚠️ Error al eliminar el registro.');
                }
            } catch (error) {
                alert('❌ Error en la solicitud: ' + error.message);
            }
        });
    });
}
</script>

</body>
</html>
