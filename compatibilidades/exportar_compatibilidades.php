<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../funciones.php'; // Usa tu conexión PDO

try {
    $conn = conectarBD();

    // Filtro opcional por tipo (Glass o Funda)
    $tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';

    $sql = "
        SELECT 
            c.tipo,
            m1.marca AS marca_principal,
            m1.modelo AS modelo_principal,
            GROUP_CONCAT(CONCAT(m2.marca, ' ', m2.modelo) ORDER BY m2.marca, m2.modelo SEPARATOR ', ') AS compatibilidades,
            GROUP_CONCAT(DISTINCT c.nota SEPARATOR ', ') AS observaciones
        FROM compatibilidades c
        INNER JOIN modelos m1 ON c.modelo_id = m1.id
        INNER JOIN modelos m2 ON c.compatible_id = m2.id
        " . ($tipo ? "WHERE c.tipo = ?" : "") . "
        GROUP BY c.modelo_id, c.tipo
        ORDER BY m1.marca ASC, m1.modelo ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($tipo) {
        $stmt->execute([$tipo]);
    } else {
        $stmt->execute();
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generar XML de Excel (sin dependencias externas)
    $xml = '<?xml version="1.0"?>
    <?mso-application progid="Excel.Sheet"?>
    <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
              xmlns:o="urn:schemas-microsoft-com:office:office"
              xmlns:x="urn:schemas-microsoft-com:office:excel"
              xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
              xmlns:html="http://www.w3.org/TR/REC-html40">
      <Worksheet ss:Name="Compatibilidades">
        <Table>
          <Column ss:AutoFitWidth="0" ss:Width="100"/> <!-- Tipo -->
          <Column ss:AutoFitWidth="0" ss:Width="150"/> <!-- Marca -->
          <Column ss:AutoFitWidth="0" ss:Width="150"/> <!-- Modelo -->
          <Column ss:AutoFitWidth="0" ss:Width="500"/> <!-- Compatibilidades -->
          <Column ss:AutoFitWidth="0" ss:Width="300"/> <!-- Observaciones -->
          <Row>
            <Cell><Data ss:Type="String">Tipo</Data></Cell>
            <Cell><Data ss:Type="String">Marca</Data></Cell>
            <Cell><Data ss:Type="String">Modelo Principal</Data></Cell>
            <Cell><Data ss:Type="String">Compatibilidades</Data></Cell>
            <Cell><Data ss:Type="String">Observaciones</Data></Cell>
          </Row>';

    foreach ($rows as $r) {
        $xml .= "<Row>";
        $xml .= "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($r['tipo']) . "</Data></Cell>";
        $xml .= "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($r['marca_principal']) . "</Data></Cell>";
        $xml .= "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($r['modelo_principal']) . "</Data></Cell>";
        $xml .= "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($r['compatibilidades']) . "</Data></Cell>";
        $xml .= "<Cell><Data ss:Type=\"String\">" . htmlspecialchars($r['observaciones'] ?? '') . "</Data></Cell>";
        $xml .= "</Row>";
    }

    $xml .= '</Table></Worksheet></Workbook>';

    // Cabeceras para descarga
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=compatibilidades.xlsx");
    header("Cache-Control: max-age=0");

    echo $xml;

    // Regresar automáticamente a la página anterior después de la descarga
    echo "<script>setTimeout(() => window.history.back(), 1000);</script>";

} catch (Exception $e) {
    echo "Error al generar archivo: " . $e->getMessage();
    exit;
}
?>
