<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

include_once '../../funciones.php';
$conn = conectarBD();

// Se ejecutará máximo una vez al día
$actualizadas = actualizarGarantiasDiario($conn);

$garantias = verTablavalidador();

$nombre = $_SESSION['validador_nombre'] ?? '';
$apellido = $_SESSION['validador_apellido'] ?? '';
$validador_id = $_SESSION['validador_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Validador</title>
   <link rel="stylesheet" href="../../csstabla.css">
  <style>
  .btn-edit {
    display: inline-block !important;
    visibility: visible !important;
    opacity: 1 !important;
  }
</style>
</head>
<script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
<body>
    

<nav style="background:#1D6C90; padding:10px;">
  <ul id="menu">
    
    <li>
      <a href="validador.php" style="display: flex; align-items: center; gap: 12px;">
        <span style="
          display: inline-flex;
          width: 40px; 
          height: 40px; 
          background: white; 
          border-radius: 50%; 
          justify-content: center; 
          align-items: center; 
          overflow: visible;
          position: relative;
        ">
          <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" 
               style="
                 width: 30px; 
                 height: 30px; 
                 object-fit: contain;
                 position: relative;
                 top: 0; left: 0;
               " />
        </span>
        Home
      </a>
    </li>

    <li>
      <a href="../vendedor/garantias.php" style="display: flex; align-items: center; gap: 12px;">
        👨🏻‍💼 Apdo. Vendedor
      </a>
    </li>

    <li>
      <a href="../../existencias/index.php" style="display: flex; align-items: center; gap: 12px;">
        📦 Existencias
      </a>
    </li>

    <li>
      <a href="anotarmermassinregistrar.php" style="display: flex; align-items: center; gap: 12px;">
        ⚠️ Anot Mer.
      </a>
    </li>

    <li>
      <a href="tabla.php" style="display: flex; align-items: center; gap: 12px;">
        📌 Mermas sin reg.
      </a>
    </li>
    

    <li>
      <a href="../../Evaluacion/lista_colaboradores.php" style="display: flex; align-items: center; gap: 12px;">
        📘 Capacit.
      </a>
    </li>
    <li>
      <a href="../../compatibilidades/index.php" style="display: flex; align-items: center; gap: 12px;">
        🔗 Compatibilidades
      </a>
    </li>
     <li>
      <a href="../../kpis/index.php" style="display: flex; align-items: center; gap: 12px;">
        📈  KPIs
      </a>
    </li>
    <li>
      <a href="sucursales.php" style="display: flex; align-items: center; gap: 12px;">
        🏬 Sucursales
      </a>
    </li>
    <li>
      <a href="https://docs.google.com/spreadsheets/d/1QIicEhXQNDOwBXIwqZs9Y0KT1MdWluXwdPh68vwlCVc/edit?usp=sharing" style="display: flex; align-items: center; gap: 12px;">
       📑 Bitacora
      </a>
    </li>


    <li>
      <a href="Validadores.php" style="display: flex; align-items: center; gap: 12px;">
        🆕 Validador
      </a>
    </li>

  </ul>
</nav>


    <header style="display: flex; justify-content: flex-end; align-items: center; gap: 15px; padding: 10px 20px; font-family: Arial, sans-serif;">
  <span style="font-weight: 600; font-size: 18px;">Bienvenido, <?= htmlspecialchars($nombre) . ' ' . htmlspecialchars($apellido) ?></span>
  <form action="logout.php" method="POST" style="margin: 0;">
    <button type="submit" class="Btn" aria-label="Cerrar sesión">
      <div class="sign">
        <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"
          ></path>Filtros por Columna
        </svg>
      </div>
      <div class="text">ㅤLogout</div>
    </button>
  </form>
</header>

    
    <div class="container">
        <h2>Historial de Garantías y Mermas</h2>
        
        <div class="filters-container">
            <h3>Filtros por Columna</h3>
            <div class="filter-row">
                <div class="filter-group">
    <label>PLOWS:</label>
    <input type="text" id="filter-plows" placeholder="Escanea o escribe el código">
</div>

                <div class="filter-group">
                    <label>Tipo:</label>
                    <select id="filter-tipo">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Causa:</label>
                    <select id="filter-causa">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Sucursal:</label>
                    <select id="filter-sucursal">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Colaborador:</label>
                    <select id="filter-colaborador">
                        <option value="">Todos</option>
                    </select>
                </div>
            </div>
            <div class="filter-row">
                <div class="filter-group">
                    <label>Estatus:</label>
                    <select id="filter-estatus">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Validador:</label>
                    <select id="filter-validador">
                        <option value="">Todos</option>
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
            </div>
            <div class="filter-buttons">
                <button class="btn-clear" onclick="clearAllFilters()">🔄 Limpiar Filtros</button>
                 <button type="button" id="btn-descargar">📥 Descargar</button>
            </div>
            
        </div>
        
        <div class="results-info">
            <span id="results-count">Cargando datos...</span>
        </div>
        <form method="POST" action="guardar_validaciones.php">
        <button class="action_has has_saved" aria-label="Guardar cambios" type="submit">
  <svg
    aria-hidden="true"
    xmlns="http://www.w3.org/2000/svg"
    width="20"
    height="20"
    stroke-linejoin="round"
    stroke-linecap="round"
    stroke-width="2"
    viewBox="0 0 24 24"
    stroke="currentColor"
    fill="none"
  >
    <path
      d="m19,21H5c-1.1,0-2-.9-2-2V5c0-1.1.9-2,2-2h11l5,5v11c0,1.1-.9,2-2,2Z"
      stroke-linejoin="round"
      stroke-linecap="round"
      data-path="box"
    ></path>
    <path
      d="M7 3L7 8L15 8"
      stroke-linejoin="round"
      stroke-linecap="round"
      data-path="line-top"
    ></path>
    <path
      d="M17 20L17 13L7 13L7 20"
      stroke-linejoin="round"
      stroke-linecap="round"
      data-path="line-bottom"
    ></path>
  </svg>
</button> <br>

        <div class="table-container">
            <table border="1" cellpadding="0" cellspacing="0" id="garantias-table">

    <thead>
        <tr style="background:#ddd;">
            <th>PLOWS</th>
            <th>Tipo</th>
            <th>Causa</th>
            <th>Piezas</th>
            <th>Sucursal</th>
            <th>Colaborador</th>
            <th>Fecha de Registro</th>
            <th>Estatus</th>
            <th>Acciones</th>
            <th>Validador</th>
            <th>Piezas Validadas</th>
            <th>Número de Ajuste</th>
            <th>Anotación del Validador</th>
            <th>Hora de Validación</th>
            <th>Fecha de Validación</th>
            <th>Anotación del Vendedor</th>
            <th>Fecha Creación</th>

        </tr>
    </thead>
    <tbody id="table-body">
        <?php foreach ($garantias as $g): ?>
            <?php
                $bloquear = ($g['piezas_validadas'] > 0 && $g['numero_ajuste'] > 0 && $g['id_validador'] !== null);
                $readonly = $bloquear ? 'readonly' : '';
            ?>
            <tr data-id="<?= $g['id'] ?>" 
                data-plows="<?= htmlspecialchars($g['plows']) ?>"
                data-tipo="<?= htmlspecialchars($g['tipo']) ?>"
                data-causa="<?= htmlspecialchars($g['causa']) ?>"
                data-piezas="<?= htmlspecialchars($g['piezas']) ?>"
                data-sucursal="<?= htmlspecialchars($g['sucursal']) ?>"
                data-colaborador="<?= htmlspecialchars($g['apasionado']) ?>"
                data-fecha="<?= htmlspecialchars($g['fecha']) ?>"
                data-estatus="<?= htmlspecialchars($g['estatus']) ?>"
                data-validador="<?= $g['validador_nombre'] ? htmlspecialchars($g['validador_nombre'] . ' ' . $g['validador_apellido']) : 'No validado' ?>">

                <td><?= htmlspecialchars($g['plows']) ?></td>
                <td><?= htmlspecialchars($g['tipo']) ?></td>
                <td><?= htmlspecialchars($g['causa']) ?></td>
                <td><?= htmlspecialchars($g['piezas']) ?></td>
                <td><?= htmlspecialchars($g['sucursal']) ?></td>
                <td><?= htmlspecialchars($g['apasionado']) ?></td>
                <td><?= htmlspecialchars($g['fecha']) ?></td>
                <td><?= htmlspecialchars($g['estatus']) ?></td>

                <td class="action-links">
                    <button type="button" onclick="openEditModal(this)" 
                    data-id="<?= $g['id'] ?>"
                    data-plows="<?= htmlspecialchars($g['plows']) ?>"
                    data-tipo="<?= htmlspecialchars($g['tipo']) ?>"
                    data-causa="<?= htmlspecialchars($g['causa']) ?>"
                    data-piezas="<?= htmlspecialchars($g['piezas']) ?>"
                    data-sucursal="<?= htmlspecialchars($g['sucursal']) ?>"
                    data-colaborador="<?= htmlspecialchars($g['apasionado']) ?>"
                    class="btn-edit">✏️ Editar</button>
                    |
                    <a href="eliminar.php?id=<?= $g["id"] ?>" onclick="return confirm('¿Seguro que quieres eliminar esta garantía?')">🗑️ Eliminar</a>
                </td>

                <td>
                    <?php
                        if ($g['validador_nombre']) {
                            echo htmlspecialchars($g['validador_nombre'] . ' ' . $g['validador_apellido']);
                        } else {
                            echo 'No validado';
                        }
                    ?>
                </td>

                <td>
                    <input type="number" name="piezas_validadas[<?= $g['id'] ?>]" value="<?= htmlspecialchars($g['piezas_validadas']) ?>" <?= $readonly ?>>
                </td>
                <td>
                    <input type="number" name="numero_ajuste[<?= $g['id'] ?>]" value="<?= htmlspecialchars($g['numero_ajuste']) ?>" <?= $readonly ?>>
                </td>
                <td>
                    <input type="text" name="anotaciones_validador[<?= $g['id'] ?>]" value="<?= htmlspecialchars($g['anotaciones_validador']) ?>" <?= $readonly ?>>
                </td>
                <td><?= htmlspecialchars($g['hora']) ?></td>
                <td><?= htmlspecialchars($g['fecha_validacion']) ?></td>
                <td><?= htmlspecialchars($g['anotaciones_vendedor']) ?></td>
                <td><?= isset($g['created_at']) && $g['created_at'] !== null ? htmlspecialchars($g['created_at']) : '---' ?></td>



            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            </form>
            <div id="editModal" class="modal" style="
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background-color: rgba(0,0,0,0.6);
  justify-content: center;
  align-items: center;
">
  <div  class="modaldos">
    <h3>Editar Registro</h3>
    <form id="editForm">
      <input type="hidden" id="edit-id" name="id">

      <label>PLOWS:</label>
      <input type="text" id="edit-plows" name="plows" required><br>

      <label>Piezas Validadas:</label>
      <input type="number" id="edit-piezas-validadas" name="piezas_validadas"><br>

      <label>Número de Ajuste:</label>
      <input type="text" id="edit-numero-ajuste" name="numero_ajuste"><br>

      <label>Anotaciones del Validador:</label>
      <textarea id="edit-anotaciones-validador" name="anotaciones_validador" rows="3"></textarea><br>

      <div style="margin-top:15px;display:flex;justify-content:space-between;">
        <button type="button" onclick="closeModal()">Cancelar</button>
        <button type="submit" style="background:#1D6C90;color:white;">Guardar</button>
      </div>
    </form>
  </div>
</div>
        </div>
    </div>

    <script>
        // Obtener todos los datos de las filas de la tabla
        let allRows = [];
        let filteredRows = [];

        function initializeData() {
            const tableRows = document.querySelectorAll('#table-body tr');
            allRows = Array.from(tableRows);
            filteredRows = [...allRows];
            
            initializeFilters();
            updateResultsCount();
        }

        function initializeFilters() {
            // Obtener valores únicos para cada columna
            const plowsValues = [...new Set(allRows.map(row => row.dataset.plows))].filter(v => v).sort();
            const tipoValues = [...new Set(allRows.map(row => row.dataset.tipo))].filter(v => v).sort();
            const causaValues = [...new Set(allRows.map(row => row.dataset.causa))].filter(v => v).sort();
            const sucursalValues = [...new Set(allRows.map(row => row.dataset.sucursal))].filter(v => v).sort();
            const colaboradorValues = [...new Set(allRows.map(row => row.dataset.colaborador))].filter(v => v).sort();
            const estatusValues = [...new Set(allRows.map(row => row.dataset.estatus))].filter(v => v).sort();
            const validadorValues = [...new Set(allRows.map(row => row.dataset.validador))].filter(v => v).sort();

            // Poblar los selectores
            //populateSelect('filter-plows', plowsValues);
            populateSelect('filter-tipo', tipoValues);
            populateSelect('filter-causa', causaValues);
            populateSelect('filter-sucursal', sucursalValues);
            populateSelect('filter-colaborador', colaboradorValues);
            populateSelect('filter-estatus', estatusValues);
            populateSelect('filter-validador', validadorValues);

            // Agregar event listeners
           document.getElementById('filter-plows').addEventListener('input', applyFilters);


            document.getElementById('filter-tipo').addEventListener('change', applyFilters);
            document.getElementById('filter-causa').addEventListener('change', applyFilters);
            document.getElementById('filter-sucursal').addEventListener('change', applyFilters);
            document.getElementById('filter-colaborador').addEventListener('change', applyFilters);
            document.getElementById('filter-estatus').addEventListener('change', applyFilters);
            document.getElementById('filter-validador').addEventListener('change', applyFilters);
            document.getElementById('filter-fecha-desde').addEventListener('change', applyFilters);
            document.getElementById('filter-fecha-hasta').addEventListener('change', applyFilters);
        }

        function populateSelect(selectId, values) {
    const select = document.getElementById(selectId);
    // Guardar la primera opción (ej. "Todos")
    const firstOption = select.options[0];
    select.innerHTML = ''; // Limpiar todo
    if (firstOption) {
        select.appendChild(firstOption); // volver a agregar la opción inicial
    }

    values.forEach(value => {
        if (value && value.trim() !== '') {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            select.appendChild(option);
        }
    });
}


        function applyFilters() {
            const filters = {
                plows: document.getElementById('filter-plows').value,
                tipo: document.getElementById('filter-tipo').value,
                causa: document.getElementById('filter-causa').value,
                sucursal: document.getElementById('filter-sucursal').value,
                colaborador: document.getElementById('filter-colaborador').value,
                estatus: document.getElementById('filter-estatus').value,
                validador: document.getElementById('filter-validador').value,
                fechaDesde: document.getElementById('filter-fecha-desde').value,
                fechaHasta: document.getElementById('filter-fecha-hasta').value
            };

            filteredRows = allRows.filter(row => {
                // Filtro por PLOWS
                if (filters.plows && !row.dataset.plows.toLowerCase().includes(filters.plows.toLowerCase())) return false;

                
                // Filtro por tipo
                if (filters.tipo && row.dataset.tipo !== filters.tipo) return false;
                
                // Filtro por causa
                if (filters.causa && row.dataset.causa !== filters.causa) return false;
                
                // Filtro por sucursal
                if (filters.sucursal && row.dataset.sucursal !== filters.sucursal) return false;
                
                // Filtro por colaborador
                if (filters.colaborador && row.dataset.colaborador !== filters.colaborador) return false;
                
                // Filtro por estatus
                if (filters.estatus && row.dataset.estatus !== filters.estatus) return false;
                
                // Filtro por validador
                if (filters.validador && row.dataset.validador !== filters.validador) return false;
                
                // Filtro por fecha desde
                if (filters.fechaDesde && row.dataset.fecha < filters.fechaDesde) return false;
                
                // Filtro por fecha hasta
                if (filters.fechaHasta && row.dataset.fecha > filters.fechaHasta) return false;
                
                return true;
            });

            renderFilteredTable();
        }

        function renderFilteredTable() {
            const tbody = document.getElementById('table-body');
            
            // Ocultar todas las filas
            allRows.forEach(row => {
                row.style.display = 'none';
            });
            
            // Mostrar solo las filas filtradas
            if (filteredRows.length === 0) {
                // Si no hay resultados, mostrar mensaje
                if (!document.getElementById('no-results-row')) {
                    const noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'no-results-row';
                    noResultsRow.innerHTML = '<td colspan="16" class="no-results">No se encontraron resultados con los filtros aplicados</td>';
                    tbody.appendChild(noResultsRow);
                }
                document.getElementById('no-results-row').style.display = '';
            } else {
                // Ocultar mensaje de no resultados si existe
                const noResultsRow = document.getElementById('no-results-row');
                if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }
                
                // Mostrar filas filtradas
                filteredRows.forEach(row => {
                    row.style.display = '';
                });
            }

            updateResultsCount();
        }

        function updateResultsCount() {
            const resultsCount = document.getElementById('results-count');
            resultsCount.textContent = `Mostrando ${filteredRows.length} de ${allRows.length} registros`;
        }

        function clearAllFilters() {
            document.getElementById('filter-plows').value = '';
            document.getElementById('filter-tipo').value = '';
            document.getElementById('filter-causa').value = '';
            document.getElementById('filter-sucursal').value = '';
            document.getElementById('filter-colaborador').value = '';
            document.getElementById('filter-estatus').value = '';
            document.getElementById('filter-validador').value = '';
            document.getElementById('filter-fecha-desde').value = '';
            document.getElementById('filter-fecha-hasta').value = '';
            
            filteredRows = [...allRows];
            renderFilteredTable();
        }

        // Inicializar cuando se carga la página
        document.addEventListener('DOMContentLoaded', function() {
            initializeData();
        });
        //sxel
        document.getElementById('btn-descargar').addEventListener('click', function() {
    descargarExcel();
});

