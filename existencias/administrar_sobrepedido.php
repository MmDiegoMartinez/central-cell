<?php
require_once '../funciones.php';

/* ════════════════════════════════════════════════════════════════
   ADMINISTRAR TELÉFONOS BAJO PEDIDO  (CRUD sobre telefonos_sobrepedido)
   ════════════════════════════════════════════════════════════════ */

/* ── CONFIGURACIÓN DE ACCESO ─────────────────────────────────────
   Esta página es administrativa y por seguridad queda BLOQUEADA hasta
   que indiques qué variable de sesión identifica a un administrador
   en tu sistema (la misma que ya usan tus otras páginas de admin).
   Ejemplo:  const SP_SESION_CLAVE = 'admin_id';   // $_SESSION['admin_id']
   SP_LOGIN_URL: a dónde mandar a quien no ha iniciado sesión ('' = mostrar 403).
   ─────────────────────────────────────────────────────────────── */
const SP_SESION_CLAVE = 'validador_id';
const SP_LOGIN_URL    = 'loginvalidador.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('Cache-Control: no-store');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');

function sp_exigir_admin(): void {
    if (SP_SESION_CLAVE === '') {
        http_response_code(403);
        exit('Acceso restringido. Falta configurar la autenticación (ver SP_SESION_CLAVE al inicio de este archivo).');
    }
    if (empty($_SESSION[SP_SESION_CLAVE])) {
        if (SP_LOGIN_URL !== '') { header('Location: ' . SP_LOGIN_URL); exit; }
        http_response_code(403);
        exit('Acceso restringido.');
    }
}
sp_exigir_admin();

/* ── Utilidades ── */
if (!function_exists('sp_h')) {
    function sp_h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('sp_pdo')) {
    function sp_pdo(): PDO {
        if (function_exists('conectarBD')) {
            $c = conectarBD();
            $c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $c;
        }
        foreach (['pdo', 'conn', 'conexion', 'db', 'dbh'] as $n) {
            if (isset($GLOBALS[$n]) && $GLOBALS[$n] instanceof PDO) {
                $GLOBALS[$n]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $GLOBALS[$n];
            }
        }
        foreach (['conectarDB', 'conectarBD', 'conectar', 'getConexion', 'obtenerConexion', 'getPDO', 'conexion'] as $fn) {
            if (function_exists($fn) && (new ReflectionFunction($fn))->getNumberOfRequiredParameters() === 0) {
                $c = $fn();
                if ($c instanceof PDO) {
                    $c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    return $c;
                }
            }
        }
        throw new RuntimeException('No se encontró una conexión PDO en funciones.php');
    }
}

/* ── CSRF ── */
if (empty($_SESSION['sp_csrf'])) $_SESSION['sp_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['sp_csrf'];
function sp_csrf_ok(): bool {
    return isset($_POST['csrf'], $_SESSION['sp_csrf']) && hash_equals($_SESSION['sp_csrf'], (string)$_POST['csrf']);
}

/* ── Mensajes (flash) ── */
function sp_flash(string $tipo, string $texto): void { $_SESSION['sp_flash'] = ['tipo' => $tipo, 'texto' => $texto]; }
function sp_volver(): void { header('Location: administrar_sobrepedido.php'); exit; }

/* ── PayJoy: utilidades (independientes de las de bajo pedido) ── */
/** Misma normalización que usa catalogo.php para comparar nombres. */
function pj_norm(string $s): string {
    $s = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s) ?? ''), 'UTF-8');
    return strtr($s, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
}
function pj_limpiar_nombre(string $s): string {
    $s = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $s) ?? '';
    return trim(preg_replace('/\s+/u', ' ', $s) ?? '');
}
function pj_volver(): void { header('Location: administrar_sobrepedido.php#payjoy'); exit; }

