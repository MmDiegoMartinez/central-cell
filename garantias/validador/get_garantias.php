<?php
session_start();

if (!isset($_SESSION['validador_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([]);
    exit;
}

include_once '../../funciones.php';

try {
    $conn = conectarBD();

    $sql = "SELECT 
        g.id,
        g.plows, 
        g.tipo,
        g.dpto,
        d.nombre  AS dpto_nombre,
        g.causa, 
        g.piezas, 
        s.nombre  AS sucursal,
        c.nombre  AS apasionado,
        g.fecha, 
        g.estatus,
        g.anotaciones_vendedor, 
        g.piezas_validadas, 
        g.hora, 
        g.fecha_validacion, 
        g.numero_ajuste, 
        g.anotaciones_validador,
        g.id_validador, 
        v.nombre  AS validador_nombre, 
        v.apellido AS validador_apellido,
        g.foto,
        g.dispositivo,
        g.created_at
    FROM garantia g
    LEFT JOIN validador     v ON g.id_validador = v.id
    LEFT JOIN sucursales    s ON g.sucursal = s.id
    LEFT JOIN colaboradores c ON g.apasionado = c.id
    LEFT JOIN departamento  d ON g.dpto = d.cod
    WHERE g.anotado = 1
    ORDER BY g.fecha DESC, g.id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()]);
}