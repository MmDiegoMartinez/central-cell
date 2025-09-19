<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

// Mostrar errores (solo en desarrollo, no en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../funciones.php';

$mensaje = "";

// Conectar a la base de datos
try {
    $conn = conectarBD();
} catch (Exception $e) {
    die("❌ Error al conectar a la base de datos: " . $e->getMessage());
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Normalizar nombre: quitar espacios y pasar a minúsculas
        $nombreColaborador = mb_strtolower(trim($_POST['nombre'] ?? ''));

        if ($nombreColaborador === '') {
            throw new Exception("El nombre del colaborador no puede estar vacío.");
        }

        // Manejo de fecha (puede ser NULL)
        $fechaCapacitacion = $_POST['fecha'] ?? null;
        if ($fechaCapacitacion === '') {
            $fechaCapacitacion = null;
        }

        // Verificar si el colaborador ya existe
        $sql = "SELECT id, fecha_capacitacion 
                FROM colaboradores 
                WHERE LOWER(nombre) = :nombre";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':nombre' => $nombreColaborador]);
        $colaborador = $stmt->fetch();

        if ($colaborador) {
            // Si no tiene fecha, la actualizamos
            if (empty($colaborador['fecha_capacitacion']) && $fechaCapacitacion !== null) {
                $sql = "UPDATE colaboradores 
                        SET fecha_capacitacion = :fecha 
                        WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':fecha' => $fechaCapacitacion,
                    ':id'    => $colaborador['id']
                ]);
                $mensaje = "✅ Fecha de capacitación actualizada.";
            } else {
                $mensaje = "ℹ️ El colaborador ya tenía registrada la capacitación o no se proporcionó una nueva fecha.";
            }
        } else {
            // Insertar nuevo colaborador
            $sql = "INSERT INTO colaboradores (nombre, fecha_capacitacion) 
                    VALUES (:nombre, :fecha)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombreColaborador,
                ':fecha'  => $fechaCapacitacion
            ]);
            $mensaje = "✅ Nuevo colaborador agregado y fecha de capacitación registrada.";
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}

// Obtener colaboradores con capacitación registrada
try {
    $sql = "SELECT nombre, fecha_capacitacion 
            FROM colaboradores 
            WHERE fecha_capacitacion IS NOT NULL 
            ORDER BY fecha_capacitacion DESC";
    $stmt = $conn->query($sql);
    $colaboradores = $stmt->fetchAll();
} catch (Exception $e) {
    $colaboradores = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Capacitación</title>
    
    <!-- Estilos y librerías -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="css.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <script>
    $(function() {
        let autocompleteData = [];

        // Autocompletado del campo nombre
        $("#nombre").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "buscar_colaborador.php",
                    dataType: "json",
                    data: { term: request.term },
                    success: function(data) {
                        autocompleteData = data;
                        response(data);
                    }
                });
            },
            minLength: 1,
            delay: 300,
            select: function(event, ui) {
                $("#nombre").val(ui.item.label);
                return false;
            },
            open: function() {
                let widget = $(this).autocomplete("widget");
                widget.children("li").removeClass("ui-state-focus");
                widget.children("li:first").addClass("ui-state-focus");
            }
        });

        // Usar Enter para seleccionar el primer resultado
        $("#nombre").on('keydown', function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                if (autocompleteData.length > 0) {
                    $("#nombre").val(autocompleteData[0].label);
                }
            }
        });
    });
    </script>
</head>
<body>
    <h1>Registro de Capacitación</h1>

    <!-- Mensajes -->
    <?php if ($mensaje): ?>
        <p><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <!-- Formulario -->
    <form method="POST" autocomplete="off">
        <label for="nombre">Nombre del colaborador:</label>
        <input type="text" name="nombre" id="nombre" required>
        <br><br>

        <label for="fecha">Fecha de capacitación (opcional):</label>
        <input type="date" name="fecha" id="fecha" value="<?= date('Y-m-d') ?>">
        <br><br>

        <input type="submit" value="Guardar">
    </form>

    <!-- Tabla de colaboradores -->
    <h2>Colaboradores con capacitación vigente</h2>
    <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Fecha de Capacitación</th>
                <th>Fecha de Fin (1 mes después)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $hoy = new DateTime();

        foreach ($colaboradores as $col) {
            $fechaCap = new DateTime($col['fecha_capacitacion']);
            $fechaFin = (clone $fechaCap)->modify('+1 month');
            $interval = $hoy->diff($fechaFin);
            $diasRestantes = (int)$interval->format('%r%a');

            // Filtrar: ocultar si pasaron más de 60 días después de la fecha fin
            if ($diasRestantes < -60) {
                continue;
            }

            // Color de fila según estado
            if ($diasRestantes >= 8) {
                $color = '#d4edda'; // verde claro
            } elseif ($diasRestantes >= 0) {
                $color = '#fff3cd'; // amarillo claro
            } else {
                $color = '#f8d7da'; // rojo claro
            }

            echo "<tr style='background-color: $color'>";
            echo "<td>" . htmlspecialchars($col['nombre']) . "</td>";
            echo "<td>" . $fechaCap->format('Y-m-d') . "</td>";
            echo "<td>" . $fechaFin->format('Y-m-d') . "</td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
</body>
</html>
