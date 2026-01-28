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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
    <meta charset="UTF-8">
    <title>CRUD Modelos</title>
    <link rel="stylesheet" href="estilos.css?v=<?php echo time(); ?>">
    <style>
        .mensaje {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .mensaje.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .autocomplete-list {
            list-style: none;
            padding: 0;
            margin: 5px 0;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            background: white;
        }
        .autocomplete-list li {
            padding: 10px;
            cursor: pointer;
        }
        .autocomplete-list li:hover,
        .autocomplete-list li.active {
            background: #007bff;
            color: white;
        }
        .info-modo {
            padding: 10px;
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            margin-bottom: 20px;
        }
    </style>
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
                 Agregar modelos
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
             <li><a href="consultar.php">Consultar Compatibilidades 🔍</a></li>
                <li><a href="ingresar.php">Atrás 🔙</a></li>
    </nav>
</header>

    <h1>CRUD de Modelos</h1>

    <?php if ($es_admin === 2): ?>
        <div class="info-modo">
            ℹ️ <strong>Modo Usuario:</strong> Solo puedes agregar modelos de marcas existentes. 
            Para agregar una marca nueva, contacta al administrador.
        </div>
    <?php else: ?>
        <div class="info-modo" style="background: #e8f5e9; border-left-color: #4CAF50;">
            👨‍💼 <strong>Modo Administrador:</strong> Tienes todos los permisos.
        </div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <div class="mensaje <?= $tipo_mensaje ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <h2>Agregar Modelo</h2>
    <form method="post" id="formAgregar">
        <input type="hidden" name="accion" value="insertar">
        <input type="hidden" id="marca_valida" value="0">
        
        <label for="marca">Marca:</label>
        <input type="text" 
               name="marca" 
               id="marca" 
               autocomplete="off" 
               placeholder="Escribe la marca..." 
               required>
        <ul id="lista_marcas" class="autocomplete-list"></ul>
        <br><br>
        
        <label for="modelo">Modelo:</label>
        <input type="text" name="modelo" id="modelo" required>
        <br><br>
        
        <button type="submit">Agregar</button>
    </form>

    <h2>Lista de Modelos</h2>
    <table border="1" cellpadding="5">
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
                        <button type="submit" onclick="return confirm('¿Eliminar este modelo?')">Eliminar</button>
                    </form>
                <?php else: ?>
                    <span style="color: #999;">Sin permisos</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

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
</script>
</body>
</html>