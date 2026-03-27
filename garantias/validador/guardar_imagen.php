<?php
include_once '../../funciones.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = conectarBD();

        /* ── Generar ID automático (mismo patrón que garantia-merma{N}) ── */
        $stmtNum = $conn->prepare("SELECT id FROM imagenes WHERE id LIKE 'imagen-%' ORDER BY CAST(SUBSTRING(id, 8) AS UNSIGNED) DESC LIMIT 1");
        $stmtNum->execute();
        $ultimo = $stmtNum->fetchColumn();

        if ($ultimo && preg_match('/imagen-(\d+)/', $ultimo, $m)) {
            $sig = (int)$m[1] + 1;
        } else {
            $sig = 1;
        }
        $nuevoId = 'imagen-' . $sig;

        /* ── URL que llega del JS (ImgBB) ── */
        $direccion   = trim($_POST['foto_url'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (empty($direccion)) {
            throw new Exception("No se recibió ninguna imagen.");
        }

        $stmt = $conn->prepare(
            "INSERT INTO imagenes (id, descripcion, direccion) VALUES (:id, :descripcion, :direccion)"
        );
        $stmt->execute([
            ':id'          => $nuevoId,
            ':descripcion' => $descripcion ?: null,
            ':direccion'   => $direccion,
        ]);

        $mensaje = "✅ Imagen registrada correctamente con ID: <strong>" . htmlspecialchars($nuevoId) . "</strong>";

    } catch (Exception $e) {
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Imagen</title>

    <link rel="stylesheet" href="../../css.css?v=<?php echo time(); ?>">

    <style>
        /* ── Fotos (mismo estilo que registrar garantía) ── */
        .foto-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #4a90d9;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            cursor: pointer;
            font-size: .95em;
            margin-bottom: 6px;
        }
        .foto-btn:hover { background: #357abd; }
        .foto-contador   { font-size: .85em; color: #555; margin-left: 8px; }
        .foto-estado     { font-size: .85em; margin-top: 4px; }
        .foto-estado.ok       { color: #27ae60; }
        .foto-estado.error    { color: #e74c3c; }
        .foto-estado.cargando { color: #e67e22; }

        /* Vista previa de la imagen subida */
        #preview-img {
            display: none;
            margin-top: 12px;
            border-radius: 10px;
            max-width: 260px;
            max-height: 200px;
            border: 2px solid #4a90d9;
            object-fit: cover;
        }
    </style>
</head>
<body>

<nav>
    <h1 id="nombre">Innovación Móvil</h1>
    <input type="checkbox" id="check">
    <label class="bar" for="check">
        <span class="top"></span><span class="middle"></span><span class="bottom"></span>
    </label>
    <ul id="menu">
        <li>
            <a href="garantias.php" style="display:flex;align-items:center;gap:12px;">
                <span style="display:inline-flex;width:40px;height:40px;background:white;border-radius:50%;justify-content:center;align-items:center;">
                    <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" style="width:30px;height:30px;object-fit:contain;" />
                </span>
                Home
            </a>
        </li>
    </ul>
</nav>

<div class="contenedor">
    <div class="formulario">
        <h1>Registrar Imagen</h1>

        <?php if ($mensaje): ?>
            <p><?= $mensaje ?></p>
        <?php endif; ?>

        <form method="POST" id="formImagen">

            <!-- URL oculta que llena el JS -->
            <input type="hidden" name="foto_url" id="foto_url_hidden">

            <!-- Descripción -->
            <label for="descripcion">Descripción (opcional):</label>
            <input type="text" name="descripcion" id="descripcion"
                   maxlength="200" placeholder="Ej: Foto de evidencia garantía #123"><br><br>

            <!-- Botones de foto -->
            <label>Imagen:</label><br>
            <input type="file" id="inputFotoCamara" accept="image/*" capture="environment" style="display:none;">
            <input type="file" id="inputFotoGaleria" accept="image/*" style="display:none;">
            <button type="button" class="foto-btn" onclick="document.getElementById('inputFotoCamara').click()">
                📷 Tomar foto
            </button>
            <button type="button" class="foto-btn" onclick="document.getElementById('inputFotoGaleria').click()">
                🖼️ Abrir galería
            </button>
            <div id="fotoEstado" class="foto-estado"></div>
            <img id="preview-img" src="" alt="Vista previa"><br><br>

            <input type="submit" value="Guardar imagen">
        </form>
    </div>
</div>

<script>
    const IMGBB_API_KEY = '1ce477aacdd4f13a74282f8746e9edcf'; // misma key que garantías

    async function subirFoto(archivo) {
        const estado = document.getElementById('fotoEstado');
        estado.textContent = '⏳ Subiendo imagen...';
        estado.className = 'foto-estado cargando';

        const reader = new FileReader();
        return new Promise((resolve) => {
            reader.onload = async function(e) {
                const base64 = e.target.result.split(',')[1];
                const formData = new FormData();
                formData.append('key', IMGBB_API_KEY);
                formData.append('image', base64);

                try {
                    const response = await fetch('https://api.imgbb.com/1/upload', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        const url = data.data.url;
                        document.getElementById('foto_url_hidden').value = url;

                        // Vista previa
                        const prev = document.getElementById('preview-img');
                        prev.src = url;
                        prev.style.display = 'block';

                        estado.textContent = '✅ Imagen lista para guardar';
                        estado.className = 'foto-estado ok';
                    } else {
                        estado.textContent = '❌ Error: ' + (data.error?.message || 'Error desconocido');
                        estado.className = 'foto-estado error';
                    }
                } catch (err) {
                    estado.textContent = '❌ No se pudo conectar con el servidor de imágenes.';
                    estado.className = 'foto-estado error';
                }
                resolve();
            };
            reader.readAsDataURL(archivo);
        });
    }

    function manejarFoto(files, inputEl) {
        if (!files || !files[0]) return;
        subirFoto(files[0]);
        if (inputEl) inputEl.value = '';
    }

    document.getElementById('inputFotoCamara').addEventListener('change', function() {
        manejarFoto(this.files, this);
    });
    document.getElementById('inputFotoGaleria').addEventListener('change', function() {
        manejarFoto(this.files, this);
    });

    document.getElementById('formImagen').addEventListener('submit', function(e) {
        const estado = document.getElementById('fotoEstado');
        const url    = document.getElementById('foto_url_hidden').value;

        if (estado.classList.contains('cargando')) {
            alert('Espera a que termine de subir la imagen.');
            e.preventDefault(); return false;
        }
        if (!url) {
            alert('Por favor selecciona una imagen antes de guardar.');
            e.preventDefault(); return false;
        }
    });
</script>

</body>
</html>