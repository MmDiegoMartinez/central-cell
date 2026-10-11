<?php
/* =====================================================================
   Gestor de contraseñas + gestor de máquinas (una sola página)
   ---------------------------------------------------------------------
   1) Control de acceso (ANTES de cualquier salida)
   ===================================================================== */
ob_start();          // captura cualquier salida accidental (la limpian QR/PDF)
session_start();

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', '0');          // nunca mostrar errores al navegador
ini_set('log_errors', '1');

require_once '../../funciones.php';      // provee conectarBD()

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* TCPDF solo se carga cuando se necesita (QR de vista previa / PDF) */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['generar_qr']) || isset($_GET['descargar_pdf']))) {
    require_once '../../tcpdf/tcpdf.php';
    require_once '../../tcpdf/tcpdf_barcodes_2d.php';
}

/* =====================================================================
   CIFRADO DE CONTRASEÑAS (automático)
   La primera vez que se guarda una contraseña, el sistema genera solo una
   clave aleatoria de 32 bytes y la guarda en el primer lugar donde pueda
   escribir de esta lista, en una carpeta protegida con .htaccess. No tienes
   que configurar nada. (Opcional: también acepta la variable de entorno
   PASSWORDS_ENC_KEY si prefieres definirla tú.)
   IMPORTANTE: respalda ese archivo passwords.key; sin él las contraseñas
   guardadas no se pueden recuperar.
   ===================================================================== */
const RUTAS_CLAVE_PASSWORDS = [
    __DIR__ . '/../../secretos/passwords.key',   // junto a funciones.php
    __DIR__ . '/secretos/passwords.key',         // junto a este archivo
];

// <<<LOGICA
const TIPOS_MAQUINA = ['hidrogel' => 'Hidrogel', 'protection_pro' => 'Protection Pro'];
const MODOS_CODIGO  = ['ninguno', 'qr', 'barras', 'ambos'];
/* Igual que antes: Hidrogel = código de barras, Protection Pro = QR */
const CODIGO_POR_DEFECTO = ['hidrogel' => 'barras', 'protection_pro' => 'qr'];

class GmConfigCifradoException extends RuntimeException {}

/* ---------------------------------------------------------------------
   Utilidades
   --------------------------------------------------------------------- */
function gm_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = conectarBD();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function gm_fallo(string $mensaje): array {
    return ['success' => false, 'message' => $mensaje];
}

function gm_exito(string $mensaje, array $extra = []): array {
    return ['success' => true, 'message' => $mensaje] + $extra;
}

function gm_limpiarTexto(string $s): string {
    $s = preg_replace('/[\x00-\x1F\x7F]/u', '', trim($s));
    return $s ?? '';
}

function gm_tipoMaquinaValido(string $tipo): bool {
    return array_key_exists($tipo, TIPOS_MAQUINA);
}

function gm_csrfValido(): bool {
    $enviado = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($enviado) && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $enviado);
}

function gm_idUsuarioSesion(): ?int {
    $id = (int)($_SESSION['validador_id'] ?? 0);
    return $id > 0 ? $id : null;
}

/* ---------------------------------------------------------------------
   Cifrado reversible (AES-256-GCM, IV aleatorio por registro)
   Formato guardado: "v1:" + base64( iv[12] . tag[16] . ciphertext )
   --------------------------------------------------------------------- */
function gm_crearClaveAutomatica(): string {
    // Si ya hay contraseñas guardadas y la clave desapareció, NO se genera una
    // nueva (dejaría ilegibles las existentes): se avisa para restaurar el archivo.
    if ((int)gm_db()->query("SELECT COUNT(*) FROM passwords")->fetchColumn() > 0) {
        throw new GmConfigCifradoException(
            'No se encuentra la clave de cifrado (passwords.key) y ya hay contraseñas guardadas. Restaura el archivo; no se generó una nueva para no perder los datos.'
        );
    }
    $b64 = base64_encode(random_bytes(32));
    foreach (RUTAS_CLAVE_PASSWORDS as $ruta) {
        $dir = dirname($ruta);
        if (!is_dir($dir) && !@mkdir($dir, 0750, true)) {
            continue;
        }
        $ht = $dir . '/.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht,
                "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n" .
                "<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
        }
        $f = @fopen($ruta, 'x');           // 'x': nunca sobrescribe una clave existente
        if ($f === false) {
            if (is_readable($ruta)) {      // otra petición la creó justo ahora
                return trim((string)file_get_contents($ruta));
            }
            continue;
        }
        fwrite($f, $b64);
        fclose($f);
        @chmod($ruta, 0600);
        return $b64;
    }
    throw new GmConfigCifradoException(
        'No se pudo crear la clave de cifrado automáticamente: da permisos de escritura a la carpeta donde está este archivo (o a la carpeta superior).'
    );
}

function gm_claveCifrado(): string {
    static $clave = null;
    if ($clave !== null) {
        return $clave;
    }
    $b64 = getenv('PASSWORDS_ENC_KEY') ?: '';
    if ($b64 === '' && defined('PASSWORDS_ENC_KEY')) {
        $b64 = (string)constant('PASSWORDS_ENC_KEY');
    }
    if ($b64 === '') {
        foreach (RUTAS_CLAVE_PASSWORDS as $ruta) {
            if (is_readable($ruta)) {
                $b64 = trim((string)file_get_contents($ruta));
                break;
            }
        }
    }
    if ($b64 === '') {
        $b64 = gm_crearClaveAutomatica();
    }
    $raw = base64_decode($b64, true);
    if ($raw === false || strlen($raw) !== 32) {
        throw new GmConfigCifradoException('La clave de cifrado (passwords.key) está dañada o incompleta.');
    }
    return $clave = $raw;
}

function gm_cifrarSecreto(string $texto): string {
    $iv  = random_bytes(12);
    $tag = '';
    $ct  = openssl_encrypt($texto, 'aes-256-gcm', gm_claveCifrado(), OPENSSL_RAW_DATA, $iv, $tag, 'passwords.v1', 16);
    if ($ct === false) {
        throw new RuntimeException('Falló el cifrado.');
    }
    return 'v1:' . base64_encode($iv . $tag . $ct);
}

function gm_descifrarSecreto(string $almacenado): ?string {
    if (strncmp($almacenado, 'v1:', 3) !== 0) {
        return null;
    }
    $bin = base64_decode(substr($almacenado, 3), true);
    if ($bin === false || strlen($bin) < 29) {
        return null;
    }
    $pt = openssl_decrypt(
        substr($bin, 28), 'aes-256-gcm', gm_claveCifrado(), OPENSSL_RAW_DATA,
        substr($bin, 0, 12), substr($bin, 12, 16), 'passwords.v1'
    );
    return $pt === false ? null : $pt;
}

/* ---------------------------------------------------------------------
   Sucursales
   --------------------------------------------------------------------- */
function gm_obtenerSucursales(): array {
    $filas = gm_db()->query("SELECT id, nombre, estatus FROM sucursales ORDER BY nombre ASC")->fetchAll();
    foreach ($filas as &$f) {
        $f['id'] = (int)$f['id'];
        $f['estatus'] = (int)$f['estatus'];
    }
    return $filas;
}

function gm_sucursalExiste(int $id, bool $soloActiva): bool {
    $sql = "SELECT id FROM sucursales WHERE id = :id" . ($soloActiva ? " AND estatus = 1" : "") . " LIMIT 1";
    $st = gm_db()->prepare($sql);
    $st->execute([':id' => $id]);
    return (bool)$st->fetch();
}

/* =====================================================================
   GESTOR DE MÁQUINAS
   ===================================================================== */
function gm_obtenerMaquinas(): array {
    $filas = gm_db()->query(
        "SELECT m.id, m.sucursal_id, s.nombre AS sucursal_nombre, m.tipo, m.identificador, m.codigos
         FROM maquinas m
         JOIN sucursales s ON s.id = m.sucursal_id
         ORDER BY s.nombre ASC, m.tipo ASC"
    )->fetchAll();
    foreach ($filas as &$f) {
        $f['id'] = (int)$f['id'];
        $f['sucursal_id'] = (int)$f['sucursal_id'];
    }
    return $filas;
}

/* Crea ($id = 0) o edita ($id > 0). En edición solo cambian identificador y
   códigos: la sucursal únicamente cambia por traspaso. */
function gm_guardarMaquina(int $id, int $sucursalId, string $tipo, string $identificador, string $codigos): array {
    $identificador = gm_limpiarTexto($identificador);
    if ($identificador === '') {
        return gm_fallo('El identificador no puede estar vacío.');
    }
    if (mb_strlen($identificador) > 100) {
        return gm_fallo('El identificador es demasiado largo (máx. 100 caracteres).');
    }
    if (!in_array($codigos, MODOS_CODIGO, true)) {
        return gm_fallo('Opción de códigos inválida.');
    }
    if (in_array($codigos, ['barras', 'ambos'], true) && preg_match('/[^\x20-\x7E]/', $identificador)) {
        return gm_fallo('Para generar código de barras el identificador solo puede usar caracteres ASCII (sin acentos ni ñ).');
    }

    $pdo = gm_db();
    try {
        if ($id > 0) {
            $st = $pdo->prepare("SELECT id FROM maquinas WHERE id = :id LIMIT 1");
            $st->execute([':id' => $id]);
            if (!$st->fetch()) {
                return gm_fallo('La máquina no existe.');
            }
            $st = $pdo->prepare("SELECT id FROM maquinas WHERE identificador = :i AND id <> :id LIMIT 1");
            $st->execute([':i' => $identificador, ':id' => $id]);
            if ($st->fetch()) {
                return gm_fallo('Ya existe otra máquina con ese identificador.');
            }
            $st = $pdo->prepare("UPDATE maquinas SET identificador = :i, codigos = :c WHERE id = :id");
            $st->execute([':i' => $identificador, ':c' => $codigos, ':id' => $id]);
            return gm_exito('Máquina actualizada correctamente.');
        }

        if (!gm_tipoMaquinaValido($tipo)) {
            return gm_fallo('Tipo de máquina inválido.');
        }
        if (!gm_sucursalExiste($sucursalId, true)) {
            return gm_fallo('La sucursal no existe o está inactiva.');
        }
        $st = $pdo->prepare("SELECT identificador FROM maquinas WHERE sucursal_id = :s AND tipo = :t LIMIT 1");
        $st->execute([':s' => $sucursalId, ':t' => $tipo]);
        if ($st->fetch()) {
            return gm_fallo('Esa sucursal ya tiene una máquina ' . TIPOS_MAQUINA[$tipo] . '. Edítala o usa el traspaso.');
        }
        $st = $pdo->prepare("SELECT id FROM maquinas WHERE identificador = :i LIMIT 1");
        $st->execute([':i' => $identificador]);
        if ($st->fetch()) {
            return gm_fallo('Ya existe una máquina con ese identificador.');
        }
        $st = $pdo->prepare(
            "INSERT INTO maquinas (sucursal_id, tipo, identificador, codigos) VALUES (:s, :t, :i, :c)"
        );
        $st->execute([':s' => $sucursalId, ':t' => $tipo, ':i' => $identificador, ':c' => $codigos]);
        return gm_exito('Máquina registrada correctamente.');
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return gm_fallo('Ya existe una máquina con ese identificador o esa sucursal ya tiene una de ese tipo.');
        }
        throw $e;
    }
}

