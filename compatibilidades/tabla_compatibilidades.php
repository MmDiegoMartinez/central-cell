<?php
require_once '../funciones.php';
$conn = conectarBD();
// Consulta para obtener todas las compatibilidades agrupadas por modelo principal y tipo
$sql = "
    SELECT 
        c.tipo,
        CONCAT(m1.marca, ' ', m1.modelo) AS modelo_principal,
        GROUP_CONCAT(DISTINCT CONCAT(m2.marca, ' ', m2.modelo) ORDER BY m2.marca, m2.modelo SEPARATOR ', ') AS modelos_compatibles
    FROM compatibilidades c
    INNER JOIN modelos m1 ON c.modelo_id = m1.id
    INNER JOIN modelos m2 ON c.compatible_id = m2.id
    GROUP BY c.tipo, m1.id
    ORDER BY m1.marca ASC, m1.modelo ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$compatibilidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tabla de Compatibilidades</title>
<link rel="stylesheet" href="../styles.css?v=<?php echo time(); ?>">
<style>
    /* ---------------------------------------------------
       Estilos complementarios específicos de esta página.
       No se toca styles.css: todo lo que falta (tabla,
       botón de exportar, toggle del sidebar) se agrega aquí
       reutilizando los tokens de diseño ya definidos.
       --------------------------------------------------- */
    .topbar{
        display:flex;align-items:center;justify-content:space-between;
        gap:var(--space-md);flex-wrap:wrap;
        margin:var(--space-xl) 0 var(--space-md);
    }
    .topbar h1{margin:0;}

    .btn-exportar{
        display:inline-flex;align-items:center;gap:8px;
        padding:10px 18px;border-radius:var(--radius-lg);
        font-size:14px;font-weight:600;letter-spacing:0.02em;
        background:var(--secondary);color:var(--on-secondary);
        margin-bottom:var(--space-lg);
        transition:opacity .15s ease, transform .15s ease;
    }
    .btn-exportar:hover{opacity:0.9;transform:translateY(-1px);}

    .table-responsive{overflow-x:auto;-webkit-overflow-scrolling:touch;}
    table.data-table{
        width:100%;border-collapse:collapse;font-size:14px;
    }
    table.data-table thead th{
        text-align:left;padding:12px var(--space-md);
        background:var(--surface-container-high);
        color:var(--on-surface-variant);
        font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;
        border-bottom:1px solid var(--outline-variant);
        white-space:nowrap;
    }
    table.data-table thead th:first-child{border-top-left-radius:var(--radius-lg);}
    table.data-table thead th:last-child{border-top-right-radius:var(--radius-lg);}
    table.data-table tbody td{
        padding:12px var(--space-md);
        border-bottom:1px solid var(--outline-variant);
        color:var(--on-surface);vertical-align:top;
    }
    table.data-table tbody tr:hover{background:var(--surface-container-low);}
    table.data-table tbody tr:last-child td{border-bottom:none;}
    .text-center{text-align:center;color:var(--on-surface-variant);}
</style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-head">
            <div>
                <h2 class="sidebar-brand text-headline-sm">CentralCell</h2>
                <p class="sidebar-sub text-label-sm">Compatibilidades</p>
            </div>
            <button class="sidebar-close material-symbols-outlined" id="sidebarClose" type="button">close</button>
        </div>
        <nav class="sidebar-nav">
            <a class="sidebar-link" href="consultar.php">
                <span class="material-symbols-outlined">search</span> Consultar Compatibilidades
            </a>
        </nav>
    </aside>

    <div class="main">
        <header class="topheader">
            <div class="topheader-left">
                <button class="menu-toggle material-symbols-outlined" id="menuToggle" type="button">menu</button>
                <span class="logo-circle" style="display:inline-flex;width:32px;height:32px;background:#fff;border-radius:50%;justify-content:center;align-items:center;overflow:hidden;flex:0 0 auto;">
                    <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" style="width:24px;height:24px;object-fit:contain;" />
                </span>
                <h2 class="text-headline-sm">Descargar Compatibilidades</h2>
            </div>
        </header>

        <div class="container">

            <div class="topbar">
                <h1 class="text-headline-md">Tabla de Compatibilidades</h1>
                <span class="badge" style="display:inline-flex;align-items:center;gap:6px;background:var(--secondary-container);color:var(--on-secondary-container);padding:6px 14px;border-radius:var(--radius-full);font-size:12px;font-weight:700;letter-spacing:0.03em;white-space:nowrap;"><?= count($compatibilidades) ?> registro<?= count($compatibilidades) === 1 ? '' : 's' ?></span>
            </div>

            <a href="exportar_compatibilidades.php" class="btn-exportar">📥 Descargar en Excel</a>

            <div class="lesson">
                <div class="lesson-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Modelo Principal</th>
                                    <th>Modelos Compatibles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($compatibilidades): ?>
                                    <?php foreach ($compatibilidades as $c): ?>
                                    <tr>
                                        <td><?= ucfirst(htmlspecialchars($c['tipo'])) ?></td>
                                        <td><?= htmlspecialchars($c['modelo_principal']) ?></td>
                                        <td><?= htmlspecialchars($c['modelos_compatibles']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center">No hay compatibilidades registradas.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var menuToggle = document.getElementById('menuToggle');
            var sidebarClose = document.getElementById('sidebarClose');
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebarOverlay');

            function openSidebar(){
                sidebar.classList.add('open');
                overlay.classList.add('show');
            }
            function closeSidebar(){
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
            }

            if (menuToggle) menuToggle.addEventListener('click', openSidebar);
            if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);
        });
    </script>
</body>
</html>