async function descargarExcel() {
    const filasVisibles = filteredRows;
    if (filasVisibles.length === 0) {
        alert('No hay datos para descargar.');
        return;
    }

    const wb = XLSX.utils.book_new();

    // 1. Hoja "Tabla Completa"
    const encabezados = [
        "PLOWS", "Tipo", "Causa", "Piezas", "Sucursal", "Colaborador", "Fecha de Registro", 
        "Estatus", "Validador", "Piezas Validadas", "Número de Ajuste", "Anotación del Validador", 
        "Hora de Validación", "Fecha de Validación", "Anotación del Vendedor"
    ];
    const datosTablaCompleta = [encabezados];

    filasVisibles.forEach(row => {
        datosTablaCompleta.push([
            row.dataset.plows,
            row.dataset.tipo,
            row.dataset.causa,
            row.dataset.piezas,
            row.dataset.sucursal,
            row.dataset.colaborador,
            row.dataset.fecha,
            row.dataset.estatus,
            row.dataset.validador,
            row.querySelector('input[name^="piezas_validadas"]').value,
            row.querySelector('input[name^="numero_ajuste"]').value,
            row.querySelector('input[name^="anotaciones_validador"]').value,
            row.querySelector('td:nth-last-child(2)').textContent.trim(), // Hora validación
            row.querySelector('td:nth-last-child(1)').textContent.trim(), // Fecha validación
            row.querySelector('td:nth-child(9)').textContent.trim() // Anotación vendedor
        ]);
    });

    const ws1 = XLSX.utils.aoa_to_sheet(datosTablaCompleta);
    XLSX.utils.book_append_sheet(wb, ws1, 'Tabla Completa');

    // 2. Agrupar sucursales y causas
    const sucursales = Array.from(new Set(filasVisibles.map(r => r.dataset.sucursal))).sort();
    const causas = Array.from(new Set(filasVisibles.map(r => r.dataset.causa))).sort();

    const piezasPorSucursalYCausa = {};
    const totalMermasPorSucursal = {};

    sucursales.forEach(sucursal => {
        piezasPorSucursalYCausa[sucursal] = {};
        totalMermasPorSucursal[sucursal] = 0;

        causas.forEach(causa => {
            const suma = filasVisibles
                .filter(r => r.dataset.sucursal === sucursal && r.dataset.causa === causa)
                .reduce((sum, r) => sum + (parseInt(r.dataset.piezas) || 0), 0);
            piezasPorSucursalYCausa[sucursal][causa] = suma;
            totalMermasPorSucursal[sucursal] += suma;
        });

        // Hoja por sucursal con columna de total
        const datosSucursal = [["Causa de Merma", "Total Piezas"]];
        causas.forEach(causa => {
            const val = piezasPorSucursalYCausa[sucursal][causa];
            if (val > 0) {
                datosSucursal.push([causa, val]);
            }
        });
        datosSucursal.push(["Total", totalMermasPorSucursal[sucursal]]);
        const ws = XLSX.utils.aoa_to_sheet(datosSucursal);
        XLSX.utils.book_append_sheet(wb, ws, sucursal.substring(0, 30));
    });

    // 3. Hoja resumen total (todas las sucursales)
    const datosResumen = [["Causa de Merma", "Total Piezas"]];
    causas.forEach(causa => {
        const suma = filasVisibles
            .filter(r => r.dataset.causa === causa)
            .reduce((sum, r) => sum + (parseInt(r.dataset.piezas) || 0), 0);
        if (suma > 0) {
            datosResumen.push([causa, suma]);
        }
    });
    const totalGeneral = datosResumen.slice(1).reduce((sum, row) => sum + row[1], 0);
    datosResumen.push(["Total", totalGeneral]);

    const wsResumen = XLSX.utils.aoa_to_sheet(datosResumen);
    XLSX.utils.book_append_sheet(wb, wsResumen, 'Resumen Total');

    // 4. Descargar directamente el archivo
    XLSX.writeFile(wb, 'garantias_filtradas.xlsx');
}

