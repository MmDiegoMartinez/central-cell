<?php include_once '../funciones.php'; $garantias = verTabla(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tabla de Garantías y Mermas</title>
     <link rel="stylesheet" href="../csstabla.css?v=<?php echo time(); ?>">

</head>
<body>
    
     <nav style="background:#0F5476; padding:10px;">
        <ul id="menu">
            <li>
  <a href="garantias.php" style="display: flex; align-items: center; gap: 12px;  ">
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
      <img src="../Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" 
           style="
             width: 30px; 
             height: 30Px; 
             object-fit: contain;
             position: relative;
             top: 0; left: 0;
           " />
    </span>
     Home
  </a>
</li>

<li>
  <a href="tabla.php" style="display: flex; align-items: center; gap: 12px;  ">
    
      <img src="../recursos/img/merma.png" alt="Logo Central Cell" 
           style="
             widthttps://garantiasinnovacionmovil.rf.gd/vendedor/garantias.php?i=1h: 40px; 
             height: 40Px; 
             object-fit: contain;
             position: relative;
             top: 0; left: 0;
           " />
    </span>
     Garantías / Mermas
  </a>
</li>

            
        </ul>
    </nav>
    <hr>
    
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
            </div>
        </div>
        
        <div class="results-info">
            <span id="results-count">Cargando datos...</span>
        </div>
        
        <div class="table-container">
            <table border="1" cellpadding="5" cellspacing="0" id="garantias-table">
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
                        
                        <th></th>
                        <th>Validador</th>
                        <th>Piezas Validadas</th>
                        <th>Hora de Validación</th>
                        <th>Fecha de Validación</th>
                        <th>Número de Ajuste</th>
                        <th>Anotación del Validador</th>
                        <th>Anotación del Vendedor</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    <?php foreach ($garantias as $g): ?>
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
                                <?php if (!$g["validador_nombre"]): ?>
                                    
                                    <a href="eliminar.php?id=<?= $g["id"] ?>" onclick="return confirm('¿Seguro que quieres eliminar esta garantía?')">🗑️</a>
                                <?php else: ?>
                                    <span class="validated">(Validado)</span>
                                <?php endif; ?>
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
                            <td><?= htmlspecialchars($g['piezas_validadas']) ?></td>
                            <td><?= htmlspecialchars($g['hora']) ?></td>
                            <td><?= htmlspecialchars($g['fecha_validacion']) ?></td>
                            <td><?= htmlspecialchars($g['numero_ajuste']) ?></td>
                            <td><?= htmlspecialchars($g['anotaciones_validador']) ?></td>
                             <td><?= htmlspecialchars($g['anotaciones_vendedor']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
    </script>
    <script>
let currentHash = "";

/*async function checkForNewGarantias() {
    try {
        const response = await fetch('ver_garantias_json.php');
        const data = await response.json();

        // Obtener IDs ya existentes en la tabla
        const existingIds = Array.from(document.querySelectorAll('#table-body tr'))
                                 .map(row => row.dataset.id);

        let nuevos = 0;

        data.forEach(g => {
            if (!existingIds.includes(g.id)) {
                // Crear nueva fila solo si no existe
                const row = document.createElement('tr');
                row.setAttribute('data-id', g.id);
                row.setAttribute('data-plows', g.plows);
                row.setAttribute('data-tipo', g.tipo);
                row.setAttribute('data-causa', g.causa);
                row.setAttribute('data-piezas', g.piezas);
                row.setAttribute('data-sucursal', g.sucursal);
                row.setAttribute('data-colaborador', g.apasionado);
                row.setAttribute('data-fecha', g.fecha);
                row.setAttribute('data-estatus', g.estatus);
                row.setAttribute('data-validador', g.validador_nombre ? g.validador_nombre + ' ' + g.validador_apellido : 'No validado');

                // Generar HTML de la fila (idéntico a tu PHP)
                row.innerHTML = `
                    <td>${g.plows}</td>
                    <td>${g.tipo}</td>
                    <td>${g.causa}</td>
                    <td>${g.piezas}</td>
                    <td>${g.sucursal}</td>
                    <td>${g.apasionado}</td>
                    <td>${g.fecha}</td>
                    <td>${g.estatus}</td>
                    <td>${g.anotaciones_vendedor}</td>
                    <td class="action-links">
                        ${g.validador_nombre ? '<span class="validated">(Validado)</span>' : 
                        `<a href="editar.php?id=${g.id}">✏️ Editar</a> |
                         <a href="eliminar.php?id=${g.id}" onclick="return confirm('¿Seguro que quieres eliminar esta garantía?')">🗑️ Eliminar</a>`}
                    </td>
                    <td>${g.validador_nombre ? g.validador_nombre + ' ' + g.validador_apellido : 'No validado'}</td>
                    <td>${g.piezas_validadas}</td>
                    <td>${g.hora}</td>
                    <td>${g.fecha_validacion}</td>
                    <td>${g.numero_ajuste}</td>
                    <td>${g.anotaciones_validador}</td>
                `;

                document.querySelector('#table-body').prepend(row);  // Insertar al inicio

                nuevos++;
            }
        });

        if (nuevos > 0) {
            // Reactualizar los arrays para filtros
            allRows = Array.from(document.querySelectorAll('#table-body tr'));
            applyFilters();  // Respetar filtros activos
            updateResultsCount();
        }
    } catch (error) {
        console.error("Error al verificar nuevas garantías:", error);
    }
}

// Ejecutar cada 30 segundos
setInterval(checkForNewGarantias, 30000);*/


function resaltarCeldasValidadas() {
    const tabla = document.querySelector("table");
    const filas = tabla.querySelectorAll("tbody tr");

    filas.forEach(fila => {
        const celdas = fila.querySelectorAll("td");

        // Recorremos cada celda para buscar "(Validado)"
        celdas.forEach((celda, index) => {
            if (celda.textContent.includes("(Validado)")) {
                if (celdas[10]) celdas[10].classList.add("validado");
                if (celdas[11]) celdas[11].classList.add("validado");
                if (celdas[12]) celdas[12].classList.add("validado");
                if (celdas[13]) celdas[13].classList.add("validado");
            }
        });

        // Luego, SI la celda 14 NO está vacía, le agregamos la clase
        if (celdas[14] && celdas[14].textContent.trim() !== "") {
            celdas[14].classList.add("anotacionvalidador");
        }
        if (celdas[15] && celdas[15].textContent.trim() !== "") {
            celdas[15].classList.add("anotacionvalidador");
        }
    });
}

// Ejecutar después de cargar la tabla
document.addEventListener("DOMContentLoaded", resaltarCeldasValidadas);
</script>



<footer style="text-align: center; padding: 10px; font-size: 0.85rem; color: #777; margin-top: 50px;">
  <p>&copy; <span id="year"></span> Diego – Innovación Móvil</p>
</footer>

<script>
  document.getElementById("year").textContent = new Date().getFullYear();
</script>
</body>
</html>