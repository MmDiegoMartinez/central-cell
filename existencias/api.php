<?php
/**
 * InventaScan — API REST
 * Maneja todas las consultas a MySQL para el sistema de inventario por escaneo.
 * Uso: api.php?action=ACCION
 */

error_reporting(0);
ini_set('display_errors', 0);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once '../funciones.php';

// ── Helpers ─────────────────────────────────────────────────────────────────

function json_ok(mixed $data): never {
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

function json_err(string $msg, int $http = 400): never {
    http_response_code($http);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function get_conn(): PDO {
    try {
        return conectarBD();
    } catch (Exception $e) {
        json_err('No se pudo conectar a la base de datos', 503);
    }
}

// ── Router ───────────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? $_POST['action'] ?? '';

match ($action) {
    'check_fecha'         => action_check_fecha(),
    'get_sucursales'      => action_get_sucursales(),
    'get_categorias'      => action_get_categorias(),
    'get_productos'       => action_get_productos(),
    'lookup_plows'        => action_lookup_plows(),
    default               => json_err('Acción desconocida: ' . htmlspecialchars($action), 404),
};

// ── 1. Validar fecha de existencias ─────────────────────────────────────────

function action_check_fecha(): never {
    $conn = get_conn();

    $stmt = $conn->query("SELECT fecha FROM fechaexistencias ORDER BY id DESC LIMIT 1");
    $row  = $stmt->fetch();

    if (!$row) {
        json_err('No hay existencias cargadas. Carga el inventario antes de iniciar una auditoría.');
    }

    $fechaBD  = $row['fecha'];
    $hoyBD    = date('Y-m-d', strtotime($fechaBD));

    // Usar timezone de México para evitar desfase con servidores en UTC
    $tz       = new DateTimeZone('America/Mexico_City');
    $hoyLocal = (new DateTime('now', $tz))->format('Y-m-d');

    if ($hoyBD !== $hoyLocal) {
        json_err(
            "Las existencias no corresponden a la fecha actual ({$hoyBD}). " .
            "Actualice las existencias antes de iniciar un inventario."
        );
    }

    json_ok(['fecha' => $hoyBD]);
}

// ── 2. Sucursales disponibles en existencias ─────────────────────────────────

function action_get_sucursales(): never {
    $conn = get_conn();

    $stmt = $conn->query("
        SELECT DISTINCT e.almacen, s.nombre
        FROM existencias e
        JOIN sucursales s ON s.id = e.almacen
        WHERE e.almacen IS NOT NULL
        ORDER BY s.nombre
    ");
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        json_err('No hay sucursales con existencias registradas.');
    }

    json_ok($rows);
}

// ── 3. Árbol de categorías para una sucursal ─────────────────────────────────

function action_get_categorias(): never {
    $sucursal = intval($_GET['sucursal'] ?? 0);
    if ($sucursal <= 0) json_err('Sucursal inválida.');

    $conn = get_conn();

    $stmt = $conn->prepare("
        SELECT DISTINCT categoria
        FROM existencias
        WHERE almacen = ?
          AND categoria IS NOT NULL
          AND categoria != ''
          AND UPPER(categoria) NOT IN ('PROMOCIONES', 'N/A')
        ORDER BY categoria
    ");
    $stmt->execute([$sucursal]);
    $cats = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Categorías raíz permitidas
    $rootsPermitidos = ['INNOVACION MOVIL', 'TECNOLOGIA MOVIL'];

    // Construir árbol jerárquico
    $tree = [];
    foreach ($cats as $cat) {
        $parts = array_map('trim', explode('>', strtoupper($cat)));
        if (empty($parts[0]) || !in_array($parts[0], $rootsPermitidos)) continue;

        $ref = &$tree;
        foreach ($parts as $part) {
            if (!isset($ref[$part])) $ref[$part] = [];
            $ref = &$ref[$part];
        }
        unset($ref);
    }

    json_ok($tree);
}

// ── 4. Productos para una sucursal + categorías seleccionadas ────────────────

function action_get_productos(): never {
    $sucursal = intval($_POST['sucursal'] ?? 0);
    if ($sucursal <= 0) json_err('Sucursal inválida.');

    $cats = json_decode($_POST['categorias'] ?? '[]', true);
    if (!is_array($cats) || empty($cats)) json_err('Selecciona al menos una categoría.');

    $conn = get_conn();

    // Eliminar categorías padre cuando hay un hijo más específico seleccionado.
    $catsLimpias = [];
    foreach ($cats as $cat) {
        $esPadre = false;
        foreach ($cats as $other) {
            if ($other !== $cat && str_starts_with(strtoupper($other), strtoupper($cat) . '>')) {
                $esPadre = true;
                break;
            }
        }
        if (!$esPadre) $catsLimpias[] = $cat;
    }

    // Construir cláusula: match exacto O hijos (LIKE 'CAT>%')
    $likeParams = [];
    $likeClause = [];
    foreach ($catsLimpias as $cat) {
        $likeClause[] = '(UPPER(categoria) = ? OR UPPER(categoria) LIKE ?)';
        $exacta = strtoupper(trim($cat));
        $likeParams[] = $exacta;
        $likeParams[] = $exacta . '>%';
    }

    $sql = "
        SELECT id, descripcion, existencia,
               BarcodeId AS plows, categoria,
               publico_general, ListaSeries
        FROM existencias
        WHERE almacen = ?
          AND (" . implode(' OR ', $likeClause) . ")
        ORDER BY descripcion
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(array_merge([$sucursal], $likeParams));
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        json_err('No hay productos para la sucursal y categorías seleccionadas.');
    }

    $conSeries = 0;
    $sinSeries = 0;
    foreach ($rows as $r) {
        if (!empty($r['ListaSeries'])) $conSeries++;
        else                           $sinSeries++;
    }

    json_ok([
        'productos'  => $rows,
        'conSeries'  => $conSeries,
        'sinSeries'  => $sinSeries,
        'total'      => count($rows),
    ]);
}

// ── 5. Lookup PLOWS: busca en toda la BD (cualquier sucursal / categoría) ────
//
//  Recibe: plows (string), sucursal (int), categorias (JSON array)
//  Devuelve:
//    found:           bool — ¿existe en alguna sucursal?
//    mismatch_cat:    bool — ¿pertenece a categoría distinta en esta misma sucursal?
//    wrong_branch:    bool — ¿existe en esta sucursal pero en otra categoría?
//    descripcion      string
//    categoria_real   string
//    existencia_local int   (existencia en la sucursal auditada, puede ser 0)
//    otras_sucursales array — [{almacen, nombre, existencia, categoria}]

function action_lookup_plows(): never {
    $plows    = trim($_GET['plows'] ?? '');
    $sucursal = intval($_GET['sucursal'] ?? 0);
    $cats     = json_decode($_GET['categorias'] ?? '[]', true);

    if (!$plows || $sucursal <= 0) json_err('Parámetros inválidos.');

    $conn = get_conn();

    // Buscar en TODAS las sucursales
    $stmt = $conn->prepare("
        SELECT e.almacen, e.descripcion, e.existencia, e.categoria,
               s.nombre AS sucursal_nombre
        FROM existencias e
        JOIN sucursales s ON s.id = e.almacen
        WHERE UPPER(e.BarcodeId) = ?
        ORDER BY e.almacen = ? DESC, e.almacen
    ");
    $stmt->execute([strtoupper($plows), $sucursal]);
    $allRows = $stmt->fetchAll();

    if (empty($allRows)) {
        // No existe en ninguna parte
        json_ok(['found' => false]);
    }

    // Separar registro de la sucursal auditada vs. otras
    $localRow  = null;
    $otrasRows = [];
    foreach ($allRows as $row) {
        if ((int)$row['almacen'] === $sucursal) {
            $localRow = $row;
        } else {
            $otrasRows[] = [
                'almacen'    => $row['almacen'],
                'nombre'     => $row['sucursal_nombre'],
                'existencia' => (int)$row['existencia'],
                'categoria'  => $row['categoria'],
            ];
        }
    }

    // Determinar si la categoría real coincide con la(s) categoría(s) auditada(s)
    $categoriaReal = $localRow ? $localRow['categoria'] : ($allRows[0]['categoria'] ?? '');
    $descripcion   = $localRow ? $localRow['descripcion'] : ($allRows[0]['descripcion'] ?? '');
    $existenciaLocal = $localRow ? (int)$localRow['existencia'] : 0;

    // Verificar si categoriaReal está dentro de las categorías auditadas
    $enCategoria = false;
    foreach ((array)$cats as $cat) {
        $catUp = strtoupper(trim($cat));
        $realUp = strtoupper(trim($categoriaReal));
        if ($realUp === $catUp || str_starts_with($realUp, $catUp . '>')) {
            $enCategoria = true;
            break;
        }
    }

    json_ok([
        'found'            => true,
        'en_categoria'     => $enCategoria,
        'en_sucursal'      => $localRow !== null,
        'descripcion'      => $descripcion,
        'categoria_real'   => $categoriaReal,
        'existencia_local' => $existenciaLocal,
        'otras_sucursales' => $otrasRows,
    ]);
}