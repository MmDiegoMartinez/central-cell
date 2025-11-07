<?php 
include_once '../../funciones.php';   
$mensaje = "";  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $insertedId = guardarproductosnegados($_POST); // ahora devuelve id
        $mensaje = "✅ Producto registrado correctamente.";
    } catch (Exception $e) {
        $mensaje = "❌ Error al guardar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Productos negados</title>
    
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../../css.css?v=<?php echo time(); ?>">


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
      

        $(function() {
    $("#apasionado").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "../../vendedor/buscar_colaborador.php",
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
<body>
    <nav>
        <h1 id="nombre">Productos negados</h1>
        <ul id="menu">
            <li>
  <a href="index.php" style="display: flex; align-items: center; gap: 12px;  ">
    <span style="
      display: inline-flex;
      width: 40px; 
      height: 40px; 
      background: white; 
      border-radius: 50%; 
      justify-content: center; 
      align-items: center; 
      overflow: visible;
      position: relative;
    ">
      <img src="../../Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" 
           style="
             width: 30px; 
             height: 30Px; 
             object-fit: contain;
             position: relative;
             top: 0; left: 0;
           " />
    </span>
     Home
  </a>
</li>

<li>
  <a href="tabla.php" style="display: flex; align-items: center; gap: 12px;  ">
    
      <img src="../../recursos/img/merma.png" alt="Logo Central Cell" 
           style="
             width: 40px; 
             height: 40Px; 
             object-fit: contain;
             position: relative;
             top: 0; left: 0;
           " />
    </span>
     Producto
  </a>
</li>

            
        </ul>
    </nav>

    <div class="contenedor">
        <div class="formulario">
            <h1>Bitácora de almacén</h1><br>

            <?php if ($mensaje): ?>
                <p><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?><br>

            <form method="POST">
                <label for="marca_modelo">Marca y modelo de producto solicitado:</label>
                <input type="text" name="marca_modelo" required><br><br>

                <label for="producto">Producto solicitado:</label>
                <input type="text" name="producto" required><br><br>

                 <label for="estatus">Estatus:</label>
                <input type="text" name="estatus" required><br><br>

                <?php
                $sucursales = obtenerSucursalesdos();
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

                <label for="anotaciones_vendedor">Anotaciones (opcional):</label><br>
                <textarea name="anotaciones_vendedor" rows="4" cols="50" maxlength="2000"></textarea><br><br>

                <input type="submit" value="Guardar Producto">
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
</body>
</html>
