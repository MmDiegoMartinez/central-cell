<?php
include_once '../../funciones.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mensaje = "";
$mensaje_tipo = "";

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

        $mensaje_tipo = "success";
        $mensaje = "Imagen registrada correctamente con ID: <strong>" . htmlspecialchars($nuevoId) . "</strong>";

    } catch (Exception $e) {
        $mensaje_tipo = "error";
        $mensaje = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Registrar Imagen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../styles.css">

    <style>
      /* ---- Ajustes puntuales que el CSS base no cubre (no se toca styles.css) ---- */

      /* Navbar horizontal (mismo patrón que validador.php) */
      .navbar{
        position:sticky;top:0;z-index:50;
        display:flex;align-items:center;justify-content:space-between;
        gap:var(--space-md);
        padding:12px var(--space-lg);
        background:var(--surface);
        border-bottom:1px solid var(--outline-variant);
      }
      .navbar-brand{
        display:flex;align-items:center;gap:10px;
        text-decoration:none;color:var(--on-surface);
      }
      .navbar-brand img{border-radius:6px;}
      .navbar-brand-text p{margin:0;line-height:1.2;}
      .navbar-links{
        display:flex;align-items:center;gap:6px;flex-wrap:wrap;
      }
      .navbar-link{
        display:flex;align-items:center;gap:6px;
        padding:8px 14px;border-radius:var(--radius-lg);
        color:var(--on-surface-variant);text-decoration:none;
        font-size:14px;font-weight:600;
        transition:all .15s ease;
      }
      .navbar-link .material-symbols-outlined{font-size:20px;}
      .navbar-link:hover{background:var(--surface-container-low);color:var(--primary);}
      .navbar-link.active{background:var(--primary);color:var(--on-primary,#fff);}
      .navbar-toggle{display:none;}

      @media (max-width:720px){
        .navbar-links{
          display:none;flex-direction:column;align-items:stretch;
          position:absolute;top:100%;left:0;right:0;
          background:var(--surface);border-bottom:1px solid var(--outline-variant);
          padding:var(--space-sm);gap:4px;
        }
        .navbar-links.open{display:flex;}
        .navbar-toggle{
          display:flex;align-items:center;justify-content:center;
          background:none;border:none;cursor:pointer;color:var(--on-surface);
        }
      }

      h1 span{color:var(--primary);}

      /* Mensaje flash */
      .flash{
        display:flex;align-items:center;gap:10px;
        padding:12px 16px;border-radius:var(--radius-lg);
        font-size:14px;font-weight:600;margin-bottom:var(--space-lg);
      }
      .flash .material-symbols-outlined{font-size:20px;}
      .flash.success{background:rgba(46,160,67,0.12);color:#2E7D32;}
      .flash.error{background:rgba(211,47,47,0.12);color:#C62828;}

      .panel{
        border:1px solid var(--outline-variant);
        border-radius:var(--radius-lg);
        background:var(--surface-container-low);
        overflow:hidden;
        max-width:560px;
      }
      .panel-header{
        padding:14px var(--space-md);
        border-bottom:1px solid var(--outline-variant);
        background:var(--surface);
      }
      .panel-title{
        font-weight:700;font-size:15px;color:var(--on-surface);
        display:flex;align-items:center;gap:8px;
      }
      .panel-body{padding:var(--space-md);}

      /* Formulario */
      .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:var(--space-md);}
      .form-group label{font-size:13px;font-weight:600;color:var(--on-surface-variant);}
      .form-group input[type="text"]{
        padding:10px 12px;
        border:1px solid var(--outline-variant);
        border-radius:var(--radius-lg);
        background:var(--surface);
        color:var(--on-surface);
        font-size:14px;font-family:inherit;
        width:100%;
      }
      .form-group input:focus{outline:none;border-color:var(--primary);}

      .btn-full{width:100%;justify-content:center;}
      .btn-accent{background:#5C6BC0;color:#fff;border-color:#5C6BC0;}
      .btn-accent:hover{filter:brightness(1.05);}
      .btn-ghost{background:transparent;color:var(--on-surface-variant);border:1px solid var(--outline-variant);}
      .btn-ghost:hover{border-color:var(--primary);color:var(--primary);}

      /* Botones de foto */
      .foto-btns{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:var(--space-sm);}
      .foto-btn{
        display:inline-flex;align-items:center;gap:8px;
        padding:10px 16px;border-radius:var(--radius-lg);
        border:1px solid var(--outline-variant);
        background:var(--surface);color:var(--on-surface);
        font-size:14px;font-weight:600;cursor:pointer;
        transition:all .15s ease;
      }
      .foto-btn:hover{border-color:var(--primary);color:var(--primary);background:var(--surface-container-low);}
      .foto-btn .material-symbols-outlined{font-size:20px;}

      .foto-estado{
        display:flex;align-items:center;gap:8px;
        padding:10px 14px;border-radius:var(--radius-lg);
        font-size:13px;font-weight:600;
        margin-bottom:var(--space-sm);
      }
      .foto-estado:empty{display:none;padding:0;margin:0;}
      .foto-estado .material-symbols-outlined{font-size:18px;}
      .foto-estado.cargando{background:rgba(245,166,35,0.15);color:#B26A00;}
      .foto-estado.cargando .material-symbols-outlined{animation:spin 1s linear infinite;}
      .foto-estado.ok{background:rgba(46,160,67,0.12);color:#2E7D32;}
      .foto-estado.error{background:rgba(211,47,47,0.12);color:#C62828;}

      @keyframes spin{ from{transform:rotate(0deg);} to{transform:rotate(360deg);} }

      #preview-img{
        display:none;
        max-width:100%;
        border-radius:var(--radius-lg);
        border:1px solid var(--outline-variant);
        margin-bottom:var(--space-md);
      }
    </style>
</head>
<body>

<!-- ===================== NAVBAR HORIZONTAL ===================== -->
<header class="navbar">
  <a href="validador.php" class="navbar-brand">
    <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="32" height="32">
    <div class="navbar-brand-text">
      <p class="text-headline-sm">Central Cell</p>
      <p class="text-label-sm" style="color:var(--outline)">Innovación Móvil</p>
    </div>
  </a>

  <button class="navbar-toggle" id="navToggle" type="button" aria-label="Abrir menú">
    <span class="material-symbols-outlined">menu</span>
  </button>

  <nav class="navbar-links" id="navLinks">
    <a href="validador.php" class="navbar-link">
      <span class="material-symbols-outlined">home</span>
      Home
    </a>
    <a href="../../kpis/modulos.html" class="navbar-link">
      <span class="material-symbols-outlined">apps</span>
      Panel de Herramientas
    </a>
  </nav>
</header>

<!-- ===================== MAIN ===================== -->
<div class="container">
  <div class="lesson">
    <div class="lesson-body">

      <span class="eyebrow">Imágenes</span>
      <h1 class="text-headline-lg" style="margin:6px 0 var(--space-lg)">Registrar <span>Imagen</span></h1>

      <?php if ($mensaje): ?>
          <div class="flash <?= htmlspecialchars($mensaje_tipo) ?>">
              <span class="material-symbols-outlined"><?= $mensaje_tipo === 'success' ? 'check_circle' : 'error' ?></span>
              <span><?= $mensaje ?></span>
          </div>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">
            <span class="material-symbols-outlined" style="font-size:18px">add_photo_alternate</span>
            Nueva imagen
          </span>
        </div>
        <div class="panel-body">
          <form method="POST" id="formImagen">

              <!-- URL oculta que llena el JS -->
              <input type="hidden" name="foto_url" id="foto_url_hidden">

              <!-- Descripción -->
              <div class="form-group">
                  <label for="descripcion">Descripción (opcional)</label>
                  <input type="text" name="descripcion" id="descripcion"
                         maxlength="200" placeholder="Ej: Foto de evidencia garantía #123">
              </div>

              <!-- Botones de foto -->
              <div class="form-group">
                  <label>Imagen</label>
                  <input type="file" id="inputFotoCamara" accept="image/*" capture="environment" style="display:none;">
                  <input type="file" id="inputFotoGaleria" accept="image/*" style="display:none;">
                  <div class="foto-btns">
                      <button type="button" class="foto-btn" onclick="document.getElementById('inputFotoCamara').click()">
                          <span class="material-symbols-outlined">photo_camera</span>
                          Tomar foto
                      </button>
                      <button type="button" class="foto-btn" onclick="document.getElementById('inputFotoGaleria').click()">
                          <span class="material-symbols-outlined">image</span>
                          Abrir galería
                      </button>
                  </div>
                  <div id="fotoEstado" class="foto-estado"></div>
                  <img id="preview-img" src="" alt="Vista previa">
              </div>

              <button type="submit" class="btn btn-primary btn-full">
                <span class="material-symbols-outlined">save</span>
                Guardar imagen
              </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
    const IMGBB_API_KEY = '1ce477aacdd4f13a74282f8746e9edcf'; // misma key que garantías

    async function subirFoto(archivo) {
        const estado = document.getElementById('fotoEstado');
        estado.innerHTML = '<span class="material-symbols-outlined">progress_activity</span> Subiendo imagen...';
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

                        estado.innerHTML = '<span class="material-symbols-outlined">check_circle</span> Imagen lista para guardar';
                        estado.className = 'foto-estado ok';
                    } else {
                        estado.innerHTML = '<span class="material-symbols-outlined">error</span> Error: ' + (data.error?.message || 'Error desconocido');
                        estado.className = 'foto-estado error';
                    }
                } catch (err) {
                    estado.innerHTML = '<span class="material-symbols-outlined">error</span> No se pudo conectar con el servidor de imágenes.';
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

<script>
  // Control del menú horizontal en móvil
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
</script>

</body>
</html>