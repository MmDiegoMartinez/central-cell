<?php
session_start();

// Determinar el origen: 1 = Administrador/Validador, 2 = Vendedor/Tienda
$origen = isset($_SESSION['validador_id']) ? 1 : 2;
?>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../funciones.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modelo_id = intval($_POST['modelo_id'] ?? 0);
    $compatible_id = intval($_POST['compatible_id'] ?? 0);
    $tipo = trim($_POST['tipo'] ?? '');
    $nota = trim($_POST['nota'] ?? '') ?: null;

    try {
        if ($modelo_id && $compatible_id && $tipo) {
            if ($modelo_id === $compatible_id) {
                $mensaje = "⚠️ El modelo principal y compatible no pueden ser iguales.";
            } else {
                // Pasar el origen a la función
                insertarCompatibilidad($modelo_id, $compatible_id, $tipo, $nota, $origen);
                $mensaje = "✅ Compatibilidad registrada con éxito.";
            }
        } else {
            $mensaje = "⚠️ Debes seleccionar un modelo principal y compatible.";
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Compatibilidad</title>

  <link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
    <style>
        /* ---------------------------------------------------
           Estilos complementarios específicos de esta página.
           No se toca styles.css: todo lo que falta (autocomplete,
           radios de tipo, mensajes de aviso, toggle del sidebar)
           se agrega aquí reutilizando los tokens ya definidos.
           --------------------------------------------------- */
        .topbar{
            display:flex;align-items:center;justify-content:space-between;
            gap:var(--space-md);flex-wrap:wrap;
            margin:var(--space-xl) 0 var(--space-md);
        }
        .topbar h1{margin:0;}

        .callout-info{
            display:flex;gap:var(--space-md);align-items:flex-start;
            background:rgba(29,78,216,0.08);
            border-left:4px solid var(--primary);
            border-radius:0 var(--radius-lg) var(--radius-lg) 0;
            padding:var(--space-md) var(--space-lg);
            margin:0 0 var(--space-lg);
            font-size:14px;color:var(--on-surface);
        }

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
        .msg.warn{
            background:#fff3cd;
            border-left:4px solid #ffc107;
            color:#7a5b00;
        }
        .msg.err{
            background:var(--error-container);
            border-left:4px solid var(--error);
            color:var(--on-error-container);
        }

        .campo{margin-bottom:var(--space-lg);position:relative;}
        .campo label{
            display:block;font-size:14px;font-weight:700;
            color:var(--on-surface);margin-bottom:6px;
        }
        .campo label.req::after{content:" *";color:var(--error);}

        input[type="text"], textarea{
            width:100%;padding:12px 14px;
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            font-family:'Inter',sans-serif;font-size:15px;
            background:var(--surface-container-lowest);color:var(--on-surface);
        }
        input[type="text"]:focus, textarea:focus{
            outline:2px solid var(--primary);outline-offset:1px;
        }
        textarea{resize:vertical;}

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

        .tipo-opciones{display:flex;gap:var(--space-md);flex-wrap:wrap;}
        .tipo-opciones label{
            display:flex;align-items:center;gap:8px;
            padding:10px 16px;
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            background:var(--surface-container-low);
            font-size:14px;font-weight:500;color:var(--on-surface-variant);
            cursor:pointer;margin-bottom:0;
            transition:border-color .15s ease, background .15s ease;
        }
        .tipo-opciones label:hover{border-color:var(--primary);}
        .tipo-opciones label:has(input:checked){
            border-color:var(--primary);
            background:rgba(29,78,216,0.08);
            color:var(--on-surface);font-weight:600;
        }
        .tipo-opciones input{accent-color:var(--primary);width:16px;height:16px;}

        .acciones{margin-top:var(--space-xl);}
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
            <a class="sidebar-link" href="modelos.php">
                <span class="material-symbols-outlined">add_circle</span> Agregar Módelo
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
                <h2 class="text-headline-sm">Agregar Compatibilidades</h2>
            </div>
        </header>

        <div class="container">

            <div class="topbar">
                <h1 class="text-headline-md">Agregar Compatibilidad</h1>
            </div>

            <?php if ($origen === 2): ?>
                <div class="callout-info">
                    ℹ️ <strong>Modo Tienda:</strong> Las compatibilidades que agregues serán marcadas como "registradas en tienda".
                </div>
            <?php endif; ?>

            <?php if ($mensaje): ?>
                <?php
                    $tipoMsg = 'ok';
                    if (strpos($mensaje, '⚠️') === 0) { $tipoMsg = 'warn'; }
                    elseif (strpos($mensaje, '❌') === 0) { $tipoMsg = 'err'; }
                ?>
                <div class="msg <?= $tipoMsg ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off" class="lesson">
                <div class="lesson-body">

                    <!-- Modelo principal -->
                    <div class="campo">
                        <label class="req" for="modelo_principal">Modelo principal:</label>
                        <input type="text" id="modelo_principal" placeholder="Escribe modelo..." required>
                        <input type="hidden" name="modelo_id" id="modelo_id_hidden">
                        <ul id="lista_principal" class="autocomplete-list"></ul>
                    </div>

                    <!-- Modelo compatible -->
                    <div class="campo">
                        <label class="req" for="modelo_compatible">Modelo compatible:</label>
                        <input type="text" id="modelo_compatible" placeholder="Escribe modelo..." required>
                        <input type="hidden" name="compatible_id" id="compatible_id_hidden">
                        <ul id="lista_compatible" class="autocomplete-list"></ul>
                    </div>

                    <div class="campo">
                        <label class="req" for="tipo">Tipo:</label>
                        <div class="tipo-opciones">
                            <label>
                                <input type="radio" name="tipo" value="glass" required> Glass
                            </label>
                            <label>
                                <input type="radio" name="tipo" value="funda"> Funda
                            </label>
                            <label>
                                <input type="radio" name="tipo" value="camara"> Protector de Cámara
                            </label>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="nota">Nota (opcional):</label>
                        <textarea name="nota" id="nota" rows="3" cols="40"></textarea>
                    </div>

                    <div class="acciones">
                        <button type="submit" class="btn btn-primary">Guardar Compatibilidad</button>
                    </div>

                </div>
            </form>

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
                data.forEach((m, index) => {
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
            if (idx === activeIndex) {
                item.classList.add('active');
                item.scrollIntoView({block: "nearest"});
            } else {
                item.classList.remove('active');
            }
        });
    }
}

// Inicializar ambos campos
setupAutocomplete('modelo_principal', 'modelo_id_hidden', 'lista_principal');
setupAutocomplete('modelo_compatible', 'compatible_id_hidden', 'lista_compatible');

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