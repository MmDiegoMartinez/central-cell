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

    // Buscar colaborador (mejor exacto o al menos case insensitive)
    $sql = "SELECT id FROM colaboradores WHERE nombre = :nombre LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':nombre' => $datos['apasionado']]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($colaborador) {
        $idColaborador = $colaborador['id'];
    } else {
        // Insertar nuevo colaborador
        $sqlInsert = "INSERT INTO colaboradores (nombre) VALUES (:nombre)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->execute([':nombre' => $datos['apasionado']]);
        $idColaborador = $conn->lastInsertId();
    }

    // Guardar garantía
    try {
    $sqlGarantia = "INSERT INTO garantia 
        (plows, tipo, causa, piezas, sucursal, apasionado, fecha, estatus, anotaciones_vendedor, anotado) 
        VALUES 
        (:plows, :tipo, :causa, :piezas, :sucursal, :apasionado, :fecha, 'Anotado', :anotaciones, 1)";

    $stmtGarantia = $conn->prepare($sqlGarantia);
    $stmtGarantia->execute([
        ':plows' => strtoupper($datos['plows']),
        ':tipo' => $datos['tipo'],
        ':causa' => $datos['causa'],
        ':piezas' => $datos['piezas'],
        ':sucursal' => $datos['sucursal'],
        ':apasionado' => $idColaborador,  // CORRECTO: el ID del colaborador
        ':fecha' => $datos['fecha'],
        ':anotaciones' => $datos['anotaciones_vendedor'] ?? null
    ]);
}catch (PDOException $e) {
        throw new Exception("Error al guardar garantía: " . $e->getMessage());
    }

    return true;
}
//guardar garantia de vendedores que no lo anotaron 
function guardarGarantiasinguardar($datos) {
    $conn = conectarBD();

    // Buscar colaborador (mejor exacto o al menos case insensitive)
    $sql = "SELECT id FROM colaboradores WHERE nombre = :nombre LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':nombre' => $datos['apasionado']]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($colaborador) {
        $idColaborador = $colaborador['id'];
    } else {
        // Insertar nuevo colaborador
        $sqlInsert = "INSERT INTO colaboradores (nombre) VALUES (:nombre)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->execute([':nombre' => $datos['apasionado']]);
        $idColaborador = $conn->lastInsertId();
    }

    // Guardar garantía
    try {
    $sqlGarantia = "INSERT INTO garantia 
        (plows, tipo, causa, piezas, sucursal, apasionado, fecha, estatus, anotaciones_vendedor, anotado) 
        VALUES 
        (:plows, :tipo, :causa, :piezas, :sucursal, :apasionado, :fecha, 'Anotado', :anotaciones, 2)";

    $stmtGarantia = $conn->prepare($sqlGarantia);
    $stmtGarantia->execute([
        ':plows' => strtoupper($datos['plows']),
        ':tipo' => $datos['tipo'],
        ':causa' => $datos['causa'],
        ':piezas' => $datos['piezas'],
        ':sucursal' => $datos['sucursal'],
        ':apasionado' => $idColaborador,  // CORRECTO: el ID del colaborador
        ':fecha' => $datos['fecha'],
        ':anotaciones' => $datos['anotaciones_vendedor'] ?? null
    ]);
}catch (PDOException $e) {
        throw new Exception("Error al guardar garantía: " . $e->getMessage());
    }

    return true;
}
function verTabla(): array {
    try {
        $conexion = conectarBD();

        // Definir la zona horaria de México
        $fechaActual = new DateTime("now", new DateTimeZone("America/Mexico_City"));
        $fechaActualStr = $fechaActual->format('Y-m-d');

        $sql = "SELECT 
    g.id,
    g.plows, 
    g.tipo, 
    g.causa, 
    g.piezas, 
    g.sucursal, 
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
    v.nombre AS validador_nombre, 
    v.apellido AS validador_apellido
FROM garantia g
LEFT JOIN validador v ON g.id_validador = v.id
LEFT JOIN colaboradores c ON g.apasionado = c.id
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
        $stmt = null;
        $conexion = null;

        return $resultado;

    } catch (PDOException $e) {
        error_log("Error al consultar tabla de garantías: " . $e->getMessage());
        return [];
    }
}
//tabla que muestra loas garantias sin guardar
function verTablanoguardados(): array {
    try {
        $conexion = conectarBD();

        $sql = "SELECT 
            g.id,
            g.plows, 
            g.tipo, 
            g.causa, 
            g.piezas, 
            g.sucursal, 
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
            v.nombre AS validador_nombre, 
            v.apellido AS validador_apellido
        FROM garantia g
        LEFT JOIN validador v ON g.id_validador = v.id
        LEFT JOIN colaboradores c ON g.apasionado = c.id
        WHERE g.anotado = 2
        ORDER BY g.fecha DESC, g.id DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        $conexion = null;

        return $resultado;

    } catch (PDOException $e) {
        error_log("Error al consultar tabla de garantías: " . $e->getMessage());
        return [];
    }
}


