<?php
session_start();

// Determinar si es administrador (1) o usuario normal (2)
$es_admin = isset($_SESSION['validador_id']) ? 1 : 2;
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../funciones.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Compatibilidades</title>
    
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">

</head>
<body>
<header class="main-header">
    <!-- Checkbox PRIMERO, antes de todo -->
    <input type="checkbox" id="check">
    
    <div class="header-top">
        <h1 class="titulo">
            <span class="logo-circle">
                <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" />
            </span>  
            Compatibilidades
        </h1>

        <!-- Botón hamburguesa animado -->
        <label class="bar" for="check">
            <span class="top"></span>
            <span class="middle"></span>
            <span class="bottom"></span>
        </label>
    </div>

    <nav id="menu">
        <ul>
            <li><a href="../garantias/vendedor/garantias.php">🧾 Registrar Garantía</a></li>
            <li><a href="tabla_compatibilidades.php">⬇️ Descargar Compatibilidades</a></li>
            <li><a href="ingresar.php">📝 Ingresar Compatibilidades</a></li>

            <?php if ($es_admin === 1): ?>
                <li><a href="eliminar.php">🗑️ Eliminar Compatibilidades</a></li>
                <li><a href="../garantias/validador/validador.php">🛡️ Validar Garantías</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<label for="modelo_buscar">Escribe el modelo:</label>
<input type="text" id="modelo_buscar" placeholder="Ej: IPHONE 13">
<input type="hidden" id="modelo_buscar_id">
<ul id="lista_buscar" class="autocomplete-list"></ul>
<br><br>

<label for="tipo_filtro">Tipo:</label>
<select id="tipo_filtro">
    <option value="glass">Glass</option>
    <option value="funda">Funda</option>
    <option value="camara">Protector de Cámara</option>
</select>
<br><br>

<button id="btn_consultar">Consultar</button>

<div id="resultados"></div>

<script>
function setupAutocomplete(inputId, hiddenId, listaId) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const lista = document.getElementById(listaId);
    let activeIndex = -1;

    input.addEventListener('input', function() {
        const q = this.value;
        hidden.value = '';
        activeIndex = -1;
        if (!q) {
            lista.innerHTML = '';
            return;
        }
        fetch(`buscar_modelos.php?q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                lista.innerHTML = '';
                data.forEach((m,index) => {
                    const li = document.createElement('li');
                    li.textContent = m.marca + ' ' + m.modelo;
                    li.dataset.id = m.id;
                    li.addEventListener('click', () => {
                        input.value = li.textContent;
                        hidden.value = li.dataset.id;
                        lista.innerHTML = '';
                    });
                    lista.appendChild(li);
                });
            });
    });

    input.addEventListener('keydown', function(e) {
        const items = lista.querySelectorAll('li');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % items.length;
            updateActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = (activeIndex - 1 + items.length) % items.length;
            updateActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && activeIndex < items.length) {
                items[activeIndex].click();
            } else if (items.length === 1) {
                items[0].click();
            }
        }
    });

    function updateActive(items) {
        items.forEach((item, idx) => {
            item.classList.toggle('active', idx === activeIndex);
            if (idx === activeIndex) item.scrollIntoView({block: "nearest"});
        });
    }
}

// Inicializar autocompletado
setupAutocomplete('modelo_buscar', 'modelo_buscar_id', 'lista_buscar');

// Consultar compatibilidades
document.getElementById('btn_consultar').addEventListener('click', function() {
    const modelo_id = document.getElementById('modelo_buscar_id').value;
    const tipo = document.getElementById('tipo_filtro').value;

    if (!modelo_id) {
        alert("Selecciona un modelo válido");
        return;
    }

    fetch(`buscar_compatibilidades.php?modelo_id=${modelo_id}&tipo=${tipo}`)
        .then(res => res.json())
        .then(data => {
            let html = '';
            if (!data.length) {
                html = '<p>No se encontraron compatibilidades.</p>';
            } else {
                html = '<table><tr><th>Tipo</th><th>Modelo Compatible</th><th>Notas</th></tr>';
                data.forEach(row => {
                    html += `<tr>
                        <td>${row.tipo}</td>
                        <td>${row.modelo}</td>
                        <td>${row.nota}</td>
                    </tr>`;
                });
                html += '</table>';
            }
            document.getElementById('resultados').innerHTML = html;
        });
});
</script>

</body>
</html>
