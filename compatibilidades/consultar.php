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
    <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
    <style>
        
        .badge{
            display:inline-flex;align-items:center;gap:6px;
            background:var(--secondary-container);
            color:var(--on-secondary-container);
            padding:6px 14px;border-radius:var(--radius-full);
            font-size:12px;font-weight:700;letter-spacing:0.03em;
            white-space:nowrap;
        }
        .topbar{
            display:flex;align-items:center;justify-content:space-between;
            gap:var(--space-md);flex-wrap:wrap;
            margin:var(--space-xl) 0 var(--space-md);
        }
        .topbar h1{margin:0;}

        .campo{margin-bottom:var(--space-lg);}
        .campo label{
            display:block;font-size:14px;font-weight:700;
            color:var(--on-surface);margin-bottom:6px;
        }
        .campo-inline{
            display:flex;gap:var(--space-md);flex-wrap:wrap;align-items:flex-end;
        }
        .campo-inline .campo{flex:1;min-width:220px;margin-bottom:0;}

        input[type="text"], select{
            width:100%;padding:12px 14px;
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            font-family:'Inter',sans-serif;font-size:15px;
            background:var(--surface-container-lowest);color:var(--on-surface);
        }
        input[type="text"]:focus, select:focus{
            outline:2px solid var(--primary);outline-offset:1px;
        }

        .autocomplete-wrap{position:relative;}
        ul.autocomplete-list{
            list-style:none;margin:4px 0 0;padding:0;
            position:absolute;left:0;right:0;z-index:60;
            background:var(--surface-container-lowest);
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            box-shadow:0 4px 12px rgba(17,28,45,0.12);
            max-height:240px;overflow-y:auto;
        }
        ul.autocomplete-list:empty{display:none;}
        ul.autocomplete-list li{
            padding:10px 14px;font-size:14px;color:var(--on-surface-variant);
            cursor:pointer;border-bottom:1px solid var(--outline-variant);
        }
        ul.autocomplete-list li:last-child{border-bottom:none;}
        ul.autocomplete-list li:hover,
        ul.autocomplete-list li.active{
            background:var(--surface-container-high);color:var(--on-surface);
        }

        .acciones{margin-top:var(--space-lg);}

        #resultados{margin-top:var(--space-xl);}
        #resultados table{
            width:100%;border-collapse:collapse;font-size:14px;
        }
        #resultados table th{
            text-align:left;padding:12px var(--space-md);
            background:var(--surface-container-high);
            color:var(--on-surface-variant);
            font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;
            border-bottom:1px solid var(--outline-variant);
        }
        #resultados table th:first-child{border-top-left-radius:var(--radius-lg);}
        #resultados table th:last-child{border-top-right-radius:var(--radius-lg);}
        #resultados table td{
            padding:12px var(--space-md);
            border-bottom:1px solid var(--outline-variant);
            color:var(--on-surface);
        }
        #resultados table tr:hover td{background:var(--surface-container-low);}
        #resultados p{color:var(--on-surface-variant);font-size:14px;}
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-head">
            <div>
                <h2 class="sidebar-brand text-headline-sm">CentralCell</h2>
                <p class="sidebar-sub text-label-sm">Compatibilidades</p>
            </div>
            <button class="sidebar-close material-symbols-outlined" id="sidebarClose" type="button">close</button>
        </div>
        <nav class="sidebar-nav">
            <a class="sidebar-link" href="../garantias/vendedor/garantias.php">
                <span class="material-symbols-outlined">home</span> Home
            </a>
            <a class="sidebar-link" href="tabla_compatibilidades.php">
                <span class="material-symbols-outlined">download</span> Descargar Compatibilidades
            </a>
            <a class="sidebar-link" href="ingresar.php">
                <span class="material-symbols-outlined">edit_note</span> Ingresar Compatibilidades
            </a>
            <?php if ($es_admin === 1): ?>
            <a class="sidebar-link" href="eliminar.php">
                <span class="material-symbols-outlined">delete</span> Eliminar Compatibilidades
            </a>
            <a class="sidebar-link" href="../garantias/validador/validador.php">
                <span class="material-symbols-outlined">verified_user</span> Validar Garantías
            </a>
            <a class="sidebar-link" href="../kpis/modulos.html">
                <span class="material-symbols-outlined">build</span> Panel de Herramientas
            </a>
            <?php endif; ?>
        </nav>
    </aside>

    <div class="main">
        <header class="topheader">
            <div class="topheader-left">
                <button class="menu-toggle material-symbols-outlined" id="menuToggle" type="button">menu</button>
                <span class="logo-circle" style="display:inline-flex;width:32px;height:32px;background:#fff;border-radius:50%;justify-content:center;align-items:center;overflow:hidden;flex:0 0 auto;">
                    <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" style="width:24px;height:24px;object-fit:contain;" />
                </span>
                <h2 class="text-headline-sm">Compatibilidades</h2>
            </div>
        </header>

        <div class="container">

            <div class="topbar">
                <h1 class="text-headline-md">Consultar Compatibilidades</h1>
                <?php if ($es_admin === 1): ?>
                    <span class="badge">Sesión de validador</span>
                <?php endif; ?>
            </div>

            <div class="lesson">
                <div class="lesson-body">
                    <div class="campo autocomplete-wrap">
                        <label for="modelo_buscar">Escribe el modelo:</label>
                        <input type="text" id="modelo_buscar" placeholder="Ej: IPHONE 13">
                        <input type="hidden" id="modelo_buscar_id">
                        <ul id="lista_buscar" class="autocomplete-list"></ul>
                    </div>

                    <div class="campo-inline">
                        <div class="campo">
                            <label for="tipo_filtro">Tipo:</label>
                            <select id="tipo_filtro">
                                <option value="glass">Glass</option>
                                <option value="funda">Funda</option>
                                <option value="camara">Protector de Cámara</option>
                            </select>
                        </div>
                        <div class="acciones">
                            <button id="btn_consultar" class="btn btn-primary">Consultar</button>
                        </div>
                    </div>

                    <div id="resultados"></div>
                </div>
            </div>

        </div>
    </div>

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

// Toggle del sidebar en móvil
document.addEventListener('DOMContentLoaded', function(){
    var menuToggle = document.getElementById('menuToggle');
    var sidebarClose = document.getElementById('sidebarClose');
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    function openSidebar(){
        sidebar.classList.add('open');
        overlay.classList.add('show');
    }
    function closeSidebar(){
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }

    if (menuToggle) menuToggle.addEventListener('click', openSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);
});
</script>

</body>
</html>