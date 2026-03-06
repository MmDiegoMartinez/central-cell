<?php 
include_once '../../funciones.php';  
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
$mensaje = "";  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {     
    try {         
        guardarGarantia($_POST);         
        $mensaje = "✅ Garantía registrada correctamente.";     
    } catch (Exception $e) {         
        $mensaje = "❌ Error al guardar: " . $e->getMessage();     
    } 
} 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Garantía</title>
    
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../../css.css?v=<?php echo time(); ?>">


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
        function validarPlows(input) {
            const valor = input.value.toUpperCase();
            input.value = valor;

            const regex = /^PLOWS\d{6}$/;
            if (!regex.test(valor)) {
                input.value = '';
                new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg').play();
            }
        }

        $(function() {
    $("#apasionado").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "buscar_colaborador.php",
                dataType: "json",
                data: { term: request.term },
                success: function(data) {
                    response(data);
                },
                error: function() {
                    response([]);
                }
            });
        },
        minLength: 1,
        delay: 300,
        autoFocus: true,  // <-- Esto hace que la primera opción esté seleccionada por defecto
        focus: function(event, ui) {
            event.preventDefault(); // Para que no sobrescriba el input al navegar con teclas
            $("#preview-apasionado").text("Seleccionando: " + ui.item.label);
        },
        select: function(event, ui) {
            event.preventDefault();
            $("#apasionado").val(ui.item.label);
            $("#apasionado_id").val(ui.item.value);
            $("#preview-apasionado").text("Seleccionado: " + ui.item.label);
        },
        open: function() {
            // Esto es opcional si quieres asegurar que la primera opción se resalte visualmente
            const menu = $(this).autocomplete("widget");
            menu.find("li:first .ui-menu-item-wrapper").addClass("ui-state-active");
        }
    });
});



    </script>
</head>
<body><!--
    <div class="overlay navidad-overlay" id="navidad"></div>
    <div class="overlay sanvalentin-overlay" id="sanvalentin"></div>
    <div class="overlay muertos-overlay" id="muertos"></div>
    <div class="overlay independencia-overlay" id="independencia"></div>-->
   <nav>
    <h1 id="nombre">Innovación Móvil</h1>
    
    <!-- Checkbox PRIMERO (importante para el CSS) -->
    <input type="checkbox" id="check">
    
    <!-- Menú Hamburguesa -->
    <label class="bar" for="check">
        <span class="top"></span>
        <span class="middle"></span>
        <span class="bottom"></span>
    </label>
    
    <ul id="menu">
        <li>
            <a href="garantias.php" style="display: flex; align-items: center; gap: 12px;">
                <span style="display: inline-flex; width: 40px; height: 40px; background: white; border-radius: 50%; justify-content: center; align-items: center; overflow: visible; position: relative;">
                    <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" alt="Logo Central Cell" style="width: 30px; height: 30px; object-fit: contain; position: relative; top: 0; left: 0;" />
                </span>
                Home
            </a>
        </li>

        <li>
            <a href="metas.php" style="display: flex; align-items: center; gap: 12px;">
                <img src="../../recursos/img/Metas.png" alt="Metas" style="width: 40px; height: 40px; object-fit: contain; position: relative; top: 0; left: 0;" />
                Metas IM
            </a>
        </li>

        <li>
            <a href="../../bitacora/Vendedores/index.php" style="display: flex; align-items: center; gap: 12px;">
                <img src="../../recursos/img/productosNegados.png" alt="Productos Negados" style="width: 40px; height: 40px; object-fit: contain; position: relative; top: 0; left: 0;" />
                Productos negados
            </a>
        </li>

        <li>
            <a href="../../compatibilidades/consultar.php" style="display: flex; align-items: center; gap: 12px;">
                <img src="../../recursos/img/compatibilidades.png" alt="Compatibilidades" style="width: 40px; height: 40px; object-fit: contain; position: relative; top: 0; left: 0;" />
                Compatibilidades
            </a>
        </li>

        <li>
            <a href="../../Evaluacion/mermas.php" style="display: flex; align-items: center; gap: 12px;">
                <img src="../../recursos/img/tuto.png" alt="Tutorial" style="width: 40px; height: 40px; object-fit: contain; position: relative; top: 0; left: 0;" />
                Cómo Enviar
            </a>
        </li>

        <li>
            <a href="tabla.php" style="display: flex; align-items: center; gap: 12px;">
                <img src="../../recursos/img/merma.png" alt="Mermas" style="width: 40px; height: 40px; object-fit: contain; position: relative; top: 0; left: 0;" />
                Garantías / Mermas
            </a>
        </li>
    </ul>
