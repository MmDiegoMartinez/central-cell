<?php
function conectarBD(): PDO {
    // Datos de conexión personalizados
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

function actualizarGarantiasDiario(PDO $conn, $validador_id) {
    $hoy = date("Y-m-d");
   

    // Revisar si ya se ejecutó hoy
    $sqlCheck = "SELECT id FROM actualizaciones_diarias WHERE fecha = :fecha";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([':fecha' => $hoy]);
    $existe = $stmtCheck->fetch();
  
    
    if ($existe) {
        return 0;
    }

    try {
        // A los 2 días, si no tiene anotación, marcar como no llegó
        $sqlNoLlego = "
            UPDATE garantia
            SET anotaciones_validador = 'Merma o Garantia No Llego'
            WHERE estatus = 'Anotado'
              AND (anotaciones_validador IS NULL OR anotaciones_validador = '')
              AND DATEDIFF(CURDATE(), fecha) > 2
        ";
        $stmtNoLlego = $conn->prepare($sqlNoLlego);
        $stmtNoLlego->execute();
        $afectadosNoLlego = $stmtNoLlego->rowCount();
        

        // A los 3 días, cerrar automáticamente
        $sqlCerrar = "
            UPDATE garantia
            SET estatus = 'Cerrada',
                id_validador = :validador_id
            WHERE estatus = 'Anotado'
              AND DATEDIFF(CURDATE(), fecha) > 2
        ";
        $stmtCerrar = $conn->prepare($sqlCerrar);
        $stmtCerrar->execute([':validador_id' => $validador_id]);
        $afectadosCerrar = $stmtCerrar->rowCount();
       

        // Registrar la ejecución del día
        $sqlInsert = "INSERT INTO actualizaciones_diarias (fecha) VALUES (:fecha)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $result = $stmtInsert->execute([':fecha' => $hoy]);
      

        return $afectadosNoLlego + $afectadosCerrar;

    } catch (PDOException $e) {
        echo "ERROR PDO: " . $e->getMessage() . "<br>";
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
            array_fill(0, count($chunk), '(?,?,?,?,?,?,?)')
        );
 
        $sql = "INSERT INTO existencias
            (almacen, descripcion, existencia, BarcodeId, categoria, publico_general, ListaSeries)
        VALUES $placeholders";
 
        $params = [];
        foreach ($chunk as $fila) {
            $params[] = $fila['almacen'];
            $params[] = $fila['descripcion'];
            $params[] = $fila['existencia'];
            $params[] = $fila['barcodeId'];
            $params[] = $fila['categoria'];
            $params[] = $fila['publicoGeneral'];
            $params[] = $fila['listaSeries'] !== '' ? $fila['listaSeries'] : null;
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

        // Abrir el archivo .xlsx como ZIP
        $zip = new ZipArchive();
        if ($zip->open($rutaArchivo) !== true) {
            $resultado['mensaje'] = 'No se pudo abrir el archivo Excel';
            return $resultado;
        }

        $sheetXml         = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        // ── Shared strings → acceso O(1) por índice ──────────────────
        $sharedStrings = [];
        if ($sharedStringsXml) {
            $xmlSS = new SimpleXMLElement($sharedStringsXml);
            foreach ($xmlSS->si as $si) {
                $sharedStrings[] = isset($si->t)
                    ? (string) $si->t
                    : implode('', array_map(fn($r) => (string) $r->t, iterator_to_array($si->r)));
            }
            unset($xmlSS);
        }

        // ── Categorías especiales como set O(1) ──────────────────────
        $categoriasConCero = array_flip([
            'tecnologia movil>smartphone>batycell',
            'tecnologia movil>smartphone>propios',
        ]);

        // ── Parsear hoja ─────────────────────────────────────────────
        $xmlSheet       = new SimpleXMLElement($sheetXml);
        $primeraFila    = true;
        $filasValidas   = [];
        $omitidos       = [];
        $cacheAlmacen   = [];
        $barcodesVistos = [];

        foreach ($xmlSheet->sheetData->row as $row) {
            if ($primeraFila) { $primeraFila = false; continue; }

            $numFila = (int) $row['r'];

            // ── Leer celdas ──────────────────────────────────────────
            $celdas = [];
            foreach ($row->c as $c) {
                preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
                $v = isset($c->v) ? (string) $c->v : '';
                $celdas[$m[1]] = (isset($c['t']) && $c['t'] == 's')
                    ? ($sharedStrings[(int) $v] ?? '')
                    : $v;
            }

            // ── Extraer campos para filtros tempranos ─────────────────
            $almacenCompleto = trim($celdas['A'] ?? '');
            $nombreCategoria = trim($celdas['N'] ?? '');

            // ── FILTROS SILENCIOSOS tempranos ─────────────────────────
            if (stripos($almacenCompleto, 'Almacén general')    !== false) continue;
            if (stripos($nombreCategoria, 'SOLUCIONES TECNICAS') !== false) continue;

            $existencia  = (int)   ($celdas['H'] ?? 0);
            $descripcion = trim($celdas['C'] ?? '');
            $barcodeId   = trim($celdas['M'] ?? '');
            $listaSeries = trim($celdas['J'] ?? '');

            // ── Lógica existencia = 0 ─────────────────────────────────
            if ($existencia === 0) {
                // Categoría no especial → omitir
                if (!isset($categoriasConCero[strtolower($nombreCategoria)])) continue;
                // Campos mínimos vacíos → omitir
                if ($descripcion === '' || $barcodeId === '')                  continue;
                // Barcode ya registrado → deduplicar
                if (isset($barcodesVistos[$barcodeId]))                        continue;

                $barcodesVistos[$barcodeId] = true;
                $filasValidas[] = [
                    'fila'           => $numFila,
                    'almacen'        => null,  // NULL respeta la FK con sucursales
                    'almacenNombre'  => '',
                    'descripcion'    => $descripcion,
                    'existencia'     => 0,
                    'barcodeId'      => $barcodeId,
                    'categoria'      => $nombreCategoria,
                    'publicoGeneral' => 0.0,
                    'listaSeries'    => $listaSeries,
                ];
                continue;
            }

            // ── Extraer precio (solo si existencia > 0) ───────────────
            $publicoGeneral = isset($celdas['Q']) && $celdas['Q'] !== ''
                ? (float) $celdas['Q']
                : null;

            // ── VALIDACIONES CON REPORTE ──────────────────────────────
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

            // ── Resolver almacén con caché O(1) ──────────────────────
            $nombreAlmacen = str_starts_with($almacenCompleto, 'Central Cell ')
                ? trim(substr($almacenCompleto, 13))
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

            $barcodesVistos[$barcodeId] = true;
            $filasValidas[] = [
                'fila'           => $numFila,
                'almacen'        => $idAlmacen,
                'almacenNombre'  => $almacenCompleto,
                'descripcion'    => $descripcion,
                'existencia'     => $existencia,
                'barcodeId'      => $barcodeId,
                'categoria'      => $nombreCategoria,
                'publicoGeneral' => $publicoGeneral ?? 0.0,
                'listaSeries'    => $listaSeries,
            ];
        }

        unset($xmlSheet, $sharedStrings);

        // ── Inserción masiva ──────────────────────────────────────────
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
                     'stock_por_color' => $coloresExist,
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

//crud empĺeados


// ============================================================
// MÓDULO: COLABORADORES
// Archivo: funciones.php (sección colaboradores)
// Descripción: Funciones CRUD y lógica de negocio para
//              la gestión de colaboradores.
// ============================================================

// Requiere que conectarBD() esté definida previamente en funciones.php


// ─────────────────────────────────────────────
// SECCIÓN 1: CRUD BÁSICO
// ─────────────────────────────────────────────

/**
 * Obtiene todos los colaboradores ordenados alfabéticamente.
 * Incluye un flag `tiene_garantias` para resaltar el nombre en la vista.
 *
 * @return array Lista de colaboradores con sus datos y flag de garantías.
 */
function obtenerColab(): array
{
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
    if (!$stmt) return [];

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
/**
 * Inserta un nuevo colaborador en la base de datos.
 *
 * @param string $nombre        Nombre completo del colaborador.
 * @param string $fecha_ingreso Fecha de ingreso en formato Y-m-d.
 *
 * @return array ['ok' => bool, 'mensaje' => string, 'id' => int|null]
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

    $conn = conectarBD();
    $stmt = $conn->prepare("INSERT INTO colaboradores (nombre, fecha_ingreso, fecha_capacitacion, payjoy_int) VALUES (?, ?, NULL, 0)");

    if (!$stmt) {
        return ['ok' => false, 'mensaje' => 'Error al preparar la consulta.', 'id' => null];
    }

    $stmt->execute([$nombre, $fecha_ingreso]);
    $id = $conn->lastInsertId();

    return ['ok' => true, 'mensaje' => 'Colaborador creado correctamente.', 'id' => $id];
}

/**
 * Actualiza los datos de un colaborador existente.
 *
 * @param int         $id                ID del colaborador.
 * @param string      $nombre            Nombre completo.
 * @param string      $fecha_ingreso     Fecha de ingreso (Y-m-d).
 * @param string|null $fecha_capacitacion Fecha de capacitación (Y-m-d) o null.
 * @param int         $payjoy_int        Estado PayJoy (0 o 1).
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
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

    $conn = conectarBD();
    $stmt = $conn->prepare("
        UPDATE colaboradores 
        SET nombre = ?, fecha_ingreso = ?, fecha_capacitacion = ?, payjoy_int = ? 
        WHERE id = ?
    ");

    if (!$stmt) {
        return ['ok' => false, 'mensaje' => 'Error al preparar la consulta.'];
    }

    $stmt->execute([$nombre, $fecha_ingreso, $fecha_capacitacion, $payjoy_int, $id]);

    return ['ok' => true, 'mensaje' => 'Colaborador actualizado correctamente.'];
}

/**
 * Elimina un colaborador por su ID.
 *
 * @param int $id ID del colaborador a eliminar.
 *
 * @return array ['ok' => bool, 'mensaje' => string]
 */
function eliminarColaborador(int $id): array
{
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

    return ['ok' => true, 'mensaje' => 'Colaborador eliminado correctamente.'];
}

// ─────────────────────────────────────────────
// SECCIÓN 2: IMPORTACIÓN DESDE EXCEL
// ─────────────────────────────────────────────

/**
 * Procesa un archivo .xlsx y sincroniza los colaboradores con la BD.
 * Solo considera registros con puesto "Apasionado de la telefonía" o
 * "Encargado de Sucursal". Encabezados en fila 4, datos desde fila 5.
 *
 * Requiere: PhpSpreadsheet instalado vía Composer.
 *
 * @param string $ruta_archivo Ruta temporal del archivo subido.
 *
 * @return array ['insertados' => int, 'actualizados' => int, 'sin_cambios' => int, 'errores' => array]
 */
function importarColaboradoresDesdeExcel(string $ruta_archivo): array
{
    $puestos_validos = [
        'apasionado de la telefonía',
        'encargado de sucursal',
    ];

    $resultado = ['insertados' => 0, 'actualizados' => 0, 'sin_cambios' => 0, 'errores' => []];

    $zip = new ZipArchive();
    if ($zip->open($ruta_archivo) !== true) {
        $resultado['errores'][] = 'No se pudo abrir el archivo xlsx.';
        return $resultado;
    }

    $shared_strings = [];
    $ss_xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss_xml) {
        $ss = simplexml_load_string($ss_xml);
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
        if ($num_fila < 5) continue;

        $nombre_excel = trim($cols['B'] ?? '');
        $puesto_excel = trim($cols['E'] ?? '');
        $fecha_raw    = trim($cols['H'] ?? '');

        if (empty($nombre_excel)) continue;
        if (!in_array(mb_strtolower($puesto_excel), $puestos_validos, true)) continue;

        $fecha_ingreso = null;
        if (is_numeric($fecha_raw)) {
            $ts = ($fecha_raw - 25569) * 86400;
            $fecha_ingreso = date('Y-m-d', $ts);
        } else {
            $fecha_ingreso = normalizarFecha($fecha_raw);
        }

        if (!$fecha_ingreso) {
            $resultado['errores'][] = "Fila $num_fila: fecha inválida para '$nombre_excel'.";
            continue;
        }

        $clave_excel = mb_strtolower(trim($nombre_excel));
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
                $colaboradores_bd[]        = $nuevo;
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
                $col_existente['nombre'],       // nombre intacto, no se pisa
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

    return $resultado;
}

// ─────────────────────────────────────────────
// SECCIÓN 3: FUSIÓN DE COLABORADORES
// ─────────────────────────────────────────────

/**
 * Reasigna todas las garantías del colaborador origen al destino.
 * El registro origen NO se elimina automáticamente; puede borrarse
 * manualmente desde el CRUD.
 *
 * @param int $id_origen  ID del colaborador a fusionar (origen).
 * @param int $id_destino ID del colaborador que recibirá las garantías (destino).
 *
 * @return array ['ok' => bool, 'mensaje' => string, 'garantias_reasignadas' => int]
 */
function fusionarColaboradores(int $id_origen, int $id_destino): array
{
    if ($id_origen === $id_destino) {
        return ['ok' => false, 'mensaje' => 'El origen y el destino no pueden ser el mismo.', 'garantias_reasignadas' => 0];
    }

    $conn = conectarBD();

    // Contar garantías del origen
    $stmt = $conn->prepare("SELECT COUNT(*) FROM garantia WHERE apasionado = ?");
    $stmt->execute([$id_origen]);
    $total = (int) $stmt->fetchColumn();

    // Reasignar: mover el id_origen al id_destino en el campo apasionado
    $stmt = $conn->prepare("UPDATE garantia SET apasionado = ? WHERE apasionado = ?");
    $stmt->execute([$id_destino, $id_origen]);

    return [
        'ok'                    => true,
        'mensaje'               => "Fusión completada. $total garantías reasignadas.",
        'garantias_reasignadas' => $total,
    ];
}
// ─────────────────────────────────────────────
// SECCIÓN 4: HELPERS / UTILIDADES
// ─────────────────────────────────────────────

/**
 * Normaliza un nombre: trim y ucwords para consistencia.
 *
 * @param string $nombre
 * @return string
 */
function normalizarNombre2(string $nombre): string
{
    return trim($nombre);
}

/**
 * Valida y normaliza una fecha al formato Y-m-d.
 * Acepta formatos comunes: d/m/Y, Y-m-d, d-m-Y.
 *
 * @param  string $fecha
 * @return string|false Fecha formateada o false si es inválida.
 */
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

    // Último intento con strtotime
    $ts = strtotime($fecha);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }

    return false;
}

/**
 * Convierte un valor de celda de Excel (número serial o string) a Y-m-d.
 *
 * @param  mixed $valor_celda
 * @return string|false
 */
function parsearFechaExcel(mixed $valor_celda): string|false
{
    if (empty($valor_celda)) {
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

/**
 * Calcula el estado PayJoy de un colaborador para mostrarlo en la vista.
 *
 * @param int         $payjoy_int    Valor del campo payjoy_int (0 o 1).
 * @param string|null $fecha_ingreso Fecha de ingreso en formato Y-m-d.
 *
 * @return array ['etiqueta' => string, 'clase_css' => string]
 */
function calcularEstadoPayjoy(int $payjoy_int, ?string $fecha_ingreso): array
{
    switch ($payjoy_int) {
        case 1:
            return ['etiqueta' => 'ACTIVO', 'clase_css' => 'badge-activo'];

        case 2:
            return ['etiqueta' => 'BLOCK', 'clase_css' => 'badge-bloqueada'];

        case 3:
            return ['etiqueta' => 'NO LABORA', 'clase_css' => 'badge-inactivo'];

        case 0:
        default:
            if (empty($fecha_ingreso)) {
                return ['etiqueta' => 'SIN FECHA', 'clase_css' => 'badge-sin-fecha'];
            }
            $hoy     = new DateTime();
            $ingreso = new DateTime($fecha_ingreso);
            $diff    = (int) $hoy->diff($ingreso)->days;

            if ($diff < 30) {
                $faltan = 30 - $diff;
                return [
                    'etiqueta'  => "Restan $faltan d" . ($faltan === 1 ? '' : ''),
                    'clase_css' => 'badge-pendiente',
                ];
            }

            return ['etiqueta' => 'S/CUENTA', 'clase_css' => 'badge-sin-cuenta'];
    }
}
function guardarGarantiaTelefono($datos)
{
    $conn = conectarBD();

    date_default_timezone_set('America/Mexico_City');
    $fecha_registro = date('Y-m-d H:i:s');

    $plows = strtoupper(trim($datos['plows']));

    if (!preg_match('/^PLOWS\d{6}$/', $plows)) {
        throw new Exception("PLOWS inválido.");
    }

    $nombre_cliente = trim(
        mb_convert_case(
            $datos['nombre_cliente'],
            MB_CASE_TITLE,
            "UTF-8"
        )
    );

    $numero_contacto = preg_replace('/\D/', '', $datos['numero_contacto']);

    $numero_ticket = trim($datos['numero_ticket']);

    $sucursal = intval($datos['sucursal']);
    $tipo_venta = trim($datos['tipo_venta']);
    $imei = trim($datos['imei']);

    if (empty($imei)) {
        throw new Exception("IMEI requerido.");
    }

    /* ── Helper: obtener ID o insertar colaborador nuevo ── */
    $resolverColaborador = function(string $idRaw, string $nombreRaw) use ($conn): int {
        $id     = intval($idRaw);
        $nombre = trim($nombreRaw);

        // Si ya viene con ID válido, usarlo directo
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT id FROM colaboradores WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn()) return $id;
        }

        // Buscar por nombre (por si el autocomplete no llenó el hidden)
        if ($nombre !== '') {
            $stmt = $conn->prepare("SELECT id FROM colaboradores WHERE nombre = :nombre LIMIT 1");
            $stmt->execute([':nombre' => $nombre]);
            $encontrado = $stmt->fetchColumn();
            if ($encontrado) return (int) $encontrado;

            // No existe — insertarlo
            $stmt = $conn->prepare("INSERT INTO colaboradores (nombre) VALUES (:nombre)");
            $stmt->execute([':nombre' => $nombre]);
            return (int) $conn->lastInsertId();
        }

        throw new Exception("No se pudo identificar al colaborador: '$nombre'.");
    };

    $vendedor        = $resolverColaborador($datos['vendedor'],        $datos['vendedor_texto']        ?? '');
    $vendedor_recibe = $resolverColaborador($datos['vendedor_recibe'], $datos['vendedor_recibe_texto'] ?? '');

    $sql = "
        INSERT INTO garantias_telefonos (
            plows,
            nombre_cliente,
            numero_contacto,
            numero_ticket,
            sucursal,
            vendedor,
            vendedor_recibe,
            tipo_venta,
            imei,
            fecha_registro
        ) VALUES (
            :plows,
            :nombre_cliente,
            :numero_contacto,
            :numero_ticket,
            :sucursal,
            :vendedor,
            :vendedor_recibe,
            :tipo_venta,
            :imei,
            :fecha_registro
        )
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        ':plows'            => $plows,
        ':nombre_cliente'   => $nombre_cliente,
        ':numero_contacto'  => $numero_contacto,
        ':numero_ticket'    => $numero_ticket,
        ':sucursal'         => $sucursal,
        ':vendedor'         => $vendedor,
        ':vendedor_recibe'  => $vendedor_recibe,
        ':tipo_venta'       => $tipo_venta,
        ':imei'             => $imei,
        ':fecha_registro'   => $fecha_registro
    ]);

    return (int) $conn->lastInsertId();
}

