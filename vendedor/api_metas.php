<?php
/**
 * API REST para Metas
 * Usa la función conectarBD() de funciones.php
 */

// Desactivar salida de errores en producción
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Incluir funciones (ajusta la ruta según tu estructura)
require_once __DIR__ . '/../funciones.php';

// Buffer de salida
ob_start();

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Enviar respuesta JSON y terminar
 */
function enviarJSON($success, $data = null, $error = null, $code = 200) {
    // Limpiar buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($code);
    
    $response = ['success' => $success];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($error !== null) {
        $response['error'] = $error;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    exit;
}

// ============================================
// PROCESAMIENTO DE SOLICITUDES
// ============================================

try {
    $accion = $_GET['accion'] ?? '';
    
    switch ($accion) {
        
        // ============================================
        case 'obtener_sucursales':
        // ============================================
            try {
                $conn = conectarBD(); // Usa la función de funciones.php
                
                $sql = "SELECT id, nombre, metaIM 
                        FROM sucursales 
                        WHERE estatus = 1 
                        ORDER BY nombre ASC";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $sucursales = $stmt->fetchAll();
                
                // Asegurar tipos correctos
                foreach ($sucursales as &$sucursal) {
                    $sucursal['id'] = (int)$sucursal['id'];
                    $sucursal['metaIM'] = (float)$sucursal['metaIM'];
                }
                unset($sucursal);
                
                enviarJSON(true, $sucursales);
                
            } catch (Exception $e) {
                error_log("Error en obtener_sucursales: " . $e->getMessage());
                enviarJSON(false, null, 'Error al obtener sucursales: ' . $e->getMessage(), 500);
            }
            break;
        
        // ============================================
        case 'test':
        // ============================================
            try {
                $conn = conectarBD();
                
                // Verificar conexión
                $stmt = $conn->query("SELECT COUNT(*) as total FROM sucursales");
                $resultado = $stmt->fetch();
                
                $info = [
                    'mensaje' => 'API funcionando correctamente',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'php_version' => phpversion(),
                    'total_sucursales' => (int)$resultado['total'],
                    'conexion_bd' => 'OK'
                ];
                
                // Si existe la función obtenerInfoEntorno, incluir esa info
                if (function_exists('obtenerInfoEntorno')) {
                    $info['entorno'] = obtenerInfoEntorno();
                }
                
                enviarJSON(true, $info);
                
            } catch (Exception $e) {
                error_log("Error en test: " . $e->getMessage());
                enviarJSON(false, null, 'Error de prueba: ' . $e->getMessage(), 500);
            }
            break;
        
        // ============================================
        case 'verificar_conexion':
        // ============================================
            try {
                $conn = conectarBD();
                
                // Verificar tabla
                $stmt = $conn->query("SHOW TABLES LIKE 'sucursales'");
                $tablaExiste = $stmt->rowCount() > 0;
                
                $info = [
                    'mensaje' => 'Verificación de conexión',
                    'tabla_existe' => $tablaExiste,
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                
                if ($tablaExiste) {
                    $stmt = $conn->query("SELECT COUNT(*) as total FROM sucursales");
                    $resultado = $stmt->fetch();
                    $info['total_sucursales'] = (int)$resultado['total'];
                    
                    $stmt = $conn->query("SELECT COUNT(*) as activas FROM sucursales WHERE estatus = 1");
                    $resultado = $stmt->fetch();
                    $info['sucursales_activas'] = (int)$resultado['activas'];
                }
                
                enviarJSON(true, $info);
                
            } catch (Exception $e) {
                error_log("Error en verificar_conexion: " . $e->getMessage());
                enviarJSON(false, null, 'Error de verificación: ' . $e->getMessage(), 500);
            }
            break;
        
        // ============================================
        default:
        // ============================================
            enviarJSON(false, null, 'Acción no válida. Acciones disponibles: obtener_sucursales, test, verificar_conexion', 400);
            break;
    }
    
} catch (Exception $e) {
    error_log("Error general en API: " . $e->getMessage());
    enviarJSON(false, null, 'Error del servidor', 500);
}

// Si llegamos aquí sin haber enviado respuesta, hay un error
enviarJSON(false, null, 'Error inesperado', 500);