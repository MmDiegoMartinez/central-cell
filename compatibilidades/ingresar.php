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
                insertarCompatibilidad($modelo_id, $compatible_id, $tipo, $nota);
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
    <title>Agregar Compatibilidad</title>
    
    <link rel="stylesheet" href="estilos.css">


</head>
<body>
    <header>
    <div class="logo">Agregar Compatibiliedades</div>
    <nav>
        <ul>
            <li><a href="index.php">Inicio 🏠</a></li>
            
        </ul>
    </nav>
</header>

<h1>Agregar Compatibilidad</h1>

<?php if ($mensaje): ?>
    <p><?= htmlspecialchars($mensaje) ?></p>
<?php endif; ?>

<form method="post" autocomplete="off">
    <!-- Modelo principal -->
    <label for="modelo_principal">Modelo principal:</label>
    <input type="text" id="modelo_principal" placeholder="Escribe modelo..." required>
    <input type="hidden" name="modelo_id" id="modelo_id_hidden">
    <ul id="lista_principal" class="autocomplete-list"></ul>
    <br><br>

    <!-- Modelo compatible -->
    <label for="modelo_compatible">Modelo compatible:</label>
    <input type="text" id="modelo_compatible" placeholder="Escribe modelo..." required>
    <input type="hidden" name="compatible_id" id="compatible_id_hidden">
    <ul id="lista_compatible" class="autocomplete-list"></ul>
    <br><br>

     <label for="tipo">Tipo:</label>
<label>
  <input type="radio" name="tipo" value="glass"> Glass
</label>
<label>
  <input type="radio" name="tipo" value="funda"> Funda
</label>

    <label for="nota">Nota (opcional):</label>
    <textarea name="nota" id="nota" rows="3" cols="40"></textarea>
    <br>

    <button type="submit">Guardar Compatibilidad</button>
</form>

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
</script>
</body>
</html>
