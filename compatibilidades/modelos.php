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
$mensaje = "";
$tipo_mensaje = ""; // success, error, warning

// Insertar, actualizar o eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    $marca = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $id = intval($_POST['id'] ?? 0);

    try {
        if ($accion === 'insertar') {
            // VALIDACIÓN: Verificar si la marca existe en la BD
            $marca_existe = verificarMarcaExiste($marca);
            
            if (!$marca_existe && $es_admin === 2) {
                // Usuario normal intentando agregar marca inexistente
                $mensaje = "⚠️ La marca '$marca' no está registrada en la base de datos. Verifique que esté correctamente escrita o, si considera que se trata de una marca nueva, contacte al administrador para que sea agregada.";
                $tipo_mensaje = "warning";
            } else {
                // Marca existe O es administrador (puede crear nueva marca)
                insertarModelo($marca, $modelo);
                $mensaje = "✅ Modelo agregado con éxito.";
                $tipo_mensaje = "success";
            }
            
        } elseif ($accion === 'actualizar') {
            actualizarModelo($id, $marca, $modelo);
            $mensaje = "✅ Modelo actualizado con éxito.";
            $tipo_mensaje = "success";
            
        } elseif ($accion === 'eliminar') {
            // Solo permitir eliminar si es administrador
            if ($es_admin === 1) {
                eliminarModelo($id);
                $mensaje = "✅ Modelo eliminado con éxito.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "❌ No tienes permisos para eliminar modelos.";
                $tipo_mensaje = "error";
            }
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

$modelos = obtenerModelos();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Modelos</title>
    <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
    <style>
        /* ---------------------------------------------------
           Estilos complementarios específicos de esta página.
           No se toca styles.css: todo lo que falta (mensajes,
           autocomplete, aviso de modo, tabla, toggle del
           sidebar) se agrega aquí reutilizando los tokens ya
           definidos.
           --------------------------------------------------- */
        .topbar{
            display:flex;align-items:center;justify-content:space-between;
            gap:var(--space-md);flex-wrap:wrap;
            margin:var(--space-xl) 0 var(--space-md);
        }
        .topbar h1{margin:0;}

        .mensaje{
            display:flex;gap:var(--space-md);align-items:flex-start;
            border-radius:0 var(--radius-lg) var(--radius-lg) 0;
            padding:var(--space-md) var(--space-lg);
            margin:0 0 var(--space-lg);font-size:14px;font-weight:600;
        }
        .mensaje.success{
            background:rgba(109,245,225,0.18);
            border-left:4px solid var(--secondary);
            color:var(--on-secondary-container);
        }
        .mensaje.warning{
            background:#fff3cd;
            border-left:4px solid #ffc107;
            color:#7a5b00;
        }
        .mensaje.error{
            background:var(--error-container);
            border-left:4px solid var(--error);
            color:var(--on-error-container);
        }

        .info-modo{
            display:flex;gap:var(--space-md);align-items:flex-start;
            background:rgba(29,78,216,0.08);
            border-left:4px solid var(--primary);
            border-radius:0 var(--radius-lg) var(--radius-lg) 0;
            padding:var(--space-md) var(--space-lg);
            margin:0 0 var(--space-lg);
            font-size:14px;color:var(--on-surface);
        }
        .info-modo.admin{
            background:rgba(109,245,225,0.18);
            border-left-color:var(--secondary);
        }

        .section-title{
            font-size:14px;font-weight:700;letter-spacing:0.03em;text-transform:uppercase;
            color:var(--outline);margin:0 0 var(--space-md);
        }

        .campo{margin-bottom:var(--space-lg);position:relative;}
        .campo label{
            display:block;font-size:14px;font-weight:700;
            color:var(--on-surface);margin-bottom:6px;
        }
        input[type="text"]{
            width:100%;padding:12px 14px;
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            font-family:'Inter',sans-serif;font-size:15px;
            background:var(--surface-container-lowest);color:var(--on-surface);
        }
        input[type="text"]:focus{outline:2px solid var(--primary);outline-offset:1px;}

        ul.autocomplete-list{
            list-style:none;margin:4px 0 0;padding:0;
            position:absolute;left:0;right:0;z-index:60;
            background:var(--surface-container-lowest);
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            box-shadow:0 4px 12px rgba(17,28,45,0.12);
            max-height:200px;overflow-y:auto;
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

        .table-responsive{overflow-x:auto;-webkit-overflow-scrolling:touch;}
        table.data-table{width:100%;border-collapse:collapse;font-size:14px;}
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

        .sin-permisos{color:var(--outline);font-size:13px;}
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
            <a class="sidebar-link" href="ingresar.php">
                <span class="material-symbols-outlined">arrow_back</span> Atrás
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
                <h2 class="text-headline-sm">Agregar modelos</h2>
            </div>
        </header>

        <div class="container">

            <div class="topbar">
                <h1 class="text-headline-md">CRUD de Modelos</h1>
            </div>

            <?php if ($es_admin === 2): ?>
                <div class="info-modo">
                    ℹ️ <strong>Modo Usuario:</strong> Solo puedes agregar modelos de marcas existentes.
                    Para agregar una marca nueva, contacta al administrador.
                </div>
            <?php else: ?>
                <div class="info-modo admin">
                    👨‍💼 <strong>Modo Administrador:</strong> Tienes todos los permisos.
                </div>
            <?php endif; ?>

            <?php if ($mensaje): ?>
                <div class="mensaje <?= $tipo_mensaje ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <div class="lesson">
                <div class="lesson-body">
                    <p class="section-title">Agregar Modelo</p>
                    <form method="post" id="formAgregar">
                        <input type="hidden" name="accion" value="insertar">
                        <input type="hidden" id="marca_valida" value="0">

                        <div class="campo">
                            <label for="marca">Marca:</label>
                            <input type="text"
                                   name="marca"
                                   id="marca"
                                   autocomplete="off"
                                   placeholder="Escribe la marca..."
                                   required>
                            <ul id="lista_marcas" class="autocomplete-list"></ul>
                        </div>

                        <div class="campo">
                            <label for="modelo">Modelo:</label>
                            <input type="text" name="modelo" id="modelo" required>
                        </div>

                        <div class="acciones">
                            <button type="submit" class="btn btn-primary">Agregar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="lesson" style="margin-top:var(--space-xl);">
                <div class="lesson-body">
                    <p class="section-title">Lista de Modelos</p>
                    <div class="table-responsive">
                        <table class="data-table">
                            <tr>
                                <th>ID</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Acciones</th>
                            </tr>
                            <?php foreach ($modelos as $m): ?>
                            <tr>
                                <td><?= $m['id'] ?></td>
                                <td><?= htmlspecialchars($m['marca']) ?></td>
                                <td><?= htmlspecialchars($m['modelo']) ?></td>
                                <td>
                                    <?php if ($es_admin === 1): ?>
                                        <form style="display:inline;" method="post">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn btn-outline" onclick="return confirm('¿Eliminar este modelo?')">Eliminar</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="sin-permisos">Sin permisos</span>
                                    <?php endif; ?>
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
const esAdmin = <?= $es_admin ?>;
const inputMarca = document.getElementById('marca');
const listaMarcas = document.getElementById('lista_marcas');
const marcaValida = document.getElementById('marca_valida');
const formAgregar = document.getElementById('formAgregar');
let activeIndex = -1;

// Autocompletado de marcas
inputMarca.addEventListener('input', function() {
    const q = this.value.trim();
    marcaValida.value = '0';
    activeIndex = -1;
    
    if (!q) {
        listaMarcas.innerHTML = '';
        return;
    }
    
    fetch(`buscar_marcas.php?q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(data => {
            listaMarcas.innerHTML = '';
            data.forEach((marca, index) => {
                const li = document.createElement('li');
                li.textContent = marca;
                li.addEventListener('click', () => {
                    inputMarca.value = marca;
                    marcaValida.value = '1';
                    listaMarcas.innerHTML = '';
                });
                listaMarcas.appendChild(li);
            });
        });
});

// Navegación con teclado
inputMarca.addEventListener('keydown', function(e) {
    const items = listaMarcas.querySelectorAll('li');
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
        if (idx === activeIndex) {
            item.classList.add('active');
            item.scrollIntoView({block: "nearest"});
        } else {
            item.classList.remove('active');
        }
    });
}

// Validación antes de enviar el formulario
formAgregar.addEventListener('submit', function(e) {
    // Si no es admin y la marca no está validada, prevenir envío
    if (esAdmin === 2 && marcaValida.value === '0') {
        e.preventDefault();
        alert('⚠️ Debes seleccionar una marca de la lista. Si la marca no aparece, contacta al administrador.');
        return false;
    }
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