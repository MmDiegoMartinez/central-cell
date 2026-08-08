<?php
session_start();

if (!isset($_SESSION['validador_id'])) {
    header("Location: ../validador/loginvalidador.php");
    exit;
}
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../funciones.php';
$mensaje = "";

// ---------------------------
// Eliminar compatibilidad
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    try {
        if ($accion === 'eliminar') {
            $id = intval($_POST['id']);
            eliminarCompatibilidad($id);
            $mensaje = "✅ Compatibilidad eliminada con éxito.";
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}

// ---------------------------
// Obtener datos
// ---------------------------
$compatibilidades = obtenerTodasCompatibilidades();
$modelos = obtenerModelos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Eliminar Compatibilidades</title>

<link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
<style>
    /* ---------------------------------------------------
       Estilos complementarios específicos de esta página.
       No se toca styles.css: todo lo que falta (filtros,
       autocomplete, tabla, mensajes, toggle del sidebar)
       se agrega aquí reutilizando los tokens ya definidos.
       --------------------------------------------------- */
    .topbar{
        display:flex;align-items:center;justify-content:space-between;
        gap:var(--space-md);flex-wrap:wrap;
        margin:var(--space-xl) 0 var(--space-md);
    }
    .topbar h1{margin:0;}

    .msg{
        display:flex;gap:var(--space-md);align-items:flex-start;
        border-radius:0 var(--radius-lg) var(--radius-lg) 0;
        padding:var(--space-md) var(--space-lg);
        margin:0 0 var(--space-lg);font-size:14px;font-weight:600;
    }
    .msg.ok{
        background:rgba(109,245,225,0.18);
        border-left:4px solid var(--secondary);
        color:var(--on-secondary-container);
    }
    .msg.err{
        background:var(--error-container);
        border-left:4px solid var(--error);
        color:var(--on-error-container);
    }

    .section-title{
        font-size:14px;font-weight:700;letter-spacing:0.03em;text-transform:uppercase;
        color:var(--outline);margin:0 0 var(--space-md);
    }

    .filtros-card{margin-bottom:var(--space-xl);}
    .filtros-row{display:flex;gap:var(--space-md);flex-wrap:wrap;align-items:flex-end;}
    .campo{flex:1;min-width:220px;margin-bottom:0;position:relative;}
    .campo label{
        display:block;font-size:14px;font-weight:700;
        color:var(--on-surface);margin-bottom:6px;
    }

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

    .table-responsive{overflow-x:auto;-webkit-overflow-scrolling:touch;}
    table.data-table{
        width:100%;border-collapse:collapse;font-size:14px;
    }
    table.data-table thead th{
        text-align:left;padding:12px var(--space-md);
        background:var(--surface-container-high);
        color:var(--on-surface-variant);
        font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;
        border-bottom:1px solid var(--outline-variant);
        white-space:nowrap;
    }
    table.data-table thead th:first-child{border-top-left-radius:var(--radius-lg);}
    table.data-table thead th:last-child{border-top-right-radius:var(--radius-lg);}
    table.data-table tbody td{
        padding:12px var(--space-md);
        border-bottom:1px solid var(--outline-variant);
        color:var(--on-surface);vertical-align:middle;
    }
    table.data-table tbody tr:hover{background:var(--surface-container-low);}
    table.data-table tbody tr:last-child td{border-bottom:none;}

    .btn-delete{
        background:none;border:none;font-size:1.2em;cursor:pointer;
        padding:4px 8px;border-radius:var(--radius-lg);
        transition:background .15s ease;
    }
    .btn-delete:hover{background:var(--error-container);}
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
            <a class="sidebar-link" href="consultar.php">
                <span class="material-symbols-outlined">search</span> Consultar Compatibilidades
            </a>
        </nav>
    </aside>

    <div class="main">
        <header class="topheader">
            <div class="topheader-left">
                <button class="menu-toggle material-symbols-outlined" id="menuToggle" type="button">menu</button>
                <span class="logo-circle" style="display:inline-flex;width:32px;height:32px;background:#fff;border-radius:50%;justify-content:center;align-items:center;overflow:hidden;flex:0 0 auto;">
                    <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" style="width:24px;height:24px;object-fit:contain;" />
                </span>
                <h2 class="text-headline-sm">Eliminar Compatibilidades</h2>
            </div>
        </header>

        <div class="container">

            <div class="topbar">
                <h1 class="text-headline-md">Eliminar Compatibilidades</h1>
            </div>

            <?php if ($mensaje): ?>
                <?php $tipoMsg = (strpos($mensaje, '❌') === 0) ? 'err' : 'ok'; ?>
                <div class="msg <?= $tipoMsg ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <div class="lesson filtros-card">
                <div class="lesson-body">
                    <p class="section-title">Filtro</p>
                    <div class="filtros-row">
                        <div class="campo">
                            <label for="filtroTipo">Tipo:</label>
                            <select id="filtroTipo">
                                <option value="">Todos</option>
                                <option value="glass">Glass</option>
                                <option value="funda">Funda</option>
                                <option value="camara">Protector de Cámara</option>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="filtroModelo">Modelo (principal o compatible):</label>
                            <input type="text" id="filtroModelo" placeholder="Escribe modelo...">
                            <input type="hidden" id="filtroModeloId">
                            <ul id="listaModelos" class="autocomplete-list"></ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lesson">
                <div class="lesson-body">
                    <p class="section-title">Lista de Compatibilidades</p>
                    <div class="table-responsive">
                        <table class="data-table" id="tablaCompatibilidades">
                            <tr>
                                <th>Tipo</th>
                                <th>Modelo principal</th>
                                <th>Modelo compatible</th>
                                <th>Nota</th>
                                <th>Acciones</th>
                            </tr>
                            <?php foreach ($compatibilidades as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['tipo']) ?></td>
                                <td><?= htmlspecialchars($c['marca1'].' '.$c['modelo1']) ?></td>
                                <td><?= htmlspecialchars($c['marca2'].' '.$c['modelo2']) ?></td>
                                <td><?= htmlspecialchars($c['nota'] ?? '') ?></td>
                                <td>
                                    <form style="display:inline;" method="post">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn-delete" title="Eliminar" onclick="return confirm('Eliminar esta compatibilidad?')">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

<script>
// Autocomplete de modelos para filtro
const inputModelo = document.getElementById('filtroModelo');
const hiddenModelo = document.getElementById('filtroModeloId');
const lista = document.getElementById('listaModelos');
let activeIndex = -1;

inputModelo.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    hiddenModelo.value = '';
    activeIndex = -1;
    lista.innerHTML = '';
    if(!q) return;
    const modelosJS = <?= json_encode(array_map(function($m){ return $m['marca'].' '.$m['modelo']; }, $modelos)); ?>;
    modelosJS.forEach((m) => {
        if(m.toLowerCase().includes(q)){
            const li = document.createElement('li');
            li.textContent = m;
            li.addEventListener('click', () => {
                inputModelo.value = m;
                hiddenModelo.value = m;
                lista.innerHTML = '';
                filtrarTabla();
            });
            lista.appendChild(li);
        }
    });
});

