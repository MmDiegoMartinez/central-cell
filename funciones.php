<?php
function conectarBD(): PDO {
    // Datos de conexión para entorno local
    $host = 'localhost';
    $usuario = 'root';
    $password = ''; // En XAMPP por defecto no hay contraseña para root
    $base_datos = 'if0_39427481_tienda_garantias';

    $dsn = "mysql:host=$host;dbname=$base_datos;charset=utf8mb4";

    try {
        $conn = new PDO($dsn, $usuario, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $conn;
    } catch (PDOException $e) {
        error_log("Error al conectar a la base de datos: " . $e->getMessage());
        throw new Exception("No se pudo conectar a la base de datos. Por favor, intente más tarde.");
    }
}


function guardarGarantia($datos) {
    $conn = conectarBD();

    ini_set('date.timezone', 'America/Mexico_City');
    date_default_timezone_set('America/Mexico_City');

    $hora_actual = date('Y-m-d H:i:s');

    /* ── Departamento ── */
    $dpto = (isset($datos['dpto']) && $datos['dpto'] === 'tm') ? 'tm' : 'im';

    /* ── Campos que varían por depto ── */
    if ($dpto === 'im') {
        $tipo         = $datos['tipo_im']  ?? null;
        $causa        = $datos['causa_im'] ?? null;
        $piezas       = $datos['piezas']   ?? null;
        $sucursal     = $datos['sucursal'] ?? null;
        $apasionado   = $datos['apasionado'] ?? null;
        $fecha        = $datos['fecha']    ?? null;
        $anotaciones  = $datos['anotaciones_vendedor'] ?? null;
        $numero_serie = null;
    } else {
        $tipo         = $datos['tipo_tm']       ?? null;
        $causa        = $datos['causa_tm']      ?? null;
        $piezas       = $datos['piezas_tm']     ?? null;
        $sucursal     = $datos['sucursal_tm']   ?? null;
        $apasionado   = $datos['apasionado_tm'] ?? null;
        $fecha        = $datos['fecha_tm']      ?? null;
        $anotaciones  = $datos['anotaciones_tm'] ?? null;
        $numero_serie = trim($datos['numero_serie'] ?? '');
        if ($numero_serie === '') $numero_serie = null;
    }

    /* ── Colaborador ── */
    $stmt = $conn->prepare("SELECT id FROM colaboradores WHERE nombre = :nombre LIMIT 1");
    $stmt->execute([':nombre' => $apasionado]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($colaborador) {
        $idColaborador = $colaborador['id'];
    } else {
        $stmtIns = $conn->prepare("INSERT INTO colaboradores (nombre) VALUES (:nombre)");
        $stmtIns->execute([':nombre' => $apasionado]);
        $idColaborador = $conn->lastInsertId();
    }

    /* ── Validar sucursal ── */
    $stmtSuc = $conn->prepare("SELECT id FROM sucursales WHERE id = :id AND estatus = 1 LIMIT 1");
    $stmtSuc->execute([':id' => $sucursal]);
    if (!$stmtSuc->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Sucursal inválida o inactiva.");
    }

    /* ── Procesar fotos ── */
    $fotoGuardar = null;
    if (!empty($datos['foto_url']) && is_array($datos['foto_url'])) {
        $stmtNum = $conn->prepare("SELECT foto FROM garantia WHERE foto IS NOT NULL ORDER BY id DESC LIMIT 100");
        $stmtNum->execute();
        $registrosFoto = $stmtNum->fetchAll(PDO::FETCH_COLUMN);

        $numerosUsados = [];
        foreach ($registrosFoto as $fotoStr) {
            foreach (explode(',', $fotoStr) as $parte) {
                if (preg_match('/garantia-merma(\d+)/i', trim($parte), $m)) {
                    $numerosUsados[] = (int)$m[1];
                }
            }
        }

        $sig = empty($numerosUsados) ? 1 : (max($numerosUsados) + 1);
        $fotosConNombre = [];
        foreach ($datos['foto_url'] as $url) {
            $url = trim($url);
            if (empty($url)) continue;
            $fotosConNombre[] = 'garantia-merma' . $sig . '|' . $url;
            $sig++;
        }
        if (!empty($fotosConNombre)) $fotoGuardar = implode(',', $fotosConNombre);
    }

    /* ── Insertar ── */
    try {
        $sql = "INSERT INTO garantia 
            (plows, tipo, numero_serie, causa, piezas, sucursal, apasionado, fecha,
             estatus, anotaciones_vendedor, anotado, foto, dispositivo, dpto,
             created_at, updated_at) 
            VALUES 
            (:plows, :tipo, :numero_serie, :causa, :piezas, :sucursal, :apasionado, :fecha,
             'Anotado', :anotaciones, 1, :foto, :dispositivo, :dpto,
             :created_at, :updated_at)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':plows'        => strtoupper($datos['plows']),
            ':tipo'         => $tipo,
            ':numero_serie' => $numero_serie,
            ':causa'        => $causa,
            ':piezas'       => $piezas,
            ':sucursal'     => $sucursal,
            ':apasionado'   => $idColaborador,
            ':fecha'        => $fecha,
            ':anotaciones'  => $anotaciones,
            ':foto'         => $fotoGuardar,
            ':dispositivo'  => trim(($datos['dispositivo'] ?? '') . ' | IP:' . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'IP?')),
            ':dpto'         => $dpto,
            ':created_at'   => $hora_actual,
            ':updated_at'   => $hora_actual,
        ]);
    } catch (PDOException $e) {
        throw new Exception("Error al guardar garantía: " . $e->getMessage());
    }

    return true;
}

function guardarGarantiasinguardar($datos) {
    $conn = conectarBD();

    ini_set('date.timezone', 'America/Mexico_City');
    date_default_timezone_set('America/Mexico_City');
    $hora_actual = date('Y-m-d H:i:s');

    /* ── Departamento ── */
    $dpto = (isset($datos['dpto']) && $datos['dpto'] === 'tm') ? 'tm' : 'im';

    /* ── Campos según depto ── */
    if ($dpto === 'im') {
        $tipo        = $datos['tipo_im']       ?? null;
        $causa       = $datos['causa_im']      ?? null;
        $piezas      = $datos['piezas_im']     ?? null;
        $sucursal    = $datos['sucursal_im']   ?? null;
        $apasionado  = $datos['apasionado']    ?? null;
        $fecha       = $datos['fecha_im']      ?? null;
        $anotaciones = $datos['anotaciones_im'] ?? null;
        $numero_serie = null;
    } else {
        $tipo        = $datos['tipo_tm']        ?? null;
        $causa       = $datos['causa_tm']       ?? null;
        $piezas      = $datos['piezas_tm']      ?? null;
        $sucursal    = $datos['sucursal_tm']    ?? null;
        $apasionado  = $datos['apasionado_tm']  ?? null;
        $fecha       = $datos['fecha_tm']       ?? null;
        $anotaciones = $datos['anotaciones_tm'] ?? null;
        $numero_serie = trim($datos['numero_serie'] ?? '');
        if ($numero_serie === '') $numero_serie = null;
    }

    /* ── Colaborador ── */
    $stmt = $conn->prepare("SELECT id FROM colaboradores WHERE nombre = :nombre LIMIT 1");
    $stmt->execute([':nombre' => $apasionado]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($colaborador) {
        $idColaborador = $colaborador['id'];
    } else {
        $stmtIns = $conn->prepare("INSERT INTO colaboradores (nombre) VALUES (:nombre)");
        $stmtIns->execute([':nombre' => $apasionado]);
        $idColaborador = $conn->lastInsertId();
    }

    /* ── Validar sucursal (acepta estatus 1 o 4) ── */
    $stmtSuc = $conn->prepare("SELECT id FROM sucursales WHERE id = :id AND estatus IN (1, 4) LIMIT 1");
    $stmtSuc->execute([':id' => $sucursal]);
    if (!$stmtSuc->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception("Sucursal inválida o inactiva.");
    }

    /* ── Procesar fotos ── */
    $fotoGuardar = null;
    if (!empty($datos['foto_url']) && is_array($datos['foto_url'])) {
        $stmtNum = $conn->prepare("SELECT foto FROM garantia WHERE foto IS NOT NULL ORDER BY id DESC LIMIT 100");
        $stmtNum->execute();
        $registrosFoto = $stmtNum->fetchAll(PDO::FETCH_COLUMN);

        $numerosUsados = [];
        foreach ($registrosFoto as $fotoStr) {
            foreach (explode(',', $fotoStr) as $parte) {
                $parte = trim($parte);
                if (preg_match('/garantia-merma(\d+)/i', $parte, $m)) {
                    $numerosUsados[] = (int)$m[1];
                }
            }
        }

        $sig = empty($numerosUsados) ? 1 : (max($numerosUsados) + 1);
        $fotosConNombre = [];
        foreach ($datos['foto_url'] as $url) {
            $url = trim($url);
            if (empty($url)) continue;
            $fotosConNombre[] = 'garantia-merma' . $sig . '|' . $url;
            $sig++;
        }
        if (!empty($fotosConNombre)) $fotoGuardar = implode(',', $fotosConNombre);
    }

    /* ── Insertar — anotado = 2 ── */
    try {
        $sql = "INSERT INTO garantia 
            (plows, tipo, numero_serie, causa, piezas, sucursal, apasionado, fecha,
             estatus, anotaciones_vendedor, anotado, foto, dispositivo, dpto,
             created_at, updated_at) 
            VALUES 
            (:plows, :tipo, :numero_serie, :causa, :piezas, :sucursal, :apasionado, :fecha,
             'Anotado', :anotaciones, 2, :foto, :dispositivo, :dpto,
             :created_at, :updated_at)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':plows'        => strtoupper($datos['plows']),
            ':tipo'         => $tipo,
            ':numero_serie' => $numero_serie,
            ':causa'        => $causa,
            ':piezas'       => $piezas,
            ':sucursal'     => $sucursal,
            ':apasionado'   => $idColaborador,
            ':fecha'        => $fecha,
            ':anotaciones'  => $anotaciones,
            ':foto'         => $fotoGuardar,
            ':dispositivo'  => trim(($datos['dispositivo'] ?? '') . ' | IP:' . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'IP?')),
            ':dpto'         => $dpto,
            ':created_at'   => $hora_actual,
            ':updated_at'   => $hora_actual,
        ]);
    } catch (PDOException $e) {
        throw new Exception("Error al guardar garantía: " . $e->getMessage());
    }

    return true;
}

function obtenerSucursalesValidador(): array
{
    try {
        $conn = conectarBD();

        $query = "SELECT id, nombre 
                  FROM sucursales 
                  WHERE estatus IN (1, 4) 
                  ORDER BY nombre ASC";

        $stmt = $conn->prepare($query);
        $stmt->execute();

        $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $sucursales ?: [];
    } catch (PDOException $e) {
        error_log(sprintf('[%s] Error en obtenerSucursalesValidador: %s', date('Y-m-d H:i:s'), $e->getMessage()));
        return [];
    } finally {
        $conn = null;
    }
}


function verTabla(): array {
    try {
        $conexion = conectarBD();

        $fechaActual    = new DateTime("now", new DateTimeZone("America/Mexico_City"));
        $fechaActualStr = $fechaActual->format('Y-m-d');

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
            g.foto
        FROM garantia g
        LEFT JOIN validador     v ON g.id_validador = v.id
        LEFT JOIN sucursales    s ON g.sucursal = s.id
        LEFT JOIN colaboradores c ON g.apasionado = c.id
        LEFT JOIN departamento  d ON g.dpto = d.cod
        WHERE g.anotado = 1
          AND NOT (
                g.estatus = 'Anotado'
                AND g.fecha < DATE_SUB(:fechaActual, INTERVAL 1 MONTH)
            )
        ORDER BY g.fecha DESC, g.id DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->bindValue(':fechaActual', $fechaActualStr);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt      = null;
        $conexion  = null;

        return $resultado;

    } catch (PDOException $e) {
        error_log("Error al consultar tabla de garantías: " . $e->getMessage());
        return [];
    }
}

