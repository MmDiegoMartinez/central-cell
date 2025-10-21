<?php
// get_garantias.php
header('Content-Type: application/json');
require_once '../funciones.php'; 

try {
    $conn = conectarBD();

    $sql = "SELECT 
        g.id,
        g.plows,
        g.tipo,
        g.causa,
        g.piezas,
        s.nombre AS sucursal,  
        c.nombre AS apasionado,  
        g.fecha,
        g.estatus,
        g.anotaciones_vendedor,
        g.piezas_validadas,
        g.hora,
        g.fecha_validacion,
        g.numero_ajuste,
        g.anotaciones_validador,
        g.id_validador,
        g.created_at,
        v.nombre AS validador_nombre,
        v.apellido AS validador_apellido
    FROM garantia g
    LEFT JOIN sucursales s ON g.sucursal = s.id  
    LEFT JOIN validador v ON g.id_validador = v.id
    LEFT JOIN colaboradores c ON g.apasionado = c.id
    WHERE g.anotado = 1
    ORDER BY g.fecha DESC, g.id DESC";

    $stmt = $conn->query($sql);
    $garantias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($garantias);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    http_response_code(500);
}
?>
