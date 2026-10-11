<?php 
include_once '../../funciones.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Productos negados</title>

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../../css.css?v=<?php echo time(); ?>">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
       
        const GOOGLE_SCRIPT_URL = "https://script.google.com/macros/s/AKfycbynYy67vrk7v0GzC7gzBrjzseVPj6RrxRAxn5AssxVdith8SwcejDzHjytWUlJSTjtW/exec";

        $(function() {

            $("#apasionado").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "../../garantias/vendedor/buscar_colaborador.php",
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
                autoFocus: true,
                focus: function(event, ui) {
                    // Evita que el texto cambie mientras se navega con flechas
                    return false;
                },
                select: function(event, ui) {
                    $("#apasionado_id").val(ui.item.value);
                    $("#apasionado").val(ui.item.label);
                    return false;
                }
            });

            $("form").on("submit", function(e) {
                e.preventDefault();

                var $form = $(this);
                var $btn = $form.find('input[type="submit"]');
                var $mensaje = $("#mensaje-envio");

                // Evita doble envío
                if ($form.data("enviando")) {
                    return false;
                }
                $form.data("enviando", true);
                $btn.prop("disabled", true);
                $btn.val("Guardando...");

                var data = {
                    marca_modelo: $form.find('[name="marca_modelo"]').val(),
                    producto: $form.find('[name="producto"]').val(),
                    sucursal: $form.find('[name="sucursal"]').val(),
                    apasionado: $form.find('[name="apasionado"]').val(),
                    estatus: $form.find('[name="estatus"]').val(),
                    anotaciones_vendedor: $form.find('[name="anotaciones_vendedor"]').val()
                };

                // Se manda como text/plain para evitar el preflight CORS
                // que Google Apps Script no maneja bien con application/json.
                fetch(GOOGLE_SCRIPT_URL, {
                    method: "POST",
                    headers: { "Content-Type": "text/plain;charset=utf-8" },
                    body: JSON.stringify({
                        marca_modelo: data.marca_modelo,
                        producto: data.producto,
                        sucursal: data.sucursal,
                        colaborador: data.apasionado,
                        estatus: data.estatus,
                        anotaciones: data.anotaciones_vendedor || ""
                    })
                })
                .then(function(res) { return res.json(); })
                .then(function(respuesta) {
                    if (respuesta && respuesta.ok) {
                        $mensaje.text("Producto registrado correctamente.").show();
                        $form[0].reset();
                        $("#apasionado_id").val("");
                    } else {
                        $mensaje.text("Error: " + (respuesta && respuesta.error ? respuesta.error : "Respuesta inválida")).show();
                    }
                })
                .catch(function(err) {
                    $mensaje.text("Error al enviar datos a Google Sheets: " + err).show();
                })
                .finally(function() {
                    $form.data("enviando", false);
                    $btn.prop("disabled", false);
                    $btn.val("Guardar Producto");
                });
            });
        });

    </script>
</head>

<body>

<nav>
    <h1 id="nombre">Bitácora de almacén</h1>
    
    <input type="checkbox" id="check">
    
    <label class="bar" for="check">
        <span class="top"></span>
        <span class="middle"></span>
        <span class="bottom"></span>
    </label>
    
    <ul id="menu">
        <li>
            <a href="garantias.php" style="display:flex;align-items:center;gap:12px;">
                <span style="
                    display:inline-flex;
                    width:40px;
                    height:40px;
                    background:white;
                    border-radius:50%;
                    justify-content:center;
                    align-items:center;
                    overflow:visible;
                    position:relative;">
                    <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>"
                         style="width:30px;height:30px;"/>
                </span>
                Home
            </a>
        </li>

        <li>
            <a href="prod-neg.php" style="display:flex;align-items:center;gap:12px;">
                <img src="../../recursos/img/merma.png" style="width:40px;height:40px;"/>
                Ver Productos Negados
            </a>
        </li>
    </ul>
</nav>

<div class="contenedor">
    <div class="formulario">

        <h1>Productos Negados</h1><br>

        <p id="mensaje-envio" style="display:none;"></p>

        <br>

        <form method="POST">

            <label>Marca y modelo de producto solicitado:</label>
            <input type="text" name="marca_modelo" required><br><br>

            <label>Producto solicitado:</label>
            <input type="text" name="producto" required><br><br>

            <label>Estatus:</label>
            <select name="estatus" required>
                <option value="">Seleccione un estatus</option>
                <option value="No hay">No hay</option>
                <option value="Pocas existencias">Pocas existencias</option>
                <option value="Descontinuado">Descontinuado</option>
                <option value="Otro">Otro</option>
            </select><br><br>

            <?php $sucursales = obtenerSucursalesdos(); ?>

            <label>Sucursal:</label>
            <select name="sucursal" required>
                <option value="">Seleccione una sucursal</option>

                <?php foreach ($sucursales as $s): ?>
                    <option value="<?= htmlspecialchars($s['nombre']) ?>">
                        <?= htmlspecialchars($s['nombre']) ?>
                    </option>
                <?php endforeach; ?>

            </select><br><br>

            <label>Nombre del colaborador:</label>
            <input type="text" name="apasionado" id="apasionado" required>
            <input type="hidden" name="apasionado_id" id="apasionado_id"><br><br>

            <label>Observaciones (opcional):</label><br>
            <textarea name="anotaciones_vendedor" rows="4"></textarea><br><br>

            <input type="submit" value="Guardar Producto">

        </form>
    </div>
</div>

</body>
</html>