//
async function cargarDatos() {
    try {
        const resp = await fetch('get_garantias.php');
        if (!resp.ok) throw new Error('Error al cargar datos');
        const garantias = await resp.json();

        // Limpiar tabla
        const tbody = document.getElementById('table-body');
        tbody.innerHTML = '';

        garantias.forEach(g => {
            const bloquear = (g.piezas_validadas > 0 && g.numero_ajuste > 0 && g.id_validador !== null);
            const readonly = bloquear ? 'readonly' : '';

            const tr = document.createElement('tr');
            tr.dataset.id = g.id;
            tr.dataset.plows = g.plows;
            tr.dataset.tipo = g.tipo;
            tr.dataset.causa = g.causa;
            tr.dataset.piezas = g.piezas;
            tr.dataset.sucursal = g.sucursal;
            tr.dataset.colaborador = g.apasionado;
            tr.dataset.fecha = g.fecha;
            tr.dataset.estatus = g.estatus;
            tr.dataset.validador = g.validador_nombre ? g.validador_nombre + ' ' + g.validador_apellido : 'No validado';

            tr.innerHTML = `
                <td>${g.plows}</td>
                <td>${g.tipo}</td>
                <td>${g.causa}</td>
                <td>${g.piezas}</td>
                <td>${g.sucursal}</td>
                <td>${g.apasionado}</td>
                <td>${g.fecha}</td>
                <td>${g.estatus}</td>
                
               <td class="action-links">
                <button type="button" onclick="openEditModal(this)" 
                    data-id="${g.id}"
                    data-plows="${g.plows || ''}"
                    data-piezas_validadas="${g.piezas_validadas || ''}"
                    data-numero_ajuste="${g.numero_ajuste || ''}"
                    data-anotaciones_validador="${g.anotaciones_validador || ''}"
                    class="btn-edit">✏️</button>
                |
                <button 
onclick="if(confirm(\`¿Seguro que quieres eliminar esta garantía?\`)) location.href=\`eliminar.php?id=${g.id}\`" 
class="btn-eliminar">
🗑️
</button>
                </td>
                <td>${g.validador_nombre ? g.validador_nombre + ' ' + g.validador_apellido : 'No validado'}</td>
                <td><input type="number" name="piezas_validadas[${g.id}]" value="${g.piezas_validadas}" ${readonly} style="width: 80px; padding: 4px; border: 1px solid #ccc; border-radius: 4px;"></td>
                <td><input type="number" name="numero_ajuste[${g.id}]" value="${g.numero_ajuste}" ${readonly} style="width: 100px; padding: 4px; border: 1px solid #ccc; border-radius: 4px;"></td>
                <td><input type="text" name="anotaciones_validador[${g.id}]" value="${g.anotaciones_validador ?? ''}" ${readonly} style="width: 100px; padding: 4px; border: 1px solid #ccc; border-radius: 4px;"></td>

                <td>${g.hora}</td>
                <td>${g.fecha_validacion}</td>
                <td>${g.anotaciones_vendedor}</td>
                 <td>${g.created_at}</td> 
            `;
            tbody.appendChild(tr);
        });

        initializeData(); // re-inicializar filtros y demás lógicas
    } catch (error) {
        console.error(error);
        alert('Error al cargar los datos.');
    }
}

