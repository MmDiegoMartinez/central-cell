<?php 
include_once '../../funciones.php'; 

define(
    "GOOGLE_SCRIPT_URL",
    "https://script.google.com/macros/s/AKfycby0vakEWFxjgzKoeoanwOrqxG5rlAbpiK7jQqiKdL6e5B4Ddvomnltu436epFHsAUG_/exec"
);   

$mensaje = "";

// Si se envía el formulario (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Datos enviados a Google Sheets
    $data = [
    "indicador" => "Anotado",
    "marca_modelo" => $_POST['marca_modelo'],
    "producto" => $_POST['producto'],
    "sucursal" => $_POST['sucursal'],
    "colaborador" => $_POST['apasionado'],
    "estatus" => $_POST['estatus'],
    "anotaciones" => $_POST['anotaciones_vendedor'] // <--- AGREGADO
];

    // Opciones para enviar el JSON por POST
    $options = [
        "http" => [
            "header"  => "Content-Type: application/json",
            "method"  => "POST",
            "content" => json_encode($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result   = file_get_contents(GOOGLE_SCRIPT_URL, false, $context);

    // Manejo de errores
    if ($result === FALSE) {
        $mensaje = "❌ Error al enviar datos a Google Sheets.";
    } else {

        $respuesta = json_decode($result, true);

        if ($respuesta["ok"]) {
            $mensaje = "✅ Producto registrado Correctamente.";
        } else {
            $mensaje = "❌ Error: " . $respuesta["error"];
        }
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
            // Guardamos el id en el campo oculto
            $("#apasionado_id").val(ui.item.value);
            // Opcional: mantener el texto tal como está en el input
            $("#apasionado").val(ui.item.label);
            return false; // Evita que jQuery UI reemplace el texto con value predeterminado
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
            <a href="index.php" style="display:flex;align-items:center;gap:12px;">
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
                    <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png"
                         style="width:30px;height:30px;"/>
                </span>
                Home
            </a>
        </li>

        <li>
            <a href="tabla.php" style="display:flex;align-items:center;gap:12px;">
                <img src="../../recursos/img/merma.png" style="width:40px;height:40px;"/>
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
        <?php endif; ?>

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