function verTablanoguardados(): array {
    try {
        $conexion = conectarBD();

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
            g.foto
        FROM garantia g
        LEFT JOIN validador     v ON g.id_validador = v.id
        LEFT JOIN sucursales    s ON g.sucursal = s.id
        LEFT JOIN colaboradores c ON g.apasionado = c.id
        LEFT JOIN departamento  d ON g.dpto = d.cod
        WHERE g.anotado = 2
        ORDER BY g.fecha DESC, g.id DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt      = null;
        $conexion  = null;

        return $resultado;

    } catch (PDOException $e) {
        error_log("Error al consultar tabla de garantías no guardadas: " . $e->getMessage());
        return [];
    }
}

function verTablavalidador(): array {
    try {
        $conexion = conectarBD();

        $sql = "SELECT 
            g.id,
            g.plows, 
             g.numero_serie,
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
        LEFT JOIN validador    v ON g.id_validador = v.id
        LEFT JOIN sucursales   s ON g.sucursal = s.id
        LEFT JOIN colaboradores c ON g.apasionado = c.id
        LEFT JOIN departamento  d ON g.dpto = d.cod
        WHERE g.anotado = 1
        ORDER BY g.fecha DESC, g.id DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt      = null;
        $conexion  = null;

        return $resultado;

    } catch (PDOException $e) {
        error_log("Error al consultar tabla de garantías: " . $e->getMessage());
        return [];
    }
}


function obtenerGarantiaPorId($id): ?array {
    if (!is_numeric($id) || $id <= 0) {
        return null;
    }

    try {
        $conexion = conectarBD();

        $sql = "SELECT g.*, c.nombre AS nombre_colaborador
                FROM garantia g
                LEFT JOIN colaboradores c ON g.apasionado = c.id
                WHERE g.id = :id";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt     = null;
        $conexion = null;

        return $resultado ?: null;

    } catch (PDOException $e) {
        error_log("Error al obtener garantía por ID ($id): " . $e->getMessage());
        return null;
    }
}



function validarLoginValidador(string $usuario, string $password): array|false {
    if (empty($usuario) || empty($password)) {
        return false; // Validación básica de entrada
    }

    try {
        $conexion = conectarBD();

        // ✅ Evita SELECT * y limita los campos al mínimo necesario
        $sql = "SELECT id, usuario, password, nombre FROM validador WHERE usuario = :usuario LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':usuario' => $usuario]);

        $validador = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = null;
        $conexion = null;

        // ✅ Verifica contraseña usando hashing seguro
        if ($validador && password_verify($password, $validador['password'])) {
            // ✅ Evita exponer la contraseña incluso en memoria
            unset($validador['password']);
            return $validador;
        }

        return false;

    } catch (PDOException $e) {
        error_log("Error en login validador: " . $e->getMessage());
        return false;
    }
}
function obtenerValidadores(): array {
    try {
        $conexion = conectarBD();

        // ✅ Seleccionamos solo campos necesarios
        $sql = "SELECT id, usuario, nombre, apellido, created_at FROM validador ORDER BY created_at DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        $validadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ✅ Cierre de recursos
        $stmt = null;
        $conexion = null;

        return $validadores;

    } catch (PDOException $e) {
        error_log("Error al obtener validadores: " . $e->getMessage());
        return []; // ❌ No exponer el error al usuario
    }
}


function obtenerValidadorPorId($id): ?array {
    try {
        $conn = conectarBD();

        // ✅ Solo campos explícitos (evita SELECT *)
        $sql = "SELECT id, usuario, nombre, apellido, created_at FROM validador WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);

        $validador = $stmt->fetch(PDO::FETCH_ASSOC);

        // ✅ Liberar recursos
        $stmt = null;
        $conn = null;

        return $validador ?: null;

    } catch (PDOException $e) {
        error_log("Error al obtener validador por ID: " . $e->getMessage());
        return null;
    }
}


function actualizarValidadorConPassword(
    int $id,
    string $nombre,
    string $apellido,
    string $usuario,
    string $password_hash
): bool {
    if ($id <= 0 || empty($nombre) || empty($apellido) || empty($usuario) || empty($password_hash)) {
        return false; // Validación básica
    }

    try {
        $conexion = conectarBD();

        // Opcional: comprobar si usuario ya existe para otro id
        $stmtCheck = $conexion->prepare("SELECT id FROM validador WHERE usuario = :usuario AND id != :id LIMIT 1");
        $stmtCheck->execute([':usuario' => $usuario, ':id' => $id]);
        if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
            // Usuario ya existe para otro validador
            return false;
        }
        $stmtCheck = null;

        $sql = "UPDATE validador SET nombre = :nombre, apellido = :apellido, usuario = :usuario, password = :password WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':usuario' => $usuario,
            ':password' => $password_hash,
            ':id' => $id,
        ]);

        $filasAfectadas = $stmt->rowCount();

        $stmt = null;
        $conexion = null;

        return $filasAfectadas > 0;

    } catch (PDOException $e) {
        error_log("Error al actualizar validador con password (ID: $id): " . $e->getMessage());
        return false;
    }
}
function actualizarValidador(int $id, string $nombre, string $apellido, string $usuario): bool {
    if ($id <= 0 || empty($nombre) || empty($apellido) || empty($usuario)) {
        return false;
    }

    try {
        $conexion = conectarBD();

        // Comprobar si usuario ya existe para otro id
        $stmtCheck = $conexion->prepare("SELECT id FROM validador WHERE usuario = :usuario AND id != :id LIMIT 1");
        $stmtCheck->execute([':usuario' => $usuario, ':id' => $id]);
        if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
            return false; // Usuario duplicado
        }
        $stmtCheck = null;

        $sql = "UPDATE validador SET nombre = :nombre, apellido = :apellido, usuario = :usuario WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':apellido' => $apellido,
            ':usuario' => $usuario,
            ':id' => $id,
        ]);

        $filasAfectadas = $stmt->rowCount();

        $stmt = null;
        $conexion = null;

        return $filasAfectadas > 0;

    } catch (PDOException $e) {
        error_log("Error al actualizar validador (ID: $id): " . $e->getMessage());
        return false;
    }
}

