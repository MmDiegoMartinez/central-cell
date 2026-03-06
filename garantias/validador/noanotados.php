<?php
session_start();

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

$nombre = $_SESSION['validador_nombre'] ?? '';
$apellido = $_SESSION['validador_apellido'] ?? '';
$validador_id = $_SESSION['validador_id'];



include_once '../../funciones.php'; 

$mostrarMensajeNoEncontrado = false;
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = conectarBD();

        $nombreColaborador = trim($_POST['apasionado'] ?? '');
        $sql = "SELECT COUNT(*) FROM colaboradores WHERE nombre = :nombre";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':nombre' => $nombreColaborador]);
        $existe = $stmt->fetchColumn();

        if (!$existe) {
            $mostrarMensajeNoEncontrado = true; // para mostrar el div modal
        } else {
            guardarGarantia($_POST);
            $mensaje = "✅ Garantía registrada correctamente.";
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error al guardar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Garantía</title>
    
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../../css.css">

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
                url: "../vendedor/buscar_colaborador.php",
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
        <h1 id="nombre">Central Cell Garantias</h1>
        <ul id="menu">
            <li>
  <a href="garantias.php" style="display: flex; align-items: center; gap: 12px;  ">
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
      <img src="../../recursos/imgCentral-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" 
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
    
      <img src="../../recursos/img/image.png" alt="Logo Central Cell" 
           style="
             width: 40px; 
             height: 40Px; 
             object-fit: contain;
             position: relative;
             top: 0; left: 0;
           " />
    </span>
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
            <?php endif; ?>

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

                <label for="sucursal">Sucursal:</label>
                <select name="sucursal" required>
                    <option value="">Seleccione una sucursal</option>
                    <option>Reforma</option>
                    <option>Labo</option>
                    <option>Abastos</option>
                    <option>Revis</option>
                    <option>Bella</option>
                    <option>Violetas</option>
                    <option>Bonn</option>
                    <option>Nuño</option>
                    <option>20 de Noviembre</option>
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
        e.preventDefault(); // detener envío automático

        let nombreColaborador = $('#apasionado').val().trim();

        if (nombreColaborador === '') {
            alert('Por favor ingresa el nombre del colaborador.');
            return false;
        }

        // Hacer AJAX para validar si existe el colaborador
        $.ajax({
            url: '../vendedor/verificar_colaborador.php.php',
            method: 'GET',
            data: { nombre: nombreColaborador },
            success: function(response) {
                // response será JSON con { existe: true/false }
                if (!response.existe) {
                    alert("No se encontró tu nombre registrado en la base de datos. Por favor, verifica que esté escrito correctamente. Si eres un nuevo ingreso, al hacer clic en 'Aceptar' se dará de alta tu nombre para este y futuros registros, asegurándote de incluir al menos un nombre y un apellido.");
                }
                // Luego enviamos el formulario igual, se guarda siempre
                e.currentTarget.submit();
            },
            error: function() {
                alert("Error validando colaborador. Intenta de nuevo.");
            },
            dataType: 'json'
        });
    });
});</script>
</body>
</html>
