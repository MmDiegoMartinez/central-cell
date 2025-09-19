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
// Insertar / Eliminar compatibilidad
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    $tipo = trim($_POST['tipo'] ?? '');
   $nota = trim($_POST['nota'] ?? '');

    $modelo_principal = trim($_POST['modelo_principal'] ?? '');
    $modelo_compatible = trim($_POST['modelo_compatible'] ?? '');

    try {
        $m1 = $modelo_principal ? obtenerModeloPorNombre($modelo_principal) : null;
        $m2 = $modelo_compatible ? obtenerModeloPorNombre($modelo_compatible) : null;

        if ($accion === 'insertar') {
            if (!$m1 || !$m2) throw new Exception("El modelo principal o compatible no existe en la base de datos.");
            insertarCompatibilidad($m1['id'], $m2['id'], $tipo, $nota);
            $mensaje = "✅ Compatibilidad registrada con éxito.";
        } elseif ($accion === 'eliminar') {
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
<title>CRUD de Compatibilidades</title>
<link rel="stylesheet" href="estilos.css">


</head>
<body>
    <header>
    <div class="logo">CRUD de Compatibilidades</div>
    <nav>
        <ul>
            <li><a href="index.php">Inicio 🏠</a></li>
            <li><a href="modelos.php">Agregar Modelo🆕</a></li>
            
        </ul>
    </nav>
</header>



<?php if ($mensaje): ?>
<p><?= htmlspecialchars($mensaje) ?></p>
<?php endif; ?>

<h2>Filtro</h2>
Tipo:
<select id="filtroTipo">
    <option value="">Todos</option>
    <option value="glass">Glass</option>
    <option value="funda">Funda</option>
</select>
<br><br>

Modelo (principal o compatible):
<input type="text" id="filtroModelo" placeholder="Escribe modelo...">
<input type="hidden" id="filtroModeloId">
<ul id="listaModelos" class="autocomplete-list"></ul>
<br><br>

<h2>Agregar Compatibilidad</h2>
<form method="post">
    <input type="hidden" name="accion" value="insertar">
    
    Modelo principal  :
    <input type="text" name="modelo_principal" list="modelos" required>
    
    <br>Modelo compatible:
    <input type="text" name="modelo_compatible" list="modelos" required>

    <br>Tipo:
    <select name="tipo" required>
        <option value="glass">Glass</option>
        <option value="funda">Funda</option>
    </select>

    <br>Nota (opcional):
    <input type="text" name="nota">

    <button type="submit">Guardar</button>
</form>

<datalist id="modelos">
<?php foreach ($modelos as $m): ?>
    <option value="<?= htmlspecialchars($m['marca'].' '.$m['modelo']) ?>">
<?php endforeach; ?>
</datalist>

<h2>Lista de Compatibilidades</h2>
<table border="1" cellpadding="5" id="tablaCompatibilidades">
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
                <button type="submit" style="background:none;border:none;font-size:1.2em;" title="Eliminar" onclick="return confirm('Eliminar esta compatibilidad?')">🗑️</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

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
    <?php // Pasamos todos los modelos a JS ?>
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
</script>

</body>
</html>