function generarPDFGarantiaTelefono($id_caso)
{
    require_once __DIR__ . '/tcpdf/tcpdf.php';
 
    $conn = conectarBD();
 
    $stmt = $conn->prepare("
        SELECT
            gt.*,
            s.nombre  AS sucursal_nombre,
            v.nombre  AS vendedor_nombre,
            vr.nombre AS vendedor_recibe_nombre
        FROM garantias_telefonos gt
        LEFT JOIN sucursales    s  ON s.id  = gt.sucursal
        LEFT JOIN colaboradores v  ON v.id  = gt.vendedor
        LEFT JOIN colaboradores vr ON vr.id = gt.vendedor_recibe
        WHERE gt.id_caso = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id_caso]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if (!$g) throw new Exception("Garantía no encontrada.");
 
    $modelo = 'No encontrado';
    $color  = 'No encontrado';
 
    $stmtE = $conn->prepare("SELECT descripcion FROM existencias WHERE BarcodeId = :plows LIMIT 1");
    $stmtE->execute([':plows' => $g['plows']]);
    $ex = $stmtE->fetch(PDO::FETCH_ASSOC);
 
    if ($ex) {
        if (preg_match('/-\s*(.*?)\s*-\s*NUEVO/i', $ex['descripcion'], $m))  $color  = trim($m[1]);
        if (preg_match('/\(\s*(.*?)\s*\//',          $ex['descripcion'], $m2)) $modelo = trim($m2[1]);
    }
 
    date_default_timezone_set('America/Mexico_City');
    $fechaObj         = new DateTime($g['fecha_registro']);
    $fecha_formateada = $fechaObj->format('d/m/Y h:i A');
    $folio            = str_pad($g['id_caso'], 5, '0', STR_PAD_LEFT);
    $esCred           = strtolower(trim($g['tipo_venta'])) === 'credito';
    $sucNombre        = mb_strtoupper($g['sucursal_nombre'], 'UTF-8');
 
    $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
    $pdf->SetCreator('Central Cell');
    $pdf->SetAuthor('Central Cell');
    $pdf->SetTitle('Garantía Telefonía');
    $pdf->SetPrintHeader(false);
    $pdf->SetPrintFooter(false);
    $pdf->SetMargins(14, 8, 14);
    $pdf->SetAutoPageBreak(false);
    $pdf->setCellHeightRatio(1.1);
 
    $pdf->AddPage();
 
    $lm = 14;
    $pw = 215.9 - ($lm * 2);
    $y  = 8;
 
    /* ─── ENCABEZADO AZUL ─────────────────────────────────── */
    $pdf->SetFillColor(18, 52, 104);
    $pdf->Rect($lm, $y, $pw, 18, 'F');
 
    $logo = __DIR__ . '/recursos/img/central-cell-logo.png';
    if (file_exists($logo)) {
        $pdf->Image($logo, $lm + 3, $y + 2, 32, 14, '', '', '', false, 150, '', false, false, 0);
    }
 
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY($lm + 38, $y + 1);
    $pdf->Cell($pw - 38, 8, 'CENTRAL CELL ' . $sucNombre, 0, 1, 'L');
 
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY($lm + 38, $y + 9);
    $pdf->Cell($pw - 38, 6, 'Formato de Recepción de Equipo para Revisión de Garantía', 0, 1, 'L');
 
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY($lm, $y + 3);
    $pdf->Cell($pw - 2, 6, 'FOLIO: ' . $folio, 0, 0, 'R');
 
    $y += 20;
 
    /* ─── TICKET ──────────────────────────────────────────── */
    $pdf->SetFillColor(232, 240, 254);
    $pdf->SetDrawColor(18, 52, 104);
    $pdf->SetTextColor(18, 52, 104);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY($lm, $y);
    $pdf->Cell($pw, 6, 'NO. DE TICKET / CONTROL:  ' . $g['numero_ticket'], 1, 1, 'C', true);
    $pdf->SetDrawColor(180, 180, 180);
    $pdf->SetTextColor(0, 0, 0);
    $y += 8;
 
    /* ─── HELPERS ─────────────────────────────────────────── */
    $seccion = function(string $titulo) use ($pdf, $lm, $pw, &$y) {
        $pdf->SetFillColor(18, 52, 104);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetXY($lm, $y);
        $pdf->Cell($pw, 5.5, '  ' . $titulo, 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $y += 6.5;
    };
 
    $dato2 = function(string $l1, string $v1, string $l2, string $v2) use ($pdf, $lm, $pw, &$y) {
        $half = $pw / 2;
        $lw   = $half * 0.44;
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY($lm + 2, $y);
        $pdf->Cell($lw, 5, $l1 . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($half - $lw - 2, 5, $v1, 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($lw, 5, $l2 . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($half - $lw - 2, 5, $v2, 0, 1, 'L');
        $y += 5;
    };
 
    /* ════ SECCIÓN 1 ════ */
    $seccion('1. DATOS DE LA RECEPCIÓN Y VENTA');
    $dato2('Fecha de Recepción', $fecha_formateada, 'Tipo de Venta', strtoupper($g['tipo_venta']));
    $dato2('Vendedor (Venta)', $g['vendedor_nombre'], 'Recibió Equipo', $g['vendedor_recibe_nombre']);
    $y += 2;
 
    /* ════ SECCIÓN 2 ════ */
    $seccion('2. INFORMACIÓN DEL CLIENTE');
    $dato2('Nombre Completo', $g['nombre_cliente'], 'Número de Contacto', $g['numero_contacto']);
    $y += 2;
 
    /* ════ SECCIÓN 3 ════ */
    $seccion('3. ESPECIFICACIONES DEL EQUIPO');
    $dato2('Marca y Modelo', $modelo, 'Color', $color);
    $dato2('PLOWS / Código de Barras', $g['plows'], 'Número de IMEI', $g['imei']);
    $y += 3;
 
    /* ════ SECCIÓN 4 ════ */
    $seccion('4. POLÍTICAS Y REGLAS BÁSICAS PARA VALIDACIÓN DE GARANTÍA');
 
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->SetXY($lm + 2, $y);
    $pdf->MultiCell($pw - 2, 3.5,
        'Al firmar el presente documento, el cliente declara haber entregado voluntariamente el equipo antes descrito para su revisión y diagnóstico.',
    0, 'L');
    $y = $pdf->GetY() + 1.5;
 
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetXY($lm + 2, $y);
    $pdf->Cell($pw - 2, 4, 'Condiciones Generales', 0, 1, 'L');
    $y += 4;
 
    $condiciones = [
        'El tiempo estimado de revisión y respuesta es de 1 a 4 semanas, dependiendo de la evaluación y del fabricante.',
        'La recepción del equipo no garantiza la aprobación de la garantía. El equipo será sometido a una revisión  para determinar si la falla está cubierta.',
        'Para que la garantía pueda ser considerada, el equipo no debe exceder los 3 meses desde la fecha de compra.',
        'En caso de determinarse que la falla corresponde a defectos de fabricación y cumple con las políticas de garantía, se procederá conforme a las disposiciones aplicables para hacer válida la garantía.',
        'Si durante la revisión se determina que la falla fue ocasionada por mal uso, golpes, humedad, alteraciones de software, modificaciones no autorizadas o cualquier causa ajena a defectos de fabricación, la garantía será rechazada.',
        "No aplicará garantía en equipos que presenten:\n   • Golpes, fracturas o daños físicos.\n   • Pantallas rotas o estrelladas.\n   • Señales de humedad o corrosión.\n   • Manipulación o reparación por terceros no autorizados.\n   • Alteración o eliminación de etiquetas, IMEI o sellos de garantía.",
        'El establecimiento no se hace responsable por información personal almacenada en el equipo. Se recomienda al cliente realizar previamente respaldos de su información.',
        'Durante el proceso de diagnóstico puede ser necesario restablecer el equipo a valores de fábrica.',
        'En caso de aprobarse la garantía, el equipo podrá ser reparado, reemplazado o recibir la solución determinada por el fabricante o proveedor.',
        'El cliente deberá presentar este comprobante para recoger su equipo o dar seguimiento al trámite.',
    ];
 
    $pdf->SetFont('helvetica', '', 7);
    foreach ($condiciones as $i => $cond) {
        $pdf->SetXY($lm + 2, $y);
        $pdf->Cell(5, 3.5, ($i + 1) . '.', 0, 0, 'R');
        $pdf->SetXY($lm + 7, $y);
        $pdf->MultiCell($pw - 7, 3.5, $cond, 0, 'L');
        $y = $pdf->GetY() + 0.6;
    }
 
    if ($esCred) {
        $y += 1;
        $pdf->SetFont('helvetica', 'B', 7.2);
        $pdf->SetXY($lm + 2, $y);
        $pdf->Cell($pw - 2, 4, 'Disposición Especial de Crédito (PayJoy):', 0, 1, 'L');
        $y += 4;
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($lm + 2, $y);
        $pdf->MultiCell($pw - 2, 3.5,
            'Al tratarse de un equipo adquirido bajo la modalidad de venta de crédito con la plataforma PayJoy, el cliente está obligado a continuar al corriente con sus pagos semanales/mensuales de manera regular. El ingreso del equipo a revisión no suspende, congela, ni condona la deuda ni los plazos de pago con PayJoy.',
        0, 'L');
        $y = $pdf->GetY() + 1;
    }
 
    $y += 2;
 
    /* ─── CONTACTO ────────────────────────────────────────── */
    $pdf->SetFillColor(232, 240, 254);
    $pdf->SetDrawColor(18, 52, 104);
    $pdf->Rect($lm, $y, $pw, 11, 'FD');
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetTextColor(18, 52, 104);
    $pdf->SetXY($lm + 2, $y + 1);
    $pdf->Cell($pw - 2, 4, 'CONTACTO PARA SEGUIMIENTO', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($lm + 2, $y + 5.5);
    $pdf->Cell($pw - 2, 3.8,
        'Para cualquier duda o aclaración puede comunicarse a nuestro Call Center:   Tel. 951 215 4060   |   Horario: 10:00 a.m. a 8:00 p.m.',
    0, 1, 'L');
    $pdf->SetDrawColor(180, 180, 180);
    $y += 14;
 
    /* ─── FIRMAS ──────────────────────────────────────────── */
if ($y > 240) $y = 240;

$half  = ($pw - 10) / 2;
$col2x = $lm + $half + 10;

$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->SetXY($lm, $y);
$pdf->Cell($pw, 5, 'FIRMAS', 0, 1, 'C');
$y += 7;

// Encabezados de columna
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY($lm, $y);
$pdf->Cell($half, 4, 'Cliente', 0, 0, 'C');
$pdf->SetXY($col2x, $y);
$pdf->Cell($half, 4, 'Recepción Central Cell ' . $g['sucursal_nombre'], 0, 1, 'C');
$y += 8;

$lw = 20;
$pdf->SetFont('helvetica', '', 8);
$pdf->SetDrawColor(0, 0, 0);

// Cliente — Nombre
$pdf->SetXY($lm, $y);
$pdf->Cell($lw, 4, 'Nombre:', 0, 0, 'L');
$pdf->Line($lm + $lw, $y + 3.5, $lm + $half - 2, $y + 3.5);

// Central Cell — solo Nombre (línea vacía, sin imprimir el valor)
$pdf->SetXY($col2x, $y);
$pdf->Cell($lw, 4, 'Nombre:', 0, 0, 'L');
$pdf->Line($col2x + $lw, $y + 3.5, $col2x + $half - 2, $y + 3.5);

$y += 12;

// Cliente — Firma (solo lado izquierdo)
$pdf->SetXY($lm, $y);
$pdf->Cell($lw, 4, 'Firma:', 0, 0, 'L');
$pdf->Line($lm + $lw, $y + 3.5, $lm + $half - 2, $y + 3.5);

// Lado derecho — vacío, sin línea ni etiqueta

$pdf->SetDrawColor(180, 180, 180);
 
    /* ─── PIE ─────────────────────────────────────────────── */
    $pdf->SetFont('helvetica', 'I', 6.5);
    $pdf->SetTextColor(130, 130, 130);
    $pdf->SetXY($lm, 268);
    $pdf->Cell($pw, 4, 'Original para el Establecimiento — Copia para el Cliente', 0, 1, 'C');
 
    /* ══════════════════════════════════════════════════════
       PÁGINA 2 — Etiqueta compacta
    ══════════════════════════════════════════════════════ */
    $pdf->AddPage();
    $pdf->SetTextColor(0, 0, 0);
 
    $ew  = 110;
    $exL = (215.9 - $ew) / 2;
    $ey  = 22;
 
    $pdf->SetFillColor(18, 52, 104);
    $pdf->Rect($exL, $ey, $ew, 8, 'F');
    $pdf->SetFont('helvetica', 'B', 8.5);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetXY($exL, $ey + 1);
    $pdf->Cell($ew, 6, 'CENTRAL CELL ' . $sucNombre, 0, 1, 'C');
 
    $ey2 = $ey + 9;
 
    $pdf->SetFillColor(232, 240, 254);
    $pdf->Rect($exL, $ey2, $ew, 5, 'F');
    $pdf->SetFont('helvetica', 'B', 6.5);
    $pdf->SetTextColor(18, 52, 104);
    $pdf->SetXY($exL, $ey2 + 0.8);
    $pdf->Cell($ew, 3.5, 'COMPROBANTE DE RECEPCIÓN — FOLIO ' . $folio, 0, 1, 'C');
    $ey2 += 6;
 
    $pdf->SetTextColor(0, 0, 0);
 
    $etFila = function(string $lbl, string $val) use ($pdf, $exL, $ew, &$ey2) {
        $lw = $ew * 0.36;
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($exL + 3, $ey2);
        $pdf->Cell($lw, 4.2, $lbl . ':', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell($ew - $lw - 3, 4.2, $val, 0, 1, 'L');
        $ey2 += 4.2;
    };
 
    $etFila('Ticket',        $g['numero_ticket']);
    $etFila('Fecha',         $fecha_formateada);
    $etFila('Cliente',       $g['nombre_cliente']);
    $etFila('Contacto',      $g['numero_contacto']);
    $etFila('Modelo',        $modelo);
    $etFila('Color',         $color);
    $etFila('PLOWS',         $g['plows']);
    $etFila('IMEI',          $g['imei']);
    $etFila('Vendedor',      $g['vendedor_nombre']);
    $etFila('Recibió',       $g['vendedor_recibe_nombre']);
    $etFila('Tipo de Venta', strtoupper($g['tipo_venta']));
 
    $pdf->SetDrawColor(18, 52, 104);
    $pdf->SetLineWidth(0.5);
    $pdf->Rect($exL, $ey, $ew, $ey2 - $ey + 4, 'D');
    $pdf->SetLineWidth(0.2);
 
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetXY($exL, $ey2 + 7);
    $pdf->Cell($ew, 4, "\xe2\x9c\x82  Recortar y pegar en el equipo", 0, 1, 'C');

    while (ob_get_level()) { ob_end_clean(); }
 
    $pdf->Output('Garantia-' . $folio . '.pdf', 'D');
    exit;
}
?>
