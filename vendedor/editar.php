<?php
include_once '../funciones.php';

if (!isset($_GET['id'])) {
    die("ID de garantía no especificado.");
}

$id = intval($_GET['id']);
$garantia = obtenerGarantiaPorId($id);

if (!$garantia) {
    die("Garantía no encontrada.");
}

if ($garantia['id_validador'] !== null) {
    die("⚠️ Esta garantía ya fue validada y no se puede editar.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    actualizarGarantia($id, $_POST);
    header("Location: tabla.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Garantía</title>
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
    </script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const titulo = document.getElementById("titulo");
        const texto = "Garantías y Mermas";
        let i = 0;
        let borrando = false;

        function escribirMaquina() {
            const inicioInmediato = 1;

            if (i === 0 && !borrando) {
                titulo.textContent = texto.charAt(0);
                i = inicioInmediato + 1;
            } else if (!borrando && i <= texto.length) {
                titulo.textContent = texto.slice(0, i);
                i++;
            } else if (borrando && i >= 0) {
                titulo.textContent = texto.slice(0, i);
                i--;
            }

            if (i > texto.length) {
                borrando = true;
                setTimeout(escribirMaquina, 1500);
                return;
            } else if (i === 0 && borrando) {
                borrando = false;
            }

            setTimeout(escribirMaquina, borrando ? 70 : 170);
        }

        escribirMaquina();
    });
    </script>
    <link rel="stylesheet" href="../css.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
</head>
<body>

<nav>
   <H1 id="titulo"></H1>
    <ul id="menu">
        <li><a href="tabla.php">⬅️ Atras</a></li>
    </ul>
</nav>

<div class="contenedor">
    <div class="formulario">
        <h1>Editar Garantía</h1>

        <form method="POST">
            <!-- PLOWS -->
            <label for="plows">PLOWS:</label>
            <input type="text" name="plows" id="plows" maxlength="11" value="<?= htmlspecialchars($garantia['plows']) ?>" onblur="validarPlows(this)" required><br><br>

            <!-- Tipo de producto -->
            <label for="tipo">Tipo de producto:</label>
            <select name="tipo" required>
                <?php
                $tipos = ['Caratula Case','Hidrogel','Kits de Carga','Protection Pro','Glass Full','Glass Mobo','Cable USB','Funda Tablet','Electronico','Adaptador de carga','Otros'];
                foreach ($tipos as $tipo) {
                    $selected = $garantia['tipo'] === $tipo ? 'selected' : '';
                    echo "<option value=\"$tipo\" $selected>$tipo</option>";
                }
                ?>
            </select><br><br>

            <!-- Causa -->
            <label for="causa">Causa:</label>
            <select name="causa" required>
                <?php
                $causas = ['Cambio de producto (Garantia)', 'Defecto de fabrica', 'Mala instalacion de producto (garantia)', 'Error (Nuevo Ingreso)', 'Se encontro roto o descompuesto', 'Mala instalacion del producto (merma)', 'Fallo de la maquina'];
                foreach ($causas as $causa) {
                    $selected = $garantia['causa'] === $causa ? 'selected' : '';
                    echo "<option value=\"$causa\" $selected>$causa</option>";
                }
                ?>
            </select><br><br>

            <!-- Piezas -->
            <label for="piezas">Piezas:</label>
            <input type="number" name="piezas" min="1" value="<?= htmlspecialchars($garantia['piezas']) ?>" required><br><br>

            <!-- Sucursal -->
            <label for="sucursal">Sucursal:</label>
            <select name="sucursal" required>
                <?php
                $sucursales = ['Reforma','Labo','Abastos','Revis','Bella','Violetas','Bonn','Nuño','20 de Noviembre'];
                foreach ($sucursales as $sucursal) {
                    $selected = $garantia['sucursal'] === $sucursal ? 'selected' : '';
                    echo "<option value=\"$sucursal\" $selected>$sucursal</option>";
                }
                ?>
            </select><br><br>

            <!-- Apasionado -->
            <label for="apasionado">Nombre del colaborador:</label>
            <input type="text" name="apasionado" id="apasionado" value="<?= htmlspecialchars($garantia['nombre_colaborador']) ?>" required>
            <input type="hidden" name="apasionado_id" id="apasionado_id" value="<?= htmlspecialchars($garantia['apasionado']) ?>">

            <!-- Fecha -->
            <label for="fecha">Fecha:</label>
            <input type="date" name="fecha" value="<?= htmlspecialchars($garantia['fecha']) ?>" required><br><br>

            <!-- Estatus -->
            <label for="estatus">Estatus:</label>
            <select name="estatus" required>
                <option value="Anotado" <?= $garantia['estatus'] === 'Anotado' ? 'selected' : '' ?>>Anotado</option>
                <option value="Entregado Al Repartidor" <?= $garantia['estatus'] === 'Entregado Al Repartidor' ? 'selected' : '' ?>>Entregado Al Repartidor</option>
            </select><br><br>

            <!-- Anotaciones -->
            <label for="anotaciones_vendedor">Anotaciones (opcional):</label><br>
            <textarea name="anotaciones_vendedor" rows="4" cols="50" maxlength="2000"><?= htmlspecialchars($garantia['anotaciones_vendedor']) ?></textarea><br><br>

            <input type="submit" value="Actualizar garantía">
        </form>
    </div>
</div>
<script>
$(function() {
    $("#apasionado").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "buscar_colaborador.php",
                dataType: "json",
                data: { term: request.term },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 1,
        select: function(event, ui) {
            $("#apasionado").val(ui.item.label);
            $("#apasionado_id").val(ui.item.value);
            return false;
        },
        open: function() {
            const menu = $(this).autocomplete("widget");
            menu.find("li:first .ui-menu-item-wrapper").addClass("ui-state-active");
        }
    });

    $("#apasionado").on("keydown", function(e) {
        const widget = $(this).autocomplete("widget");
        if (e.keyCode === 13 && widget.is(":visible")) {
            e.preventDefault();
            widget.find(".ui-menu-item-wrapper.ui-state-active").trigger("click");
        }
    });
});
</script>
</body>
</html>

<?php