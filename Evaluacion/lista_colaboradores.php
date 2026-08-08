<?php
session_start();
if (!isset($_SESSION['validador_id'])) {
header('Location: loginvalidador.php');
exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once("../funciones.php");
// Crear la conexión usando PDO
try {
$conexion = conectarBD();
} catch (Exception $e) {
die("❌ Error de conexión: " . $e->getMessage());
}
// Consulta
$sql = "
SELECT 
    c.id AS id_colaborador,
    c.nombre,
    COUNT(r.id) AS total_respuestas,
    SUM(CASE WHEN o.es_correcta = 1 AND o.id = r.id_opcion THEN 1 ELSE 0 END) AS correctas
FROM colaboradores c
JOIN respuestas_colaborador r ON c.id = r.id_colaborador
JOIN opciones_respuesta o ON r.id_opcion = o.id
GROUP BY c.id, c.nombre
ORDER BY c.nombre ASC
";
try {
$stmt = $conexion->query($sql);
$colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
die("❌ Error al ejecutar la consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resultados de Examen</title>
<link rel="stylesheet" href="../styles.css">
<style>
    
    .topbar{
        display:flex;align-items:center;justify-content:space-between;
        gap:var(--space-md);flex-wrap:wrap;
        margin:var(--space-xl) 0 var(--space-md);
    }
    .topbar h1{margin:0;}
    .badge{
        display:inline-flex;align-items:center;gap:6px;
        background:var(--secondary-container);
        color:var(--on-secondary-container);
        padding:6px 14px;border-radius:var(--radius-full);
        font-size:12px;font-weight:700;letter-spacing:0.03em;
        white-space:nowrap;
    }

    .table-wrap{
        overflow-x:auto;
        -webkit-overflow-scrolling:touch;
    }
    table.data-table{
        width:100%;border-collapse:collapse;
        font-size:14px;
    }
    table.data-table thead th{
        text-align:left;
        padding:12px var(--space-md);
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
        color:var(--on-surface);
        vertical-align:middle;
    }
    table.data-table tbody tr:hover{background:var(--surface-container-low);}
    table.data-table tbody tr:last-child td{border-bottom:none;}

    .calif{
        display:inline-flex;align-items:center;justify-content:center;
        min-width:64px;
        padding:4px 10px;border-radius:var(--radius-full);
        font-weight:700;font-size:13px;
    }
    .calif.alta{background:rgba(109,245,225,0.25);color:var(--on-secondary-container);}
    .calif.media{background:var(--surface-container-high);color:var(--on-surface-variant);}
    .calif.baja{background:var(--error-container);color:var(--on-error-container);}

    .btn-sm{
        padding:6px 14px;font-size:13px;
    }

    .empty-state{
        text-align:center;color:var(--on-surface-variant);
        padding:var(--space-2xl) var(--space-md);
        font-size:14px;
    }
</style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-head">
            <div>
                <h2 class="sidebar-brand text-headline-sm">CentralCell</h2>
                <p class="sidebar-sub text-label-sm">Innovación Móvil</p>
            </div>
            <button class="sidebar-close material-symbols-outlined" id="sidebarClose" type="button">close</button>
        </div>
        <nav class="sidebar-nav">
            <a class="sidebar-link" href="../garantias/validador/validador.php">
                <span class="material-symbols-outlined">home</span> Inicio
            </a>
           
            <a class="sidebar-link" href="material.html">
                <span class="material-symbols-outlined">menu_book</span> Material
            </a>
            <a class="sidebar-link" href="examen.php">
                <span class="material-symbols-outlined">quiz</span> Cuestionario
            </a>
            <a class="sidebar-link" href="../capacitados/capa.php">
                <span class="material-symbols-outlined">event</span> Fechas
            </a>
        </nav>
    </aside>

    <div class="main">
        <header class="topheader">
            <div class="topheader-left">
                <button class="menu-toggle material-symbols-outlined" id="menuToggle" type="button">menu</button>
                <h2 class="text-headline-sm">Resultados de Examen</h2>
            </div>
        </header>

        <div class="container">

            <div class="topbar">
                <h1 class="text-headline-md">Lista de Colaboradores con Examen</h1>
                <span class="badge"><?= count($colaboradores) ?> colaborador<?= count($colaboradores) === 1 ? '' : 'es' ?></span>
            </div>

            <div class="lesson">
                <div class="lesson-body">
                    <?php if (empty($colaboradores)): ?>
                        <p class="empty-state">Aún no hay colaboradores con exámenes registrados.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Calificación</th>
                                        <th>Ver Respuestas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($colaboradores as $row): 
                                        $total = $row['total_respuestas'];
                                        $correctas = $row['correctas'];
                                        $calificacion = $total > 0 ? round(($correctas / $total) * 10, 2) : 0;
                                        $nivel = $calificacion >= 8 ? 'alta' : ($calificacion >= 6 ? 'media' : 'baja');
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                                        <td><span class="calif <?= $nivel ?>"><?= $calificacion ?> / 10</span></td>
                                        <td><a class="btn btn-primary btn-sm" href="detalle_respuestas.php?id_colaborador=<?= $row['id_colaborador'] ?>">Ver</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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