/* ── Validaciones ── */
function sp_limpiar_nombre(string $s): string {
    $s = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $s) ?? '';
    return trim(preg_replace('/\s+/u', ' ', $s) ?? '');
}
/** Acepta "12999", "12,999.50", "$12999.5". Devuelve "12999.50" o null si no es válido / es negativo. */
function sp_parse_precio(string $raw): ?string {
    $s = str_replace([',', ' ', '$'], '', trim($raw));
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $s)) return null;
    return number_format((float)$s, 2, '.', '');
}

/* ── Estado ── */
$errores = [];
$errorDb = null;
$form    = null;      // datos del formulario (alta/edición)
$filas   = [];
$pdo     = null;

// Estado del apartado PayJoy (independiente del CRUD de bajo pedido)
$pjErrores = [];
$pjForm    = null;

try {
    $pdo = sp_pdo();
} catch (Throwable $e) {
    error_log('[admin_sobrepedido] ' . $e->getMessage());
    $errorDb = 'No se pudo conectar con la base de datos.';
}

/* ── Acciones (POST) ── */
if ($pdo && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sp_csrf_ok()) {
        sp_flash('error', 'La sesión del formulario expiró. Vuelve a intentarlo.');
        sp_volver();
    }

    $accion = (string)($_POST['accion'] ?? '');

    try {
        if ($accion === 'guardar') {
            $id      = (int)($_POST['id'] ?? 0);
            $nombre  = sp_limpiar_nombre((string)($_POST['nombre'] ?? ''));
            $precio  = sp_parse_precio((string)($_POST['precio'] ?? ''));
            // El campo oculto envía 0 y el checkbox marcado envía 1 (el último gana)
            $estatus = (($_POST['estatus'] ?? '0') === '1') ? 1 : 0;

            if ($nombre === '')                    $errores[] = 'El nombre del teléfono no puede estar vacío.';
            elseif (mb_strlen($nombre, 'UTF-8') > 150) $errores[] = 'El nombre no puede superar los 150 caracteres.';
            if ($precio === null)                  $errores[] = 'Escribe un precio válido, mayor o igual a 0 (máximo 2 decimales).';

            if (!$errores) {
                $st = $pdo->prepare('SELECT id FROM telefonos_sobrepedido WHERE nombre = ? AND id <> ? LIMIT 1');
                $st->execute([$nombre, $id]);
                if ($st->fetch()) $errores[] = 'Ya existe un teléfono con ese nombre.';
            }

            if (!$errores && $id > 0) {
                $st = $pdo->prepare('SELECT id FROM telefonos_sobrepedido WHERE id = ? LIMIT 1');
                $st->execute([$id]);
                if (!$st->fetch()) $errores[] = 'El teléfono que intentas editar ya no existe.';
            }

            if (!$errores) {
                if ($id > 0) {
                    $st = $pdo->prepare('UPDATE telefonos_sobrepedido SET nombre = ?, precio = ?, estatus = ? WHERE id = ?');
                    $st->execute([$nombre, $precio, $estatus, $id]);
                    sp_flash('ok', 'Teléfono actualizado correctamente.');
                } else {
                    $st = $pdo->prepare('INSERT INTO telefonos_sobrepedido (nombre, precio, estatus) VALUES (?, ?, ?)');
                    $st->execute([$nombre, $precio, $estatus]);
                    sp_flash('ok', 'Teléfono agregado correctamente.');
                }
                sp_volver();
            }

            // Hay errores: se vuelve a mostrar el formulario con lo que escribió el usuario
            $form = ['id' => $id, 'nombre' => $nombre, 'precio' => (string)($_POST['precio'] ?? ''), 'estatus' => $estatus];

        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                sp_flash('error', 'Registro no válido.');
            } else {
                $st = $pdo->prepare('DELETE FROM telefonos_sobrepedido WHERE id = ?');
                $st->execute([$id]);
                sp_flash($st->rowCount() ? 'ok' : 'error', $st->rowCount() ? 'Teléfono eliminado.' : 'El teléfono ya no existe.');
            }
            sp_volver();

        } elseif ($accion === 'pj_guardar') {
            // ── PayJoy: agregar / editar dispositivo no compatible ──
            $pjId     = (int)($_POST['pj_id'] ?? 0);
            $pjNombre = pj_limpiar_nombre((string)($_POST['pj_nombre'] ?? ''));

            if ($pjNombre === '')                              $pjErrores[] = 'El nombre del dispositivo no puede estar vacío.';
            elseif (mb_strlen($pjNombre, 'UTF-8') > 191)       $pjErrores[] = 'El nombre no puede superar los 191 caracteres.';

            if (!$pjErrores) {
                // Duplicados: misma comparación que catalogo.php (exacta, sin importar mayúsculas, acentos ni espacios extra)
                $st = $pdo->prepare('SELECT id, nombre_dispositivo FROM telefonos_no_payjoy');
                $st->execute();
                $existeEdit = ($pjId === 0);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    if ((int)$r['id'] === $pjId) $existeEdit = true;
                    if ((int)$r['id'] !== $pjId && pj_norm($r['nombre_dispositivo']) === pj_norm($pjNombre)) {
                        $pjErrores[] = 'Ese dispositivo ya está registrado como no compatible.';
                        break;
                    }
                }
                if (!$pjErrores && !$existeEdit) $pjErrores[] = 'El dispositivo que intentas editar ya no existe.';
            }

            if (!$pjErrores) {
                try {
                    if ($pjId > 0) {
                        $st = $pdo->prepare('UPDATE telefonos_no_payjoy SET nombre_dispositivo = ? WHERE id = ?');
                        $st->execute([$pjNombre, $pjId]);
                        sp_flash('ok', 'Dispositivo PayJoy actualizado correctamente.');
                    } else {
                        $st = $pdo->prepare('INSERT INTO telefonos_no_payjoy (nombre_dispositivo) VALUES (?)');
                        $st->execute([$pjNombre]);
                        sp_flash('ok', 'Dispositivo agregado a la lista de no compatibles con PayJoy.');
                    }
                    pj_volver();
                } catch (PDOException $e) {
                    error_log('[admin_sobrepedido][payjoy] ' . $e->getMessage());
                    if ((string)$e->getCode() === '23000') $pjErrores[] = 'Ese dispositivo ya está registrado como no compatible.';
                    else                                   $pjErrores[] = 'No se pudo guardar. Intenta de nuevo.';
                }
            }
            $pjForm = ['id' => $pjId, 'nombre' => $pjNombre];

        } elseif ($accion === 'pj_eliminar') {
            // ── PayJoy: eliminar dispositivo ──
            $pjId = (int)($_POST['pj_id'] ?? 0);
            if ($pjId <= 0) {
                sp_flash('error', 'Registro no válido.');
            } else {
                $st = $pdo->prepare('DELETE FROM telefonos_no_payjoy WHERE id = ?');
                $st->execute([$pjId]);
                sp_flash($st->rowCount() ? 'ok' : 'error', $st->rowCount() ? 'Dispositivo eliminado de la lista PayJoy.' : 'El dispositivo ya no existe.');
            }
            pj_volver();

        } else {
            sp_flash('error', 'Acción no reconocida.');
            sp_volver();
        }
    } catch (Throwable $e) {
        error_log('[admin_sobrepedido] ' . $e->getMessage());
        if ($e instanceof PDOException && (string)$e->getCode() === '23000') {
            $errores[] = 'Ya existe un teléfono con ese nombre.';
            $form = ['id' => (int)($_POST['id'] ?? 0), 'nombre' => sp_limpiar_nombre((string)($_POST['nombre'] ?? '')), 'precio' => (string)($_POST['precio'] ?? ''), 'estatus' => (($_POST['estatus'] ?? '0') === '1') ? 1 : 0];
        } else {
            sp_flash('error', 'No se pudo completar la operación. Intenta de nuevo.');
            sp_volver();
        }
    }
}

