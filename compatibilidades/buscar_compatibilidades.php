<?php
require_once '../funciones.php';
header('Content-Type: application/json');

$modelo_id = intval($_GET['modelo_id'] ?? 0);
$tipo_filtro = trim($_GET['tipo'] ?? '');

if (!$modelo_id) {
    echo json_encode([]);
    exit;
}

try {
    $conn = conectarBD();

    //  Buscar modelos principales donde el modelo ingresado es compatible (y opcionalmente del tipo elegido)
    $sql_principales = "SELECT DISTINCT modelo_id FROM compatibilidades WHERE compatible_id = ?";
    $params_principales = [$modelo_id];

    if ($tipo_filtro) {
        $sql_principales .= " AND tipo = ?";
        $params_principales[] = $tipo_filtro;
    }

    $stmt = $conn->prepare($sql_principales);
    $stmt->execute($params_principales);
    $modelos_principales = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Agregamos el mismo modelo también
    $modelos_principales[] = $modelo_id;

    $placeholders = implode(',', array_fill(0, count($modelos_principales), '?'));

    //  Traer compatibilidades del tipo seleccionado únicamente
    $sql = "
        SELECT c.tipo, m2.marca, m2.modelo,
               GROUP_CONCAT(DISTINCT c.nota SEPARATOR '. ') AS nota
        FROM compatibilidades c
        JOIN modelos m2 ON c.compatible_id = m2.id
        WHERE c.modelo_id IN ($placeholders)
    ";

    $params = $modelos_principales;

    if ($tipo_filtro) {
        $sql .= " AND c.tipo = ?";
        $params[] = $tipo_filtro;
    }

    $sql .= " GROUP BY c.tipo, m2.id
              ORDER BY c.tipo, m2.marca, m2.modelo";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Buscar si el modelo ingresado es compatible en otros registros del mismo tipo
    $sql2 = "
        SELECT c.tipo, m1.marca, m1.modelo,
               GROUP_CONCAT(DISTINCT c.nota SEPARATOR '. ') AS nota
        FROM compatibilidades c
        JOIN modelos m1 ON c.modelo_id = m1.id
        WHERE c.compatible_id = ?
    ";
    $params2 = [$modelo_id];
    if ($tipo_filtro) {
        $sql2 .= " AND c.tipo = ?";
        $params2[] = $tipo_filtro;
    }
    $sql2 .= " GROUP BY c.tipo, m1.id";

    $stmt2 = $conn->prepare($sql2);
    $stmt2->execute($params2);
    $results2 = $stmt2->fetchAll();

    //  Combinar resultados y eliminar duplicados
    $all_results = array_merge($results, $results2);

    $final = [];
    foreach ($all_results as $row) {
        $key = $row['tipo'] . '|' . $row['marca'] . '|' . $row['modelo'];
        if (isset($final[$key])) {
            if (!empty($row['nota'])) {
                if (!empty($final[$key]['nota'])) {
                    $final[$key]['nota'] .= '. ' . $row['nota'];
                } else {
                    $final[$key]['nota'] = $row['nota'];
                }
            }
        } else {
            $final[$key] = [
                'tipo' => $row['tipo'],
                'modelo' => $row['marca'] . ' ' . $row['modelo'],
                'nota' => $row['nota'] ?? ''
            ];
        }
    }

    //  Ordenar resultados
    usort($final, function($a, $b){
        return $a['tipo'] <=> $b['tipo'] ?: strcmp($a['modelo'], $b['modelo']);
    });

    echo json_encode(array_values($final));

} catch (PDOException $e) {
    echo json_encode([]);
}