inputModelo.addEventListener('keydown', function(e){
    const items = lista.querySelectorAll('li');
    if(!items.length) return;

    if(e.key === 'ArrowDown'){ e.preventDefault(); activeIndex=(activeIndex+1)%items.length; updateActive(items);}
    else if(e.key==='ArrowUp'){ e.preventDefault(); activeIndex=(activeIndex-1+items.length)%items.length; updateActive(items);}
    else if(e.key==='Enter'){ e.preventDefault(); if(activeIndex>=0) items[activeIndex].click();}
});

function updateActive(items){ items.forEach((item,idx)=>item.classList.toggle('active',idx===activeIndex)); if(activeIndex>=0) items[activeIndex].scrollIntoView({block:'nearest'}); }

// Filtrar tabla
const filtroTipo = document.getElementById('filtroTipo');
filtroTipo.addEventListener('change', filtrarTabla);
inputModelo.addEventListener('input', filtrarTabla);

function filtrarTabla(){
    const tipo = filtroTipo.value.toLowerCase();
    const texto = inputModelo.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaCompatibilidades tr');
    filas.forEach((fila,index)=>{
        if(index===0) return;
        const celdas = fila.querySelectorAll('td');
        let mostrar = true;
        if(tipo && celdas[0].textContent.toLowerCase()!==tipo) mostrar=false;
        if(texto){
            const m1 = celdas[1].textContent.toLowerCase();
            const m2 = celdas[2].textContent.toLowerCase();
            if(!m1.includes(texto) && !m2.includes(texto)) mostrar=false;
        }
        fila.style.display = mostrar?'':'none';
    });
}

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