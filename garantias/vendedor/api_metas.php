<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../funciones.php';

ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function enviarJSON($success, $data = null, $error = null, $code = 200) {
    while (ob_get_level()) ob_end_clean();
    http_response_code($code);
    $r = ['success' => $success];
    if ($data  !== null) $r['data']  = $data;
    if ($error !== null) $r['error'] = $error;
    echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
}

try {
    $accion = $_GET['accion'] ?? '';

    switch ($accion) {

        case 'obtener_sucursales':
            $conn = conectarBD();
            /* ── Ahora devuelve metaIM Y metaTM ── */
            $stmt = $conn->prepare(
                "SELECT id, nombre, metaIM, metaTM
                 FROM sucursales
                 WHERE estatus = 1
                 ORDER BY nombre ASC"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll();
            foreach ($rows as &$s) {
                $s['id']     = (int)$s['id'];
                $s['metaIM'] = (float)$s['metaIM'];
                $s['metaTM'] = (float)$s['metaTM'];
            }
            enviarJSON(true, $rows);
            break;

        case 'test':
            $conn   = conectarBD();
            $stmt   = $conn->query("SELECT COUNT(*) as total FROM sucursales");
            $res    = $stmt->fetch();
            enviarJSON(true, [
                'mensaje'           => 'API funcionando',
                'timestamp'         => date('Y-m-d H:i:s'),
                'php_version'       => phpversion(),
                'total_sucursales'  => (int)$res['total'],
            ]);
            break;

        case 'verificar_conexion':
            $conn  = conectarBD();
            $stmt  = $conn->query("SHOW TABLES LIKE 'sucursales'");
            $existe = $stmt->rowCount() > 0;
            $info  = ['tabla_existe' => $existe, 'timestamp' => date('Y-m-d H:i:s')];
            if ($existe) {
                $info['total']   = (int)$conn->query("SELECT COUNT(*) FROM sucursales")->fetchColumn();
                $info['activas'] = (int)$conn->query("SELECT COUNT(*) FROM sucursales WHERE estatus=1")->fetchColumn();
            }
            enviarJSON(true, $info);
            break;

        default:
            enviarJSON(false, null, 'Acción no válida', 400);
    }

} catch (Exception $e) {
    error_log("API metas error: " . $e->getMessage());
    enviarJSON(false, null, 'Error del servidor', 500);
}

enviarJSON(false, null, 'Error inesperado', 500);