function verTablavalidador(): array {
    try {
        $conexion = conectarBD();

        $sql = "SELECT 
    g.id,
    g.plows, 
    g.tipo, 
    g.causa, 
    g.piezas, 
    g.sucursal, 
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
    v.nombre AS validador_nombre, 
    v.apellido AS validador_apellido
FROM garantia g
LEFT JOIN validador v ON g.id_validador = v.id
LEFT JOIN colaboradores c ON g.apasionado = c.id
WHERE g.anotado = 1
ORDER BY g.fecha DESC, g.id DESC;
";

        // ✅ Se usa prepare() en lugar de query() por consistencia y seguridad
        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        // ✅ Se obtiene el resultado y se cierra explícitamente la conexión
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;          // Limpieza explícita del statement
        $conexion = null;      // Cierre explícito de la conexión

        return $resultado;

    } catch (PDOException $e) {
        // ✅ Manejo controlado del error. Se puede loguear si se desea
        error_log("Error al consultar tabla de garantías: " . $e->getMessage());

        // ✅ Nunca se expone el error real al usuario por seguridad
        return [];
    }
}


function obtenerGarantiaPorId($id): ?array {
    if (!is_numeric($id) || $id <= 0) {
        // ❗ Validación de entrada: solo IDs numéricos positivos
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

        $stmt = null;         // 🔐 Libera statement
        $conexion = null;     // 🔐 Cierra conexión

        return $resultado ?: null; // Devuelve null si no se encontró nada

    } catch (PDOException $e) {
        error_log("Error al obtener garantía por ID ($id): " . $e->getMessage());
        return null; // ⚠️ No exponer el error
    }
}

function actualizarGarantia(int $id, array $datos): bool {
    if ($id <= 0) {
        return false; // ❗ ID inválido
    }

    try {
        $conexion = conectarBD();

        // Validación mínima de campos requeridos
        $camposObligatorios = ['plows', 'tipo', 'causa', 'piezas', 'sucursal', 'fecha', 'estatus'];
        foreach ($camposObligatorios as $campo) {
            if (!isset($datos[$campo])) {
                return false;
            }
        }

        // Obtener o insertar colaborador
        if (!empty($datos['apasionado_id']) && is_numeric($datos['apasionado_id'])) {
            $idColaborador = $datos['apasionado_id'];
        } else {
            $stmt = $conexion->prepare("SELECT id FROM colaboradores WHERE nombre LIKE :nombre LIMIT 1");
            $stmt->execute([':nombre' => '%' . $datos['apasionado'] . '%']);
            $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($colaborador) {
                $idColaborador = $colaborador['id'];
            } else {
                $stmtInsert = $conexion->prepare("INSERT INTO colaboradores (nombre) VALUES (:nombre)");
                $stmtInsert->execute([':nombre' => $datos['apasionado']]);
                $idColaborador = $conexion->lastInsertId();
            }
        }

        // Actualizar garantía
        $stmtUpdate = $conexion->prepare("UPDATE garantia SET
            plows = :plows,
            tipo = :tipo,
            causa = :causa,
            piezas = :piezas,
            sucursal = :sucursal,
            apasionado = :apasionado,
            fecha = :fecha,
            estatus = :estatus,
            anotaciones_vendedor = :anotaciones
        WHERE id = :id");

        $stmtUpdate->execute([
            ':plows' => strtoupper($datos['plows']),
            ':tipo' => $datos['tipo'],
            ':causa' => $datos['causa'],
            ':piezas' => $datos['piezas'],
            ':sucursal' => $datos['sucursal'],
            ':apasionado' => $idColaborador,
            ':fecha' => $datos['fecha'],
            ':estatus' => $datos['estatus'],
            ':anotaciones' => $datos['anotaciones_vendedor'] ?? null,
            ':id' => $id
        ]);

        // Liberar recursos
        $stmtUpdate = null;
        $conexion = null;

        return true;

    } catch (PDOException $e) {
        error_log("Error al actualizar garantía ID $id: " . $e->getMessage());
        return false;
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
function insertarCompatibilidad(int $modelo_id, int $compatible_id, string $tipo, ?string $nota = null): bool {
    try {
        $conn = conectarBD();

        // Validar que tipo sea válido
        if (!in_array($tipo, ['glass', 'funda'])) {
            throw new Exception("Tipo de compatibilidad inválido");
        }

        $sql = "INSERT INTO compatibilidades (modelo_id, compatible_id, tipo, nota)
                VALUES (:modelo_id, :compatible_id, :tipo, :nota)";
        $stmt = $conn->prepare($sql);

        return $stmt->execute([
            ':modelo_id' => $modelo_id,
            ':compatible_id' => $compatible_id,
            ':tipo' => $tipo,
            ':nota' => $nota
        ]);
    } catch (PDOException $e) {
        error_log("Error en insertarCompatibilidad: " . $e->getMessage());
        throw new Exception("No se pudo insertar la compatibilidad. Intente de nuevo.");
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

function actualizarGarantiasDiario(PDO $conn, $rutaArchivo = __DIR__ . "/last_update.txt") {
    $hoy = date("Y-m-d");

    // Revisar si ya se ejecutó hoy
    if (file_exists($rutaArchivo)) {
        $ultimaEjecucion = trim(file_get_contents($rutaArchivo));
        if ($ultimaEjecucion === $hoy) {
            return 0; // Ya se ejecutó hoy
        }
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

        // Número de filas afectadas
        $afectados = $stmt->rowCount();

        // Liberar statement
        $stmt = null;

        // Guardar la fecha de ejecución
        file_put_contents($rutaArchivo, $hoy);

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
?>