/* ── Lectura ── */
if ($pdo) {
    try {
        $st = $pdo->prepare('SELECT id, nombre, precio, estatus FROM telefonos_sobrepedido ORDER BY nombre ASC');
        $st->execute();
        $filas = $st->fetchAll(PDO::FETCH_ASSOC);

        // Modo edición (?editar=ID), solo si no estamos mostrando errores de un envío
        if ($form === null) {
            $form = ['id' => 0, 'nombre' => '', 'precio' => '', 'estatus' => 1];   // alta: activo por defecto
            $editarId = (int)($_GET['editar'] ?? 0);
            if ($editarId > 0) {
                $st = $pdo->prepare('SELECT id, nombre, precio, estatus FROM telefonos_sobrepedido WHERE id = ? LIMIT 1');
                $st->execute([$editarId]);
                $r = $st->fetch(PDO::FETCH_ASSOC);
                if ($r) {
                    $form = ['id' => (int)$r['id'], 'nombre' => $r['nombre'], 'precio' => number_format((float)$r['precio'], 2, '.', ''), 'estatus' => (int)$r['estatus']];
                } else {
                    sp_flash('error', 'El teléfono que intentas editar no existe.');
                    sp_volver();
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[admin_sobrepedido] ' . $e->getMessage());
        $errorDb = 'No se pudo leer la tabla telefonos_sobrepedido. Verifica que ya la hayas creado.';
    }
}
/* ── PayJoy: lectura ── */
$pjFilas = [];
$pjError = null;
$pjSugerencias = [];
if ($pdo) {
    try {
        $st = $pdo->prepare('SELECT id, nombre_dispositivo FROM telefonos_no_payjoy ORDER BY nombre_dispositivo ASC');
        $st->execute();
        $pjFilas = $st->fetchAll(PDO::FETCH_ASSOC);

        if ($pjForm === null) {
            $pjForm = ['id' => 0, 'nombre' => ''];
            $pjEditarId = (int)($_GET['pj_editar'] ?? 0);
            if ($pjEditarId > 0) {
                $enc = null;
                foreach ($pjFilas as $r) if ((int)$r['id'] === $pjEditarId) { $enc = $r; break; }
                if ($enc) {
                    $pjForm = ['id' => (int)$enc['id'], 'nombre' => $enc['nombre_dispositivo']];
                } else {
                    sp_flash('error', 'El dispositivo que intentas editar no existe.');
                    pj_volver();
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[admin_sobrepedido][payjoy] ' . $e->getMessage());
        $pjError = 'No se pudo leer la tabla telefonos_no_payjoy. Verifica que ya la hayas creado.';
    }

    // Sugerencias: nombres de modelo tal como los muestra catalogo.php (opcional; si falla, solo no hay sugerencias)
    if (!$pjError && function_exists('extraerDescripcionModelo')) {
        try {
            $st = $pdo->query("SELECT DISTINCT descripcion FROM existencias
                               WHERE categoria IN ('TECNOLOGIA MOVIL>SMARTPHONE>PROPIOS','TECNOLOGIA MOVIL>SMARTPHONE>BATYCELL','TECNOLOGIA MOVIL>EQUIPO BASICO','TECNOLOGIA MOVIL>SMARTWHATCH')
                               AND existencia > 0 ORDER BY descripcion");
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $d) $pjSugerencias[extraerDescripcionModelo($d)] = true;
            ksort($pjSugerencias);
        } catch (Throwable $e) {
            error_log('[admin_sobrepedido][payjoy] sugerencias: ' . $e->getMessage());
        }
    }
}
if ($pjForm === null) $pjForm = ['id' => 0, 'nombre' => ''];

if ($form === null) $form = ['id' => 0, 'nombre' => '', 'precio' => '', 'estatus' => 1];

$flash = $_SESSION['sp_flash'] ?? null;
unset($_SESSION['sp_flash']);

$totalActivos   = 0;
foreach ($filas as $f) if ((int)$f['estatus'] === 1) $totalActivos++;
$totalInactivos = count($filas) - $totalActivos;
$editando       = $form['id'] > 0;
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Administrar teléfonos bajo pedido</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="catalogo.css?v=<?php echo time(); ?>">
<style>
/* Solo componentes propios del panel; los tokens y botones vienen de catalogo.css */
.sp-admin { max-width: 1040px; margin: 0 auto; }
.sp-link-btn { width: auto; display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; text-decoration: none; white-space: nowrap; }
.sp-save { width: auto; display: inline-flex; align-items: center; gap: 6px; padding: 0 22px; height: 40px; }
a.clear-btn { text-decoration: none; }
.clear-btn.sp-danger { color: var(--danger); }
.clear-btn.sp-danger:hover { background: var(--danger-bg); border-color: var(--danger); }

.sp-panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px 22px; margin-bottom: 28px; }
.sp-panel h2 { font-size: 17px; font-weight: 700; letter-spacing: -.01em; margin-bottom: 16px; }
.sp-form { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 16px 20px; align-items: start; }
.sp-field label { display: block; font-size: 12.5px; font-weight: 500; color: var(--ink2); margin-bottom: 6px; }
.sp-input {
  width: 100%; height: 40px; padding: 0 12px;
  background: var(--surface2); border: 1px solid transparent; border-radius: 8px;
  color: var(--ink); font-size: 14px; outline: none; transition: background .15s, border-color .15s;
}
.sp-input:focus { background: var(--surface); border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
.sp-money { position: relative; }
.sp-money > span { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--ink3); pointer-events: none; }
.sp-money .sp-input { padding-left: 26px; font-variant-numeric: tabular-nums; }
.sp-check { grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--ink); cursor: pointer; width: fit-content; }
.sp-check input { width: 18px; height: 18px; accent-color: var(--accent); cursor: pointer; }
.sp-check small { color: var(--ink3); font-size: 12.5px; }
.sp-actions { grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.sp-msg { display: flex; align-items: flex-start; gap: 10px; padding: 12px 16px; border-radius: var(--radius); font-size: 13.5px; margin-bottom: 20px; }
.sp-msg.ok    { background: var(--success-soft); color: var(--success); }
.sp-msg.error { background: var(--danger-bg);    color: var(--danger); }
.sp-msg ul { margin: 0; padding-left: 18px; }

.sp-table-wrap { overflow-x: auto; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); }
.sp-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.sp-table th { text-align: left; font-weight: 500; font-size: 12px; color: var(--ink3); padding: 12px 16px; border-bottom: 1px solid var(--border); white-space: nowrap; }
.sp-table td { padding: 12px 16px; border-bottom: 1px solid var(--border); color: var(--ink); vertical-align: middle; }
.sp-table tbody tr:last-child td { border-bottom: none; }
.sp-table tbody tr:hover { background: var(--surface2); }
.sp-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 600; }
.sp-table th.num { font-weight: 500; }
.sp-table tr.inactivo .nombre, .sp-table tr.inactivo .num { color: var(--ink3); }
.sp-table .acciones { display: flex; gap: 8px; justify-content: flex-end; }
.sp-table .acciones form { margin: 0; }
.sp-estado { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; padding: 2px 10px; border-radius: 99px; white-space: nowrap; }
.sp-estado::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.sp-estado.on  { background: var(--success-soft); color: var(--success); }
.sp-estado.off { background: var(--surface2); color: var(--ink3); }
.sp-vacio { text-align: center; color: var(--ink3); padding: 32px 16px !important; }

@media (max-width: 640px) {
  .sp-form { grid-template-columns: 1fr; }
  .site-header .sp-link-btn { padding: 8px 12px; font-size: 13px; }
}
</style>
</head>
<body>

<header class="site-header">
  <div class="header-brand">
    <div class="dot"></div>
    <div><div class="brand-title"><span class="material-symbols-outlined" style="font-size:19px;">settings</span> Administrar bajo pedido</div></div>
  </div>

  <div class="search-wrap">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input type="text" id="buscador" placeholder="Buscar en la lista…" autocomplete="off">
  </div>

  <div class="header-controls">
    <a class="aviso-btn sp-link-btn" href="../garantias/validador/guardar_imagen.php"><span class="material-symbols-outlined" style="font-size:18px;">image</span> IMG</a>
    <a class="aviso-btn sp-link-btn" href="sobrepedido.php"><span class="material-symbols-outlined" style="font-size:18px;">local_shipping</span> Teléfonos sobre pedido</a>
    <a class="aviso-btn sp-link-btn" href="catalogo.php"><span class="material-symbols-outlined" style="font-size:18px;">smartphone</span> Teléfonos en stock</a>
    <button class="dark-toggle" id="darkToggle" title="Cambiar tema" aria-label="Modo oscuro"><span class="material-symbols-outlined" style="font-size:16px;">dark_mode</span></button>
  </div>
</header>

<main class="main">
<div class="sp-admin">

  <?php if ($flash): ?>
  <div class="sp-msg <?= $flash['tipo'] === 'ok' ? 'ok' : 'error' ?>" role="alert">
    <span class="material-symbols-outlined" style="font-size:18px;"><?= $flash['tipo'] === 'ok' ? 'check_circle' : 'error' ?></span>
    <span><?= sp_h($flash['texto']) ?></span>
  </div>
  <?php endif; ?>

  <?php if ($errorDb): ?>
  <div class="sp-msg error" role="alert">
    <span class="material-symbols-outlined" style="font-size:18px;">error</span>
    <span><?= sp_h($errorDb) ?></span>
  </div>
  <?php endif; ?>

  <?php if ($pdo && !$errorDb): ?>

  <div class="results-bar">
    <div class="results-info">
      <div class="rstat accent"><div class="rstat-num"><?= $totalActivos ?></div><div class="rstat-lbl">Activos</div></div>
      <div class="rstat"><div class="rstat-num"><?= $totalInactivos ?></div><div class="rstat-lbl">Inactivos</div></div>
    </div>
  </div>

  <!-- Alta / edición -->
  <section class="sp-panel" id="formulario" aria-labelledby="formTitulo">
    <h2 id="formTitulo"><?= $editando ? 'Editar teléfono' : 'Agregar teléfono' ?></h2>

    <?php if ($errores): ?>
    <div class="sp-msg error" role="alert">
      <span class="material-symbols-outlined" style="font-size:18px;">error</span>
      <ul><?php foreach ($errores as $er): ?><li><?= sp_h($er) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" action="administrar_sobrepedido.php#formulario" class="sp-form" autocomplete="off" novalidate>
      <input type="hidden" name="csrf" value="<?= sp_h($csrf) ?>">
      <input type="hidden" name="accion" value="guardar">
      <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

      <div class="sp-field">
        <label for="nombre">Nombre del teléfono</label>
        <input class="sp-input" type="text" id="nombre" name="nombre" maxlength="150" required
               value="<?= sp_h($form['nombre']) ?>" placeholder="Ej. Samsung Galaxy A56 8/256 GB">
      </div>

      <div class="sp-field">
        <label for="precio">Precio</label>
        <div class="sp-money">
          <span>$</span>
          <input class="sp-input" type="text" id="precio" name="precio" inputmode="decimal" required
                 value="<?= sp_h($form['precio']) ?>" placeholder="0.00">
        </div>
      </div>

      <label class="sp-check" for="estatus">
        <!-- El valor 0 se envía siempre; si el checkbox está marcado, su valor 1 lo sustituye -->
        <input type="hidden" name="estatus" value="0">
        <input type="checkbox" id="estatus" name="estatus" value="1" <?= (int)$form['estatus'] === 1 ? 'checked' : '' ?>>
        <span>Activo <small>(visible en el catálogo público)</small></span>
      </label>

      <div class="sp-actions">
        <button type="submit" class="aviso-btn sp-save"><?= $editando ? 'Guardar cambios' : 'Agregar teléfono' ?></button>
        <?php if ($editando): ?>
        <a class="clear-btn show" href="administrar_sobrepedido.php">Cancelar edición</a>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <!-- Listado -->
  <div class="sp-table-wrap">
    <table class="sp-table" id="tablaTelefonos">
      <thead>
        <tr><th>Teléfono</th><th class="num">Precio</th><th>Estatus</th><th></th></tr>
      </thead>
      <tbody>
      <?php if (!$filas): ?>
        <tr><td colspan="4" class="sp-vacio">Aún no hay teléfonos registrados. Agrega el primero con el formulario de arriba.</td></tr>
      <?php endif; ?>
      <?php foreach ($filas as $f): $activo = (int)$f['estatus'] === 1; ?>
        <tr class="<?= $activo ? '' : 'inactivo' ?>" data-nombre="<?= sp_h(mb_strtolower($f['nombre'], 'UTF-8')) ?>">
          <td class="nombre"><?= sp_h($f['nombre']) ?></td>
          <td class="num">$<?= number_format((float)$f['precio'], 2) ?></td>
          <td><span class="sp-estado <?= $activo ? 'on' : 'off' ?>"><?= $activo ? 'Activo' : 'Inactivo' ?></span></td>
          <td>
            <div class="acciones">
              <a class="clear-btn show" href="?editar=<?= (int)$f['id'] ?>#formulario"><span class="material-symbols-outlined" style="font-size:16px;">edit</span> Editar</a>
              <form method="post" action="administrar_sobrepedido.php" class="form-eliminar" data-nombre="<?= sp_h($f['nombre']) ?>">
                <input type="hidden" name="csrf" value="<?= sp_h($csrf) ?>">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                <button type="submit" class="clear-btn show sp-danger"><span class="material-symbols-outlined" style="font-size:16px;">delete</span> Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
        <tr id="filaSinResultados" style="display:none;"><td colspan="4" class="sp-vacio">Ningún teléfono coincide con la búsqueda.</td></tr>
      </tbody>
    </table>
  </div>

  <!-- ═══ PayJoy: dispositivos NO compatibles ═══ -->
  <section class="sp-panel" id="payjoy" aria-labelledby="pjTitulo" style="margin-top:36px;">
    <h2 id="pjTitulo">Teléfonos no compatibles con PayJoy</h2>
    <p style="font-size:13px;color:var(--ink3);margin:-8px 0 16px;">Los equipos de esta lista aparecen en el catálogo con el logotipo de PayJoy tachado. Escribe el nombre exactamente como aparece en el catálogo.</p>

    <?php if ($pjError): ?>
    <div class="sp-msg error" role="alert">
      <span class="material-symbols-outlined" style="font-size:18px;">error</span>
      <span><?= sp_h($pjError) ?></span>
    </div>
    <?php else: $pjEditando = $pjForm['id'] > 0; ?>

    <?php if ($pjErrores): ?>
    <div class="sp-msg error" role="alert">
      <span class="material-symbols-outlined" style="font-size:18px;">error</span>
      <ul><?php foreach ($pjErrores as $er): ?><li><?= sp_h($er) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="post" action="administrar_sobrepedido.php#payjoy" class="sp-form" style="grid-template-columns:minmax(0,1fr);margin-bottom:20px;" autocomplete="off" novalidate>
      <input type="hidden" name="csrf" value="<?= sp_h($csrf) ?>">
      <input type="hidden" name="accion" value="pj_guardar">
      <input type="hidden" name="pj_id" value="<?= (int)$pjForm['id'] ?>">

      <div class="sp-field">
        <label for="pj_nombre">Nombre del dispositivo</label>
        <input class="sp-input" type="text" id="pj_nombre" name="pj_nombre" maxlength="191" required list="pjModelos"
               value="<?= sp_h($pjForm['nombre']) ?>" placeholder="Ej. Samsung Galaxy A06">
        <datalist id="pjModelos">
          <?php foreach (array_keys($pjSugerencias) as $m): ?><option value="<?= sp_h($m) ?>"></option><?php endforeach; ?>
        </datalist>
      </div>

      <div class="sp-actions">
        <button type="submit" class="aviso-btn sp-save"><?= $pjEditando ? 'Guardar cambios' : 'Agregar dispositivo' ?></button>
        <?php if ($pjEditando): ?>
        <a class="clear-btn show" href="administrar_sobrepedido.php#payjoy">Cancelar edición</a>
        <?php endif; ?>
      </div>
    </form>

    <div class="sp-table-wrap">
      <table class="sp-table" id="tablaPayjoy">
        <thead><tr><th>Dispositivo no compatible</th><th></th></tr></thead>
        <tbody>
        <?php if (!$pjFilas): ?>
          <tr><td colspan="2" class="sp-vacio">Aún no hay dispositivos registrados: todos se muestran como compatibles.</td></tr>
        <?php endif; ?>
        <?php foreach ($pjFilas as $r): ?>
          <tr>
            <td class="nombre"><?= sp_h($r['nombre_dispositivo']) ?></td>
            <td>
              <div class="acciones">
                <a class="clear-btn show" href="?pj_editar=<?= (int)$r['id'] ?>#payjoy"><span class="material-symbols-outlined" style="font-size:16px;">edit</span> Editar</a>
                <form method="post" action="administrar_sobrepedido.php#payjoy" class="form-eliminar" data-nombre="<?= sp_h($r['nombre_dispositivo']) ?>">
                  <input type="hidden" name="csrf" value="<?= sp_h($csrf) ?>">
                  <input type="hidden" name="accion" value="pj_eliminar">
                  <input type="hidden" name="pj_id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="clear-btn show sp-danger"><span class="material-symbols-outlined" style="font-size:16px;">delete</span> Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <?php endif; ?>

</div>
</main>

<script>
/* ── Tema ── */
const htmlEl  = document.documentElement;
const darkBtn = document.getElementById('darkToggle');
function setTheme(t) {
  htmlEl.setAttribute('data-theme', t);
  darkBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">' + (t === 'dark' ? 'light_mode' : 'dark_mode') + '</span>';
  try { localStorage.setItem('tema', t); } catch (e) {}
}
let temaGuardado = 'light';
try { temaGuardado = localStorage.getItem('tema') || 'light'; } catch (e) {}
setTheme(temaGuardado);
darkBtn.addEventListener('click', () =>
  setTheme(htmlEl.getAttribute('data-theme') === 'dark' ? 'light' : 'dark')
);

/* ── Confirmación antes de eliminar ── */
document.querySelectorAll('.form-eliminar').forEach(f =>
  f.addEventListener('submit', e => {
    if (!confirm('¿Eliminar «' + f.dataset.nombre + '»?\nEsta acción no se puede deshacer.')) e.preventDefault();
  })
);

/* ── Búsqueda en la lista ── */
const buscador = document.getElementById('buscador');
const filas    = Array.from(document.querySelectorAll('#tablaTelefonos tbody tr[data-nombre]'));
const sinRes   = document.getElementById('filaSinResultados');
buscador.addEventListener('input', () => {
  const q = buscador.value.toLowerCase().trim();
  let n = 0;
  filas.forEach(tr => {
    const ok = !q || tr.dataset.nombre.includes(q);
    tr.style.display = ok ? '' : 'none';
    if (ok) n++;
  });
  if (sinRes) sinRes.style.display = (filas.length && !n) ? '' : 'none';
});
document.addEventListener('keydown', e => {
  if (e.key === '/' && !/INPUT|TEXTAREA/.test(document.activeElement.tagName)) { e.preventDefault(); buscador.focus(); }
});
</script>
</body>
</html>