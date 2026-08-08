<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../funciones.php';

// ----------------------------------------------
// Utilidades
// ----------------------------------------------
function normalizarNombre(string $nombre): string {
    return mb_strtolower(trim($nombre));
}

function ensureSchema(PDO $conn): void {
    // Crea tablas si no existen para hacer el archivo auto-contenido
    $conn->exec("CREATE TABLE IF NOT EXISTS `preguntas` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `texto_pregunta` TEXT NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    $conn->exec("CREATE TABLE IF NOT EXISTS `opciones_respuesta` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `id_pregunta` INT(11) NOT NULL,
        `texto_opcion` VARCHAR(200) NOT NULL,
        `es_correcta` TINYINT(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `id_pregunta` (`id_pregunta`),
        CONSTRAINT `opciones_respuesta_ibfk_1` FOREIGN KEY (`id_pregunta`) REFERENCES `preguntas` (`id`)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    $conn->exec("CREATE TABLE IF NOT EXISTS `respuestas_colaborador` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `id_colaborador` INT(11) NOT NULL,
        `id_pregunta` INT(11) NOT NULL,
        `id_opcion` INT(11) NOT NULL,
        `fecha_respuesta` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `id_colaborador` (`id_colaborador`),
        KEY `id_pregunta` (`id_pregunta`),
        KEY `id_opcion` (`id_opcion`),
        CONSTRAINT `respuestas_colaborador_ibfk_1` FOREIGN KEY (`id_colaborador`) REFERENCES `colaboradores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `respuestas_colaborador_ibfk_2` FOREIGN KEY (`id_pregunta`) REFERENCES `preguntas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `respuestas_colaborador_ibfk_3` FOREIGN KEY (`id_opcion`) REFERENCES `opciones_respuesta` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
}

function seedPreguntas(PDO $conn): void {
    // Si ya hay preguntas, no sembrar
    $existe = (int)$conn->query("SELECT COUNT(*) FROM preguntas")->fetchColumn();
    if ($existe > 0) return;

    $preguntas = [
        [
            'texto' => '¿Cuál es la función principal del Departamento de Innovación y Tecnología Móvil en CentralCell?',
            'opciones' => [
                ['A) Atender a los clientes en mostrador', 0],
                ['B) Surtir de mercancía a las sucursales', 1],
                ['C) Realizar mantenimiento de redes', 0],
                ['D) Desarrollar software interno', 0],
            ],
        ],
        [
            'texto' => '¿Qué se necesita para cotizar un financiamiento con PayJoy?',
            'opciones' => [
                ['A) Estados de cuenta bancarios de los últimos 6 meses', 0],
                ['B) Tener tarjeta de crédito y comprobante de domicilio', 0],
                ['C) Identificación oficial, una línea celular activa y una selfie', 1],
                ['D) Tener un aval con propiedad a su nombre', 0],
            ],
        ],
        [
            'texto' => '¿Qué hidrogel puede causar conflicto con el desbloqueo de huella?',
            'opciones' => [
                ['A) Hidrogel Mate', 0],
                ['B) Hidrogel Antiblue', 0],
                ['C) Hidrogel Privacidad', 1],
                ['D) Hidrogel Transparente', 0],
            ],
        ],
        [
            'texto' => '¿Qué combinación permite obtener aproximadamente 15 W de potencia en un cargador?',
            'opciones' => [
                ['A) 5V × 1A', 0],
                ['B) 5V × 3A', 1],
                ['C) 5V × 2A', 0],
                ['D) 10V × 1A', 0],
            ],
        ],
        [
            'texto' => '¿Por qué existe la lista de productos negados?',
            'opciones' => [
                ['A) Para registrar devoluciones', 0],
                ['B) Para registrar productos dañados', 0],
                ['C) Para dar seguimiento a productos sin existencia y evitar perder ventas', 1],
                ['D) Para controlar garantías', 0],
            ],
        ],
        [
            'texto' => 'En smartphones, ¿qué gama de teléfonos manejamos en tienda?',
            'opciones' => [
                ['A) Baja, media baja y media', 1],
                ['B) Alta, premium y de lujo', 0],
                ['C) Solo gama alta', 0],
                ['D) Baja, media y alta', 0],
            ],
        ],
        [
            'texto' => '¿Qué material compone principalmente el hidrogel?',
            'opciones' => [
                ['A) Vidrio templado', 0],
                ['B) Fibra de carbono', 0],
                ['C) Resina líquida y silicona', 1],
                ['D) Plástico ABS', 0],
            ],
        ],
        [
            'texto' => '¿Cuál es la garantía de los productos de CentralCell?',
            'opciones' => [
                ['A) 1 mes', 0],
                ['B) 2 meses', 0],
                ['C) 3 meses', 1],
                ['D) 6 meses', 0],
            ],
        ],
        [
            'texto' => '¿Qué tipo de corte de hidrogel evita el riesgo de levantarse si la carátula cubre parte de la pantalla?',
            'opciones' => [
                ['A) Corte FRONT', 0],
                ['B) Corte MATTE y WITH-COVER', 1],
                ['C) Corte UV', 0],
                ['D) sin opción D', 0],
            ],
        ],
        [
            'texto' => '¿Qué condición es necesaria para hacer válida la garantía de un accesorio?',
            'opciones' => [
                ['A) Presentar el ticket de compra en foto o físico y empaque si es el caso', 1],
                ['B) Enviar un correo a soporte', 0],
                ['C) Pagar una tarifa de reposición', 0],
                ['D) Presentar un video del defecto', 0],
            ],
        ],
    ];

    $stmtP = $conn->prepare("INSERT INTO preguntas (texto_pregunta) VALUES (:t)");
    $stmtO = $conn->prepare("INSERT INTO opciones_respuesta (id_pregunta, texto_opcion, es_correcta) VALUES (:pid, :txt, :ok)");

    foreach ($preguntas as $p) {
        $stmtP->execute([':t' => $p['texto']]);
        $pid = (int)$conn->lastInsertId();
        foreach ($p['opciones'] as $opt) {
            $stmtO->execute([':pid' => $pid, ':txt' => $opt[0], ':ok' => $opt[1]]);
        }
    }
}

function obtenerColaboradorId(PDO $conn, string $nombre): int {
    date_default_timezone_set('America/Mexico_City');
    $fechaHoy = date('Y-m-d');
    $nombreNorm = normalizarNombre($nombre);

    // Verificar si ya existe el colaborador
    $stmt = $conn->prepare("SELECT id FROM colaboradores WHERE LOWER(nombre) = :n LIMIT 1");
    $stmt->execute([':n' => $nombreNorm]);
    $row = $stmt->fetch();

    if ($row) {
        // Actualizar la fecha de capacitación si ya existe
        $stmt = $conn->prepare("UPDATE colaboradores SET fecha_capacitacion = :f WHERE id = :id");
        $stmt->execute([':f' => $fechaHoy, ':id' => $row['id']]);
        return (int)$row['id'];
    }

    // Insertar nuevo colaborador con fecha actual
    $stmt = $conn->prepare("INSERT INTO colaboradores (nombre, fecha_capacitacion) VALUES (:n, :f)");
    $stmt->execute([':n' => $nombreNorm, ':f' => $fechaHoy]);
    return (int)$conn->lastInsertId();
}

function cargarCuestionario(PDO $conn): array {
    $sql = "SELECT p.id AS pid, p.texto_pregunta, o.id AS oid, o.texto_opcion, o.es_correcta
            FROM preguntas p
            JOIN opciones_respuesta o ON o.id_pregunta = p.id
            ORDER BY p.id ASC, o.id ASC";
    $rows = $conn->query($sql)->fetchAll();
    $pregs = [];
    foreach ($rows as $r) {
        $pid = (int)$r['pid'];
        if (!isset($pregs[$pid])) {
            $pregs[$pid] = [
                'pid' => $pid,
                'texto' => $r['texto_pregunta'],
                'opciones' => []
            ];
        }
        $pregs[$pid]['opciones'][] = [
            'oid' => (int)$r['oid'],
            'texto' => $r['texto_opcion'],
            'ok' => (int)$r['es_correcta'] === 1
        ];
    }
    return array_values($pregs);
}

function guardarRespuestas(PDO $conn, int $colabId, array $post): array {
    $puntos = 0; $total = 0; $detalle = [];
    $stmtOpt = $conn->prepare("SELECT o.id_pregunta, o.es_correcta, p.texto_pregunta, o.texto_opcion
                               FROM opciones_respuesta o JOIN preguntas p ON p.id = o.id_pregunta
                               WHERE o.id = :oid");
    $stmtIns = $conn->prepare("INSERT INTO respuestas_colaborador (id_colaborador, id_pregunta, id_opcion) VALUES (:cid, :pid, :oid)");

    foreach ($post as $k => $v) {
        if (strpos($k, 'q_') !== 0) continue;
        $oid = (int)$v;
        $stmtOpt->execute([':oid' => $oid]);
        $info = $stmtOpt->fetch();
        if (!$info) continue;
        $total++;
        $pid = (int)$info['id_pregunta'];
        $correcta = (int)$info['es_correcta'] === 1;
        if ($correcta) $puntos++;
        $stmtIns->execute([':cid' => $colabId, ':pid' => $pid, ':oid' => $oid]);
        $detalle[] = [
            'pid' => $pid,
            'pregunta' => $info['texto_pregunta'],
            'respuesta' => $info['texto_opcion'],
            'correcta' => $correcta
        ];
    }

    return ['puntos' => $puntos, 'total' => $total, 'detalle' => $detalle];
}

// ----------------------------------------------
// Main
// ----------------------------------------------
try {
    $conn = conectarBD();
    try {
    $colCheck = $conn->query("SHOW COLUMNS FROM colaboradores LIKE 'fecha_capacitacion'");
    if ($colCheck->rowCount() === 0) {
        $conn->exec("ALTER TABLE colaboradores ADD COLUMN fecha_capacitacion DATE DEFAULT NULL");
        echo "✅ Se agregó la columna 'fecha_capacitacion' en la tabla 'colaboradores'.<br>";
    }
} catch (Exception $e) {
    echo "⚠️ Error al verificar o modificar la tabla colaboradores: " . $e->getMessage() . "<br>";
}
    ensureSchema($conn);
    seedPreguntas($conn);
} catch (Exception $e) {
    die('❌ Error al preparar el examen: ' . htmlspecialchars($e->getMessage()));
}

$mensaje = '';
$resultado = null;
$colaboradorNombre = '';
$cuestionario = cargarCuestionario($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $colaboradorNombre = $_POST['nombre'] ?? '';
        if (trim($colaboradorNombre) === '') throw new Exception('El nombre es obligatorio.');
        $colabId = obtenerColaboradorId($conn, $colaboradorNombre);
        $resultado = guardarRespuestas($conn, $colabId, $_POST);
        $mensaje = '✅ Respuestas guardadas correctamente.';
    } catch (Exception $e) {
        $mensaje = '❌ ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examen - Innovación Móvil</title>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <link rel="stylesheet" href="../styles.css">
    <style>
        /* ---------------------------------------------------
           Estilos complementarios específicos de esta página.
           No se toca styles.css: todo lo que falta se agrega aquí
           reutilizando los tokens de diseño ya definidos.
           --------------------------------------------------- */
        .badge{
            display:inline-flex;align-items:center;gap:6px;
            background:var(--secondary-container);
            color:var(--on-secondary-container);
            padding:6px 14px;border-radius:var(--radius-full);
            font-size:12px;font-weight:700;letter-spacing:0.03em;
            white-space:nowrap;
        }
        .topbar{
            display:flex;align-items:center;justify-content:space-between;
            gap:var(--space-md);flex-wrap:wrap;
            margin:var(--space-xl) 0 var(--space-md);
        }
        .topbar h1{margin:0;}

        .msg{
            display:flex;gap:var(--space-md);align-items:flex-start;
            border-radius:0 var(--radius-lg) var(--radius-lg) 0;
            padding:var(--space-md) var(--space-lg);
            margin:0 0 var(--space-lg);font-size:14px;font-weight:600;
        }
        .msg.ok{
            background:rgba(109,245,225,0.18);
            border-left:4px solid var(--secondary);
            color:var(--on-secondary-container);
        }
        .msg.err{
            background:var(--error-container);
            border-left:4px solid var(--error);
            color:var(--on-error-container);
        }

        .campo{margin-bottom:6px;}
        label.req{font-size:14px;font-weight:700;color:var(--on-surface);}
        label.req::after{content:" *";color:var(--error);}

        input#nombre{
            width:100%;padding:12px 14px;margin:6px 0 0;
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            font-family:'Inter',sans-serif;font-size:15px;
            background:var(--surface-container-lowest);color:var(--on-surface);
        }
        input#nombre:focus{outline:2px solid var(--primary);outline-offset:1px;}

        .divider{height:1px;background:var(--outline-variant);margin:var(--space-lg) 0;}

        .pregunta{
            padding:var(--space-lg) 0;
            border-top:1px solid var(--outline-variant);
        }
        .pregunta:first-of-type{border-top:none;padding-top:var(--space-md);}
        .pregunta strong{
            display:block;margin-bottom:var(--space-md);
            font-size:15px;line-height:22px;color:var(--on-surface);
        }

        label.opcion{
            display:flex;align-items:center;gap:10px;
            padding:10px 14px;margin-bottom:8px;
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            background:var(--surface-container-low);
            font-size:14px;color:var(--on-surface-variant);
            cursor:pointer;transition:border-color .15s ease, background .15s ease;
        }
        label.opcion:hover{border-color:var(--primary);}
        label.opcion:has(input:checked){
            border-color:var(--primary);
            background:rgba(29,78,216,0.08);
            color:var(--on-surface);font-weight:600;
        }
        label.opcion input{accent-color:var(--primary);width:16px;height:16px;flex:0 0 auto;}

        .acciones{
            display:flex;align-items:center;gap:var(--space-md);
            flex-wrap:wrap;margin-top:var(--space-xl);
        }
        .btn.secondary{background:var(--surface-container-high);color:var(--on-surface);}
        .nota.muted{font-size:12px;color:var(--outline);}

        .resultado h2{margin-top:0;}
        .resultado ol{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:var(--space-md);}
        .resultado ol li{
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            padding:var(--space-md) var(--space-lg);
            background:var(--surface-container-low);
        }
        .resultado .correcta{color:var(--on-secondary-container);}
        .resultado .incorrecta{color:var(--on-error-container);}
        .resultado .correcta::before{content:"✓ ";font-weight:700;}
        .resultado .incorrecta::before{content:"✕ ";font-weight:700;}

        /* Sidebar / topheader toggle helpers (JS controla las clases open/show) */
        .sidebar-nav a{cursor:pointer;}

        /* Autocomplete jQuery UI con la tipografía del sistema */
        .ui-autocomplete{
            font-family:'Inter',sans-serif;font-size:14px;
            border:1px solid var(--outline-variant);border-radius:var(--radius-lg);
            box-shadow:0 4px 12px rgba(17,28,45,0.12);z-index:1000;
        }
        .ui-menu-item{padding:2px 4px;}
    </style>
    <script>
    $(function(){
        let autocompleteData = [];
        $("#nombre").autocomplete({
            source: function(request, response){
                $.ajax({
                    url: "../garantias/vendedor/buscar_colaborador.php",
                    dataType: "json",
                    data: { term: request.term },
                    success: function(data){
                        autocompleteData = data;
                        response(data);
                    }
                });
            },
            minLength: 1,
            delay: 300,
            select: function(event, ui){
                $("#nombre").val(ui.item.label);
                return false;
            },
            open: function(){
                let w = $(this).autocomplete("widget");
                w.children("li").removeClass("ui-state-focus");
                w.children("li:first").addClass("ui-state-focus");
            }
        });
        $("#nombre").on('keydown', function(e){
            if(e.key === 'Enter'){
                e.preventDefault();
                if(autocompleteData.length>0){ $("#nombre").val(autocompleteData[0].label); }
            }
        });

        // Toggle del sidebar en móvil
        $("#menuToggle").on('click', function(){
            $("#sidebar").addClass('open');
            $("#sidebarOverlay").addClass('show');
        });
        $("#sidebarClose, #sidebarOverlay").on('click', function(){
            $("#sidebar").removeClass('open');
            $("#sidebarOverlay").removeClass('show');
        });
    });
    </script>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-head">
            <div>
                <h2 class="sidebar-brand text-headline-sm">CentralCell</h2>
                <p class="sidebar-sub text-label-sm">Innovación Móvil</p>
            </div>
            <button class="sidebar-close material-symbols-outlined" id="sidebarClose" type="button">close</button>
        </div>
        <nav class="sidebar-nav">
            <a class="sidebar-link" href="index.html">
                <span class="material-symbols-outlined">home</span> Inicio Capacitados
            </a>
            <a class="sidebar-link" href="material.html">
                <span class="material-symbols-outlined">menu_book</span> Material
            </a>
            <a class="sidebar-link active" href="examen.php">
                <span class="material-symbols-outlined">quiz</span> Cuestionario
            </a>
        </nav>
    </aside>

    <div class="main">
        <header class="topheader">
            <div class="topheader-left">
                <button class="menu-toggle material-symbols-outlined" id="menuToggle" type="button">menu</button>
                <h2 class="text-headline-sm">Examen – Innovación Móvil</h2>
            </div>
        </header>

        <div class="container">

            <div class="topbar">
                <h1 class="text-headline-md">Examen – Innovación Móvil</h1>
                <span class="badge">10 preguntas de opción múltiple</span>
            </div>

            <?php if ($mensaje): ?>
                <div class="msg <?php echo $resultado ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <?php if (!$resultado): ?>
            <form method="POST" class="lesson" autocomplete="off">
                <div class="lesson-body">
                    <div class="campo">
                        <label class="req" for="nombre">Nombre del colaborador</label>
                    </div>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($colaboradorNombre); ?>" required>
                    <div class="divider"></div>

                    <?php foreach ($cuestionario as $idx => $p): ?>
                        <div class="pregunta">
                            <strong><?php echo ($idx+1) . '. ' . htmlspecialchars($p['texto']); ?></strong>
                            <?php foreach ($p['opciones'] as $op): ?>
                                <label class="opcion">
                                    <input type="radio" name="q_<?php echo (int)$p['pid']; ?>" value="<?php echo (int)$op['oid']; ?>" required>
                                    <span><?php echo htmlspecialchars($op['texto']); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="acciones">
                        <button type="submit" class="btn btn-primary">Enviar examen</button>
                        <button type="reset" class="btn btn-outline">Limpiar</button>
                        <span class="nota muted">Tu nombre se vincula con la BD automáticamente. Si no existe, se crea.</span>
                    </div>
                </div>
            </form>
            <?php else: ?>
                <div class="lesson resultado">
                    <div class="lesson-body">
                        <h2 class="text-headline-sm">Resultado</h2>
                        <p><strong>Puntaje:</strong> <?php echo (int)$resultado['puntos']; ?> / <?php echo (int)$resultado['total']; ?></p>
                        <div class="divider"></div>
                        <ol>
                            <?php foreach ($resultado['detalle'] as $d): ?>
                                <li>
                                    <div><strong><?php echo htmlspecialchars($d['pregunta']); ?></strong></div>
                                    <div class="<?php echo $d['correcta'] ? 'correcta' : 'incorrecta'; ?>">
                                        Tu respuesta: <?php echo htmlspecialchars($d['respuesta']); ?>
                                        <?php if (!$d['correcta']): ?>
                                            <?php
                                            // Mostrar la correcta
                                            $stmt = $conn->prepare('SELECT texto_opcion FROM opciones_respuesta WHERE id_pregunta = :pid AND es_correcta = 1 LIMIT 1');
                                            $stmt->execute([':pid' => $d['pid']]);
                                            $corr = $stmt->fetchColumn();
                                            ?>
                                            <span> | Correcta: <strong><?php echo htmlspecialchars($corr ?: 'N/D'); ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                        <div class="acciones" style="margin-top:12px;">
                            <a class="btn btn-outline" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">Hacer otro intento</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>