function gm_eliminarMaquina(int $id): array {
    $pdo = gm_db();
    $st = $pdo->prepare("SELECT id FROM maquinas WHERE id = :id LIMIT 1");
    $st->execute([':id' => $id]);
    if (!$st->fetch()) {
        return gm_fallo('La máquina no existe.');
    }
    $pdo->prepare("DELETE FROM maquinas WHERE id = :id")->execute([':id' => $id]);
    return gm_exito('Máquina eliminada correctamente.');
}

/* ---------------------------------------------------------------------
   TRASPASO EN CADENA (misma semántica que la versión anterior)
   cadena = [origen, destino1, destino2, ...]
   - El origen se queda sin máquina de ese tipo.
   - Cada sucursal recibe la máquina que tenía la anterior de la cadena.
   - Si la última sucursal ya tenía una máquina de ese tipo, esa máquina
     se descarta definitivamente (es la opción "eliminar").
   - Todo ocurre en UNA transacción con las filas bloqueadas.
   Antes la máquina era un valor en una columna y "viajaba" de fila en fila;
   ahora la fila de `maquinas` cambia de sucursal. El resultado visible en
   cada sucursal es idéntico.
   --------------------------------------------------------------------- */
function gm_traspasarMaquina(string $tipo, array $cadena): array {
    if (!gm_tipoMaquinaValido($tipo)) {
        return gm_fallo('Tipo de máquina inválido.');
    }

    $cadena = array_map('intval', $cadena);
    $cadena = array_values(array_filter($cadena, fn($id) => $id > 0));

    if (count($cadena) < 2) {
        return gm_fallo('La cadena de traspaso debe tener al menos origen y destino.');
    }
    if (count($cadena) !== count(array_unique($cadena))) {
        return gm_fallo('No se puede repetir la misma sucursal dentro de la cadena.');
    }

    $pdo = gm_db();

    try {
        $pdo->beginTransaction();
        $ph = implode(',', array_fill(0, count($cadena), '?'));

        // Bloquea las sucursales involucradas (serializa traspasos simultáneos)
        $st = $pdo->prepare("SELECT id FROM sucursales WHERE id IN ({$ph}) ORDER BY id FOR UPDATE");
        $st->execute($cadena);
        $existentes = array_map('intval', array_column($st->fetchAll(), 'id'));

        foreach ($cadena as $id) {
            if (!in_array($id, $existentes, true)) {
                $pdo->rollBack();
                return gm_fallo("La sucursal con id {$id} no existe.");
            }
        }

        // Máquinas de ese tipo que hay actualmente en esas sucursales
        $st = $pdo->prepare(
            "SELECT id, sucursal_id FROM maquinas WHERE tipo = ? AND sucursal_id IN ({$ph}) ORDER BY id FOR UPDATE"
        );
        $st->execute(array_merge([$tipo], $cadena));
        $maquinaDe = [];
        foreach ($st->fetchAll() as $fila) {
            $maquinaDe[(int)$fila['sucursal_id']] = (int)$fila['id'];
        }

        $total  = count($cadena);
        $origen = $cadena[0];
        if (!isset($maquinaDe[$origen])) {
            $pdo->rollBack();
            return gm_fallo('La sucursal de origen no tiene una máquina asignada de ese tipo.');
        }
        // Solo el último eslabón puede estar vacío (lo cierra la cadena)
        for ($i = 1; $i < $total - 1; $i++) {
            if (!isset($maquinaDe[$cadena[$i]])) {
                $pdo->rollBack();
                return gm_fallo('Solo la última sucursal de la cadena puede estar sin máquina.');
            }
        }

        $ultimo = $cadena[$total - 1];
        if (isset($maquinaDe[$ultimo])) {
            // La máquina que ya tenía el último destino se descarta
            $pdo->prepare("DELETE FROM maquinas WHERE id = ?")->execute([$maquinaDe[$ultimo]]);
        }

        // De atrás hacia adelante, para que cada destino ya esté libre
        // cuando llegue su nueva máquina (respeta UNIQUE sucursal+tipo).
        $mover = $pdo->prepare("UPDATE maquinas SET sucursal_id = ? WHERE id = ?");
        for ($i = $total - 2; $i >= 0; $i--) {
            $mover->execute([$cadena[$i + 1], $maquinaDe[$cadena[$i]]]);
        }

        $pdo->commit();
        return gm_exito('Traspaso realizado correctamente.');

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error en traspasarMaquina: ' . $e->getMessage());
        return gm_fallo('Ocurrió un error al realizar el traspaso.');
    }
}

/* =====================================================================
   GESTOR DE CONTRASEÑAS
   ===================================================================== */
function gm_obtenerPasswords(): array {
    // Nunca se envía password_cifrado al navegador en el listado
    $filas = gm_db()->query(
        "SELECT p.id, p.sucursal_id, s.nombre AS sucursal_nombre, p.servicio, p.usuario, p.url
         FROM passwords p
         JOIN sucursales s ON s.id = p.sucursal_id
         ORDER BY p.servicio ASC"
    )->fetchAll();
    foreach ($filas as &$f) {
        $f['id'] = (int)$f['id'];
        $f['sucursal_id'] = (int)$f['sucursal_id'];
    }
    return $filas;
}

function gm_normalizarUrl(string $url): ?string {
    if (!preg_match('#^[a-z][a-z0-9+.\-]*://#i', $url)) {
        $url = 'https://' . $url;
    }
    if (mb_strlen($url) > 255 || !filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    $esquema = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($esquema, ['http', 'https'], true) ? $url : null;
}

/* Crea ($id = 0) o edita ($id > 0). En edición, contraseña vacía = conservar la actual. */
function gm_guardarPassword(int $id, int $sucursalId, string $servicio, string $usuario, string $password, string $url): array {
    $servicio = gm_limpiarTexto($servicio);
    $usuario  = gm_limpiarTexto($usuario);
    $url      = gm_limpiarTexto($url);

    if ($servicio === '' || mb_strlen($servicio) > 100) {
        return gm_fallo('El nombre del servicio es obligatorio (máx. 100 caracteres).');
    }
    if ($usuario === '' || mb_strlen($usuario) > 150) {
        return gm_fallo('El usuario es obligatorio (máx. 150 caracteres).');
    }
    if ($id === 0 && $password === '') {
        return gm_fallo('La contraseña es obligatoria.');
    }
    if (mb_strlen($password) > 200) {
        return gm_fallo('La contraseña es demasiado larga (máx. 200 caracteres).');
    }
    $urlFinal = null;
    if ($url !== '') {
        $urlFinal = gm_normalizarUrl($url);
        if ($urlFinal === null) {
            return gm_fallo('La página web no es una URL válida (http/https, máx. 255 caracteres).');
        }
    }

    $pdo = gm_db();
    $uid = gm_idUsuarioSesion();

    if ($id === 0) {
        if (!gm_sucursalExiste($sucursalId, true)) {
            return gm_fallo('La sucursal no existe o está inactiva.');
        }
        $st = $pdo->prepare(
            "INSERT INTO passwords (sucursal_id, servicio, usuario, password_cifrado, url, creado_por, actualizado_por)
             VALUES (:s, :sv, :u, :p, :url, :c1, :c2)"
        );
        $st->execute([
            ':s' => $sucursalId, ':sv' => $servicio, ':u' => $usuario,
            ':p' => gm_cifrarSecreto($password), ':url' => $urlFinal, ':c1' => $uid, ':c2' => $uid,
        ]);
        return gm_exito('Contraseña guardada correctamente.');
    }

    $st = $pdo->prepare("SELECT sucursal_id FROM passwords WHERE id = :id LIMIT 1");
    $st->execute([':id' => $id]);
    $actual = $st->fetch();
    if (!$actual) {
        return gm_fallo('El registro no existe.');
    }
    if ((int)$actual['sucursal_id'] !== $sucursalId && !gm_sucursalExiste($sucursalId, true)) {
        return gm_fallo('La sucursal no existe o está inactiva.');
    }

    $sql = "UPDATE passwords SET sucursal_id = :s, servicio = :sv, usuario = :u, url = :url, actualizado_por = :c";
    $par = [':s' => $sucursalId, ':sv' => $servicio, ':u' => $usuario, ':url' => $urlFinal, ':c' => $uid, ':id' => $id];
    if ($password !== '') {
        $sql .= ", password_cifrado = :p";
        $par[':p'] = gm_cifrarSecreto($password);
    }
    $pdo->prepare($sql . " WHERE id = :id")->execute($par);
    return gm_exito('Registro actualizado correctamente.');
}

function gm_eliminarPassword(int $id): array {
    $pdo = gm_db();
    $st = $pdo->prepare("SELECT id FROM passwords WHERE id = :id LIMIT 1");
    $st->execute([':id' => $id]);
    if (!$st->fetch()) {
        return gm_fallo('El registro no existe.');
    }
    $pdo->prepare("DELETE FROM passwords WHERE id = :id")->execute([':id' => $id]);
    return gm_exito('Registro eliminado correctamente.');
}

function gm_revelarPassword(int $id): array {
    $st = gm_db()->prepare("SELECT password_cifrado FROM passwords WHERE id = :id LIMIT 1");
    $st->execute([':id' => $id]);
    $fila = $st->fetch();
    if (!$fila) {
        return gm_fallo('El registro no existe.');
    }
    $plano = gm_descifrarSecreto((string)$fila['password_cifrado']);
    if ($plano === null) {
        error_log("revelarPassword: no se pudo descifrar el registro {$id} (¿clave distinta?)");
        return gm_fallo('No se pudo descifrar la contraseña. Verifica la clave de cifrado del servidor.');
    }
    return gm_exito('', ['password' => $plano]);
}
// >>>LOGICA

/* =====================================================================
   QR / PDF  (mismo generador TCPDF para vista previa y PDF)
   ===================================================================== */
function gm_generarQrPng(string $texto, int $escalaPx = 8): string {
    $barcode = new TCPDF2DBarcode($texto, 'QRCODE,M');
    return $barcode->getBarcodePngData($escalaPx, $escalaPx, [0, 0, 0]);
}

/* Vista previa del QR (se pide como imagen al servidor) */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['generar_qr'])) {
    $valor = gm_limpiarTexto(is_string($_GET['valor'] ?? null) ? $_GET['valor'] : '');
    if ($valor === '' || mb_strlen($valor) > 100) {
        http_response_code(400);
        exit;
    }
    $png = gm_generarQrPng($valor);
    while (ob_get_level() > 0) { ob_end_clean(); }   // descarta cualquier salida previa
    header('Content-Type: image/png');
    header('Cache-Control: private, no-store');
    echo $png;
    exit;
}

