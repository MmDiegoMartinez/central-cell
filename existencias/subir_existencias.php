
<?php
// Activar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}


$validador_id = $_SESSION['validador_id'];

// Intentar cargar funciones.php
try {
    require_once '../funciones.php';
} catch (Exception $e) {
    die("Error al cargar funciones.php: " . $e->getMessage());
}


$mensaje = '';
$tipo_mensaje = '';
$resultado = null;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
    $archivo = $_FILES['archivo_excel'];
    
    // Validar que se subió correctamente
    if ($archivo['error'] === UPLOAD_ERR_OK) {
        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        
        if ($extension === 'xlsx') {
            // Crear directorio temporal si no existe
            $dirTemp = '../temp';
            if (!is_dir($dirTemp)) {
                mkdir($dirTemp, 0777, true);
            }
            
            // Guardar archivo temporalmente
            $rutaTemporal = $dirTemp . '/' . uniqid() . '.xlsx';
            
            if (move_uploaded_file($archivo['tmp_name'], $rutaTemporal)) {
                // Procesar archivo
                $resultado = procesarArchivoExcel($rutaTemporal);
                
                // Eliminar archivo temporal
                unlink($rutaTemporal);
                
                // Establecer mensaje
                $mensaje = $resultado['mensaje'];
                $tipo_mensaje = $resultado['exito'] ? 'success' : 'error';
            } else {
                $mensaje = 'Error al guardar el archivo temporal';
                $tipo_mensaje = 'error';
            }
        } else {
            $mensaje = 'Por favor, sube un archivo con extensión .xlsx';
            $tipo_mensaje = 'error';
        }
    } else {
        $mensaje = 'Error al subir el archivo';
        $tipo_mensaje = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Existencias</title>
     <link rel="stylesheet" href="css.css?v=<?php echo time(); ?>">
 
    
</head>
<body>
    <div class="container">
        <h1>📦 Cargar Existencias</h1>
        <p class="subtitle">Importa el archivo Excel con las existencias de los almacenes</p>
        
        <div class="instructions">
            <h4>📋 Instrucciones:</h4>
            <ul>
                <li>El archivo debe ser formato .xlsx</li>
                <li>Columna A: Almacén (ej: "Central Cell Reforma")</li>
                <li>Columna E: Descripción del producto</li>
                <li>Columna H: Existencia (cantidad)</li>
                <li>Columna M: BarcodeId (código de barras)</li>
                <li>Columna Q: Publico General (precio)</li>
            </ul>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area">
                <div class="upload-icon">📄</div>
                <label for="archivo_excel" class="file-label">
                    Seleccionar archivo Excel
                </label>
                <input type="file" id="archivo_excel" name="archivo_excel" accept=".xlsx" required>
                <div class="file-name" id="fileName">Ningún archivo seleccionado</div>
            </div>
            
            <button type="submit" class="btn-primary" id="submitBtn" disabled>
                Cargar Existencias
            </button>
        </form>
        
        <?php if ($resultado && $resultado['exito']): ?>
            <div class="results">
                <h3>📊 Resultados del Proceso</h3>
                
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $resultado['registros_insertados']; ?></div>
                        <div class="stat-label">Registros Insertados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo count($resultado['registros_omitidos']); ?></div>
                        <div class="stat-label">Registros Omitidos</div>
                    </div>
                </div>
                
                <?php if (!empty($resultado['registros_omitidos'])): ?>
                    <h4 style="color: #d32f2f; margin-bottom: 10px;">⚠️ Registros Omitidos:</h4>
                    <div class="omitted-list">
                        <?php foreach ($resultado['registros_omitidos'] as $omitido): ?>
                            <div class="omitted-item">
                                <strong>Fila <?php echo $omitido['fila']; ?>:</strong>
                                <?php echo htmlspecialchars($omitido['descripcion']); ?>
                                <br>
                                <strong>Almacén:</strong> <?php echo htmlspecialchars($omitido['almacen']); ?>
                                <div class="motivo"><?php echo htmlspecialchars($omitido['motivo']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        const fileInput = document.getElementById('archivo_excel');
        const fileName = document.getElementById('fileName');
        const submitBtn = document.getElementById('submitBtn');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileName.textContent = this.files[0].name;
                submitBtn.disabled = false;
            } else {
                fileName.textContent = 'Ningún archivo seleccionado';
                submitBtn.disabled = true;
            }
        });
        
        document.getElementById('uploadForm').addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Procesando...';
        });
    </script>
</body>
</html>