// Carga inicial
document.addEventListener('DOMContentLoaded', () => {
    cargarDatos();

    // Refrescar cada 120
    //  segundos (o el intervalo que prefieras)
    setInterval(cargarDatos, 1200000);
});
//AQUI ES SONDE SE MUVE LA TABLA POR CELDAS 
document.querySelectorAll('#garantias-table td').forEach(td => {
    td.setAttribute('tabindex', '0');
});
    </script>

  <script>
function openEditModal(button) {
  document.getElementById('edit-id').value = button.dataset.id;
  document.getElementById('edit-plows').value = button.dataset.plows || '';
  document.getElementById('edit-piezas-validadas').value = button.dataset.piezas_validadas || '';
  document.getElementById('edit-numero-ajuste').value = button.dataset.numero_ajuste || '';
  document.getElementById('edit-anotaciones-validador').value = button.dataset.anotaciones_validador || '';

  document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}

document.getElementById('editForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  try {
    const resp = await fetch('editar_garantia.php', {
      method: 'POST',
      body: formData
    });
    const data = await resp.json();

    if (data.success) {
      alert('Registro actualizado correctamente');
      location.reload();
    } else {
      alert('Error al actualizar: ' + data.message);
    }
  } catch (err) {
    console.error(err);
    alert('Error de conexión con el servidor.');
  }
});
</script>

    <br>
    <footer style="text-align: center; padding: 10px; font-size: 0.85rem; color: #777; margin-top: 50px;">
  <p>&copy; <span id="year"></span> Diego Fernando Martínez Santiago</p>
</footer>

<script>
  document.getElementById("year").textContent = new Date().getFullYear();
</script>
</body>
</html>