/* $filtro: hidrogel | protection_pro | todas  (también acepta los nombres
   anteriores maquinahgl / maquinapp). Opcionales: id de máquina o de sucursal. */
function gm_generarPdfCodigos(string $filtro, int $idMaquina = 0, int $idSucursal = 0): void {
    $alias = ['maquinahgl' => 'hidrogel', 'maquinapp' => 'protection_pro'];
    $filtro = $alias[$filtro] ?? $filtro;
    if ($filtro !== 'todas' && !gm_tipoMaquinaValido($filtro)) {
        http_response_code(400);
        exit('Tipo de máquina inválido.');
    }

    $maquinas = array_values(array_filter(gm_obtenerMaquinas(), function ($m) use ($filtro, $idMaquina, $idSucursal) {
        if ($idMaquina > 0 && $m['id'] !== $idMaquina) return false;
        if ($idSucursal > 0 && $m['sucursal_id'] !== $idSucursal) return false;
        return $filtro === 'todas' || $m['tipo'] === $filtro;
    }));

    $titulo = $filtro === 'todas'
        ? 'Códigos de Máquinas'
        : 'Códigos de Máquinas ' . TIPOS_MAQUINA[$filtro];

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    while (ob_get_level() > 0) { ob_end_clean(); }   // TCPDF falla si hay salida pendiente
    $pdf->SetCreator('Panel de Administración');
    $pdf->SetAuthor('Innovación Móvil');
    $pdf->SetTitle($titulo);
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, $titulo, 0, 1, 'C');
    $pdf->Ln(6);

    if (empty($maquinas)) {
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'No hay máquinas registradas para este filtro.', 0, 1, 'C');
    } else {
        foreach ($maquinas as $m) {
            $codigo = (string)$m['identificador'];
            $modo   = $m['codigos'];
            $conBarras = in_array($modo, ['barras', 'ambos'], true);
            $conQr     = in_array($modo, ['qr', 'ambos'], true);

            // Reservar espacio para no cortar la tarjeta entre páginas
            $umbral = $conQr ? 235 : ($conBarras ? 245 : 265);
            if ($pdf->GetY() > $umbral) {
                $pdf->AddPage();
            }

            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, $m['sucursal_nombre'], 0, 1, 'L');

            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'Identificador: ' . $codigo . '  (' . TIPOS_MAQUINA[$m['tipo']] . ')', 0, 1, 'L');

            $y = $pdf->GetY() + 2;
            $alto = 0;

            if ($conBarras) {
                // Código de barras 1D (Code 128), como antes en Hidrogel
                $pdf->write1DBarcode($codigo, 'C128', 15, $y, 90, 20, 0.4, [
                    'position'    => '',
                    'border'      => false,
                    'padding'     => 2,
                    'fgcolor'     => [0, 0, 0],
                    'bgcolor'     => false,
                    'text'        => true,
                    'font'        => 'helvetica',
                    'fontsize'    => 8,
                    'stretchtext' => 4,
                ], 'N');
                $alto = max($alto, 26);
            }
            if ($conQr) {
                // QR (2D), como antes en Protection Pro, con gm_generarQrPng()
                $qrPng = gm_generarQrPng($codigo, 10);
                $pdf->Image('@' . $qrPng, $conBarras ? 115 : 15, $y, 32, 32, 'PNG');
                $alto = max($alto, 36);
            }

            $pdf->SetY($y + $alto);
            $pdf->Ln(4);
        }
    }

    $prefijo = $filtro === 'todas' ? 'codigos_maquinas_' : 'codigos_' . str_replace('_', '', $filtro) . '_';
    $pdf->Output($prefijo . date('Ymd_His') . '.pdf', 'D');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['descargar_pdf'])) {
    gm_generarPdfCodigos(
        is_string($_GET['descargar_pdf']) ? $_GET['descargar_pdf'] : '',
        (int)($_GET['id'] ?? 0),
        (int)($_GET['sucursal'] ?? 0)
    );
}

/* =====================================================================
   Router AJAX (POST + CSRF)
   ===================================================================== */
function gm_postTexto(string $clave): string {
    $v = $_POST[$clave] ?? '';
    return is_string($v) ? $v : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    if (!gm_csrfValido()) {
        http_response_code(403);
        echo json_encode(gm_fallo('Token de seguridad inválido. Recarga la página.'));
        exit;
    }

    $accion = gm_postTexto('accion');
    $respuesta = gm_fallo('Acción no reconocida.');

    try {
        switch ($accion) {
            case 'listar_sucursales':
                $respuesta = ['success' => true, 'data' => gm_obtenerSucursales()];
                break;

            case 'listar_maquinas':
                $respuesta = ['success' => true, 'data' => gm_obtenerMaquinas()];
                break;

            case 'guardar_maquina':
                $respuesta = gm_guardarMaquina(
                    (int)gm_postTexto('id'),
                    (int)gm_postTexto('sucursal'),
                    gm_postTexto('tipo'),
                    gm_postTexto('identificador'),
                    gm_postTexto('codigos')
                );
                break;

            case 'eliminar_maquina':
                $respuesta = gm_eliminarMaquina((int)gm_postTexto('id'));
                break;

            case 'traspasar_maquina':
                $cadena = json_decode(gm_postTexto('cadena') ?: '[]', true);
                $respuesta = gm_traspasarMaquina(gm_postTexto('tipo'), is_array($cadena) ? $cadena : []);
                break;

            case 'listar_passwords':
                $respuesta = ['success' => true, 'data' => gm_obtenerPasswords()];
                break;

            case 'guardar_password':
                $respuesta = gm_guardarPassword(
                    (int)gm_postTexto('id'),
                    (int)gm_postTexto('sucursal'),
                    gm_postTexto('servicio'),
                    gm_postTexto('usuario'),
                    gm_postTexto('password'),
                    gm_postTexto('url')
                );
                break;

            case 'eliminar_password':
                $respuesta = gm_eliminarPassword((int)gm_postTexto('id'));
                break;

            case 'revelar_password':
                $respuesta = gm_revelarPassword((int)gm_postTexto('id'));
                break;
        }
    } catch (GmConfigCifradoException $e) {
        $respuesta = gm_fallo($e->getMessage());
    } catch (Throwable $e) {
        // Nunca se devuelve el mensaje interno (podría contener SQL o datos)
        error_log('maquinas.php [' . $accion . ']: ' . $e->getMessage());
        $codigo = ($e instanceof PDOException && !empty($e->errorInfo[0])) ? ' SQLSTATE ' . $e->errorInfo[0] . '/' . ($e->errorInfo[1] ?? '') : '';
        $respuesta = gm_fallo('Ocurrió un error interno (' . get_class($e) . $codigo . '). Inténtalo de nuevo.');
    }

    echo json_encode($respuesta);
    exit;
}

/* Carga inicial. Las contraseñas NUNCA viajan en esta carga. */
try {
    $datosIniciales = [
        'sucursales' => gm_obtenerSucursales(),
        'maquinas'   => gm_obtenerMaquinas(),
        'passwords'  => gm_obtenerPasswords(),
    ];
} catch (Throwable $e) {
    error_log('maquinas.php carga inicial: ' . $e->getMessage());
    http_response_code(500);
    exit('No se pudieron cargar los datos. Verifica que las tablas "maquinas" y "passwords" existan en la base de datos que usa conectarBD(). Detalle en el log de errores del servidor.');
}
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Contraseñas y Máquinas por Sucursal</title>
<link rel="stylesheet" href="../../styles.css">
<!-- Código de barras (vista previa en pantalla). El QR se pide como imagen al
     servidor (?generar_qr) con el mismo motor TCPDF que arma el PDF. -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.6/JsBarcode.all.min.js"></script>
