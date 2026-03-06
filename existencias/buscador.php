<?php
// Activar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Cargar funciones.php
try {
    require_once '../funciones.php';
} catch (Exception $e) {
    die("Error al cargar funciones.php: " . $e->getMessage());
}

// Manejar peticiones AJAX para autocompletado
if (isset($_GET['action']) && $_GET['action'] === 'autocompletar') {
    header('Content-Type: application/json');
    $termino = $_GET['termino'] ?? '';
    
    if (strlen($termino) >= 2) {
        $sugerencias = buscarSugerencias($termino);
        echo json_encode($sugerencias);
    } else {
        echo json_encode([]);
    }
    exit;
}

// Manejar peticiones AJAX para búsqueda de productos
if (isset($_GET['action']) && $_GET['action'] === 'buscar') {
    header('Content-Type: application/json');
    $termino = $_GET['termino'] ?? '';
    
    if (strlen($termino) >= 2) {
        $resultados = buscarProductos($termino);
        echo json_encode($resultados);
    } else {
        echo json_encode([]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscador de Productos</title>
      <link rel="stylesheet" href="css.css?v=<?php echo time(); ?>">
 
</head>
<body>
    <div class="container">
        <h1>🔍 Buscador de Productos</h1>
        <p class="subtitle">Escribe para buscar productos por descripción o código de barras</p>
        
        <div class="search-section">
            <div class="search-wrapper">
                <input 
                    type="text" 
                    id="termino_busqueda" 
                    placeholder="Escribe la descripción o código de barras..."
                    autocomplete="off"
                >
                <div class="autocomplete-list" id="autocompleteList"></div>
            </div>
            
           
        </div>
        
        <div class="results-section" id="resultsSection">
            <div class="results-header">
                <h3>📊 Resultados de la búsqueda</h3>
                <span class="results-count" id="resultsCount">0 resultado(s)</span>
            </div>
            
            <div id="tableContainer">
                <!-- Aquí se cargará la tabla dinámicamente -->
            </div>
        </div>
    </div>
    
    <script>
        let lastSearchId = 0;
       const input = document.getElementById('termino_busqueda');
const autocompleteList = document.getElementById('autocompleteList');
const resultsSection = document.getElementById('resultsSection');
const tableContainer = document.getElementById('tableContainer');
const resultsCount = document.getElementById('resultsCount');

let selectedIndex = -1;
let suggestions = [];
let debounceTimer;

// Forzar mayúsculas
input.addEventListener('input', function () {
    const cursorPos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(cursorPos, cursorPos);
});

// SOLUCIÓN: UNA SOLA búsqueda con un solo timer
input.addEventListener('input', function() {
    const termino = this.value.trim();
    
    // Limpiar timer anterior
    clearTimeout(debounceTimer);
    
    // Si el campo está vacío, ocultar todo
    if (termino.length === 0) {
        autocompleteList.classList.remove('show');
        resultsSection.classList.remove('show');
        return;
    }
    
    // UNA SOLA búsqueda después de 400ms
    if (termino.length >= 2) {
        debounceTimer = setTimeout(() => {
            // Cargar autocompletado y búsqueda al mismo tiempo
            cargarAutocompletado(termino);
            buscarProductos(termino);
        }, 400);
    }
});

// Cargar sugerencias de autocompletado
function cargarAutocompletado(termino) {
    fetch(`?action=autocompletar&termino=${encodeURIComponent(termino)}`)
        .then(response => response.json())
        .then(data => {
            suggestions = data;
            mostrarSugerencias(data);
        })
        .catch(error => console.error('Error en autocompletado:', error));
}

// Buscar productos y actualizar tabla
function buscarProductos(termino) {
    const searchId = ++lastSearchId; // ID único para esta búsqueda
    
    tableContainer.innerHTML = '<div class="loading"><div class="loading-spinner"></div>Buscando productos...</div>';
    resultsSection.classList.add('show');

    fetch(`?action=buscar&termino=${encodeURIComponent(termino)}`)
        .then(response => response.json())
        .then(data => {
            // ⚠️ SOLO mostrar si es la búsqueda MÁS RECIENTE
            if (searchId === lastSearchId) {
                mostrarResultados(data);
            }
        })
        .catch(error => {
            if (searchId === lastSearchId) {
                tableContainer.innerHTML = `
                    <div class="no-results">
                        <h3>Error al buscar</h3>
                    </div>`;
            }
        });
}
// Mostrar resultados en la tabla
function mostrarResultados(productos) {
    resultsCount.textContent = `${productos.length} resultado(s)`;
    
    if (productos.length === 0) {
        tableContainer.innerHTML = `
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>No se encontraron resultados</h3>
                <p>Intenta con otro término de búsqueda</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Sucursal / Almacén</th>
                        <th>Descripción</th>
                        <th>Existencia</th>
                        <th>BarcodeId</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    productos.forEach(producto => {
        const existencia = parseInt(producto.existencia);
        let claseExistencia = 'existencia-baja';
        if (existencia >= 10) claseExistencia = 'existencia-alta';
        else if (existencia >= 5) claseExistencia = 'existencia-media';
        
        const nombreAlmacen = producto.nombre_almacen || 'Sin almacén';
        const precio = parseFloat(producto.publico_general).toFixed(2);
        
        html += `
            <tr>
                <td>${escapeHtml(nombreAlmacen)}</td>
                <td>${escapeHtml(producto.descripcion)}</td>
                <td>
                    <span class="existencia-badge ${claseExistencia}">
                        ${existencia}
                    </span>
                </td>
                <td>
                    <span class="barcode-text">${escapeHtml(producto.BarcodeId)}</span>
                </td>
                <td class="precio">$${precio}</td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    tableContainer.innerHTML = html;
}

// Mostrar sugerencias de autocompletado
function mostrarSugerencias(data) {
    if (data.length === 0) {
        autocompleteList.classList.remove('show');
        return;
    }
    
    autocompleteList.innerHTML = '';
    data.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'autocomplete-item';
        if (index === 0) div.classList.add('active');
        div.innerHTML = `
            <div class="descripcion">${escapeHtml(item.descripcion)}</div>
            <div class="barcode">Código: ${escapeHtml(item.BarcodeId)}</div>
        `;
        div.addEventListener('click', () => seleccionarItem(item.descripcion));
        autocompleteList.appendChild(div);
    });
    
    autocompleteList.classList.add('show');
    selectedIndex = 0;
}

// Navegación con teclado
input.addEventListener('keydown', function(e) {
    const items = autocompleteList.querySelectorAll('.autocomplete-item');
    
    if (items.length === 0) return;
    
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedIndex = (selectedIndex + 1) % items.length;
        actualizarSeleccion(items);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedIndex = (selectedIndex - 1 + items.length) % items.length;
        actualizarSeleccion(items);
    } else if (e.key === 'Enter') {
        if (selectedIndex >= 0 && selectedIndex < items.length) {
            e.preventDefault();
            const descripcion = suggestions[selectedIndex].descripcion;
            seleccionarItem(descripcion);
        }
    } else if (e.key === 'Escape') {
        autocompleteList.classList.remove('show');
    }
});

// Actualizar selección visual
function actualizarSeleccion(items) {
    items.forEach((item, index) => {
        if (index === selectedIndex) {
            item.classList.add('active');
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } else {
            item.classList.remove('active');
        }
    });
}

// Seleccionar item del autocompletado
function seleccionarItem(descripcion) {
    input.value = descripcion;
    autocompleteList.classList.remove('show');
    selectedIndex = -1;
    
    // Cancelar cualquier búsqueda pendiente
    clearTimeout(debounceTimer);
    
    // Disparar búsqueda inmediata
    buscarProductos(descripcion);
}

// Cerrar autocompletado al hacer click fuera
document.addEventListener('click', function(e) {
    if (!input.contains(e.target) && !autocompleteList.contains(e.target)) {
        autocompleteList.classList.remove('show');
    }
});

// Función para escapar HTML y prevenir XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
    </script>
</body>
</html>