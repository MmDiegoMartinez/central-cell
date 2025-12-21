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

    // Forzar configuración de zona horaria en InfinityFree
    ini_set('date.timezone', 'America/Mexico_City');
    date_default_timezone_set('America/Mexico_City');

    // Generar fecha actual en formato compatible con MySQL
    $hora_actual = date('Y-m-d H:i:s');

    // Confirmar que se generó correctamente (solo para pruebas)
    // echo "Fecha actual: $hora_actual";

    // Buscar colaborador (exacto o case-insensitive)
    $sql = "SELECT id FROM colaboradores WHERE nombre = :nombre LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':nombre' => $datos['apasionado']]);
    $colaborador = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($colaborador) {
        $idColaborador = $colaborador['id'];
    } else {
        $sqlInsert = "INSERT INTO colaboradores (nombre) VALUES (:nombre)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->execute([':nombre' => $datos['apasionado']]);
        $idColaborador = $conn->lastInsertId();
    }

    // Validar sucursal
    $sqlSucursal = "SELECT id FROM sucursales WHERE id = :id AND estatus = 1 LIMIT 1";
    $stmtSucursal = $conn->prepare($sqlSucursal);
    $stmtSucursal->execute([':id' => $datos['sucursal']]);
    $sucursalValida = $stmtSucursal->fetch(PDO::FETCH_ASSOC);

    if (!$sucursalValida) {
        throw new Exception("Sucursal inválida o inactiva.");
    }

    // Guardar garantía
    try {
        $sqlGarantia = "INSERT INTO garantia 
            (plows, tipo, causa, piezas, sucursal, apasionado, fecha, estatus, anotaciones_vendedor, anotado, created_at, updated_at) 
            VALUES 
            (:plows, :tipo, :causa, :piezas, :sucursal, :apasionado, :fecha, 'Anotado', :anotaciones, 1, :created_at, :updated_at)";

        $stmtGarantia = $conn->prepare($sqlGarantia);
        $stmtGarantia->execute([
            ':plows' => strtoupper($datos['plows']),
            ':tipo' => $datos['tipo'],
            ':causa' => $datos['causa'],
            ':piezas' => $datos['piezas'],
            ':sucursal' => $datos['sucursal'],
            ':apasionado' => $idColaborador,
            ':fecha' => $datos['fecha'],
            ':anotaciones' => $datos['anotaciones_vendedor'] ?? null,
            ':created_at' => $hora_actual,
            ':updated_at' => $hora_actual
        ]);
    } catch (PDOException $e) {
        throw new Exception("Error al guardar garantía: " . $e->getMessage());
    }

    return true;
}
//guardar garantia de vendedores que no lo anotaron 
// Guardar garantía de vendedores que no lo anotaron 
function guardarGarantiasinguardar($datos) {
    $conn = conectarBD();

    // Forzar configuración de zona horaria para InfinityFree
    ini_set('date.timezone', 'America/Mexico_City');
    date_default_timezone_set('America/Mexico_City');

    //  Generar fecha y hora actual (formato compatible con MySQL)
    $hora_actual = date('Y-m-d H:i:s');

    // Buscar colaborador (exacto o case-insensitive)
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

    // Validar sucursal
    $sqlSucursal = "SELECT id FROM sucursales WHERE id = :id AND estatus = 1 LIMIT 1";
    $stmtSucursal = $conn->prepare($sqlSucursal);
    $stmtSucursal->execute([':id' => $datos['sucursal']]);
    $sucursalValida = $stmtSucursal->fetch(PDO::FETCH_ASSOC);

    if (!$sucursalValida) {
        throw new Exception("Sucursal inválida o inactiva.");
    }

    // Guardar garantía
    try {
        $sqlGarantia = "INSERT INTO garantia 
            (plows, tipo, causa, piezas, sucursal, apasionado, fecha, estatus, anotaciones_vendedor, anotado, created_at, updated_at) 
            VALUES 
            (:plows, :tipo, :causa, :piezas, :sucursal, :apasionado, :fecha, 'Anotado', :anotaciones, 2, :created_at, :updated_at)";

        $stmtGarantia = $conn->prepare($sqlGarantia);
        $stmtGarantia->execute([
            ':plows' => strtoupper($datos['plows']),
            ':tipo' => $datos['tipo'],
            ':causa' => $datos['causa'],
            ':piezas' => $datos['piezas'],
            ':sucursal' => $datos['sucursal'], 
            ':apasionado' => $idColaborador,   
            ':fecha' => $datos['fecha'],
            ':anotaciones' => $datos['anotaciones_vendedor'] ?? null,
            ':created_at' => $hora_actual,
            ':updated_at' => $hora_actual
        ]);
    } catch (PDOException $e) {
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
            s.nombre AS sucursal,  
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
        LEFT JOIN sucursales s ON g.sucursal = s.id   
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
            s.nombre AS sucursal,   
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
        LEFT JOIN sucursales s ON g.sucursal = s.id  
        WHERE g.anotado = 2
        ORDER BY g.fecha DESC, g.id DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        $conexion = null;

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
            g.tipo, 
            g.causa, 
            g.piezas, 
            s.nombre AS sucursal,   -- <-- mostrar nombre de sucursal
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
        LEFT JOIN sucursales s ON g.sucursal = s.id  -- <-- nuevo JOIN
        WHERE g.anotado = 1
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
function obtenerMetasTiendas(): array {
    try {
        $conexion = conectarBD();
        $sql = "SELECT id, nombre, metaIM, estatus FROM sucursales WHERE estatus = 1";
        $stmt = $conexion->prepare($sql);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = null;
        $conexion = null;

        $metas = [];
        foreach ($resultados as $row) {
            // Concatenar "Central Cell " para que coincida con Excel
            $key = "Central Cell " . trim($row['nombre']);
            $metas[$key] = [
                'diaria' => floatval($row['metaIM']),
                'limite' => 9999 // si quieres poner un límite fijo o puedes agregar columna en la BD
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


function agregarSucursal(string $nombre, float $metaIM): bool {
    if (empty(trim($nombre))) {
        throw new InvalidArgumentException("El nombre de la sucursal no puede estar vacío.");
    }
    if ($metaIM < 0) {
        throw new InvalidArgumentException("La meta IM no puede ser negativa.");
    }

    try {
        $conn = conectarBD();
        $sql = "INSERT INTO sucursales (nombre, metaIM, estatus) VALUES (:nombre, :metaIM, 1)";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':nombre' => $nombre, ':metaIM' => $metaIM]);
    } catch (PDOException $e) {
        error_log("Error agregarSucursal: " . $e->getMessage());
        throw new Exception("No se pudo agregar la sucursal.");
    }
}


function actualizarMetaSucursal(int $id, float $metaIM): bool {
    if ($id <= 0) {
        throw new InvalidArgumentException("ID inválido.");
    }
    if ($metaIM < 0) {
        throw new InvalidArgumentException("La meta IM no puede ser negativa.");
    }

    try {
        $conn = conectarBD();
        $sql = "UPDATE sucursales SET metaIM = :metaIM WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':metaIM' => $metaIM, ':id' => $id]);
    } catch (PDOException $e) {
        error_log("Error actualizarMetaSucursal: " . $e->getMessage());
        throw new Exception("No se pudo actualizar la meta de la sucursal.");
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
function procesarArchivoExcel(string $rutaArchivo): array {
    $resultado = [
        'exito' => false,
        'registros_insertados' => 0,
        'registros_omitidos' => [],
        'mensaje' => ''
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
        if ($zip->open($rutaArchivo) === true) {
            // Leer sheet1
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

            // Procesar sharedStrings
            $sharedStrings = [];
            if ($sharedStringsXml) {
                $xml = new SimpleXMLElement($sharedStringsXml);
                foreach ($xml->si as $si) {
                    $sharedStrings[] = (string)$si->t;
                }
            }

            // Procesar filas
            $xml = new SimpleXMLElement($sheetXml);
            $contadorInsertados = 0;
            $primeraFila = true;

            foreach ($xml->sheetData->row as $row) {
                if ($primeraFila) { 
                    $primeraFila = false; 
                    continue; 
                }

                // Crear array asociativo con las columnas usando la referencia de celda
                $celdas = [];
                foreach ($row->c as $c) {
                    $ref = (string)$c['r']; // Ej: "A2", "M2", "Q2"
                    preg_match('/^([A-Z]+)(\d+)$/', $ref, $matches);
                    $columna = $matches[1];
                    
                    $v = isset($c->v) ? (string)$c->v : '';
                    
                    // Si es un string compartido
                    if (isset($c['t']) && $c['t'] == 's') {
                        $v = $sharedStrings[(int)$v] ?? '';
                    }
                    
                    $celdas[$columna] = $v;
                }

                // Obtener columnas específicas por letra
                $almacenCompleto = trim($celdas['A'] ?? '');
                $descripcion = trim($celdas['C'] ?? '');
                $existencia = (int)($celdas['H'] ?? 0);
                $barcodeId = trim($celdas['M'] ?? '');
                $nombreCategoria = trim($celdas['N'] ?? '');
                $publicoGeneral = (float)($celdas['Q'] ?? 0);

                // FILTRO SILENCIOSO 1: Omitir si el almacén es "Central Cell Almacén general"
                if (stripos($almacenCompleto, 'Almacén general') !== false) {
                    continue;
                }

                // FILTRO SILENCIOSO 2: Omitir si la columna N contiene "SOLUCIONES TECNICAS"
                if (stripos($nombreCategoria, 'SOLUCIONES TECNICAS') !== false) {
                    continue;
                }

                // FILTRO SILENCIOSO 3: Omitir si la existencia es 0
                if ($existencia == 0) {
                    continue;
                }

                // Validación básica
                if (empty($almacenCompleto) || empty($descripcion)) {
                    continue;
                }

                // Extraer nombre del almacén
                $nombreAlmacen = (strpos($almacenCompleto, 'Central Cell ') === 0) 
                    ? trim(substr($almacenCompleto, strlen('Central Cell '))) 
                    : $almacenCompleto;

                $idAlmacen = obtenerIDSucursal($nombreAlmacen);

                // SOLO REPORTAR ERROR SI NO SE ENCUENTRA EL ALMACÉN
                if ($idAlmacen === null) {
                    $resultado['registros_omitidos'][] = [
                        'fila' => (int)$row['r'],
                        'almacen' => $almacenCompleto,
                        'descripcion' => $descripcion,
                        'motivo' => 'Almacén no encontrado en la base de datos'
                    ];
                    continue;
                }

                // SOLO REPORTAR ERROR SI FALLA LA INSERCIÓN
                if (insertarExistencia($idAlmacen, $descripcion, $existencia, $barcodeId, $publicoGeneral)) {
                    $contadorInsertados++;
                } else {
                    $resultado['registros_omitidos'][] = [
                        'fila' => (int)$row['r'],
                        'almacen' => $almacenCompleto,
                        'descripcion' => $descripcion,
                        'motivo' => 'Error al insertar en la base de datos'
                    ];
                }
            }

            $zip->close();
            $resultado['exito'] = true;
            $resultado['registros_insertados'] = $contadorInsertados;
            
            if (count($resultado['registros_omitidos']) > 0) {
                $resultado['mensaje'] = "Proceso completado. $contadorInsertados registros insertados, " . count($resultado['registros_omitidos']) . " con errores.";
            } else {
                $resultado['mensaje'] = "Proceso completado exitosamente. $contadorInsertados registros insertados.";
            }
        } else {
            $resultado['mensaje'] = "No se pudo abrir el archivo Excel";
        }
    } catch (Exception $e) {
        $resultado['mensaje'] = "Error: " . $e->getMessage();
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

?>
