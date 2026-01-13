<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../funciones.php';

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

    // Generar XML de Excel (SpreadsheetML)
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:html="http://www.w3.org/TR/REC-html40">
  <Styles>
    <Style ss:ID="Header">
      <Font ss:Bold="1"/>
      <Interior ss:Color="#4472C4" ss:Pattern="Solid"/>
      <Font ss:Color="#FFFFFF"/>
    </Style>
  </Styles>
  <Worksheet ss:Name="Compatibilidades">
    <Table>
      <Column ss:Width="100"/>
      <Column ss:Width="150"/>
      <Column ss:Width="150"/>
      <Column ss:Width="500"/>
      <Column ss:Width="300"/>
      <Row ss:StyleID="Header">
        <Cell><Data ss:Type="String">Tipo</Data></Cell>
        <Cell><Data ss:Type="String">Marca</Data></Cell>
        <Cell><Data ss:Type="String">Modelo Principal</Data></Cell>
        <Cell><Data ss:Type="String">Compatibilidades</Data></Cell>
        <Cell><Data ss:Type="String">Observaciones</Data></Cell>
      </Row>';

    foreach ($rows as $r) {
        $xml .= '<Row>';
        $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($r['tipo'], ENT_XML1, 'UTF-8') . '</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($r['marca_principal'], ENT_XML1, 'UTF-8') . '</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($r['modelo_principal'], ENT_XML1, 'UTF-8') . '</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($r['compatibilidades'], ENT_XML1, 'UTF-8') . '</Data></Cell>';
        $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($r['observaciones'] ?? '', ENT_XML1, 'UTF-8') . '</Data></Cell>';
        $xml .= '</Row>';
    }

    $xml .= '    </Table>
  </Worksheet>
</Workbook>';

    // Cabeceras correctas para Excel XML (formato .xls)
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="compatibilidades_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    echo $xml;
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "Error al generar archivo: " . htmlspecialchars($e->getMessage());
    exit;
}
?>