</nav>

    <div class="contenedor">
        <div class="formulario">
            <h1>Garantías y Mermas</h1><br>

            <?php if ($mensaje): ?>
                <p><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?><br>

            <form method="POST">
                <label for="plows">PLOWS:</label>
                <input type="text" name="plows" id="plows" maxlength="11" onblur="validarPlows(this)" required><br><br>

                <label for="tipo">Tipo de producto:</label>
                <select name="tipo" required>
                    <option value="">Seleccione</option>
                    <option>Caratula Case</option>
                    <option>Hidrogel</option>
                    <option>Kits de Carga</option>
                    <option>Protection Pro</option>
                    <option>Glass Full</option>
                    <option>Glass Mobo</option>
                    <option>Cable USB</option>
                    <option>Funda Tablet</option>
                    <option>Electronico</option>
                    <option>Adaptador de carga</option>
                    <option>Otros</option>
                </select><br><br>

                <label for="causa">Causa:</label>
                <select name="causa" required>
                    <option value="">Seleccione</option>
                    <option>Cambio de producto (Garantia)</option>
                    <option>Defecto de fabrica</option>
                    <option>Mala instalacion de producto (garantia)</option>
                    <option>Error (Nuevo Ingreso)</option>
                    <option>Se encontro roto o descompuesto</option>
                    <option>Mala instalacion del producto (merma)</option>
                    <option>Fallo de la maquina</option>
                </select><br><br>

                <label for="piezas">Piezas:</label>
                <input type="number" name="piezas" min="1" required><br><br>

               <?php
                $sucursales = obtenerSucursales();
                ?>
                <label for="sucursal">Sucursal:</label>
                <select name="sucursal" required>
                    <option value="">Seleccione una sucursal</option>

                    <?php if (!empty($sucursales)): ?>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?= htmlspecialchars($sucursal['id']) ?>">
                                <?= htmlspecialchars($sucursal['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option disabled>No hay sucursales disponibles</option>
                    <?php endif; ?>
                </select><br><br>

                <input type="hidden" id="apasionado_id" name="apasionado_id">

                <label for="apasionado">Nombre del colaborador:</label>
                <input type="text" name="apasionado" id="apasionado" autocomplete="off" required>
                <div id="preview-apasionado" style="margin-top:5px; color: #555; font-size: 0.9em;"></div><br>

                <label for="fecha">Fecha:</label>
                <input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required><br><br>

                <label for="anotaciones_vendedor">Anotaciones (opcional):</label><br>
                <textarea name="anotaciones_vendedor" rows="4" cols="50" maxlength="2000"></textarea><br><br>

                <input type="submit" value="Guardar garantía">
            </form>
        </div>
    </div>
   <script>$(function() {
    $('form').on('submit', function(e) {
        let nombreColaborador = $('#apasionado').val().trim();

        if (nombreColaborador === '') {
            alert('Por favor ingresa el nombre del colaborador.');
            e.preventDefault();
            return false;
        }

        // Ya no se valida contra la BD, se envía directo
    });
});</script>
    
    <!--<script src="../../recursos/efecto.js"></script>-->
</body>
</html>
