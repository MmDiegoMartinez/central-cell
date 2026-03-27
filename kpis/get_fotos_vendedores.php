<?php
/**
 * get_fotos_vendedores.php
 * Devuelve todas las filas de la tabla `imagenes` como JSON.
 * El JS del analizador lo llama al cargar para tener siempre
 * las fotos actualizadas sin hardcodear nada.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // mismo dominio, pero por si acaso

include_once '../funciones.php';

try {
    $conn = conectarBD();

    // Traemos solo las filas que tienen descripcion (los vendedores)
    // Excluimos imagen-1 (payjoy) e imagen-2 (contado) que no son personas
    $stmt = $conn->prepare("
        SELECT descripcion, direccion
        FROM imagenes
        WHERE descripcion IS NOT NULL
          AND descripcion != ''
          AND id NOT IN ('imagen-1', 'imagen-2')
        ORDER BY id ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Devolver array limpio: [ { descripcion, url }, ... ]
    $resultado = array_map(fn($r) => [
        'descripcion' => $r['descripcion'],
        'url'         => $r['direccion'],
    ], $rows);

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}