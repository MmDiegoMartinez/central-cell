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
            'texto' => '¿Cuál es la función principal del Departamento de Innovación Móvil en CentralCell?',
            'opciones' => [
                ['A) Atender a los clientes en mostrador', 0],
                ['B) Surtir todos los accesorios a las sucursales', 1],
                ['C) Realizar mantenimiento de redes', 0],
                ['D) Desarrollar software interno', 0],
            ],
        ],
        [
            'texto' => '¿Qué tipo de carga proporcionan los cargadores V8?',
            'opciones' => [
                ['A) Carga rápida', 0],
                ['B) Carga lenta', 0],
                ['C) Carga normal', 1],
                ['D) Carga ultrarrápida', 0],
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
            'texto' => '¿A qué hace referencia la dureza 9H en un Glass Full?',
            'opciones' => [
                ['A) Resistencia al agua', 0],
                ['B) Nivel de dureza en la escala Mohs', 1],
                ['C) Compatibilidad con dispositivos', 0],
                ['D) Tipo de vidrio antirreflejo', 0],
            ],
        ],
        [
            'texto' => '¿Qué significa la letra “H” en la dureza 9H de un vidrio templado?',
            'opciones' => [
                ['A) Horas de resistencia', 0],
                ['B) Herramienta de corte', 0],
                ['C) Dureza de un lápiz', 1],
                ['D) Hidrorepelencia', 0],
            ],
        ],
        [
            'texto' => '¿En qué sucursales se pueden cortar hidrogel de tablet hasta 12.9 pulgadas?',
            'opciones' => [
                ['A) Reforma y Bella', 1],
                ['B) Reforma y Boon', 0],
                ['C) Boon y Bella', 0],
                ['D) Todas las sucursales', 0],
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
            'texto' => '¿Cuál es la garantía de todos los accesorios de CentralCell?',
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
    $nombreNorm = normalizarNombre($nombre);
    $stmt = $conn->prepare("SELECT id FROM colaboradores WHERE LOWER(nombre) = :n LIMIT 1");
    $stmt->execute([':n' => $nombreNorm]);
    $row = $stmt->fetch();
    if ($row) return (int)$row['id'];

    $stmt = $conn->prepare("INSERT INTO colaboradores (nombre) VALUES (:n)");
    $stmt->execute([':n' => $nombreNorm]);
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
    <style>
        body{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;max-width:980px;margin:0 auto;padding:24px;background:#fafafa;color:#222}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;box-shadow:0 1px 2px rgba(0,0,0,.05);margin-bottom:16px}
        h1{margin:0 0 12px}
        .muted{color:#6b7280}
        .pregunta{margin:16px 0}
        .opcion{display:flex;align-items:center;gap:8px;margin:8px 0}
        .acciones{display:flex;gap:12px;align-items:center;margin-top:16px}
        .btn{padding:10px 16px;border-radius:12px;border:1px solid #111;background:#111;color:#fff;cursor:pointer}
        .btn.secondary{background:#fff;color:#111}
        .msg{padding:10px 12px;border-radius:12px;margin:12px 0}
        .ok{background:#ecfdf5;color:#065f46;border:1px solid #34d399}
        .err{background:#fef2f2;color:#991b1b;border:1px solid #fca5a5}
        .resultado{padding:12px 14px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0}
        .correcta{color:#065f46}
        .incorrecta{color:#9a3412}
        .campo{display:flex;gap:12px;align-items:center}
        input[type=text]{padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;width:100%}
        .topbar{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}
        .badge{font-size:12px;padding:4px 8px;border-radius:999px;background:#eef2ff;color:#3730a3;border:1px solid #c7d2fe}
        .nota{font-size:13px}
        .divider{height:1px;background:#e5e7eb;margin:16px 0}
        label.req::after{content:' *';color:#dc2626}
    </style>
    <link rel="stylesheet" href="css/css.css">
    <script>
    $(function(){
        let autocompleteData = [];
        $("#nombre").autocomplete({
            source: function(request, response){
                $.ajax({
                    url: "../capacitados/buscar_colaborador.php",
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
    });
    </script>
</head>
<body>
<nav>
        <h1 id="titulo">Innovación móvil Capacitación</h1>
        <input id="checkbox2" type="checkbox">
        <label class="toggle toggle2" for="checkbox2">
            <div id="bar4" class="bars"></div>
            <div id="bar5" class="bars"></div>
            <div id="bar6" class="bars"></div>
        </label>
        <ul id="menu">
            
            <li><a href="index.php">Inicio Capacitados</a></li>
            <li><a href="material.php" >Material</a></li>
            <li><a href="examen.php">Cuestionario</a></li>
        </ul>
    </nav>
    <p>.</p><br><br><br><br><br><br>
    <div class="topbar">
        <h1>Examen – Innovación Móvil</h1>
        <span class="badge">10 preguntas de opción múltiple</span>
    </div>

    <?php if ($mensaje): ?>
        <div class="msg <?php echo $resultado ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <?php if (!$resultado): ?>
    <form method="POST" class="card" autocomplete="off">
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
            <button type="submit" class="btn">Enviar examen</button>
            <button type="reset" class="btn secondary">Limpiar</button>
            <span class="nota muted">Tu nombre se vincula con la BD automáticamente. Si no existe, se crea.</span>
        </div>
    </form>
    <?php else: ?>
        <div class="card resultado">
            <h2>Resultado</h2>
            <p><strong>Puntaje:</strong> <?php echo (int)$resultado['puntos']; ?> / <?php echo (int)$resultado['total']; ?></p>
            <div class="divider"></div>
            <ol>
                <?php foreach ($resultado['detalle'] as $d): ?>
                    <br><li>
                        <div><strong><?php echo htmlspecialchars($d['pregunta']); ?></strong></div>
                        <div class="<?php echo $d['correcta'] ? 'correcta' : 'incorrecta'; ?>"><br>
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
                <a class="btn secondary" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">Hacer otro intento</a>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