//funciones agregadas
function crearValidador(array $datos): bool|string {
    $conn = conectarBD();

    // Validar si usuario existe
    $stmt = $conn->prepare("SELECT id FROM validador WHERE usuario = ?");
    $stmt->execute([$datos['usuario']]);
    if ($stmt->fetch()) {
        return "El usuario ya existe, elige otro.";
    }

    $password_hash = password_hash($datos['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO validador (nombre, apellido, usuario, password) VALUES (?, ?, ?, ?)");
    $exito = $stmt->execute([
        $datos['nombre'],
        $datos['apellido'],
        $datos['usuario'],
        $password_hash,
    ]);

    return $exito ? true : "Error al crear validador.";
}

//editar en validador


//guardar capácitaciones
/**
 * Inserta una compatibilidad en la base de datos
 * 
 * @param int $modelo_id
 * @param int $compatible_id
 * @param string $tipo (glass | funda)
 * @param string|null $nota
 * @return bool
 * @throws Exception
 */
function insertarCompatibilidad(int $modelo_id, int $compatible_id, string $tipo, ?string $nota = null, int $origen = 1): bool {
    try {
        $conn = conectarBD();

        // Validar que tipo sea válido
        if (!in_array($tipo, ['glass', 'funda', 'camara'])) {
            throw new Exception("Tipo de compatibilidad inválido");
        }

        // PASO 1: Buscar el modelo principal real de ambos modelos ingresados
        $modelo_principal_final = null;
        $modelo_compatible_final = null;

        // Buscar si el modelo_id ya existe como principal DEL MISMO TIPO
        $stmt = $conn->prepare("SELECT modelo_id FROM compatibilidades WHERE modelo_id = :id AND tipo = :tipo LIMIT 1");
        $stmt->execute([':id' => $modelo_id, ':tipo' => $tipo]);
        $modelo_id_es_principal = $stmt->fetch(PDO::FETCH_ASSOC);

        // Buscar si el modelo_id ya existe como compatible de otro DEL MISMO TIPO
        $stmt = $conn->prepare("SELECT modelo_id FROM compatibilidades WHERE compatible_id = :id AND tipo = :tipo LIMIT 1");
        $stmt->execute([':id' => $modelo_id, ':tipo' => $tipo]);
        $modelo_id_tiene_padre = $stmt->fetch(PDO::FETCH_ASSOC);

        // Buscar si el compatible_id ya existe como principal DEL MISMO TIPO
        $stmt = $conn->prepare("SELECT modelo_id FROM compatibilidades WHERE modelo_id = :id AND tipo = :tipo LIMIT 1");
        $stmt->execute([':id' => $compatible_id, ':tipo' => $tipo]);
        $compatible_id_es_principal = $stmt->fetch(PDO::FETCH_ASSOC);

        // Buscar si el compatible_id ya existe como compatible de otro DEL MISMO TIPO
        $stmt = $conn->prepare("SELECT modelo_id FROM compatibilidades WHERE compatible_id = :id AND tipo = :tipo LIMIT 1");
        $stmt->execute([':id' => $compatible_id, ':tipo' => $tipo]);
        $compatible_id_tiene_padre = $stmt->fetch(PDO::FETCH_ASSOC);

        // LÓGICA DE DECISIÓN: Determinar cuál es el modelo principal real
        
        // Caso 1: El modelo_id YA es principal en la BD → usar ese como principal
        if ($modelo_id_es_principal) {
            $modelo_principal_final = $modelo_id;
            $modelo_compatible_final = $compatible_id;
        }
        // Caso 2: El compatible_id YA es principal en la BD → usar ese como principal
        else if ($compatible_id_es_principal) {
            $modelo_principal_final = $compatible_id;
            $modelo_compatible_final = $modelo_id;
        }
        // Caso 3: El modelo_id es compatible de otro → usar el padre de modelo_id
        else if ($modelo_id_tiene_padre) {
            $modelo_principal_final = $modelo_id_tiene_padre['modelo_id'];
            $modelo_compatible_final = $compatible_id;
        }
        // Caso 4: El compatible_id es compatible de otro → usar el padre de compatible_id
        else if ($compatible_id_tiene_padre) {
            $modelo_principal_final = $compatible_id_tiene_padre['modelo_id'];
            $modelo_compatible_final = $modelo_id;
        }
        // Caso 5: Ninguno existe aún → usar el orden que ingresó el usuario
        else {
            $modelo_principal_final = $modelo_id;
            $modelo_compatible_final = $compatible_id;
        }

        // PASO 2: Validar que no sea el mismo modelo
        if ($modelo_principal_final === $modelo_compatible_final) {
            throw new Exception("Un modelo no puede ser compatible consigo mismo");
        }

        // PASO 3: Verificar si ya existe esta compatibilidad exacta
        $stmt = $conn->prepare("
            SELECT id FROM compatibilidades 
            WHERE modelo_id = :modelo_id 
            AND compatible_id = :compatible_id 
            AND tipo = :tipo
        ");
        $stmt->execute([
            ':modelo_id' => $modelo_principal_final,
            ':compatible_id' => $modelo_compatible_final,
            ':tipo' => $tipo
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception("Esta compatibilidad ya existe en el sistema");
        }

        // PASO 4: Verificar si el compatible_final ya es compatible del principal_final
        // (evita ingresar Honor X6B PLUS dos veces si ya está)
        $stmt = $conn->prepare("
            SELECT id FROM compatibilidades 
            WHERE modelo_id = :modelo_id 
            AND compatible_id = :compatible_id 
            AND tipo = :tipo
        ");
        $stmt->execute([
            ':modelo_id' => $modelo_principal_final,
            ':compatible_id' => $modelo_compatible_final,
            ':tipo' => $tipo
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception("Esta compatibilidad ya existe en el sistema");
        }

        // PASO 5: Preparar nota final según el origen
        $nota_final = $nota;
        if ($origen === 2) {
            // Si es origen 2 (tienda), agregar el texto adicional
            $nota_final = $nota ? $nota . " | Compatibilidad registrada en tienda" : " | Compatibilidad registrada en tienda";
        }

        // PASO 6: Insertar la compatibilidad normalizada
        $sql = "INSERT INTO compatibilidades (modelo_id, compatible_id, tipo, nota)
                VALUES (:modelo_id, :compatible_id, :tipo, :nota)";
        $stmt = $conn->prepare($sql);

        $resultado = $stmt->execute([
            ':modelo_id' => $modelo_principal_final,
            ':compatible_id' => $modelo_compatible_final,
            ':tipo' => $tipo,
            ':nota' => $nota_final
        ]);

        // PASO 7: Logging para debugging
        if ($modelo_principal_final != $modelo_id || $modelo_compatible_final != $compatible_id) {
            $tipo_origen = $origen === 1 ? "ADMIN" : "TIENDA";
            error_log("COMPATIBILIDAD NORMALIZADA [$tipo] [$tipo_origen]: Usuario ingresó ($modelo_id -> $compatible_id), se guardó como ($modelo_principal_final -> $modelo_compatible_final)");
        }

        return $resultado;

    } catch (PDOException $e) {
        error_log("Error en insertarCompatibilidad: " . $e->getMessage());
        throw new Exception("No se pudo insertar la compatibilidad. Intente de nuevo.");
    }
}
function editarCompatibilidad($id, $modelo_id, $compatible_id, $tipo, $nota) {
    $pdo = conectarBD(); // <-- corregido, era conectar()
    $stmt = $pdo->prepare("
        UPDATE compatibilidades 
        SET modelo_id     = :modelo_id,
            compatible_id = :compatible_id,
            tipo          = :tipo,
            nota          = :nota
        WHERE id = :id
    ");
    $stmt->execute([
        ':modelo_id'     => $modelo_id,
        ':compatible_id' => $compatible_id,
        ':tipo'          => $tipo,
        ':nota'          => $nota,
        ':id'            => $id,
    ]);
}
 

/**
 * Verifica si una marca existe en la base de datos
 * @param string $marca - Nombre de la marca a verificar
 * @return bool - true si existe, false si no existe
 */
function verificarMarcaExiste(string $marca): bool {
    try {
        $conn = conectarBD();
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM modelos 
            WHERE marca = :marca
        ");
        
        $stmt->execute([':marca' => $marca]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total'] > 0;
        
    } catch (PDOException $e) {
        error_log("Error en verificarMarcaExiste: " . $e->getMessage());
        return false;
    }
}
/**
 * Obtener todos los modelos (para llenar los selects del formulario)
 * 
 * @return array
 */
function obtenerModelos(): array {
    try {
        $conn = conectarBD();
        $stmt = $conn->query("SELECT id, marca, modelo FROM modelos ORDER BY marca, modelo");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error en obtenerModelos: " . $e->getMessage());
        return [];
    }
}



function obtenerMarcas(): array {
    try {
        $conn = conectarBD();
        $stmt = $conn->query("SELECT DISTINCT marca FROM modelos ORDER BY marca");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Error en obtenerMarcas: " . $e->getMessage());
        return [];
    }
}

function obtenerModelosPorMarca(string $marca): array {
    try {
        $conn = conectarBD();
        $stmt = $conn->prepare("SELECT id, modelo FROM modelos WHERE marca = :marca ORDER BY modelo");
        $stmt->execute([':marca' => $marca]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error en obtenerModelosPorMarca: " . $e->getMessage());
        return [];
    }
}

function actualizarGarantiasDiario(PDO $conn) {
    $hoy = date("Y-m-d");

    // Revisar si ya se ejecutó hoy
    $sqlCheck = "SELECT id FROM actualizaciones_diarias WHERE fecha = :fecha";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([':fecha' => $hoy]);
    if ($stmtCheck->fetch()) {
        return 0; // Ya se ejecutó hoy
    }

    // Consulta de actualización
    $sql = "UPDATE garantia 
            SET anotaciones_validador = 'Merma o Garantia No Llego'
            WHERE estatus = 'Anotado'
              AND (anotaciones_validador IS NULL OR anotaciones_validador = '') 
              AND DATEDIFF(CURDATE(), fecha) > 3";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $afectados = $stmt->rowCount();
        $stmt = null;

        // Registrar la fecha de ejecución en la BD
        $sqlInsert = "INSERT INTO actualizaciones_diarias (fecha) VALUES (:fecha)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->execute([':fecha' => $hoy]);

        return $afectados;

    } catch (PDOException $e) {
        error_log("Error en actualizarGarantiasDiario: " . $e->getMessage());
        return 0;
    }
}


// ==================================================
// CRUD MODELOS
// ==================================================
if (!function_exists('obtenerModelos')) {
    function obtenerModelos(): array {
        $conn = conectarBD();
        $stmt = $conn->query("SELECT * FROM modelos ORDER BY marca, modelo");
        return $stmt->fetchAll();
    }
}

if (!function_exists('obtenerModelo')) {
    function obtenerModelo(int $id): ?array {
        $conn = conectarBD();
        $stmt = $conn->prepare("SELECT * FROM modelos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('insertarModelo')) {
    function insertarModelo(string $marca, string $modelo): int {
        $conn = conectarBD();
        $stmt = $conn->prepare("INSERT INTO modelos (marca, modelo) VALUES (?, ?)");
        try {
            $stmt->execute([$marca, $modelo]);
            return (int)$conn->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("El modelo ya existe para esa marca.");
            }
            throw $e;
        }
    }
}

if (!function_exists('actualizarModelo')) {
    function actualizarModelo(int $id, string $marca, string $modelo): bool {
        $conn = conectarBD();
        $stmt = $conn->prepare("UPDATE modelos SET marca = ?, modelo = ? WHERE id = ?");
        try {
            return $stmt->execute([$marca, $modelo, $id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new Exception("El modelo ya existe para esa marca.");
            }
            throw $e;
        }
    }
}

if (!function_exists('eliminarModelo')) {
    function eliminarModelo(int $id): bool {
        $conn = conectarBD();
        $stmt = $conn->prepare("DELETE FROM modelos WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

// ==================================================
// CRUD COMPATIBILIDADES
// ==================================================
if (!function_exists('insertarCompatibilidad')) {
    function insertarCompatibilidad(int $modelo_id, int $compatible_id, string $tipo, ?string $nota = null): bool {
        $conn = conectarBD();
        $stmt = $conn->prepare("INSERT INTO compatibilidades (modelo_id, compatible_id, tipo, nota) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$modelo_id, $compatible_id, $tipo, $nota]);
    }
}

if (!function_exists('obtenerCompatibilidadesPorModelo')) {
    function obtenerCompatibilidadesPorModelo(int $modelo_id, ?string $tipo = null): array {
        $conn = conectarBD();

        $sql = "
            SELECT c.tipo, m2.marca, m2.modelo, GROUP_CONCAT(DISTINCT c.nota SEPARATOR '. ') AS nota
            FROM compatibilidades c
            JOIN modelos m2 ON c.compatible_id = m2.id
            WHERE c.modelo_id = ?
        ";
        $params = [$modelo_id];

        if ($tipo) {
            $sql .= " AND c.tipo = ?";
            $params[] = $tipo;
        }

        $sql .= " GROUP BY c.tipo, m2.id ORDER BY c.tipo, m2.marca, m2.modelo";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('eliminarCompatibilidad')) {
    function eliminarCompatibilidad(int $id): bool {
        $conn = conectarBD();
        $stmt = $conn->prepare("DELETE FROM compatibilidades WHERE id = ?");
        return $stmt->execute([$id]);
    }
}



if (!function_exists('obtenerModeloPorNombre')) {
    /**
     * Busca un modelo por la combinación "marca + modelo" (exacto)
     */
    function obtenerModeloPorNombre(string $nombre): ?array {
        $conn = conectarBD();
        $stmt = $conn->prepare("SELECT * FROM modelos WHERE CONCAT(marca,' ',modelo) = ?");
        $stmt->execute([$nombre]);
        return $stmt->fetch() ?: null;
    }
}

if (!function_exists('obtenerTodasCompatibilidades')) {
    /**
     * Devuelve todas las compatibilidades con nombres completos
     */
    function obtenerTodasCompatibilidades(): array {
        $conn = conectarBD();
        $sql = "
            SELECT c.id, c.tipo, c.nota,
                   m1.marca AS marca1, m1.modelo AS modelo1,
                   m2.marca AS marca2, m2.modelo AS modelo2
            FROM compatibilidades c
            JOIN modelos m1 ON c.modelo_id = m1.id
            JOIN modelos m2 ON c.compatible_id = m2.id
            ORDER BY c.tipo, m1.marca, m1.modelo, m2.marca, m2.modelo
        ";
        $stmt = $conn->query($sql);
        return $stmt->fetchAll();
    }
}

//funcion para obtenersucursales ocupado en el archivo de garantias.php en el apartado vendedor 

function obtenerSucursales(): array
{
    try {
        $conn = conectarBD();

        // Solo sucursales activas (estatus = 1)
        $query = "SELECT id, nombre 
                  FROM sucursales 
                  WHERE estatus = 1 
                  ORDER BY nombre ASC";

        $stmt = $conn->prepare($query);
        $stmt->execute();

        $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $sucursales ?: [];
    } catch (PDOException $e) {
        error_log(sprintf('[%s] Error en obtenerSucursales: %s', date('Y-m-d H:i:s'), $e->getMessage()));
        return [];
    } finally {
        $conn = null; // cerrar conexión
    }
}
//Opbtiene sucursales y call center
function obtenerSucursalesdos(): array
{
    try {
        $conn = conectarBD();

        // Solo sucursales activas (estatus = 1)
        $query = "SELECT id, nombre 
                  FROM sucursales 
                  WHERE estatus = 1 or estatus = 3
                  ORDER BY nombre ASC";

        $stmt = $conn->prepare($query);
        $stmt->execute();

        $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $sucursales ?: [];
    } catch (PDOException $e) {
        error_log(sprintf('[%s] Error en obtenerSucursales: %s', date('Y-m-d H:i:s'), $e->getMessage()));
        return [];
    } finally {
        $conn = null; // cerrar conexión
    }
}
//esta es usada para obterne la meta de im por tienda en kpis
function obtenerMetasTiendas(string $depto = 'IM'): array {
    $campo = $depto === 'TM' ? 'metaTM' : 'metaIM';

    try {
        $conexion = conectarBD();
        $stmt = $conexion->prepare(
            "SELECT nombre, {$campo} AS meta FROM sucursales WHERE estatus = 1"
        );
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt     = null;
        $conexion = null;

        $metas = [];
        foreach ($resultados as $row) {
            // Prefijo igual que el original para que coincida con el Excel
            $key = "Central Cell " . trim($row['nombre']);
            $metas[$key] = [
                'diaria' => floatval($row['meta']),
                'limite' => 9999
            ];
        }
        return $metas;

    } catch (PDOException $e) {
        error_log("Error al obtener metas de tiendas: " . $e->getMessage());
        return [];
    }
}


function obtenerSucursalesActivas(): array {
    try {
        $conn = conectarBD();
        $stmt = $conn->query("SELECT * FROM sucursales WHERE estatus = 1 ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obtenerSucursalesActivas: " . $e->getMessage());
        throw new Exception("No se pudieron obtener las sucursales activas.");
    }
}


function obtenerSucursalesEliminadas(): array {
    try {
        $conn = conectarBD();
        $stmt = $conn->query("SELECT * FROM sucursales WHERE estatus = 2 ORDER BY nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obtenerSucursalesEliminadas: " . $e->getMessage());
        throw new Exception("No se pudieron obtener las sucursales eliminadas.");
    }
}


function agregarSucursal(string $nombre, float $metaIM, float $metaTM = 0): bool {
    if (empty(trim($nombre)))
        throw new InvalidArgumentException("El nombre de la sucursal no puede estar vacío.");
    if ($metaIM < 0)
        throw new InvalidArgumentException("La meta IM no puede ser negativa.");
    if ($metaTM < 0)
        throw new InvalidArgumentException("La meta TM no puede ser negativa.");

    try {
        $conn = conectarBD();
        $stmt = $conn->prepare(
            "INSERT INTO sucursales (nombre, metaIM, metaTM, estatus)
             VALUES (:nombre, :metaIM, :metaTM, 1)"
        );
        return $stmt->execute([
            ':nombre' => trim($nombre),
            ':metaIM' => $metaIM,
            ':metaTM' => $metaTM,
        ]);
    } catch (PDOException $e) {
        error_log("Error agregarSucursal: " . $e->getMessage());
        throw new Exception("No se pudo agregar la sucursal.");
    }
}


// ── Agregar al final de funciones.php ──────────────────────────────────────────

/**
 * Guarda metaIM y metaTM de todas las sucursales en una sola transacción.
 * $metas = [ id => ['im' => valor, 'tm' => valor], ... ]
 */
function actualizarTodasLasMetas(array $metas): bool {
    if (empty($metas)) return false;

    try {
        $conn = conectarBD();
        $conn->beginTransaction();

        $stmt = $conn->prepare(
            "UPDATE sucursales SET metaIM = :im, metaTM = :tm WHERE id = :id"
        );

        foreach ($metas as $id => $valores) {
            $id = (int) $id;
            $im = max(0, (float) ($valores['im'] ?? 0));
            $tm = max(0, (float) ($valores['tm'] ?? 0));

            if ($id <= 0) continue;

            $stmt->execute([':im' => $im, ':tm' => $tm, ':id' => $id]);
        }

        $conn->commit();
        return true;

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error actualizarTodasLasMetas: " . $e->getMessage());
        return false;
    }
}


function eliminarSucursal(int $id): bool {
    if ($id <= 0) {
        throw new InvalidArgumentException("ID inválido.");
    }

    try {
        $conn = conectarBD();
        $sql = "UPDATE sucursales SET estatus = 2 WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log("Error eliminarSucursal: " . $e->getMessage());
        throw new Exception("No se pudo eliminar la sucursal.");
    }
}


function eliminarSucursalDefinitivamente(int $id): bool {
    if ($id <= 0) {
        throw new InvalidArgumentException("ID inválido.");
    }

    $conn = conectarBD();
    $conn->beginTransaction();

    try {
        // Eliminar garantías relacionadas
        $stmtGarantia = $conn->prepare("DELETE FROM garantia WHERE sucursal = :id");
        $stmtGarantia->execute([':id' => $id]);

        // Eliminar la sucursal
        $stmtSucursal = $conn->prepare("DELETE FROM sucursales WHERE id = :id");
        $stmtSucursal->execute([':id' => $id]);

        $conn->commit();
        return true;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error eliminarSucursalDefinitivamente: " . $e->getMessage());
        throw new Exception("No se pudo eliminar definitivamente la sucursal.");
    }
}


function consultarGarantias(string $fechaInicio, string $fechaFin, string $tipo): array {
    try {
        $conn = conectarBD();

        $sql = "SELECT 
                    g.tipo,
                    g.causa,
                    COALESCE(g.piezas, 0) AS piezas,
                    s.nombre AS sucursal,
                    g.fecha
                FROM garantia g
                LEFT JOIN sucursales s ON g.sucursal = s.id
                WHERE g.fecha BETWEEN :fechaInicio AND :fechaFin
                  AND g.estatus = 'Ajuste Realizado'
                  AND g.tipo = :tipo
                ORDER BY s.nombre ASC, g.fecha ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':fechaInicio', $fechaInicio);
        $stmt->bindValue(':fechaFin', $fechaFin);
        $stmt->bindValue(':tipo', $tipo);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error al consultar garantías: " . $e->getMessage());
        return [];
    }
}

function guardarproductosnegados($datos) {
    // Conectar
    $conn = conectarBD();

    // Forzar modo de errores a excepciones (por si conectarBD no lo hace)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        // Comenzamos transacción por seguridad
        $conn->beginTransaction();

        // 1) Buscar colaborador por nombre (trim y case-insensitive)
        $sql = "SELECT id FROM colaboradores WHERE TRIM(nombre) = :nombre LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':nombre' => trim($datos['apasionado'])]);
        $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($colaborador) {
            $idColaborador = (int)$colaborador['id'];
        } else {
            // Insertar nuevo colaborador
            $sqlInsert = "INSERT INTO colaboradores (nombre) VALUES (:nombre)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->execute([':nombre' => trim($datos['apasionado'])]);
            $idColaborador = (int)$conn->lastInsertId();

            if ($idColaborador == 0) {
                throw new Exception("No se pudo insertar el colaborador.");
            }
        }

        // 2) Validar sucursal (activa)
        $sqlSucursal = "SELECT id FROM sucursales WHERE id = :id AND estatus = 1 OR estatus = 3 LIMIT 1";
        $stmtSucursal = $conn->prepare($sqlSucursal);
        $stmtSucursal->execute([':id' => $datos['sucursal']]);
        $sucursalValida = $stmtSucursal->fetch(PDO::FETCH_ASSOC);

        if (!$sucursalValida) {
            throw new Exception("Sucursal inválida o inactiva.");
        }

        // 3) Insertar en bitacora
        $sqlGarantia = "INSERT INTO bitacora
            (Marca_Modelo, producto, sucursal, Estatus, nombre, Anotaciones, indicador)
            VALUES
            (:marca, :producto, :sucursal, :estatus, :nombre, :anotaciones, :indicador)";

        $stmtGarantia = $conn->prepare($sqlGarantia);
        $stmtGarantia->execute([
            ':marca' => strtoupper($datos['marca_modelo']),
            ':producto' => $datos['producto'],
            ':sucursal' => (int)$datos['sucursal'],
            ':estatus' => $datos['estatus'],
            ':nombre' => $idColaborador,
            ':anotaciones' => $datos['anotaciones_vendedor'] ?? null,
            ':indicador' => 1
        ]);

        // Comprobamos que se inserto al menos 1 fila
        if ($stmtGarantia->rowCount() === 0) {
            // Puede pasar en drivers que rowCount() no sea fiable en INSERT, así que hacemos otra verificación
            $lastId = (int)$conn->lastInsertId();
            if ($lastId === 0) {
                throw new Exception("La inserción en bitacora no afectó filas (rowCount=0 y lastInsertId=0).");
            }
        } else {
            $lastId = (int)$conn->lastInsertId();
        }

        // Commit
        $conn->commit();

        // Retornar id insertado para confirmación
        return $lastId;

    } catch (Exception $e) {
        // Rollback por si hubo beginTransaction
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        // Re-lanzar para que el front capture y muestre el mensaje de error
        throw new Exception("Error al guardar Producto en bitacora: " . $e->getMessage());
    }
}

function obtenerBitacora(): array {
    try {
        $conexion = conectarBD();

        $sql = "SELECT 
            b.id,
            b.Marca_Modelo,
            b.producto,
            s.nombre AS sucursal,
            c.nombre AS nombre_colaborador,
            b.Estatus,
            b.Anotaciones,
            b.fecha,
            b.indicador
        FROM bitacora b
        LEFT JOIN sucursales s ON b.sucursal = s.id
        LEFT JOIN colaboradores c ON b.nombre = c.id
        ORDER BY b.fecha DESC, b.id DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error al consultar la bitácora: " . $e->getMessage());
        return [];
    }
}

function obtenerMermasFrecuentes(string $fechaInicio, string $fechaFin): array
{
    try {
        $pdo = conectarBD();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "
            SELECT 
                tipo, 
                plows, 
                SUM(piezas) AS total_mermas
            FROM 
                garantia
            WHERE 
                fecha BETWEEN :inicio AND :fin
                AND estatus = 'Ajuste Realizado'
            GROUP BY 
                tipo, plows
            ORDER BY 
                total_mermas DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':inicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':fin', $fechaFin, PDO::PARAM_STR);
        $stmt->execute();

        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $resultados ?: [];

    } catch (PDOException $e) {
        error_log('Error en obtenerMermasFrecuentes: ' . $e->getMessage());
        return [];
    } finally {
        $pdo = null; // Cerrar conexión explícitamente
    }
}

//  Eliminar registro de bitácora por ID
function eliminarBitacoraPorId(int $id): bool {
    try {
        $conn = conectarBD();
        $sql = "DELETE FROM bitacora WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error al eliminar registro: " . $e->getMessage());
        return false;
    }
}

// 🔹 Endpoint AJAX (para borrar registro)
if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_bitacora') {
    $id = intval($_POST['id']);
    $resultado = eliminarBitacoraPorId($id);
    echo json_encode(['success' => $resultado]);
    exit;
}

function actualizarValidacionGarantia(PDO $conn, $id, $plows, $piezas_validadas, $numero_ajuste, $anotaciones_validador) {
    $sql = "UPDATE garantia 
            SET plows = :plows,
                piezas_validadas = :piezas_validadas,
                numero_ajuste = :numero_ajuste,
                anotaciones_validador = :anotaciones_validador
            WHERE id = :id";

    $stmt = $conn->prepare($sql);
    return $stmt->execute([
        ':id' => $id,
        ':plows' => $plows,
        ':piezas_validadas' => $piezas_validadas,
        ':numero_ajuste' => $numero_ajuste,
        ':anotaciones_validador' => $anotaciones_validador
    ]);
}
// Elimina todos los registros de la tabla existencias

function eliminarExistencias(): bool {
    try {
        $conn = conectarBD();
        $conn->exec("DELETE FROM existencias");
        return true;
    } catch (Exception $e) {
        error_log("Error al eliminar existencias: " . $e->getMessage());
        return false;
    }
}

//Reinicia el AUTO_INCREMENT de la tabla existencias a 1
function reiniciarIDsExistencias(): bool {
    try {
        $conn = conectarBD();
        $conn->exec("ALTER TABLE existencias AUTO_INCREMENT = 1");
        return true;
    } catch (Exception $e) {
        error_log("Error al reiniciar IDs: " . $e->getMessage());
        return false;
    }
}

// Obtiene el ID de una sucursal por su nombre

function obtenerIDSucursal(string $nombreSucursal): ?int {
    try {
        $conn = conectarBD();
        $stmt = $conn->prepare("SELECT id FROM sucursales WHERE nombre = :nombre");
        $stmt->execute(['nombre' => $nombreSucursal]);
        $resultado = $stmt->fetch();
        
        return $resultado ? (int)$resultado['id'] : null;
    } catch (Exception $e) {
        error_log("Error al buscar sucursal: " . $e->getMessage());
        return null;
    }
}

//Inserta un registro en la tabla existencias
function insertarExistencia(int $almacen, string $descripcion, int $existencia, string $barcodeId, float $publicoGeneral): bool {
    try {
        $conn = conectarBD();
        $stmt = $conn->prepare("
            INSERT INTO existencias (almacen, descripcion, existencia, BarcodeId, publico_general)
            VALUES (:almacen, :descripcion, :existencia, :barcodeId, :publicoGeneral)
        ");

        
        return $stmt->execute([
            'almacen' => $almacen,
            'descripcion' => $descripcion,
            'existencia' => $existencia,
            'barcodeId' => $barcodeId,
            'publicoGeneral' => $publicoGeneral
        ]);
    } catch (Exception $e) {
        error_log("Error al insertar existencia: " . $e->getMessage());
        return false;
    }
}

//Convierte la referencia de columna (A, B, AA, etc.) a índice numérico
function columnaAIndice(string $columna): int {
    $columna = strtoupper($columna);
    $indice = 0;
    $longitud = strlen($columna);
    
    for ($i = 0; $i < $longitud; $i++) {
        $indice = $indice * 26 + (ord($columna[$i]) - ord('A') + 1);
    }
    
    return $indice - 1; // Restar 1 porque los arrays empiezan en 0
}

//Procesa el archivo Excel y carga los datos en la tabla existencias
function insertarExistenciasBulk(PDO $conn, array $filas): array
{
    if (empty($filas)) {
        return ['insertados' => 0, 'fallidos' => []];
    }
 
    $insertados = 0;
    $fallidos   = [];
    $chunkSize  = 500; // filas por query INSERT
 
    foreach (array_chunk($filas, $chunkSize) as $chunk) {
        // Construir placeholders: (?,?,?,?,?,?,?)
        $placeholders = implode(
            ', ',
            array_fill(0, count($chunk), '(?,?,?,?,?,?)')
        );
 
        $sql = "INSERT INTO existencias
                    (almacen, descripcion, existencia, BarcodeId, categoria, publico_general)
                VALUES $placeholders";
 
        $params = [];
        foreach ($chunk as $fila) {
            $params[] = $fila['almacen'];
            $params[] = $fila['descripcion'];
            $params[] = $fila['existencia'];
            $params[] = $fila['barcodeId'];
            $params[] = $fila['categoria'];
            $params[] = $fila['publicoGeneral'];
        }
 
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $insertados += $stmt->rowCount();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
            // Si falla el batch completo, registrar todas las filas como fallidas
            foreach ($chunk as $fila) {
                $fallidos[] = [
                    'fila'        => $fila['fila'],
                    'almacen'     => $fila['almacenNombre'],
                    'descripcion' => $fila['descripcion'],
                    'motivo'      => 'Error BD (batch): ' . $e->getMessage(),
                ];
            }
        }
    }
 
    return ['insertados' => $insertados, 'fallidos' => $fallidos];
}
 
/**
 * Función principal — reemplaza la anterior procesarArchivoExcel()
 */
function procesarArchivoExcel(string $rutaArchivo): array
{
    $resultado = [
        'exito'               => false,
        'registros_insertados' => 0,
        'registros_omitidos'  => [],
        'mensaje'             => '',
    ];

    try {
        if (!eliminarExistencias()) {
            $resultado['mensaje'] = 'Error al eliminar registros existentes';
            return $resultado;
        }
        if (!reiniciarIDsExistencias()) {
            $resultado['mensaje'] = 'Error al reiniciar IDs';
            return $resultado;
        }

        $zip = new ZipArchive();
        if ($zip->open($rutaArchivo) !== true) {
            $resultado['mensaje'] = 'No se pudo abrir el archivo Excel';
            return $resultado;
        }

        $sheetXml         = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        // ── Shared strings ──────────────────────────────────────────
        $sharedStrings = [];
        if ($sharedStringsXml) {
            $xmlSS = new SimpleXMLElement($sharedStringsXml);
            foreach ($xmlSS->si as $si) {
                $texto = '';
                if (isset($si->t)) {
                    $texto = (string) $si->t;
                } else {
                    foreach ($si->r as $r) {
                        $texto .= (string) $r->t;
                    }
                }
                $sharedStrings[] = $texto;
            }
        }

        // ── Parsear hoja ─────────────────────────────────────────────
        $xmlSheet    = new SimpleXMLElement($sheetXml);
        $primeraFila = true;
        $filasValidas = [];
        $omitidos     = [];
        $cacheAlmacen = [];

        foreach ($xmlSheet->sheetData->row as $row) {
            if ($primeraFila) {
                $primeraFila = false;
                continue;
            }

            $numFila = (int) $row['r'];

            // ── Leer celdas ──────────────────────────────────────────
            $celdas = [];
            foreach ($row->c as $c) {
                preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
                $col = $m[1];
                $v   = isset($c->v) ? (string) $c->v : '';
                if (isset($c['t']) && $c['t'] == 's') {
                    $v = $sharedStrings[(int) $v] ?? '';
                }
                $celdas[$col] = $v;
            }

            // ── Extraer campos ───────────────────────────────────────
            $almacenCompleto = trim($celdas['A'] ?? '');
            $descripcion     = trim($celdas['C'] ?? '');
            $existencia      = (int) ($celdas['H'] ?? 0);
            $barcodeId       = trim($celdas['M'] ?? '');
            $nombreCategoria = trim($celdas['N'] ?? '');
            $publicoGeneral  = isset($celdas['Q']) && $celdas['Q'] !== ''
                                   ? (float) $celdas['Q']
                                   : null;

            // ── FILTROS SILENCIOSOS (sin reportar) ───────────────────
            if (stripos($almacenCompleto, 'Almacén general') !== false) continue;
            if (stripos($nombreCategoria, 'SOLUCIONES TECNICAS') !== false) continue;
            if ($existencia == 0) continue;

            // ── VALIDACIONES CON REPORTE ─────────────────────────────
            $motivo = validarFila(
                $almacenCompleto,
                $descripcion,
                $existencia,
                $barcodeId,
                $publicoGeneral
            );

            if ($motivo !== null) {
                $omitidos[] = [
                    'fila'        => $numFila,
                    'almacen'     => $almacenCompleto ?: '(vacío)',
                    'descripcion' => $descripcion     ?: '(vacío)',
                    'motivo'      => $motivo,
                ];
                continue;
            }

            // ── Resolver almacén ─────────────────────────────────────
            $nombreAlmacen = (strpos($almacenCompleto, 'Central Cell ') === 0)
                ? trim(substr($almacenCompleto, strlen('Central Cell ')))
                : $almacenCompleto;

            if (!array_key_exists($nombreAlmacen, $cacheAlmacen)) {
                $cacheAlmacen[$nombreAlmacen] = obtenerIDSucursal($nombreAlmacen);
            }
            $idAlmacen = $cacheAlmacen[$nombreAlmacen];

            if ($idAlmacen === null) {
                $omitidos[] = [
                    'fila'        => $numFila,
                    'almacen'     => $almacenCompleto,
                    'descripcion' => $descripcion,
                    'motivo'      => 'Almacén no encontrado en la base de datos',
                ];
                continue;
            }

            $filasValidas[] = [
                'fila'           => $numFila,
                'almacen'        => $idAlmacen,
                'almacenNombre'  => $almacenCompleto,
                'descripcion'    => $descripcion,
                'existencia'     => $existencia,
                'barcodeId'      => $barcodeId,
                'categoria'      => $nombreCategoria,
                'publicoGeneral' => $publicoGeneral ?? 0.0,
            ];
        }

        // ── Inserción masiva ─────────────────────────────────────────
        $conn = conectarBD();
        $bulk = insertarExistenciasBulk($conn, $filasValidas);

        $resultado['exito']                = true;
        $resultado['registros_insertados'] = $bulk['insertados'];
        $resultado['registros_omitidos']   = array_merge($omitidos, $bulk['fallidos']);

        // ── Registrar fecha/hora de actualización (hora México) ──────
        registrarFechaExistencias($conn); // ← pasa la conexión existente

        $total   = $bulk['insertados'];
        $errores = count($resultado['registros_omitidos']);

        $resultado['mensaje'] = $errores > 0
            ? "Proceso completado. $total insertados, $errores con errores."
            : "Proceso completado exitosamente. $total registros insertados.";

    } catch (Exception $e) {
        $resultado['mensaje'] = 'Error: ' . $e->getMessage();
    }

    return $resultado;
}
 
/**
 * Valida una fila y retorna el motivo de rechazo o null si es válida.
 */
function validarFila(
    string $almacen,
    string $descripcion,
    int    $existencia,
    string $barcodeId,
    ?float $publicoGeneral
): ?string {
    if (empty($almacen))       return 'Almacén vacío';
    if (empty($descripcion))   return 'Descripción vacía';
    if (empty($barcodeId))     return 'Falta código de barras (columna M)';
    if ($existencia <= 0)      return 'Existencia inválida o cero';
    if ($publicoGeneral === null || $publicoGeneral < 0) {
        return 'Precio inválido o ausente (columna Q)';
    }
 
    // Descripción demasiado corta (probable basura)
    if (strlen($descripcion) < 3) return 'Descripción demasiado corta';
 
    return null; // fila válida
}


function registrarFechaExistencias(PDO $conn): bool
{
    try {
        // Hora México via PHP (no depende de las timezone tables de MySQL)
        $tz     = new DateTimeZone('America/Mexico_City');
        $ahora  = (new DateTime('now', $tz))->format('Y-m-d H:i:s');

        $stmt = $conn->query("SELECT id FROM fechaexistencias LIMIT 1");
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila) {
            $upd = $conn->prepare(
                "UPDATE fechaexistencias SET fecha = :fecha WHERE id = :id"
            );
            $upd->execute([':fecha' => $ahora, ':id' => $fila['id']]);
        } else {
            $ins = $conn->prepare(
                "INSERT INTO fechaexistencias (fecha) VALUES (:fecha)"
            );
            $ins->execute([':fecha' => $ahora]);
        }

        return true;
    } catch (Exception $e) {
        error_log('registrarFechaExistencias error: ' . $e->getMessage());
        return false;
    }
}

function procesarArchivoExceltel(string $rutaArchivo): array
{
    $resultado = [
        'exito'               => false,
        'registros_insertados' => 0,
        'registros_omitidos'  => [],
        'mensaje'             => '',
    ];

    // ── Categorías permitidas (columna N) ────────────────────────
    $categoriasPermitidas = [
        'TECNOLOGIA MOVIL>SMARTPHONE>PROPIOS',
        'TECNOLOGIA MOVIL>SMARTPHONE>BATYCELL',
        'TECNOLOGIA MOVIL>EQUIPO BASICO',
        'TECNOLOGIA MOVIL>SMARTWHATCH',
    ];

    try {
        if (!eliminarExistencias()) {
            $resultado['mensaje'] = 'Error al eliminar registros existentes';
            return $resultado;
        }
        if (!reiniciarIDsExistencias()) {
            $resultado['mensaje'] = 'Error al reiniciar IDs';
            return $resultado;
        }

        $zip = new ZipArchive();
        if ($zip->open($rutaArchivo) !== true) {
            $resultado['mensaje'] = 'No se pudo abrir el archivo Excel';
            return $resultado;
        }

        $sheetXml         = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        // ── Shared strings ──────────────────────────────────────────
        $sharedStrings = [];
        if ($sharedStringsXml) {
            $xmlSS = new SimpleXMLElement($sharedStringsXml);
            foreach ($xmlSS->si as $si) {
                $texto = '';
                if (isset($si->t)) {
                    $texto = (string) $si->t;
                } else {
                    foreach ($si->r as $r) {
                        $texto .= (string) $r->t;
                    }
                }
                $sharedStrings[] = $texto;
            }
        }

        // ── Parsear hoja ─────────────────────────────────────────────
        $xmlSheet    = new SimpleXMLElement($sheetXml);
        $primeraFila = true;
        $filasValidas = [];
        $omitidos     = [];
        $cacheAlmacen = [];

        foreach ($xmlSheet->sheetData->row as $row) {
            if ($primeraFila) {
                $primeraFila = false;
                continue;
            }

            $numFila = (int) $row['r'];

            // ── Leer celdas ──────────────────────────────────────────
            $celdas = [];
            foreach ($row->c as $c) {
                preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
                $col = $m[1];
                $v   = isset($c->v) ? (string) $c->v : '';
                if (isset($c['t']) && $c['t'] == 's') {
                    $v = $sharedStrings[(int) $v] ?? '';
                }
                $celdas[$col] = $v;
            }

            // ── Extraer campos ───────────────────────────────────────
            $almacenCompleto = trim($celdas['A'] ?? '');
            $descripcion     = trim($celdas['C'] ?? '');
            $existencia      = (int) ($celdas['H'] ?? 0);
            $barcodeId       = trim($celdas['M'] ?? '');
            $nombreCategoria = trim($celdas['N'] ?? '');
            $publicoGeneral  = isset($celdas['Q']) && $celdas['Q'] !== ''
                                   ? (float) $celdas['Q']
                                   : null;

            // ── FILTROS SILENCIOSOS (sin reportar) ───────────────────
            if (stripos($almacenCompleto, 'Almacén general') !== false) continue;
            if ($existencia == 0) continue;

            // ── FILTRO POR CATEGORÍA (silencioso) ────────────────────
            if (!in_array($nombreCategoria, $categoriasPermitidas, true)) continue;

            // ── VALIDACIONES CON REPORTE ─────────────────────────────
            $motivo = validarFila(
                $almacenCompleto,
                $descripcion,
                $existencia,
                $barcodeId,
                $publicoGeneral
            );

            if ($motivo !== null) {
                $omitidos[] = [
                    'fila'        => $numFila,
                    'almacen'     => $almacenCompleto ?: '(vacío)',
                    'descripcion' => $descripcion     ?: '(vacío)',
                    'motivo'      => $motivo,
                ];
                continue;
            }

            // ── Resolver almacén ─────────────────────────────────────
            $nombreAlmacen = (strpos($almacenCompleto, 'Central Cell ') === 0)
                ? trim(substr($almacenCompleto, strlen('Central Cell ')))
                : $almacenCompleto;

            if (!array_key_exists($nombreAlmacen, $cacheAlmacen)) {
                $cacheAlmacen[$nombreAlmacen] = obtenerIDSucursal($nombreAlmacen);
            }
            $idAlmacen = $cacheAlmacen[$nombreAlmacen];

            if ($idAlmacen === null) {
                $omitidos[] = [
                    'fila'        => $numFila,
                    'almacen'     => $almacenCompleto,
                    'descripcion' => $descripcion,
                    'motivo'      => 'Almacén no encontrado en la base de datos',
                ];
                continue;
            }

            $filasValidas[] = [
                'fila'           => $numFila,
                'almacen'        => $idAlmacen,
                'almacenNombre'  => $almacenCompleto,
                'descripcion'    => $descripcion,
                'existencia'     => $existencia,
                'barcodeId'      => $barcodeId,
                'categoria'      => $nombreCategoria,
                'publicoGeneral' => $publicoGeneral ?? 0.0,
            ];
        }

        // ── Inserción masiva ─────────────────────────────────────────
        $conn = conectarBD();
        $bulk = insertarExistenciasBulk($conn, $filasValidas);

        $resultado['exito']                = true;
        $resultado['registros_insertados'] = $bulk['insertados'];
        $resultado['registros_omitidos']   = array_merge($omitidos, $bulk['fallidos']);

        registrarFechaExistencias($conn);

        $total   = $bulk['insertados'];
        $errores = count($resultado['registros_omitidos']);

        $resultado['mensaje'] = $errores > 0
            ? "Proceso completado. $total insertados, $errores con errores."
            : "Proceso completado exitosamente. $total registros insertados.";

    } catch (Exception $e) {
        $resultado['mensaje'] = 'Error: ' . $e->getMessage();
    }

    return $resultado;
}
//aqui es el buscador 
//Obtiene el nombre de un almacén por su ID
function obtenerNombreAlmacen(int $idAlmacen): ?string {
    try {
        $conn = conectarBD();
        $stmt = $conn->prepare("SELECT nombre FROM sucursales WHERE id = :id");
        $stmt->execute(['id' => $idAlmacen]);
        $resultado = $stmt->fetch();
        
        return $resultado ? $resultado['nombre'] : null;
    } catch (Exception $e) {
        error_log("Error al obtener nombre del almacén: " . $e->getMessage());
        return null;
    }
}

/*
 Busca sugerencias de productos para el autocompletado
 SOLUCIÓN: Usa COLLATE utf8mb4_general_ci para búsqueda case-insensitive REAL
*/
function buscarSugerencias(string $termino): array {
    try {
        $conn = conectarBD();
        
        // Agregar % solo al FINAL para que busque "que EMPIECE con"
        $terminoBusqueda = $termino . '%';
        
        $stmt = $conn->prepare("
            SELECT DISTINCT descripcion, BarcodeId
            FROM existencias
            WHERE descripcion COLLATE utf8mb4_general_ci LIKE :termino 
               OR BarcodeId COLLATE utf8mb4_general_ci LIKE :termino
            GROUP BY descripcion
            ORDER BY descripcion ASC
            LIMIT 10
        ");
        
        $stmt->execute(['termino' => $terminoBusqueda]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error al buscar sugerencias: " . $e->getMessage());
        return [];
    }
}

/*
  Busca productos por descripción o BarcodeId
  SOLUCIÓN: Usa COLLATE utf8mb4_general_ci para búsqueda case-insensitive REAL
*/
function buscarProductos(string $termino): array {
    try {
        if (empty(trim($termino))) {
            return [];
        }

        $conn = conectarBD();
        $terminoTrim = trim($termino);

        /* =====================================================
           1️⃣ Buscar por BarcodeId EXACTO (prioridad máxima)
        ===================================================== */
        $stmt = $conn->prepare("
            SELECT e.*, s.nombre AS nombre_almacen
            FROM existencias e
            LEFT JOIN sucursales s ON e.almacen = s.id
            WHERE e.BarcodeId COLLATE utf8mb4_general_ci = :barcode
            ORDER BY s.nombre ASC
        ");
        $stmt->execute(['barcode' => $terminoTrim]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($resultados)) {
            return $resultados;
        }

        /* =====================================================
           2️⃣ Extraer modelo exacto (X7D, X8A, A15, etc)
        ===================================================== */
        preg_match('/\b[A-Z]+\d+[A-Z]?\b/', strtoupper($terminoTrim), $match);
        $modelo = $match[0] ?? null;

        /* =====================================================
           3️⃣ Buscar SOLO si contiene el modelo exacto
        ===================================================== */
        if ($modelo) {
            $stmt = $conn->prepare("
                SELECT e.*, s.nombre AS nombre_almacen
                FROM existencias e
                LEFT JOIN sucursales s ON e.almacen = s.id
                WHERE e.descripcion REGEXP CONCAT('[[:<:]]', :modelo, '[[:>:]]')
                ORDER BY s.nombre ASC, e.descripcion ASC
            ");
            $stmt->execute(['modelo' => $modelo]);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($resultados)) {
                return $resultados;
            }
        }

        /* =====================================================
           4️⃣ ÚLTIMO RECURSO (LIKE controlado)
           (No se dispara si ya hubo coincidencias)
        ===================================================== */
        $terminoLike = '%' . preg_replace('/\s+/', '%', $terminoTrim) . '%';

        $stmt = $conn->prepare("
            SELECT e.*, s.nombre AS nombre_almacen
            FROM existencias e
            LEFT JOIN sucursales s ON e.almacen = s.id
            WHERE e.descripcion COLLATE utf8mb4_general_ci LIKE :termino
            ORDER BY s.nombre ASC, e.descripcion ASC
            LIMIT 200
        ");
        $stmt->execute(['termino' => $terminoLike]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        error_log("Error al buscar productos: " . $e->getMessage());
        return [];
    }
}
function obtenerSucursalesConMetas(): array {
    try {
        $conn = conectarBD();
        $sql = "SELECT id, nombre, metaIM, estatus 
                FROM sucursales 
                WHERE estatus = 1 
                ORDER BY nombre ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al obtener sucursales: " . $e->getMessage());
        throw new Exception("Error al cargar las sucursales");
    }
}

/**
 * Obtiene la meta de una sucursal específica
 * @param int $idSucursal ID de la sucursal
 * @return array|null Datos de la sucursal o null si no existe
 */
function obtenerMetaSucursal(int $idSucursal): ?array {
    try {
        $conn = conectarBD();
        $sql = "SELECT id, nombre, metaIM, estatus 
                FROM sucursales 
                WHERE id = :id AND estatus = 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $idSucursal, PDO::PARAM_INT);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    } catch (PDOException $e) {
        error_log("Error al obtener meta de sucursal: " . $e->getMessage());
        throw new Exception("Error al cargar la meta de la sucursal");
    }
}


/**
 * Calcula las metas diarias y semanales para una tienda y sus vendedores
 * @param float $metaDiaria Meta diaria de la tienda
 * @param int $plantilla Número de vendedores en la plantilla
 * @return array Cálculos de metas
 */
function calcularMetas(float $metaDiaria, int $plantilla): array {
    $metaSemanal = $metaDiaria * 7;
    $metaIndividualDiaria = $plantilla > 0 ? $metaDiaria / $plantilla : 0;
    $metaIndividualSemanal = $plantilla > 0 ? $metaSemanal / $plantilla : 0;
    
    return [
        'tienda' => [
            'diaria' => $metaDiaria,
            'semanal' => $metaSemanal
        ],
        'individual' => [
            'diaria' => $metaIndividualDiaria,
            'semanal' => $metaIndividualSemanal
        ],
        'plantilla' => $plantilla
    ];
}


function obtenerFechaUltimaActualizacion(): ?string
{
    try {
        $conn = conectarBD();
        $stmt = $conn->query("SELECT fecha FROM fechaexistencias LIMIT 1");
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) return null;
 
        $dt    = new DateTime($fila['fecha'], new DateTimeZone('America/Mexico_City'));
        $dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
        $meses = [1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
                  7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'];
 
        return ucfirst($dias[(int)$dt->format('w')]) . ' ' . $dt->format('j') . ' de '
             . $meses[(int)$dt->format('n')] . ' de ' . $dt->format('Y') . ', ' . $dt->format('g:i A');
 
    } catch (Exception $e) {
        error_log('obtenerFechaUltimaActualizacion: ' . $e->getMessage());
        return null;
    }
}
 
/**
 * Extrae la descripción del modelo desde el campo descripcion.
 * Patrón: CÓDIGO - COLOR - ESTADO ( DESCRIPCIÓN / MARCA / MODELO )
 * Retorna la DESCRIPCIÓN interior (antes del primer /).
 */
function extraerDescripcionModelo(string $descripcion): string
{
    if (preg_match('/\(([^)]+)\)/', $descripcion, $m)) {
        $partes = explode('/', trim($m[1]));
        return trim($partes[0]);
    }
    return trim($descripcion);
}
 
/**
 * Extrae la marca. Patrón: ... ( DESCRIPCIÓN / MARCA / MODELO )
 */
function extraerMarca(string $descripcion): string
{
    if (preg_match('/\(([^)]+)\)/', $descripcion, $m)) {
        $partes = explode('/', trim($m[1]));
        return isset($partes[1]) ? trim($partes[1]) : 'Sin marca';
    }
    return 'Sin marca';
}
 
/**
 * Extrae el COLOR del campo descripcion.
 * Patrón: CÓDIGO - COLOR - ESTADO ( ... )
 * Ejemplo: "CSAMA26128 - NEGRO - NUEVO ( ... )" → "NEGRO"
 */
function extraerColor(string $descripcion): string
{
    // Tomar todo antes del paréntesis y dividir por " - "
    $sinParentesis = trim(preg_replace('/\(.*\)/s', '', $descripcion));
    $partes        = array_map('trim', explode('-', $sinParentesis));
    // índice 0 = CÓDIGO, índice 1 = COLOR, índice 2 = ESTADO
    return (isset($partes[1]) && $partes[1] !== '') ? strtoupper($partes[1]) : 'SIN COLOR';
}
 
/**
 * Obtiene la URL de la imagen de un teléfono.
 * Busca por descripcion exacta; fallback a "generaltelefono".
 */
function obtenerImagenTelefono(PDO $conn, string $descripcionModelo): string
{
    $stmt = $conn->prepare("SELECT direccion FROM imagenes WHERE descripcion = :desc LIMIT 1");
    $stmt->execute([':desc' => $descripcionModelo]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fila) return $fila['direccion'];
 
    $stmt2 = $conn->prepare("SELECT direccion FROM imagenes WHERE descripcion = 'generaltelefono' LIMIT 1");
    $stmt2->execute();
    $fila2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    return $fila2 ? $fila2['direccion'] : '';
}
 
/**
 * Obtiene todos los smartphones agrupados por modelo.
 *
 * Cada elemento:
 * [
 *   'descripcionModelo'    => 'SAMSUNG A26 5G 6+128 GB',
 *   'marca'                => 'SAMSUNG',
 *   'precio'               => 4999.00,          // mayor precio si hay inconsistencia
 *   'precio_inconsistente' => false,             // true si colores tienen precios distintos
 *   'precios_por_color'    => ['NEGRO'=>4999, 'BLANCO'=>4799],  // solo si hay inconsistencia
 *   'colores'              => ['AZUL', 'NEGRO'],
 *   'imagen'               => 'https://...',
 *   'sucursales'           => [
 *     ['nombre'=>'Centro', 'existencia'=>5, 'colores'=>['NEGRO','AZUL']],
 *   ]
 * ]
 */
function obtenerSmartphones(): array
{
    // Mapa: categoría BD → [nombre legible, orden]
    $categoriasMapa = [
        'TECNOLOGIA MOVIL>SMARTPHONE>PROPIOS'  => ['nombre' => 'Smartphones',   'orden' => 1],
        'TECNOLOGIA MOVIL>SMARTPHONE>BATYCELL' => ['nombre' => 'Smartphones',   'orden' => 1],
        'TECNOLOGIA MOVIL>EQUIPO BASICO'       => ['nombre' => 'Equipo Básico', 'orden' => 2],
        'TECNOLOGIA MOVIL>SMARTWHATCH'         => ['nombre' => 'Smartwatch',    'orden' => 3],
    ];
 
    try {
        $conn = conectarBD();
 
        $sql = "
            SELECT
                e.descripcion,
                e.existencia,
                e.publico_general,
                e.categoria,
                s.nombre AS sucursal_nombre
            FROM existencias e
            LEFT JOIN sucursales s ON s.id = e.almacen
            WHERE e.categoria IN (
                'TECNOLOGIA MOVIL>SMARTPHONE>PROPIOS',
                'TECNOLOGIA MOVIL>SMARTPHONE>BATYCELL',
                'TECNOLOGIA MOVIL>EQUIPO BASICO',
                'TECNOLOGIA MOVIL>SMARTWHATCH'
            )
            AND e.existencia > 0
            ORDER BY e.descripcion, s.nombre
        ";
 
        $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
 
        // ── Primera pasada: acumular por modelo ───────────────────
        $intermedio = [];
 
        foreach ($rows as $row) {
            $descModelo   = extraerDescripcionModelo($row['descripcion']);
            $marca        = extraerMarca($row['descripcion']);
            $color        = extraerColor($row['descripcion']);
            $precio       = (float) $row['publico_general'];
            $sucursal     = $row['sucursal_nombre'] ?? null;
            $categoriaRaw = $row['categoria'] ?? '';
 
            // Resolver nombre legible y orden
            $catInfo = $categoriasMapa[$categoriaRaw]
                     ?? ['nombre' => $categoriaRaw, 'orden' => 99];
 
            if (!isset($intermedio[$descModelo])) {
                $intermedio[$descModelo] = [
                    'descripcionModelo' => $descModelo,
                    'marca'             => $marca,
                    'imagen'            => obtenerImagenTelefono($conn, $descModelo),
                    'categoria'         => $catInfo['nombre'],
                    'categoria_orden'   => $catInfo['orden'],
                    'precios_color'     => [],  // color => precio
                    'sucursales_raw'    => [],  // sucursal => [color => existencia]
                ];
            }
 
            // Guardar precio por color
            $intermedio[$descModelo]['precios_color'][$color] = $precio;
 
            // Acumular existencias por sucursal + color
            if ($sucursal) {
                if (!isset($intermedio[$descModelo]['sucursales_raw'][$sucursal][$color])) {
                    $intermedio[$descModelo]['sucursales_raw'][$sucursal][$color] = 0;
                }
                $intermedio[$descModelo]['sucursales_raw'][$sucursal][$color] += (int) $row['existencia'];
            }
        }
 
        // ── Segunda pasada: detectar inconsistencias y normalizar ─
        $agrupados = [];
 
        foreach ($intermedio as $descModelo => $data) {
            $preciosColor  = $data['precios_color'];
            $preciosUnicos = array_unique(array_values($preciosColor));
 
            $inconsistente = count($preciosUnicos) > 1;
            $precioOficial = $inconsistente ? max($preciosUnicos) : reset($preciosUnicos);
 
            $colores = array_keys($preciosColor);
            sort($colores);
 
            // Normalizar sucursales
            $sucursales = [];
            foreach ($data['sucursales_raw'] as $nombreSuc => $coloresExist) {
                $coloresSuc = array_keys($coloresExist);
                sort($coloresSuc);
                $sucursales[] = [
                    'nombre'     => $nombreSuc,
                    'existencia' => array_sum($coloresExist),
                    'colores'    => $coloresSuc,
                ];
            }
            usort($sucursales, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));
 
            $agrupados[$descModelo] = [
                'descripcionModelo'    => $descModelo,
                'marca'                => $data['marca'],
                'categoria'            => $data['categoria'],
                'categoria_orden'      => $data['categoria_orden'],
                'precio'               => $precioOficial,
                'precio_inconsistente' => $inconsistente,
                'precios_por_color'    => $inconsistente ? $preciosColor : [],
                'colores'              => $colores,
                'imagen'               => $data['imagen'],
                'sucursales'           => $sucursales,
            ];
        }
 
        // ── Ordenar: categoría_orden → marca → modelo ─────────────
        uasort($agrupados, function($a, $b) {
            // 1. Por orden de categoría
            $cmp = $a['categoria_orden'] <=> $b['categoria_orden'];
            if ($cmp !== 0) return $cmp;
            // 2. Por marca
            $cmp = strcmp($a['marca'], $b['marca']);
            if ($cmp !== 0) return $cmp;
            // 3. Por nombre de modelo
            return strcmp($a['descripcionModelo'], $b['descripcionModelo']);
        });
 
        return array_values($agrupados);
 
    } catch (Exception $e) {
        error_log('obtenerSmartphones: ' . $e->getMessage());
        return [];
    }
}

/** MODULO COLABORADORES */

/** CRUD BASICO */

/*
 Obtiene todos los colaboradores ordenados.
 Incluye indicador de si tienen garantias.

 Retorna lista de colaboradores.
 */
function obtenerColab(): array
{
    try {
        $conn = conectarBD();

        $sql = "
            SELECT
                c.*,
                (SELECT COUNT(*) FROM garantia WHERE apasionado = c.id) AS tiene_garantias
            FROM colaboradores c
            ORDER BY
                CASE WHEN c.payjoy_int = 3 THEN 1 ELSE 0 END ASC,
                CASE WHEN c.fecha_ingreso IS NULL THEN 1 ELSE 0 END ASC,
                c.fecha_ingreso DESC,
                c.nombre ASC
        ";

        $stmt = $conn->query($sql);
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $conn = null;

        return $resultado;

    } catch (Exception $e) {
        error_log("Error en obtenerColab: " . $e->getMessage());
        return [];
    }
}
/*
 Inserta un nuevo colaborador.

 Parametros:
 nombre
 fecha_ingreso

 Retorna:
 ok, mensaje, id
 */
function crearColaborador(string $nombre, string $fecha_ingreso): array
{
    $nombre        = normalizarNombre2($nombre);
    $fecha_ingreso = normalizarFecha($fecha_ingreso);

    if (empty($nombre)) {
        return ['ok' => false, 'mensaje' => 'El nombre no puede estar vacío.', 'id' => null];
    }
    if (!$fecha_ingreso) {
        return ['ok' => false, 'mensaje' => 'La fecha de ingreso no es válida.', 'id' => null];
    }

    try {
        $conn = conectarBD();
        $stmt = $conn->prepare("INSERT INTO colaboradores (nombre, fecha_ingreso, fecha_capacitacion, payjoy_int) VALUES (?, ?, NULL, 0)");

        $stmt->execute([$nombre, $fecha_ingreso]);
        $id = $conn->lastInsertId();

        $conn = null;

        return ['ok' => true, 'mensaje' => 'Colaborador creado correctamente.', 'id' => $id];

    } catch (Exception $e) {
        error_log("Error en crearColaborador: " . $e->getMessage());
        return ['ok' => false, 'mensaje' => 'Error al crear el colaborador.', 'id' => null];
    }
}
/** Actualiza colaborador: id, nombre, fecha_ingreso, fecha_capacitacion, payjoy_int. Retorna ok y mensaje */

function actualizarColab(int $id, string $nombre, ?string $fecha_ingreso, ?string $fecha_capacitacion, int $payjoy_int): array
{
    $nombre             = normalizarNombre2($nombre);
    $fecha_capacitacion = $fecha_capacitacion ? normalizarFecha($fecha_capacitacion) : null;
    $payjoy_int         = (int) $payjoy_int;

    if (empty($nombre)) {
        return ['ok' => false, 'mensaje' => 'El nombre no puede estar vacío.'];
    }

    // Fecha de ingreso solo obligatoria si no es "ya no labora"
    if ($payjoy_int !== 3) {
        $fecha_ingreso = normalizarFecha($fecha_ingreso ?? '');
        if (!$fecha_ingreso) {
            return ['ok' => false, 'mensaje' => 'La fecha de ingreso no es válida.'];
        }
    } else {
        $fecha_ingreso = ($fecha_ingreso && trim($fecha_ingreso) !== '')
            ? normalizarFecha($fecha_ingreso)
            : null;
    }

    try {
        $conn = conectarBD();
        $stmt = $conn->prepare("
            UPDATE colaboradores 
            SET nombre = ?, fecha_ingreso = ?, fecha_capacitacion = ?, payjoy_int = ? 
            WHERE id = ?
        ");

        $stmt->execute([$nombre, $fecha_ingreso, $fecha_capacitacion, $payjoy_int, $id]);

        $conn = null;

        return ['ok' => true, 'mensaje' => 'Colaborador actualizado correctamente.'];

    } catch (Exception $e) {
        error_log("Error en actualizarColab: " . $e->getMessage());
        return ['ok' => false, 'mensaje' => 'Error al actualizar el colaborador.'];
    }
}

/** Elimina colaborador por id. Retorna ok y mensaje */
function eliminarColaborador(int $id): array
{
    try {
        $conn = conectarBD();

        // Verificar si tiene garantías vinculadas
        $stmt = $conn->prepare("SELECT COUNT(*) FROM garantia WHERE apasionado = ?");
        $stmt->execute([$id]);
        $total = (int) $stmt->fetchColumn();

        if ($total > 0) {
            return [
                'ok'      => false,
                'mensaje' => "No se puede eliminar: el colaborador tiene $total garantía(s) vinculada(s). Usa la fusión para reasignarlas primero."
            ];
        }

        $stmt = $conn->prepare("DELETE FROM colaboradores WHERE id = ?");
        $stmt->execute([$id]);

        $conn = null;

        return ['ok' => true, 'mensaje' => 'Colaborador eliminado correctamente.'];

    } catch (Exception $e) {
        error_log("Error en eliminarColaborador: " . $e->getMessage());
        return ['ok' => false, 'mensaje' => 'Error al eliminar el colaborador.'];
    }
}

/** Importa Excel y sincroniza colaboradores (filtra puestos validos). Retorna insertados, actualizados, sin_cambios y errores */
function importarColaboradoresDesdeExcel(string $ruta_archivo): array
{
    $puestos_validos = [
        'apasionado de la telefonía',
        'encargado de sucursal',
    ];

    $resultado = ['insertados' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => []];

    try {
        $zip = new ZipArchive();
        if ($zip->open($ruta_archivo) !== true) {
            $resultado['errores'][] = 'No se pudo abrir el archivo xlsx.';
            return $resultado;
        }

        $shared_strings = [];
        $ss_xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss_xml) {
            $ss = simplexml_load_string($ss_xml);
            if ($ss === false) {
                $resultado['errores'][] = 'Error al parsear sharedStrings.xml.';
                $zip->close();
                return $resultado;
            }
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $shared_strings[] = (string) $si->t;
                } else {
                    $texto = '';
                    foreach ($si->r as $r) {
                        $texto .= (string) $r->t;
                    }
                    $shared_strings[] = $texto;
                }
            }
        }

        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (!$sheet_xml) {
            $resultado['errores'][] = 'No se encontró la hoja de cálculo.';
            return $resultado;
        }

        $sheet = simplexml_load_string($sheet_xml);
        if ($sheet === false) {
            $resultado['errores'][] = 'Error al parsear la hoja de cálculo.';
            return $resultado;
        }

        $filas = [];
        foreach ($sheet->sheetData->row as $row) {
            $num_fila = (int) $row['r'];
            foreach ($row->c as $cell) {
                $ref   = (string) $cell['r'];
                $col   = preg_replace('/[0-9]/', '', $ref);
                $tipo  = (string) $cell['t'];
                $valor = isset($cell->v) ? (string) $cell->v : '';

                if ($tipo === 's') {
                    $valor = $shared_strings[(int) $valor] ?? '';
                }

                $filas[$num_fila][$col] = $valor;
            }
        }

        $colaboradores_bd = obtenerColab();

        // Índice por nombre normalizado para búsqueda exacta O(1)
        $indice_nombres = [];
        foreach ($colaboradores_bd as $col) {
            $clave = mb_strtolower(trim($col['nombre']));
            $indice_nombres[$clave] = $col;
        }

        foreach ($filas as $num_fila => $cols) {
            // Las primeras 4 filas son encabezados del formato del archivo
            if ($num_fila < 5) continue;

            $nombre_excel = trim($cols['B'] ?? '');
            $puesto_excel = trim($cols['E'] ?? '');
            $fecha_raw    = trim($cols['H'] ?? '');

            if (empty($nombre_excel)) continue;
            if (!in_array(mb_strtolower($puesto_excel), $puestos_validos, true)) continue;

            $fecha_ingreso = null;
            if (is_numeric($fecha_raw)) {
                $ts = ($fecha_raw - 25569) * 86400;
                $fecha_ingreso = gmdate('Y-m-d', $ts); // gmdate evita desfase por timezone del servidor
            } else {
                $fecha_ingreso = normalizarFecha($fecha_raw);
            }

            if (!$fecha_ingreso) {
                $resultado['errores'][] = "Fila $num_fila: fecha inválida para '$nombre_excel'.";
                continue;
            }

            $clave_excel   = mb_strtolower(trim($nombre_excel));
            $col_existente = $indice_nombres[$clave_excel] ?? null;

            if ($col_existente === null) {
                // No existe → crear
                $res = crearColaborador($nombre_excel, $fecha_ingreso);
                if ($res['ok']) {
                    $resultado['insertados']++;
                    $nuevo = [
                        'id'                 => $res['id'],
                        'nombre'             => $nombre_excel,
                        'fecha_ingreso'      => $fecha_ingreso,
                        'fecha_capacitacion' => null,
                        'payjoy_int'         => 0,
                        'tiene_garantias'    => 0,
                    ];
                    $colaboradores_bd[]           = $nuevo;
                    $indice_nombres[$clave_excel] = $nuevo;
                } else {
                    $resultado['errores'][] = "Fila $num_fila: " . $res['mensaje'];
                }
            } elseif ($col_existente['fecha_ingreso'] === $fecha_ingreso) {
                // Nombre y fecha idénticos → sin cambios
                $resultado['sin_cambios']++;
            } else {
                // Nombre exacto pero fecha diferente → actualizar solo fecha
                $res = actualizarColab(
                    (int) $col_existente['id'],
                    $col_existente['nombre'],
                    $fecha_ingreso,
                    $col_existente['fecha_capacitacion'],
                    (int) $col_existente['payjoy_int']
                );
                if ($res['ok']) {
                    $resultado['actualizados']++;
                } else {
                    $resultado['errores'][] = "Fila $num_fila: " . $res['mensaje'];
                }
            }
        }

    } catch (Exception $e) {
        error_log("Error en importarColaboradoresDesdeExcel: " . $e->getMessage());
        $resultado['errores'][] = 'Error inesperado durante la importación.';
    }

    return $resultado;
}


/** Fusiona colaboradores: pasa garantias de origen a destino. Retorna ok, mensaje y garantias_reasignadas */
function fusionarColaboradores(int $id_origen, int $id_destino): array
{
    if ($id_origen === $id_destino) {
        return ['ok' => false, 'mensaje' => 'El origen y el destino no pueden ser el mismo.', 'garantias_reasignadas' => 0];
    }

    try {
        $conn = conectarBD();

        // Verificar que el colaborador destino exista
        $stmt = $conn->prepare("SELECT COUNT(*) FROM colaboradores WHERE id = ?");
        $stmt->execute([$id_destino]);
        if ((int) $stmt->fetchColumn() === 0) {
            return ['ok' => false, 'mensaje' => 'El colaborador destino no existe.', 'garantias_reasignadas' => 0];
        }

        $conn->beginTransaction();

        // Contar garantías del origen
        $stmt = $conn->prepare("SELECT COUNT(*) FROM garantia WHERE apasionado = ?");
        $stmt->execute([$id_origen]);
        $total = (int) $stmt->fetchColumn();

        // Reasignar: mover el id_origen al id_destino en el campo apasionado
        $stmt = $conn->prepare("UPDATE garantia SET apasionado = ? WHERE apasionado = ?");
        $stmt->execute([$id_destino, $id_origen]);

        $conn->commit();
        $conn = null;

        return [
            'ok'                    => true,
            'mensaje'               => "Fusión completada. $total garantías reasignadas.",
            'garantias_reasignadas' => $total,
        ];

    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error en fusionarColaboradores: " . $e->getMessage());
        return ['ok' => false, 'mensaje' => 'Error al fusionar los colaboradores.', 'garantias_reasignadas' => 0];
    }
}
/** Normaliza nombre (trim y formato). Retorna nombre limpio */
function normalizarNombre2(string $nombre): string
{
    return trim($nombre);
}

/** Valida y formatea fecha a Y-m-d. Retorna fecha o false */
function normalizarFecha(string $fecha): string|false
{
    $fecha = trim($fecha);

    // Intentar parsear con DateTime para varios formatos
    $formatos = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'];

    foreach ($formatos as $formato) {
        $dt = \DateTime::createFromFormat($formato, $fecha);
        if ($dt && $dt->format($formato) === $fecha) {
            return $dt->format('Y-m-d');
        }
    }

    // Último intento con strtotime (gmdate evita desfase por timezone del servidor)
    $ts = strtotime($fecha);
    if ($ts !== false) {
        return gmdate('Y-m-d', $ts);
    }

    return false;
}

/** Convierte fecha de Excel a Y-m-d. Retorna fecha o false */
function parsearFechaExcel(mixed $valor_celda): string|false
{
    if ($valor_celda === null || $valor_celda === '') {
        return false;
    }

    // Si es número serial de Excel
    if (is_numeric($valor_celda)) {
        try {
            $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor_celda);
            return $fecha->format('Y-m-d');
        } catch (\Exception $e) {
            return false;
        }
    }

    return normalizarFecha((string) $valor_celda);
}

/** Calcula estado PayJoy segun valor y fecha_ingreso. Retorna etiqueta y clase_css */
function calcularEstadoPayjoy(int $payjoy_int, ?string $fecha_ingreso): array
{
    switch ($payjoy_int) {
        case 1:
            return ['etiqueta' => 'ACTIVO', 'clase_css' => 'badge-activo'];

        case 2:
            return ['etiqueta' => 'BLOQUEADA', 'clase_css' => 'badge-bloqueada'];

        case 3:
            return ['etiqueta' => 'YA NO LABORA', 'clase_css' => 'badge-inactivo'];

        case 0:
            if ($fecha_ingreso === null || $fecha_ingreso === '') {
                return ['etiqueta' => 'SIN FECHA', 'clase_css' => 'badge-sin-fecha'];
            }

            try {
                $hoy     = new DateTime();
                $ingreso = new DateTime($fecha_ingreso);
                $diff    = (int) $hoy->diff($ingreso)->days;
            } catch (\Exception $e) {
                return ['etiqueta' => 'SIN FECHA', 'clase_css' => 'badge-sin-fecha'];
            }

            if ($diff < 30) {
                $faltan = 30 - $diff;
                return [
                    'etiqueta'  => "Faltan $faltan día" . ($faltan === 1 ? '' : 's'),
                    'clase_css' => 'badge-pendiente',
                ];
            }

            return ['etiqueta' => 'NO TIENE CUENTA', 'clase_css' => 'badge-sin-cuenta'];

        default:
            return ['etiqueta' => 'DESCONOCIDO', 'clase_css' => 'badge-desconocido'];
    }
}
?>