<style>
    /* =============================================================
       Estilos del módulo (los existentes se conservan tal cual;
       al final se añaden solo las clases nuevas: tabs, filtros,
       badge, selector de tipo, opciones de código e impresión).
       Todo reutiliza los tokens de styles.css.
       ============================================================= */

    .page-header{
        display:flex;align-items:center;justify-content:space-between;
        gap:var(--space-md);flex-wrap:wrap;
        margin-bottom:var(--space-xl);
    }
    .page-header h1{margin:0;}
    .page-header p{margin:4px 0 0;color:var(--on-surface-variant);}

    .toolbar{
        display:flex;gap:var(--space-md);flex-wrap:wrap;
        margin-bottom:var(--space-lg);
    }

    /* Tarjeta contenedora de la tabla, reutilizando el look de .lesson */
    .machines-card{
        background:var(--surface-container-lowest);
        border:1px solid rgba(196,197,215,0.4);
        border-radius:var(--radius-xl);
        box-shadow:0 1px 2px rgba(17,28,45,0.04);
        overflow:hidden;
    }

    .table-wrap{
        width:100%;
        max-width:100%;
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
        scrollbar-width:thin;
        scrollbar-color:var(--outline) var(--surface-container-low);
    }
    .table-wrap::-webkit-scrollbar{height:10px;}
    .table-wrap::-webkit-scrollbar-track{background:var(--surface-container-low);}
    .table-wrap::-webkit-scrollbar-thumb{
        background:var(--outline);border-radius:var(--radius-full);
    }
    .table-wrap::-webkit-scrollbar-thumb:hover{background:var(--primary);}

    table.machines-table{width:100%;border-collapse:collapse;min-width:680px;}
    table.machines-table thead th{
        text-align:left;padding:14px var(--space-lg);
        background:var(--surface-container-low);
        color:var(--on-surface-variant);
        font-size:12px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;
        border-bottom:1px solid var(--outline-variant);
        white-space:nowrap;
    }
    table.machines-table tbody td{
        padding:14px var(--space-lg);
        border-bottom:1px solid var(--outline-variant);
        font-size:14px;vertical-align:middle;
    }
    table.machines-table tbody tr:last-child td{border-bottom:none;}
    table.machines-table tbody tr:hover{background:var(--surface-container-low);}

    .sucursal-nombre{font-weight:600;color:var(--on-surface);white-space:nowrap;}

    /* Aviso de scroll horizontal, solo visible en pantallas angostas */
    .scroll-hint{
        display:none;align-items:center;gap:6px;
        font-size:12px;color:var(--on-surface-variant);
        padding:8px var(--space-lg);
        background:var(--surface-container-low);
        border-bottom:1px solid var(--outline-variant);
    }
    .scroll-hint .material-symbols-outlined{font-size:16px;}

    /* Chip que representa un código asignado o vacío */
    .code-chip{
        display:inline-flex;align-items:center;gap:6px;
        padding:6px 12px;border-radius:var(--radius-full);
        font-size:13px;font-weight:600;cursor:pointer;
        border:1px solid transparent;
        transition:opacity .15s ease,max-width .15s ease;
        max-width:100%;
        white-space:nowrap;
    }
    .code-chip:hover{opacity:0.85;}
    .code-chip.filled{
        background:var(--secondary-container);
        color:var(--on-secondary-container);
    }
    .code-chip.empty{
        background:var(--surface-container-high);
        color:var(--on-surface-variant);
        border:1px dashed var(--outline-variant);
        cursor:default;
    }
    .code-chip .material-symbols-outlined{font-size:16px;}

    .row-actions{display:flex;gap:8px;flex-wrap:wrap;}
    .icon-btn{
        display:inline-flex;align-items:center;justify-content:center;
        width:36px;height:36px;border-radius:var(--radius-lg);
        background:var(--surface-container-high);
        color:var(--on-surface-variant);
        transition:background .15s ease,color .15s ease,transform .15s ease;
        flex:0 0 auto;
    }
    .icon-btn:hover{background:var(--primary);color:var(--on-primary);transform:translateY(-1px);}
    .icon-btn .material-symbols-outlined{font-size:18px;}

    /* ---------- Modales ---------- */
    .modal-overlay{
        display:none;position:fixed;inset:0;z-index:200;
        background:rgba(17,28,45,0.45);
        align-items:center;justify-content:center;padding:var(--space-md);
    }
    .modal-overlay.show{display:flex;}
    .modal-box{
        background:var(--surface-container-lowest);
        border-radius:var(--radius-xl);
        width:100%;max-width:440px;
        box-shadow:0 12px 40px rgba(17,28,45,0.25);
        max-height:90vh;overflow-y:auto;
        animation:modalIn .18s ease;
    }
    @keyframes modalIn{from{opacity:0;transform:translateY(8px) scale(.98);}to{opacity:1;transform:none;}}
    .modal-head{
        display:flex;align-items:center;justify-content:space-between;
        padding:var(--space-lg) var(--space-lg) 0;
        gap:var(--space-sm);
    }
    .modal-head h3{margin:0;word-break:break-word;}
    .modal-close{color:var(--on-surface-variant);font-size:22px;flex:0 0 auto;}
    .modal-body{padding:var(--space-lg);}
    .modal-foot{
        display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;
        padding:0 var(--space-lg) var(--space-lg);
    }

    .form-group{margin-bottom:var(--space-md);}
    .form-group label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--on-surface-variant);}
    .form-control{
        width:100%;padding:10px 12px;border-radius:var(--radius-lg);
        border:1px solid var(--outline-variant);background:var(--surface);
        font-size:14px;color:var(--on-surface);font-family:inherit;
        box-sizing:border-box;
    }
    .form-control:focus{outline:2px solid var(--primary);outline-offset:1px;}

    .field-error{color:var(--error);font-size:13px;margin-top:6px;display:none;}
    .field-error.show{display:block;}

    /* Breadcrumb de la cadena de traspaso */
    .chain-breadcrumb{
        display:flex;flex-wrap:wrap;align-items:center;gap:6px;
        margin-bottom:var(--space-md);
        padding:var(--space-sm) var(--space-md);
        background:var(--surface-container-low);
        border-radius:var(--radius-lg);
        font-size:13px;
    }
    .chain-item{
        padding:4px 10px;border-radius:var(--radius-full);
        background:var(--primary-container);color:var(--on-primary-container);
        font-weight:600;
        max-width:100%;
        overflow-wrap:break-word;
    }
    .chain-arrow{color:var(--outline);font-size:16px;}

    .conflict-box{
        background:var(--error-container);color:var(--on-error-container);
        border-radius:var(--radius-lg);padding:var(--space-md);
        font-size:13px;margin-bottom:var(--space-md);
        display:flex;gap:10px;align-items:flex-start;
    }
    .conflict-box .material-symbols-outlined{font-size:20px;flex:0 0 auto;}

    /* Toast de notificación */
    #toast-container{
        position:fixed;bottom:var(--space-lg);right:var(--space-lg);
        left:var(--space-lg);
        z-index:300;display:flex;flex-direction:column;align-items:flex-end;gap:8px;
    }
    .toast{
        display:flex;align-items:center;gap:10px;
        padding:12px 16px;border-radius:var(--radius-lg);
        font-size:14px;font-weight:600;
        box-shadow:0 6px 20px rgba(17,28,45,0.2);
        animation:toastIn .18s ease;
        max-width:320px;
        width:100%;
        box-sizing:border-box;
    }
    @keyframes toastIn{from{opacity:0;transform:translateX(16px);}to{opacity:1;transform:none;}}
    .toast.success{background:var(--secondary-container);color:var(--on-secondary-container);}
    .toast.error{background:var(--error-container);color:var(--on-error-container);}

    .empty-state{padding:var(--space-2xl);text-align:center;color:var(--on-surface-variant);}

    /* Navbar horizontal (idéntico al del Comparador de Series) */
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
    min-width:0;
}
.navbar-brand img{border-radius:6px;flex:0 0 auto;}
.navbar-brand-text{min-width:0;}
.navbar-brand-text p{margin:0;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
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

/* =============================================================
   RESPONSIVE
   ============================================================= */
.container{
    max-width:1100px;
    width:100%;
    box-sizing:border-box;
}

@media (max-width:960px){
    .container{padding-left:var(--space-md)!important;padding-right:var(--space-md)!important;}
}

@media (max-width:720px){
    .navbar-links{
        display:none;flex-direction:column;align-items:stretch;
        position:absolute;top:100%;left:0;right:0;
        background:var(--surface);border-bottom:1px solid var(--outline-variant);
        padding:var(--space-sm);gap:4px;
        box-shadow:0 8px 20px rgba(17,28,45,0.12);
    }
    .navbar-links.open{display:flex;}
    .navbar-toggle{
        display:flex;align-items:center;justify-content:center;
        background:none;border:none;cursor:pointer;color:var(--on-surface);
        flex:0 0 auto;
    }
    .navbar-brand-text p:first-child{font-size:14px;}
    .navbar-brand-text p:last-child{display:none;}

    .container{padding:var(--space-lg) var(--space-sm)!important;}

    .page-header{
        flex-direction:column;align-items:stretch;
        margin-bottom:var(--space-lg);
    }
    .page-header .btn{width:100%;justify-content:center;}

    .toolbar{flex-direction:column;}
    .toolbar .btn{width:100%;justify-content:center;box-sizing:border-box;}

    .scroll-hint{display:flex;}

    .modal-box{max-width:100%;margin:0;}
    .modal-foot{flex-direction:column-reverse;}
    .modal-foot .btn{width:100%;justify-content:center;}

    #toast-container{left:var(--space-sm);right:var(--space-sm);bottom:var(--space-sm);align-items:stretch;}
    .toast{max-width:100%;}

    .row-actions{gap:6px;}
    .icon-btn{width:32px;height:32px;}
}

@media (max-width:420px){
    .page-header h1{font-size:20px;}
    table.machines-table thead th,
    table.machines-table tbody td{padding:12px var(--space-md);}
}

/* =============================================================
   NUEVO: pestañas, filtros, badge, contraseña, selector de tipo,
   opciones de código y vista de códigos
   ============================================================= */
.tabs{
    display:flex;gap:6px;flex-wrap:wrap;
    margin-bottom:var(--space-lg);
    border-bottom:1px solid var(--outline-variant);
}
.tab-btn{
    display:inline-flex;align-items:center;gap:8px;
    padding:10px 18px;margin-bottom:-1px;
    background:none;border:none;border-bottom:3px solid transparent;
    cursor:pointer;font:inherit;font-size:14px;font-weight:700;
    color:var(--on-surface-variant);
    transition:color .15s ease,border-color .15s ease;
}
.tab-btn:hover{color:var(--primary);}
.tab-btn.active{color:var(--primary);border-bottom-color:var(--primary);}
.tab-btn .material-symbols-outlined{font-size:20px;}
.panel{display:none;}
.panel.active{display:block;}

.filters{
    display:flex;gap:var(--space-md);flex-wrap:wrap;align-items:center;
    margin-bottom:var(--space-lg);
}
.filters .form-control{width:auto;flex:1 1 180px;min-width:160px;}
.filters .search{flex:2 1 260px;}

.badge{
    display:inline-flex;align-items:center;
    padding:4px 10px;border-radius:var(--radius-full);
    background:var(--primary-container);color:var(--on-primary-container);
    font-size:12px;font-weight:700;white-space:nowrap;
}
.tab-btn .badge{padding:2px 8px;}

.cell-link{color:var(--primary);text-decoration:none;word-break:break-all;}
.cell-link:hover{text-decoration:underline;}

.pw-cell{display:flex;align-items:center;gap:6px;}
.pw-text{
    font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
    font-size:13px;min-width:90px;word-break:break-all;
}

.segmented{
    display:flex;gap:4px;padding:4px;margin-bottom:var(--space-md);
    background:var(--surface-container-low);border-radius:var(--radius-lg);
}
.segmented button{
    flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;
    padding:8px 10px;border:none;border-radius:var(--radius-lg);
    background:none;cursor:pointer;font:inherit;font-size:13px;font-weight:700;
    color:var(--on-surface-variant);
}
.segmented button.active{background:var(--primary);color:var(--on-primary,#fff);}
.segmented .material-symbols-outlined{font-size:18px;}

.radio-group{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.radio-opt{
    display:flex;align-items:center;gap:8px;
    padding:9px 12px;border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);cursor:pointer;
    font-size:14px;color:var(--on-surface);
}
.radio-opt input{margin:0;}
.radio-opt:has(input:checked){
    border-color:var(--primary);
    background:var(--primary-container);color:var(--on-primary-container);
}

.codes-view{display:flex;gap:var(--space-lg);flex-wrap:wrap;justify-content:center;align-items:center;}
.codes-view svg{max-width:100%;height:auto;}
.input-hint{font-size:12px;color:var(--on-surface-variant);margin-top:6px;}

/* ---------- Impresión ---------- */
@media print{
    .navbar,.page-header,.tabs,.toolbar,.filters,.scroll-hint,#toast-container,
    .col-acciones,.pw-actions{display:none!important;}
    body.print-passwords #panel-maquinas{display:none!important;}
    body.print-maquinas #panel-passwords{display:none!important;}
    body.print-codes .main,
    body.print-codes .modal-overlay:not(#modal-ver-codigos){display:none!important;}
    body.print-codes #modal-ver-codigos.show{
        display:block;position:static;background:none;padding:0;
    }
    body.print-codes #modal-ver-codigos .modal-box{
        box-shadow:none;max-height:none;overflow:visible;max-width:100%;animation:none;
    }
    body.print-codes #modal-ver-codigos .modal-foot,
    body.print-codes #modal-ver-codigos .modal-close{display:none!important;}
    .machines-card{box-shadow:none;}
}
</style>
</head>
<body>

<div class="main" style="margin-left:0;">
    <header class="navbar">
        <a href="../../garantias/validador/validador.php" class="navbar-brand">
            <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="32" height="32">
            <div class="navbar-brand-text">
                <p class="text-headline-sm">Central Cell</p>
                <p class="text-label-sm" style="color:var(--outline)">Contraseñas y Máquinas</p>
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

    <div class="container" style="padding:var(--space-xl) var(--space-md);">

        <div class="page-header">
            <div>
                <h1 class="text-headline-md">Contraseñas y máquinas por sucursal</h1>
                <p class="text-body-sm">Guarda credenciales de servicios y administra las máquinas Hidrogel y Protection Pro.</p>
            </div>
            <div style="display:flex;gap:var(--space-sm);flex-wrap:wrap;">
                <button class="btn btn-outline" id="btn-abrir-traspaso" type="button">
                    <span class="material-symbols-outlined">sync_alt</span>
                    Traspaso de máquinas
                </button>
                <button class="btn btn-primary" id="btn-nuevo" type="button">
                    <span class="material-symbols-outlined">add</span>
                    Nuevo registro
                </button>
            </div>
        </div>

        <div class="tabs" role="tablist">
            <button class="tab-btn active" id="tab-passwords" role="tab" type="button" data-tab="passwords">
                <span class="material-symbols-outlined">key</span>
                Contraseñas <span class="badge" id="count-passwords">0</span>
            </button>
            <button class="tab-btn" id="tab-maquinas" role="tab" type="button" data-tab="maquinas">
                <span class="material-symbols-outlined">precision_manufacturing</span>
                Máquinas <span class="badge" id="count-maquinas">0</span>
            </button>
        </div>

        <!-- ===================== PANEL: CONTRASEÑAS ===================== -->
        <section class="panel active" id="panel-passwords">
            <div class="filters">
                <input type="search" class="form-control search" id="buscar-passwords" placeholder="Buscar por servicio, usuario o página web…" autocomplete="off">
                <select class="form-control" id="filtro-sucursal-passwords"></select>
                <button class="btn btn-outline" type="button" id="btn-imprimir-passwords">
                    <span class="material-symbols-outlined">print</span> Imprimir listado
                </button>
            </div>

            <div class="machines-card">
                <div class="scroll-hint">
                    <span class="material-symbols-outlined">swipe</span>
                    Desliza hacia los lados para ver toda la tabla
                </div>
                <div class="table-wrap">
                    <table class="machines-table" style="min-width:820px;">
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th>Servicio</th>
                                <th>Usuario</th>
                                <th>Contraseña</th>
                                <th>Página web</th>
                                <th class="col-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-passwords"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ===================== PANEL: MÁQUINAS ===================== -->
        <section class="panel" id="panel-maquinas">
            <div class="toolbar">
                <a class="btn btn-outline" id="pdf-hidrogel" href="?descargar_pdf=hidrogel" target="_blank" rel="noopener">
                    <span class="material-symbols-outlined">barcode_reader</span>
                    PDF Hidrogel
                </a>
                <a class="btn btn-outline" id="pdf-pp" href="?descargar_pdf=protection_pro" target="_blank" rel="noopener">
                    <span class="material-symbols-outlined">qr_code_2</span>
                    PDF Protection Pro
                </a>
                <a class="btn btn-outline" id="pdf-todas" href="?descargar_pdf=todas" target="_blank" rel="noopener">
                    <span class="material-symbols-outlined">picture_as_pdf</span>
                    PDF de todas
                </a>
                <button class="btn btn-outline" type="button" id="btn-imprimir-maquinas">
                    <span class="material-symbols-outlined">print</span> Imprimir listado
                </button>
            </div>

            <div class="filters">
                <input type="search" class="form-control search" id="buscar-maquinas" placeholder="Buscar por identificador o sucursal…" autocomplete="off">
                <select class="form-control" id="filtro-sucursal-maquinas"></select>
                <select class="form-control" id="filtro-tipo-maquinas">
                    <option value="">Todos los tipos</option>
                    <option value="hidrogel">Hidrogel</option>
                    <option value="protection_pro">Protection Pro</option>
                </select>
            </div>

            <div class="machines-card">
                <div class="scroll-hint">
                    <span class="material-symbols-outlined">swipe</span>
                    Desliza hacia los lados para ver toda la tabla
                </div>
                <div class="table-wrap">
                    <table class="machines-table">
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th>Tipo</th>
                                <th>Identificador</th>
                                <th>Códigos</th>
                                <th class="col-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-maquinas"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- =========================================================
     MODAL: Nuevo / editar registro (formulario dinámico por tipo)
     ========================================================= -->
<div class="modal-overlay" id="modal-registro">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-head">
            <h3 class="text-headline-sm" id="registro-titulo">Nuevo registro</h3>
            <button class="modal-close" type="button" onclick="cerrarModal('modal-registro')" aria-label="Cerrar">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="segmented" id="registro-selector">
                <button type="button" data-tipo-registro="password">
                    <span class="material-symbols-outlined">key</span> Contraseña
                </button>
                <button type="button" data-tipo-registro="maquina">
                    <span class="material-symbols-outlined">precision_manufacturing</span> Máquina
                </button>
            </div>

            <!-- Formulario: contraseña -->
            <div id="form-password">
                <div class="form-group">
                    <label for="pw-sucursal">Sucursal</label>
                    <select class="form-control" id="pw-sucursal"></select>
                </div>
                <div class="form-group">
                    <label for="pw-servicio">Nombre del servicio</label>
                    <input type="text" class="form-control" id="pw-servicio" maxlength="100" placeholder="Ej. PayJoy, Google, Sistema interno" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="pw-usuario">Usuario</label>
                    <input type="text" class="form-control" id="pw-usuario" maxlength="150" placeholder="Usuario, correo o identificador" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="pw-password">Contraseña</label>
                    <div style="display:flex;gap:8px;">
                        <input type="password" class="form-control" id="pw-password" maxlength="200" autocomplete="new-password">
                        <button type="button" class="icon-btn" id="pw-ver" title="Mostrar / ocultar" style="width:42px;height:42px;">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                    <div class="input-hint" id="pw-hint" style="display:none;">Déjala vacía para conservar la contraseña actual.</div>
                </div>
                <div class="form-group">
                    <label for="pw-url">Página web (opcional)</label>
                    <input type="text" class="form-control" id="pw-url" maxlength="255" placeholder="https://ejemplo.com" autocomplete="off">
                </div>
            </div>

            <!-- Formulario: máquina -->
            <div id="form-maquina" style="display:none;">
                <div class="form-group">
                    <label for="mq-sucursal">Sucursal</label>
                    <select class="form-control" id="mq-sucursal"></select>
                    <div class="input-hint" id="mq-sucursal-hint" style="display:none;">Para cambiar de sucursal usa el traspaso de máquinas.</div>
                </div>
                <div class="form-group">
                    <label for="mq-tipo">Tipo de máquina</label>
                    <select class="form-control" id="mq-tipo">
                        <option value="hidrogel">Hidrogel</option>
                        <option value="protection_pro">Protection Pro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="mq-ident">Identificador</label>
                    <input type="text" class="form-control" id="mq-ident" maxlength="100" placeholder="Ej. SS5110425092745" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Códigos a generar</label>
                    <div class="radio-group">
                        <label class="radio-opt"><input type="radio" name="mq-codigos" value="qr"> Código QR</label>
                        <label class="radio-opt"><input type="radio" name="mq-codigos" value="barras"> Código de barras</label>
                        <label class="radio-opt"><input type="radio" name="mq-codigos" value="ambos"> Ambos</label>
                        <label class="radio-opt"><input type="radio" name="mq-codigos" value="ninguno"> Ninguno</label>
                    </div>
                </div>
                <div class="form-group" style="text-align:center;">
                    <label>Vista previa</label>
                    <div id="mq-preview" style="display:flex;justify-content:center;align-items:center;min-height:80px;background:var(--surface-container-low);border-radius:var(--radius-lg);padding:var(--space-md);overflow-x:auto;"></div>
                </div>
            </div>

            <div class="field-error" id="registro-error"></div>
        </div>
        <div class="modal-foot">
            <button class="btn btn-outline" type="button" onclick="cerrarModal('modal-registro')">Cancelar</button>
            <button class="btn btn-primary" type="button" id="btn-guardar-registro">
                <span class="material-symbols-outlined">save</span> Guardar
            </button>
        </div>
    </div>
</div>

<!-- =========================================================
     MODAL: Ver / imprimir códigos de una máquina
     ========================================================= -->
<div class="modal-overlay" id="modal-ver-codigos">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-head">
            <h3 class="text-headline-sm" id="vc-titulo">Códigos</h3>
            <button class="modal-close" type="button" onclick="cerrarModal('modal-ver-codigos')" aria-label="Cerrar">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body" style="text-align:center;">
            <p class="text-body-sm" id="vc-detalle" style="color:var(--on-surface-variant);margin-top:0;"></p>
            <div id="vc-codigos" style="display:flex;justify-content:center;align-items:center;min-height:100px;padding:var(--space-md);overflow-x:auto;"></div>
        </div>
        <div class="modal-foot">
            <a class="btn btn-outline" id="vc-pdf" href="#" target="_blank" rel="noopener">
                <span class="material-symbols-outlined">picture_as_pdf</span> PDF
            </a>
            <button class="btn btn-primary" type="button" id="vc-imprimir">
                <span class="material-symbols-outlined">print</span> Imprimir
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Confirmación genérica -->
<div class="modal-overlay" id="modal-confirmar">
    <div class="modal-box" style="max-width:380px;">
        <div class="modal-body" style="padding-top:var(--space-lg);">
            <h3 class="text-headline-sm" id="confirmar-titulo">¿Confirmar acción?</h3>
            <p class="text-body-sm" id="confirmar-texto" style="color:var(--on-surface-variant);"></p>
        </div>
        <div class="modal-foot">
            <button class="btn btn-outline" type="button" onclick="cerrarModal('modal-confirmar')">Cancelar</button>
            <button class="btn btn-primary" type="button" id="btn-confirmar-aceptar">Confirmar</button>
        </div>
    </div>
</div>

<!-- MODAL: Traspaso de máquinas (flujo con cascada) -->
<div class="modal-overlay" id="modal-traspaso">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-head">
            <h3 class="text-headline-sm">Traspaso de máquinas</h3>
            <button class="modal-close" type="button" onclick="cerrarModal('modal-traspaso')" aria-label="Cerrar">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="chain-breadcrumb" id="traspaso-breadcrumb" style="display:none;"></div>
            <div id="traspaso-contenido"></div>
        </div>
        <div class="modal-foot" id="traspaso-footer">
            <button class="btn btn-outline" type="button" onclick="cerrarModal('modal-traspaso')">Cancelar</button>
        </div>
    </div>
</div>

<div id="toast-container"></div>

<script>
/* =====================================================================
   Estado en memoria. Las contraseñas NUNCA están aquí: solo se piden al
   servidor (POST + CSRF) cuando el usuario las revela o copia.
   ===================================================================== */
const CSRF = <?= json_encode($_SESSION['csrf_token']) ?>;
const DATOS = <?= json_encode($datosIniciales, $jsonFlags) ?>;

let sucursalesTodas = DATOS.sucursales;
let maquinasData    = DATOS.maquinas;
let passwordsData   = DATOS.passwords;

const TIPO_LABEL = { hidrogel: 'Hidrogel', protection_pro: 'Protection Pro' };
const CODIGO_POR_DEFECTO = { hidrogel: 'barras', protection_pro: 'qr' };
const CODIGO_LABEL = { qr: 'QR', barras: 'Barras', ambos: 'QR + Barras', ninguno: 'Sin códigos' };

const $ = (id) => document.getElementById(id);

function sucursalesActivas() { return sucursalesTodas.filter(s => s.estatus === 1); }

/* ---------------------------------------------------------------------
   Helpers
   --------------------------------------------------------------------- */
function escapeHtml(texto) {
    return String(texto ?? '').replace(/[&<>"']/g, c => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));
}

async function postAccion(accion, datos = {}) {
    const body = new URLSearchParams({ accion, csrf: CSRF, ...datos });
    try {
        const resp = await fetch(location.pathname, { method: 'POST', body, credentials: 'same-origin' });
        if (resp.redirected && /loginvalidador/i.test(resp.url)) {
            window.location.href = resp.url;
            return { success: false, message: 'La sesión expiró.' };
        }
        return await resp.json();
    } catch (e) {
        return { success: false, message: 'No se pudo completar la operación. Si la sesión expiró, recarga la página.' };
    }
}

function mostrarToast(mensaje, tipo = 'success') {
    const el = document.createElement('div');
    el.className = `toast ${tipo}`;
    const icono = document.createElement('span');
    icono.className = 'material-symbols-outlined';
    icono.textContent = tipo === 'success' ? 'check_circle' : 'error';
    const texto = document.createElement('span');
    texto.textContent = mensaje;
    el.append(icono, texto);
    $('toast-container').appendChild(el);
    setTimeout(() => el.remove(), 4000);
}

function abrirModal(id) { $(id).classList.add('show'); }
function cerrarModal(id) { $(id).classList.remove('show'); }

function nombreSucursal(id) {
    const s = sucursalesTodas.find(s => s.id === id);
    return s ? s.nombre : '—';
}

function opcionesSucursales(excluirIds = [], soloActivas = true) {
    return (soloActivas ? sucursalesActivas() : sucursalesTodas)
        .filter(s => !excluirIds.includes(s.id))
        .map(s => `<option value="${s.id}">${escapeHtml(s.nombre)}</option>`)
        .join('');
}

function confirmar(titulo, texto, alAceptar) {
    $('confirmar-titulo').textContent = titulo;
    $('confirmar-texto').textContent = texto;
    $('btn-confirmar-aceptar').onclick = async () => {
        cerrarModal('modal-confirmar');
        await alAceptar();
    };
    abrirModal('modal-confirmar');
}

/* =====================================================================
   Pestañas
   ===================================================================== */
let tabActual = 'passwords';

function cambiarTab(tab) {
    tabActual = tab;
    ocultarTodasPasswords();
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    $('panel-passwords').classList.toggle('active', tab === 'passwords');
    $('panel-maquinas').classList.toggle('active', tab === 'maquinas');
    history.replaceState(null, '', '#' + tab);
}
document.querySelectorAll('.tab-btn').forEach(b => b.addEventListener('click', () => cambiarTab(b.dataset.tab)));

/* =====================================================================
   Filtros
   ===================================================================== */
function llenarFiltrosSucursal() {
    const opts = '<option value="">Todas las sucursales</option>' + opcionesSucursales([], false);
    for (const id of ['filtro-sucursal-passwords', 'filtro-sucursal-maquinas']) {
        const sel = $(id), previo = sel.value;
        sel.innerHTML = opts;
        sel.value = previo;
    }
}

function coincide(texto, termino) {
    return String(texto ?? '').toLowerCase().includes(termino);
}

/* =====================================================================
   CONTRASEÑAS: tabla
   ===================================================================== */
const MASCARA = '••••••••••';
const revelados = new Map();   // id -> timeout para volver a ocultar

function ocultarPassword(id) {
    clearTimeout(revelados.get(id));
    revelados.delete(id);
    const span = document.querySelector(`[data-pw-text="${id}"]`);
    if (span) span.textContent = MASCARA;
    const icono = document.querySelector(`[data-pw-icon="${id}"]`);
    if (icono) icono.textContent = 'visibility';
}

function ocultarTodasPasswords() {
    [...revelados.keys()].forEach(ocultarPassword);
}

function pintarPasswords() {
    ocultarTodasPasswords();
    const termino = $('buscar-passwords').value.trim().toLowerCase();
    const suc = parseInt($('filtro-sucursal-passwords').value, 10) || 0;

    const lista = passwordsData.filter(p =>
        (!suc || p.sucursal_id === suc) &&
        (!termino || coincide(p.servicio, termino) || coincide(p.usuario, termino) ||
            coincide(p.url, termino) || coincide(p.sucursal_nombre, termino))
    );

    $('count-passwords').textContent = passwordsData.length;
    const tbody = $('tbody-passwords');
    if (!lista.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="empty-state">${
            passwordsData.length ? 'Ningún registro coincide con la búsqueda.' : 'Aún no hay contraseñas guardadas.'
        }</td></tr>`;
        return;
    }

    tbody.innerHTML = lista.map(p => {
        const urlOk = p.url && /^https?:\/\//i.test(p.url);
        const web = !p.url ? '<span style="color:var(--on-surface-variant);">—</span>'
            : urlOk ? `<a class="cell-link" href="${escapeHtml(p.url)}" target="_blank" rel="noopener noreferrer">${escapeHtml(p.url)}</a>`
            : escapeHtml(p.url);
        return `
        <tr data-id="${p.id}">
            <td class="sucursal-nombre">${escapeHtml(p.sucursal_nombre)}</td>
            <td><strong>${escapeHtml(p.servicio)}</strong></td>
            <td>${escapeHtml(p.usuario)}</td>
            <td>
                <div class="pw-cell">
                    <span class="pw-text" data-pw-text="${p.id}">${MASCARA}</span>
                    <span class="pw-actions" style="display:inline-flex;gap:6px;">
                        <button class="icon-btn" type="button" title="Mostrar / ocultar" data-accion="revelar" data-id="${p.id}">
                            <span class="material-symbols-outlined" data-pw-icon="${p.id}">visibility</span>
                        </button>
                        <button class="icon-btn" type="button" title="Copiar contraseña" data-accion="copiar" data-id="${p.id}">
                            <span class="material-symbols-outlined">content_copy</span>
                        </button>
                    </span>
                </div>
            </td>
            <td>${web}</td>
            <td class="col-acciones">
                <div class="row-actions">
                    <button class="icon-btn" type="button" title="Editar" data-accion="editar-pw" data-id="${p.id}">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="icon-btn" type="button" title="Eliminar" data-accion="eliminar-pw" data-id="${p.id}">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

async function refrescarPasswords() {
    const r = await postAccion('listar_passwords');
    if (r.success) { passwordsData = r.data; pintarPasswords(); }
}

async function alternarPassword(id) {
    if (revelados.has(id)) { ocultarPassword(id); return; }
    const r = await postAccion('revelar_password', { id });
    if (!r.success) { mostrarToast(r.message, 'error'); return; }
    const span = document.querySelector(`[data-pw-text="${id}"]`);
    if (!span) return;
    span.textContent = r.password;            // textContent: nunca se interpreta como HTML
    const icono = document.querySelector(`[data-pw-icon="${id}"]`);
    if (icono) icono.textContent = 'visibility_off';
    revelados.set(id, setTimeout(() => ocultarPassword(id), 15000));  // se vuelve a ocultar sola
}

async function copiarTexto(texto) {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(texto);
            return true;
        }
    } catch (e) { /* se intenta el método alterno */ }
    const ta = document.createElement('textarea');
    ta.value = texto;
    ta.style.cssText = 'position:fixed;opacity:0;';
    document.body.appendChild(ta);
    ta.select();
    let ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    ta.remove();
    return ok;
}

async function copiarPassword(id) {
    const r = await postAccion('revelar_password', { id });
    if (!r.success) { mostrarToast(r.message, 'error'); return; }
    const ok = await copiarTexto(r.password);
    mostrarToast(ok ? 'Contraseña copiada al portapapeles.' : 'No se pudo copiar. Usa "Mostrar" y cópiala manualmente.', ok ? 'success' : 'error');
}

$('tbody-passwords').addEventListener('click', (e) => {
    const el = e.target.closest('[data-accion]');
    if (!el) return;
    const id = parseInt(el.dataset.id, 10);
    switch (el.dataset.accion) {
        case 'revelar':     alternarPassword(id); break;
        case 'copiar':      copiarPassword(id); break;
        case 'editar-pw':   abrirRegistro('password', id); break;
        case 'eliminar-pw': {
            const p = passwordsData.find(x => x.id === id);
            if (!p) return;
            confirmar('Eliminar contraseña',
                `¿Seguro que deseas eliminar "${p.servicio}" (${p.sucursal_nombre})? Esta acción no se puede deshacer.`,
                async () => {
                    const r = await postAccion('eliminar_password', { id });
                    mostrarToast(r.message, r.success ? 'success' : 'error');
                    if (r.success) await refrescarPasswords();
                });
            break;
        }
    }
});

['buscar-passwords', 'filtro-sucursal-passwords'].forEach(id => {
    $(id).addEventListener('input', pintarPasswords);
    $(id).addEventListener('change', pintarPasswords);
});

/* =====================================================================
   MÁQUINAS: tabla
   ===================================================================== */
function chipCodigos(m) {
    if (m.codigos === 'ninguno') {
        return '<span class="code-chip empty">Sin códigos</span>';
    }
    const icono = m.codigos === 'barras' ? 'barcode_reader' : 'qr_code_2';
    return `<span class="code-chip filled" data-accion="ver-codigos" data-id="${m.id}" title="Ver códigos">
                <span class="material-symbols-outlined">${icono}</span>${CODIGO_LABEL[m.codigos]}
            </span>`;
}

function pintarMaquinas() {
    const termino = $('buscar-maquinas').value.trim().toLowerCase();
    const suc = parseInt($('filtro-sucursal-maquinas').value, 10) || 0;
    const tipo = $('filtro-tipo-maquinas').value;

    const lista = maquinasData.filter(m =>
        (!suc || m.sucursal_id === suc) && (!tipo || m.tipo === tipo) &&
        (!termino || coincide(m.identificador, termino) || coincide(m.sucursal_nombre, termino))
    );

    $('count-maquinas').textContent = maquinasData.length;
    actualizarEnlacesPdf();

    const tbody = $('tbody-maquinas');
    if (!lista.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="empty-state">${
            maquinasData.length ? 'Ninguna máquina coincide con la búsqueda.' : 'Aún no hay máquinas registradas.'
        }</td></tr>`;
        return;
    }

    tbody.innerHTML = lista.map(m => `
        <tr data-id="${m.id}">
            <td class="sucursal-nombre">${escapeHtml(m.sucursal_nombre)}</td>
            <td><span class="badge">${escapeHtml(TIPO_LABEL[m.tipo] || m.tipo)}</span></td>
            <td><strong>${escapeHtml(m.identificador)}</strong></td>
            <td>${chipCodigos(m)}</td>
            <td class="col-acciones">
                <div class="row-actions">
                    <button class="icon-btn" type="button" title="Ver / imprimir códigos" data-accion="ver-codigos" data-id="${m.id}">
                        <span class="material-symbols-outlined">qr_code_2</span>
                    </button>
                    <button class="icon-btn" type="button" title="Editar" data-accion="editar-mq" data-id="${m.id}">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button class="icon-btn" type="button" title="Traspasar máquina" data-accion="traspasar" data-id="${m.id}">
                        <span class="material-symbols-outlined">sync_alt</span>
                    </button>
                    <button class="icon-btn" type="button" title="Eliminar" data-accion="eliminar-mq" data-id="${m.id}">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

/* Los botones de PDF respetan el filtro de sucursal */
function actualizarEnlacesPdf() {
    const suc = parseInt($('filtro-sucursal-maquinas').value, 10) || 0;
    const extra = suc ? '&sucursal=' + suc : '';
    $('pdf-hidrogel').href = '?descargar_pdf=hidrogel' + extra;
    $('pdf-pp').href = '?descargar_pdf=protection_pro' + extra;
    $('pdf-todas').href = '?descargar_pdf=todas' + extra;
}

async function refrescarMaquinas() {
    const r = await postAccion('listar_maquinas');
    if (r.success) { maquinasData = r.data; pintarMaquinas(); }
}

$('tbody-maquinas').addEventListener('click', (e) => {
    const el = e.target.closest('[data-accion]');
    if (!el) return;
    const id = parseInt(el.dataset.id, 10);
    const m = maquinasData.find(x => x.id === id);
    if (!m) return;
    switch (el.dataset.accion) {
        case 'ver-codigos': abrirVerCodigos(m); break;
        case 'editar-mq':   abrirRegistro('maquina', id); break;
        case 'traspasar':   abrirModalTraspaso(m.sucursal_id, m.tipo); break;
        case 'eliminar-mq':
            confirmar('Eliminar máquina',
                `¿Seguro que deseas eliminar la máquina ${TIPO_LABEL[m.tipo]} "${m.identificador}" de "${m.sucursal_nombre}"? Esta acción no se puede deshacer.`,
                async () => {
                    const r = await postAccion('eliminar_maquina', { id });
                    mostrarToast(r.message, r.success ? 'success' : 'error');
                    if (r.success) await refrescarMaquinas();
                });
            break;
    }
});

['buscar-maquinas', 'filtro-sucursal-maquinas', 'filtro-tipo-maquinas'].forEach(id => {
    $(id).addEventListener('input', pintarMaquinas);
    $(id).addEventListener('change', pintarMaquinas);
});

/* =====================================================================
   Vista de códigos (barras con JsBarcode, QR como imagen del servidor)
   ===================================================================== */
let qrDebounce = null;

function dibujarCodigos(cont, valor, modo, { debounce = false, alturaBarras = 60, qrPx = 140 } = {}) {
    if (!valor || modo === 'ninguno') {
        cont.innerHTML = `<span class="text-body-sm" style="color:var(--on-surface-variant);">${
            valor ? 'Sin códigos seleccionados.' : 'Escribe un identificador para ver la vista previa'
        }</span>`;
        return;
    }
    const bloques = [];
    if (modo === 'barras' || modo === 'ambos') bloques.push('<svg class="prev-barras"></svg>');
    if (modo === 'qr' || modo === 'ambos') {
        bloques.push(`<img class="prev-qr" alt="Código QR" style="max-width:${qrPx}px;max-height:${qrPx}px;">`);
    }
    cont.innerHTML = `<div class="codes-view">${bloques.join('')}</div>`;

    const svg = cont.querySelector('.prev-barras');
    if (svg) {
        try {
            JsBarcode(svg, valor, { format: 'CODE128', width: 2, height: alturaBarras, displayValue: true, fontSize: 14, margin: 8 });
        } catch (e) {
            svg.outerHTML = '<span class="text-body-sm" style="color:var(--error);">No se pudo generar el código de barras.</span>';
        }
    }
    const img = cont.querySelector('.prev-qr');
    if (img) {
        const url = '?generar_qr=1&valor=' + encodeURIComponent(valor);
        if (debounce) {
            clearTimeout(qrDebounce);
            qrDebounce = setTimeout(() => { img.src = url; }, 300);
        } else {
            img.src = url;
        }
    }
}

function abrirVerCodigos(m) {
    $('vc-titulo').textContent = m.identificador;
    $('vc-detalle').textContent = `${m.sucursal_nombre} · ${TIPO_LABEL[m.tipo]}`;
    $('vc-pdf').href = '?descargar_pdf=' + encodeURIComponent(m.tipo) + '&id=' + m.id;
    dibujarCodigos($('vc-codigos'), m.identificador, m.codigos, { alturaBarras: 70, qrPx: 180 });
    if (m.codigos === 'ninguno') {
        $('vc-codigos').innerHTML = '<span class="text-body-sm" style="color:var(--on-surface-variant);">Esta máquina no tiene códigos habilitados. Edítala para elegir QR, barras o ambos.</span>';
    }
    abrirModal('modal-ver-codigos');
}

/* =====================================================================
   Modal de registro (dinámico: contraseña / máquina)
   ===================================================================== */
let registro = { tipo: 'password', id: null };
let codigosTocado = false;

function marcarCodigos(valor) {
    document.querySelectorAll('input[name="mq-codigos"]').forEach(r => { r.checked = (r.value === valor); });
}
function codigosSeleccionados() {
    const r = document.querySelector('input[name="mq-codigos"]:checked');
    return r ? r.value : 'ninguno';
}

function mostrarFormulario(tipo) {
    registro.tipo = tipo;
    $('form-password').style.display = tipo === 'password' ? 'block' : 'none';
    $('form-maquina').style.display = tipo === 'maquina' ? 'block' : 'none';
    document.querySelectorAll('#registro-selector button').forEach(b =>
        b.classList.toggle('active', b.dataset.tipoRegistro === tipo));
    $('registro-error').classList.remove('show');
    if (tipo === 'maquina') renderPreviewMaquina();
}

function abrirRegistro(tipo, id = null) {
    registro = { tipo, id };
    const editando = id !== null;
    $('registro-selector').style.display = editando ? 'none' : 'flex';
    const opciones = opcionesSucursales();

    // --- Contraseña ---
    $('pw-sucursal').innerHTML = opciones;
    ['pw-servicio', 'pw-usuario', 'pw-password', 'pw-url'].forEach(i => { $(i).value = ''; });
    $('pw-password').type = 'password';
    $('pw-hint').style.display = 'none';

    // --- Máquina ---
    $('mq-sucursal').innerHTML = opciones;
    $('mq-sucursal').disabled = false;
    $('mq-tipo').disabled = false;
    $('mq-sucursal-hint').style.display = 'none';
    $('mq-ident').value = '';
    $('mq-tipo').value = 'hidrogel';
    codigosTocado = false;
    marcarCodigos(CODIGO_POR_DEFECTO['hidrogel']);

    if (editando && tipo === 'password') {
        const p = passwordsData.find(x => x.id === id);
        if (!p) return;
        // La sucursal puede estar inactiva: se agrega para poder conservarla
        if (!sucursalesActivas().some(s => s.id === p.sucursal_id)) {
            $('pw-sucursal').insertAdjacentHTML('afterbegin', `<option value="${p.sucursal_id}">${escapeHtml(p.sucursal_nombre)}</option>`);
        }
        $('pw-sucursal').value = p.sucursal_id;
        $('pw-servicio').value = p.servicio;
        $('pw-usuario').value = p.usuario;
        $('pw-url').value = p.url || '';
        $('pw-hint').style.display = 'block';
        $('registro-titulo').textContent = 'Editar contraseña';
    } else if (editando && tipo === 'maquina') {
        const m = maquinasData.find(x => x.id === id);
        if (!m) return;
        if (!sucursalesActivas().some(s => s.id === m.sucursal_id)) {
            $('mq-sucursal').insertAdjacentHTML('afterbegin', `<option value="${m.sucursal_id}">${escapeHtml(m.sucursal_nombre)}</option>`);
        }
        $('mq-sucursal').value = m.sucursal_id;
        $('mq-tipo').value = m.tipo;
        $('mq-sucursal').disabled = true;     // la sucursal solo cambia por traspaso
        $('mq-tipo').disabled = true;
        $('mq-sucursal-hint').style.display = 'block';
        $('mq-ident').value = m.identificador;
        marcarCodigos(m.codigos);
        codigosTocado = true;
        $('registro-titulo').textContent = 'Editar máquina';
    } else {
        $('registro-titulo').textContent = 'Nuevo registro';
    }

    mostrarFormulario(tipo);
    abrirModal('modal-registro');
}

function renderPreviewMaquina() {
    dibujarCodigos($('mq-preview'), $('mq-ident').value.trim(), codigosSeleccionados(), { debounce: true });
}

$('registro-selector').addEventListener('click', (e) => {
    const b = e.target.closest('button[data-tipo-registro]');
    if (b) mostrarFormulario(b.dataset.tipoRegistro);
});
$('mq-ident').addEventListener('input', renderPreviewMaquina);
$('mq-tipo').addEventListener('change', () => {
    if (!codigosTocado) marcarCodigos(CODIGO_POR_DEFECTO[$('mq-tipo').value]);
    renderPreviewMaquina();
});
document.querySelectorAll('input[name="mq-codigos"]').forEach(r =>
    r.addEventListener('change', () => { codigosTocado = true; renderPreviewMaquina(); }));

$('pw-ver').addEventListener('click', () => {
    const inp = $('pw-password');
    const ver = inp.type === 'password';
    inp.type = ver ? 'text' : 'password';
    $('pw-ver').querySelector('.material-symbols-outlined').textContent = ver ? 'visibility_off' : 'visibility';
});

$('btn-nuevo').addEventListener('click', () => abrirRegistro(tabActual === 'maquinas' ? 'maquina' : 'password'));

$('btn-guardar-registro').addEventListener('click', async () => {
    const errorEl = $('registro-error');
    errorEl.classList.remove('show');
    const boton = $('btn-guardar-registro');
    boton.disabled = true;

    let r;
    if (registro.tipo === 'password') {
        r = await postAccion('guardar_password', {
            id: registro.id ?? 0,
            sucursal: $('pw-sucursal').value,
            servicio: $('pw-servicio').value,
            usuario: $('pw-usuario').value,
            password: $('pw-password').value,
            url: $('pw-url').value,
        });
    } else {
        r = await postAccion('guardar_maquina', {
            id: registro.id ?? 0,
            sucursal: $('mq-sucursal').value,
            tipo: $('mq-tipo').value,
            identificador: $('mq-ident').value,
            codigos: codigosSeleccionados(),
        });
    }
    boton.disabled = false;

    if (r.success) {
        cerrarModal('modal-registro');
        mostrarToast(r.message, 'success');
        $('pw-password').value = '';
        if (registro.tipo === 'password') { await refrescarPasswords(); cambiarTab('passwords'); }
        else { await refrescarMaquinas(); cambiarTab('maquinas'); }
    } else {
        errorEl.textContent = r.message;
        errorEl.classList.add('show');
    }
});

/* =====================================================================
   Módulo de Traspaso de máquinas (con cascada) — mismo flujo de siempre
   -----------------------------------------------------------------
   Estado: { tipo, cadena: [idSucursal, ...] }
   La cascada se resuelve en el cliente con los datos ya cargados y el
   servidor se llama UNA sola vez al confirmar (una transacción).
   ===================================================================== */
let traspaso = { tipo: null, cadena: [] };

function maquinaEn(idSucursal, tipo) {
    return maquinasData.find(m => m.sucursal_id === idSucursal && m.tipo === tipo);
}

function abrirModalTraspaso(idSucursalInicial = null, tipo = null) {
    traspaso = { tipo: tipo, cadena: idSucursalInicial ? [idSucursalInicial] : [] };
    abrirModal('modal-traspaso');
    if (idSucursalInicial && tipo) renderPasoDestino();
    else if (idSucursalInicial) renderPasoTipo();
    else renderPasoOrigen();
}

function actualizarBreadcrumb() {
    const cont = $('traspaso-breadcrumb');
    if (!traspaso.cadena.length) { cont.style.display = 'none'; return; }
    cont.style.display = 'flex';
    cont.innerHTML = traspaso.cadena.map((id, i) =>
        (i > 0 ? '<span class="chain-arrow material-symbols-outlined">arrow_forward</span>' : '') +
        `<span class="chain-item">${escapeHtml(nombreSucursal(id))}</span>`
    ).join('');
}

// Paso 1: elegir sucursal de origen
function renderPasoOrigen() {
    traspaso.cadena = [];
    actualizarBreadcrumb();
    $('traspaso-contenido').innerHTML = `
        <div class="form-group">
            <label>Sucursal de origen</label>
            <select class="form-control" id="select-origen">
                <option value="">Selecciona una sucursal…</option>
                ${opcionesSucursales()}
            </select>
        </div>`;
    $('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" type="button" onclick="cerrarModal('modal-traspaso')">Cancelar</button>
        <button class="btn btn-primary" type="button" onclick="confirmarOrigen()">Continuar</button>`;
}

function confirmarOrigen() {
    const id = parseInt($('select-origen').value, 10);
    if (!id) { mostrarToast('Selecciona una sucursal de origen.', 'error'); return; }
    traspaso.cadena = [id];
    traspaso.tipo = null;
    renderPasoTipo();
}

// Paso 2: elegir tipo de máquina (solo tipos que el origen sí tiene)
function renderPasoTipo() {
    actualizarBreadcrumb();
    const origen = traspaso.cadena[0];
    const cont = $('traspaso-contenido');
    const disponibles = Object.keys(TIPO_LABEL).filter(t => maquinaEn(origen, t));

    if (!disponibles.length) {
        cont.innerHTML = `<div class="conflict-box">
            <span class="material-symbols-outlined">warning</span>
            <span>"${escapeHtml(nombreSucursal(origen))}" no tiene ninguna máquina asignada para traspasar.</span>
        </div>`;
        $('traspaso-footer').innerHTML = `<button class="btn btn-outline" type="button" onclick="renderPasoOrigen()">Volver</button>`;
        return;
    }

    cont.innerHTML = `
        <div class="form-group">
            <label>Tipo de máquina a traspasar</label>
            <select class="form-control" id="select-tipo">
                ${disponibles.map(t => `<option value="${t}">${escapeHtml(TIPO_LABEL[t])} (${escapeHtml(maquinaEn(origen, t).identificador)})</option>`).join('')}
            </select>
        </div>`;
    $('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" type="button" onclick="renderPasoOrigen()">Volver</button>
        <button class="btn btn-primary" type="button" onclick="confirmarTipo()">Continuar</button>`;
}

function confirmarTipo() {
    traspaso.tipo = $('select-tipo').value;
    renderPasoDestino();
}

// Paso 3: elegir sucursal destino (excluyendo las ya usadas en la cadena)
function renderPasoDestino() {
    actualizarBreadcrumb();
    $('traspaso-contenido').innerHTML = `
        <div class="form-group">
            <label>Sucursal destino (${escapeHtml(TIPO_LABEL[traspaso.tipo])})</label>
            <select class="form-control" id="select-destino">
                <option value="">Selecciona una sucursal…</option>
                ${opcionesSucursales(traspaso.cadena)}
            </select>
        </div>`;
    $('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" type="button" onclick="volverAPasoAnterior()">Volver</button>
        <button class="btn btn-primary" type="button" onclick="confirmarDestino()">Continuar</button>`;
}

function volverAPasoAnterior() {
    if (traspaso.cadena.length <= 1) {
        renderPasoTipo();
    } else {
        // Se quita el último de la cadena para elegir otra vez
        traspaso.cadena.pop();
        renderPasoDestino();
    }
}

function confirmarDestino() {
    const idDestino = parseInt($('select-destino').value, 10);
    if (!idDestino) { mostrarToast('Selecciona una sucursal destino.', 'error'); return; }

    traspaso.cadena.push(idDestino);
    const ocupada = maquinaEn(idDestino, traspaso.tipo);
    if (!ocupada) renderResumenFinal();          // destino vacío: la cadena se cierra
    else renderConflictoDestino(idDestino);      // destino ocupado: ¿reubicar o eliminar?
}

// Sub-paso: la sucursal destino ya tiene máquina -> reubicar o eliminar
function renderConflictoDestino(idDestino) {
    actualizarBreadcrumb();
    const m = maquinaEn(idDestino, traspaso.tipo);
    $('traspaso-contenido').innerHTML = `
        <div class="conflict-box">
            <span class="material-symbols-outlined">warning</span>
            <span>
                "<b>${escapeHtml(nombreSucursal(idDestino))}</b>" ya tiene una máquina ${escapeHtml(TIPO_LABEL[traspaso.tipo])}
                (identificador <b>${escapeHtml(m.identificador)}</b>).
                ¿Qué deseas hacer con ella?
            </span>
        </div>
        <div class="form-group">
            <label>Mover esa máquina a otra sucursal</label>
            <select class="form-control" id="select-reubicar">
                <option value="">— Elegir sucursal destino —</option>
                ${opcionesSucursales(traspaso.cadena)}
            </select>
        </div>
        <div style="text-align:center;margin:var(--space-sm) 0;color:var(--on-surface-variant);font-size:13px;">o bien</div>
        <button class="btn btn-outline btn-block" type="button" onclick="eliminarMaquinaDesplazada()">
            <span class="material-symbols-outlined">delete</span>
            Eliminar definitivamente esa máquina
        </button>`;
    $('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" type="button" onclick="volverAPasoAnterior()">Volver</button>
        <button class="btn btn-primary" type="button" onclick="continuarReubicacion()">Continuar</button>`;
}

function continuarReubicacion() {
    const idSiguiente = parseInt($('select-reubicar').value, 10);
    if (!idSiguiente) { mostrarToast('Elige una sucursal o usa la opción de eliminar.', 'error'); return; }

    traspaso.cadena.push(idSiguiente);
    if (!maquinaEn(idSiguiente, traspaso.tipo)) renderResumenFinal();
    else renderConflictoDestino(idSiguiente);
}

function eliminarMaquinaDesplazada() {
    // La cadena ya termina en la sucursal cuya máquina se descarta.
    const idUltimo = traspaso.cadena[traspaso.cadena.length - 1];
    renderResumenFinal(true, nombreSucursal(idUltimo));
}

// Paso final: resumen y confirmación
function renderResumenFinal(seElimina = false, nombreEliminado = null) {
    actualizarBreadcrumb();
    let html = `<p class="text-body-sm">Se realizarán los siguientes movimientos de <b>${escapeHtml(TIPO_LABEL[traspaso.tipo])}</b>:</p><ul class="step-list check">`;
    for (let i = 1; i < traspaso.cadena.length; i++) {
        html += `<li>${escapeHtml(nombreSucursal(traspaso.cadena[i - 1]))} → ${escapeHtml(nombreSucursal(traspaso.cadena[i]))}</li>`;
    }
    html += '</ul>';
    if (seElimina) {
        html += `<div class="conflict-box">
            <span class="material-symbols-outlined">delete_forever</span>
            <span>La máquina que tenía "<b>${escapeHtml(nombreEliminado)}</b>" quedará eliminada definitivamente.</span>
        </div>`;
    }
    $('traspaso-contenido').innerHTML = html;
    $('traspaso-footer').innerHTML = `
        <button class="btn btn-outline" type="button" onclick="volverAPasoAnterior()">Volver</button>
        <button class="btn btn-primary" type="button" id="btn-ejecutar-traspaso" onclick="ejecutarTraspasoFinal()">
            <span class="material-symbols-outlined">check</span> Confirmar traspaso
        </button>`;
}

async function ejecutarTraspasoFinal() {
    const boton = $('btn-ejecutar-traspaso');
    if (boton) boton.disabled = true;
    const r = await postAccion('traspasar_maquina', {
        tipo: traspaso.tipo,
        cadena: JSON.stringify(traspaso.cadena),
    });
    if (r.success) {
        cerrarModal('modal-traspaso');
        mostrarToast(r.message, 'success');
        await refrescarMaquinas();
        cambiarTab('maquinas');
    } else {
        if (boton) boton.disabled = false;
        mostrarToast(r.message, 'error');
    }
}

$('btn-abrir-traspaso').addEventListener('click', () => abrirModalTraspaso());

/* =====================================================================
   Impresión
   ===================================================================== */
function imprimirConClase(clase) {
    ocultarTodasPasswords();               // nunca imprimir contraseñas reveladas
    document.body.classList.add(clase);
    window.print();
}
window.addEventListener('afterprint', () =>
    document.body.classList.remove('print-passwords', 'print-maquinas', 'print-codes'));
window.addEventListener('beforeprint', ocultarTodasPasswords);

$('btn-imprimir-passwords').addEventListener('click', () => imprimirConClase('print-passwords'));
$('btn-imprimir-maquinas').addEventListener('click', () => imprimirConClase('print-maquinas'));
$('vc-imprimir').addEventListener('click', () => imprimirConClase('print-codes'));

/* =====================================================================
   Varios
   ===================================================================== */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('show');
    });
});

// Inicio
llenarFiltrosSucursal();
pintarPasswords();
pintarMaquinas();
cambiarTab(location.hash === '#maquinas' ? 'maquinas' : 'passwords');
</script>
<script>
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
</script>
</body>
</html>