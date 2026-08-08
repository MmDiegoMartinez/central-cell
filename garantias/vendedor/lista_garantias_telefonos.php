<?php
ob_start();
include_once '../../funciones.php';

/* ── Acciones AJAX ──────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    header('Content-Type: application/json');
    $conn = conectarBD();

    if ($_POST['accion'] === 'eliminar') {
        $id   = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM garantias_telefonos WHERE id_caso = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($_POST['accion'] === 'editar') {
        $id              = intval($_POST['id']);
        $nombre_cliente  = trim($_POST['nombre_cliente']);
        $numero_contacto = preg_replace('/\D/', '', $_POST['numero_contacto']);
        $numero_ticket   = trim($_POST['numero_ticket']);
        $tipo_venta      = trim($_POST['tipo_venta']);
        $imei            = trim($_POST['imei']);

        $stmt = $conn->prepare("
            UPDATE garantias_telefonos SET
                nombre_cliente  = :nombre_cliente,
                numero_contacto = :numero_contacto,
                numero_ticket   = :numero_ticket,
                tipo_venta      = :tipo_venta,
                imei            = :imei
            WHERE id_caso = :id
        ");
        $stmt->execute([
            ':nombre_cliente'  => $nombre_cliente,
            ':numero_contacto' => $numero_contacto,
            ':numero_ticket'   => $numero_ticket,
            ':tipo_venta'      => $tipo_venta,
            ':imei'            => $imei,
            ':id'              => $id,
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Acción desconocida']);
    exit;
}

/* ── Datos ──────────────────────────────────────────────── */
$conn = conectarBD();

$buscar  = trim($_GET['buscar']     ?? '');
$sucFil  = intval($_GET['sucursal'] ?? 0);
$pagina  = max(1, intval($_GET['pagina'] ?? 1));
$porPag  = 20;
$offset  = ($pagina - 1) * $porPag;

$where  = "WHERE 1=1";
$params = [];

if ($buscar !== '') {
    $where .= " AND (
        gt.plows           LIKE :b OR
        gt.nombre_cliente  LIKE :b OR
        gt.numero_ticket   LIKE :b OR
        gt.imei            LIKE :b OR
        gt.numero_contacto LIKE :b
    )";
    $params[':b'] = '%' . $buscar . '%';
}
if ($sucFil > 0) {
    $where .= " AND gt.sucursal = :suc";
    $params[':suc'] = $sucFil;
}

$sqlCount = "SELECT COUNT(*) FROM garantias_telefonos gt $where";
$stmtC    = $conn->prepare($sqlCount);
$stmtC->execute($params);
$total    = (int) $stmtC->fetchColumn();
$totalPag = (int) ceil($total / $porPag);

$sql = "
    SELECT
        gt.id_caso, gt.plows, gt.nombre_cliente, gt.numero_contacto,
        gt.numero_ticket, gt.tipo_venta, gt.imei, gt.fecha_registro,
        s.nombre  AS sucursal_nombre,
        v.nombre  AS vendedor_nombre,
        vr.nombre AS vendedor_recibe_nombre
    FROM garantias_telefonos gt
    LEFT JOIN sucursales    s  ON s.id  = gt.sucursal
    LEFT JOIN colaboradores v  ON v.id  = gt.vendedor
    LEFT JOIN colaboradores vr ON vr.id = gt.vendedor_recibe
    $where
    ORDER BY gt.fecha_registro DESC
    LIMIT $porPag OFFSET $offset
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sucursales = obtenerSucursales();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Garantías Telefonía</title>
<link rel="stylesheet" href="../../csstabla.css?v=<?php echo time(); ?>">
<style>
/* ── Overlay de carga ── */
#overlay-carga {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(10, 30, 70, 0.72);
    z-index: 9999;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 22px;
}
#overlay-carga.activo { display: flex; }
#overlay-carga .spinner {
    width: 56px; height: 56px;
    border: 6px solid rgba(255,255,255,0.25);
    border-top-color: #fff;
    border-radius: 50%;
    animation: girar 0.75s linear infinite;
}
@keyframes girar { to { transform: rotate(360deg); } }
#overlay-carga .texto-carga {
    color: #fff; font-size: 17px; font-weight: 600;
    font-family: sans-serif; letter-spacing: 0.3px;
}
#overlay-carga .barra-wrap {
    width: 260px; height: 8px;
    background: rgba(255,255,255,0.2);
    border-radius: 99px; overflow: hidden;
}
#overlay-carga .barra-fill {
    height: 100%; width: 0%;
    background: #fff; border-radius: 99px;
    transition: width 0.35s ease;
}
#overlay-carga .subtexto {
    color: rgba(255,255,255,0.7);
    font-size: 12px; font-family: sans-serif; margin-top: -10px;
}

/* ── Encabezado de sección ── */
.sec-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}
.sec-header h2 { margin: 0; display:flex; align-items:center; gap:8px; }
.sec-header p  { margin: 4px 0 0; color: var(--muted); font-size: .85rem; }

.btn-nueva {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--primary-600); color: #fff;
    border-radius: 8px; padding: 10px 20px;
    font-size: .87rem; font-weight: 700;
    text-decoration: none;
    box-shadow: var(--shadow-sm);
    transition: opacity var(--transition-fast);
}
.btn-nueva:hover { opacity: .85; }

/* ── Icono Material Symbols ── */
.icon {
    font-family: 'Material Symbols Outlined';
    font-weight: normal;
    font-style: normal;
    line-height: 1;
    display: inline-block;
    vertical-align: middle;
    -webkit-font-smoothing: antialiased;
}
.icon-sm { font-size: 16px; }
.icon-md { font-size: 20px; }
.icon-lg { font-size: 2.2rem; }

/* ── Badges tipo de venta ── */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 99px;
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .4px;
    text-transform: uppercase;
}
.badge-contado        { background:#dcfce7; color:#166534; }
.badge-credito        { background:#fef9c3; color:#854d0e; }
.badge-financiamiento { background:#e0eaff; color:#1e3a8a; }
.badge-apartado       { background:#f3e8ff; color:#6b21a8; }
.badge-otro           { background:#f1f5f9; color:#475569; }

/* ── Botones acción tabla ── */
.btn-accion {
    border: none; border-radius: 6px;
    padding: 5px 11px; font-size: .74rem;
    font-weight: 700; cursor: pointer;
    white-space: nowrap;
    display: inline-flex; align-items: center; gap: 4px;
    transition: opacity var(--transition-fast);
}
.btn-accion:hover { opacity: .8; }
.btn-pdf    { background: var(--primary-600); color: #fff; }
.btn-editar { background: #e0f2fe; color: #0369a1; }
.btn-borrar { background: #fee2e2; color: #991b1b; }

/* ── Paginación ── */
.paginacion {
    display: flex; justify-content: center;
    align-items: center; gap: 5px;
    padding: 24px 0; flex-wrap: wrap;
}
.paginacion a, .paginacion span {
    padding: 7px 14px; border-radius: 7px;
    font-size: .82rem; font-weight: 700;
    text-decoration: none;
    transition: opacity var(--transition-fast);
}
.paginacion a {
    border: 1px solid #e2e8f0;
    background: var(--surface); color: var(--primary-600);
}
.paginacion a:hover { opacity: .8; }
.paginacion .pag-activa {
    background: var(--primary-600); color: #fff; border: none;
}
.paginacion .pag-off {
    border: 1px solid #f1f5f9;
    background: #f8fafc; color: #cbd5e1;
}

/* ── Modales ── */
.modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(10,20,50,.55);
    z-index: 2000; align-items: center; justify-content: center;
}
.modal-overlay.activo { display: flex; }

/* reutiliza .modaldos del csstabla.css pero sin conflicto */
.modal-inner {
    background: var(--surface);
    padding: 28px 32px;
    border-radius: var(--radius-lg);
    width: 100%; max-width: 460px;
    box-shadow: var(--shadow-md);
    animation: fadeInUp .2s ease;
    position: relative;
}
.modal-inner h3 {
    margin: 0 0 20px; color: var(--primary-600);
    font-size: 1.15rem; font-weight: 700;
    text-align: center;
    display:flex; align-items:center; justify-content:center; gap:8px;
    border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;
}
.modal-cerrar {
    position: absolute; top: 14px; right: 16px;
    background: none; border: none;
    font-size: 1.4rem; cursor: pointer;
    color: var(--muted); line-height: 1;
    display: flex;
}
.modal-label {
    display: block; font-size: .78rem; font-weight: 700;
    color: var(--muted); text-transform: uppercase;
    letter-spacing: .4px; margin-bottom: 4px;
}
.modal-field {
    width: 100%; border: 1px solid #e2e8f0;
    border-radius: 8px; padding: 9px 12px;
    font-size: .9rem; margin-bottom: 14px;
    box-sizing: border-box; outline: none;
    background: #f8fafc; color: var(--text);
    transition: border var(--transition-fast);
}
.modal-field:focus {
    border-color: var(--primary-400);
    box-shadow: 0 0 0 3px rgba(14,165,233,.12);
}
.modal-footer {
    display: flex; gap: 10px; justify-content: flex-end; margin-top: 6px;
}
.btn-cancelar {
    background: #f1f5f9; color: var(--muted);
    border: none; border-radius: 8px;
    padding: 9px 18px; font-size: .87rem;
    font-weight: 700; cursor: pointer;
    transition: background var(--transition-fast);
}
.btn-cancelar:hover { background: #e2e8f0; }
.btn-ok {
    background: var(--primary-600); color: #fff;
    border: none; border-radius: 8px;
    padding: 9px 22px; font-size: .87rem;
    font-weight: 700; cursor: pointer;
    box-shadow: var(--shadow-sm);
    transition: opacity var(--transition-fast);
}
.btn-ok:hover { opacity: .85; }
.btn-danger {
    background: #b91c1c; color: #fff;
    border: none; border-radius: 8px;
    padding: 9px 20px; font-size: .87rem;
    font-weight: 700; cursor: pointer;
    transition: opacity var(--transition-fast);
}
.btn-danger:hover { opacity: .85; }

/* ── Toast ── */
#toast {
    position: fixed; bottom: 24px; right: 24px;
    background: var(--primary-600); color: #fff;
    padding: 12px 22px; border-radius: 10px;
    font-size: .87rem; font-weight: 700;
    display: flex; align-items: center; gap: 8px;
    box-shadow: var(--shadow-md);
    transform: translateY(80px); opacity: 0;
    transition: transform .3s ease, opacity .3s ease;
    z-index: 9999; pointer-events: none;
}

/* ── Tabla: filas compactas, sin scroll vertical ── */
.table-container {
    max-height: none !important;
    overflow-y: visible !important;
}
.table-container table th,
.table-container table td {
    padding: 2px 8px !important;
    font-size: .75rem !important;
    white-space: nowrap;
    line-height: 1.3 !important;
}

/* ── Tabla: columnas monoespaciadas ── */
.col-mono  { font-family: monospace; font-size: .8rem; }
.col-plows { font-family: monospace; font-size: .8rem; color: #0369a1; }
.col-imei  { font-family: monospace; font-size: .73rem; color: var(--muted); }
.col-folio { font-weight: 700; color: var(--primary-600); }
.col-fecha { white-space: nowrap; color: var(--muted); }
.col-bold  { font-weight: 600; }

@keyframes fadeInUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}

/* ── Acciones en celda ── */
.celda-acciones { display:flex; gap:4px; align-items:center; flex-wrap:nowrap; }

/* ── Menú hamburguesa: forzado responsivo ──
   El <nav> queda fijo arriba, así que hay que dar espacio al contenido. */
body { padding-top: 60px; }

.hb-bar {
    display: block; width: 100%; height: 3px;
    background: #fff; border-radius: 2px;
}

/* Escritorio: sin botón hamburguesa, sin flechas, menú horizontal normal */
#hamburger, #nav-prev, #nav-next, #menu-overlay { display: none !important; }

/* Móvil (≤768px): aparece el botón hamburguesa y el menú se despliega */
@media (max-width: 768px) {
    #hamburger { display: flex !important; }

    #menu-scroll {
        display: none;
        position: fixed;
        top: 58px; left: 0; width: 100%;
        background: #0F5476;
        z-index: 999;
        max-height: calc(100vh - 58px);
        overflow-y: auto;
    }
    #menu-scroll.abierto { display: block; }

    #menu {
        flex-direction: column !important;
        flex-wrap: nowrap !important;
        gap: 0 !important;
        padding: 8px 0 !important;
    }
    #menu li a { padding: 12px 20px; }

    #menu-overlay.abierto { display: block !important; }
}

/* ── Sin resultados ── */
.sin-resultados {
    text-align: center; padding: 48px 20px;
    color: var(--muted); font-size: .95rem;
}
.no-results {
    text-align: center; padding: 48px 20px;
    color: var(--muted); font-size: .95rem;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
}
</style>
</head>
<body>

<!-- ══ NAV ══ -->
<nav style="background:#0F5476; padding:10px 15px; position:fixed; top:0; left:0; width:100%; z-index:1000; display:flex; align-items:center; gap:8px; box-sizing:border-box;">

    <h1 id="nombre">&shy; </h1>

    <button id="hamburger" onclick="toggleMenu()" style="
        display:none; background:none; border:none; cursor:pointer;
        flex-direction:column; justify-content:center; gap:5px;
        width:36px; height:36px; flex-shrink:0; padding:4px;
      ">
        <span class="hb-bar"></span>
        <span class="hb-bar"></span>
        <span class="hb-bar"></span>
    </button>

    <div id="menu-overlay" onclick="cerrarMenu()" style="
        display:none; position:fixed; top:0; left:0;
        width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:998;
      "></div>

    <button id="nav-prev" onclick="scrollMenu(-1)" style="
        display:none; background:rgba(255,255,255,0.15); border:none; color:#fff;
        width:28px; height:36px; cursor:pointer; font-size:1.3em; border-radius:4px;
        align-items:center; justify-content:center; flex-shrink:0;
      ">&#8249;</button>

    <div id="menu-scroll" style="overflow:hidden; flex:1;">
        <ul id="menu" style="display:flex; flex-direction:row; flex-wrap:nowrap; margin:0; padding:0; list-style:none; gap:4px;">
            <li>
                <a href="garantias.php" style="display:flex;align-items:center;gap:8px;white-space:nowrap;">
                    <span style="display:inline-flex;width:40px;height:40px;background:#fff;border-radius:50%;justify-content:center;align-items:center;">
                        <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png"
                             style="width:30px;height:30px;object-fit:contain;" alt="Logo">
                    </span>Home
                </a>
            </li>
        </ul>
    </div>

    <button id="nav-next" onclick="scrollMenu(1)" style="
        display:none; background:rgba(255,255,255,0.15); border:none; color:#fff;
        width:28px; height:36px; cursor:pointer; font-size:1.3em; border-radius:4px;
        align-items:center; justify-content:center; flex-shrink:0;
      ">&#8250;</button>

</nav>

<!-- ══ Overlay PDF ══ -->
<div id="overlay-carga">
    <div class="spinner"></div>
    <div class="texto-carga" id="texto-carga">Generando PDF...</div>
    <div class="barra-wrap"><div class="barra-fill" id="barra-fill"></div></div>
    <div class="subtexto"   id="subtexto">Por favor espera</div>
</div>

<!-- ══ CONTENIDO ══ -->
<div class="container">

    <!-- Encabezado -->
    <div class="sec-header">
        <div>
            <h2><span class="icon icon-md">assignment</span> Garantías Telefonía</h2>
            <p><?= number_format($total) ?> registro<?= $total !== 1 ? 's' : '' ?> en total</p>
        </div>
        <a href="formato_garantia.php" class="btn-nueva"><span class="icon icon-sm">add</span> Nueva garantía</a>
    </div>

    <!-- Filtros — usa clases del csstabla.css -->
    <div class="filters-container">
        <form method="GET">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Buscar</label>
                    <input type="text" name="buscar" id="input-buscar"
                           placeholder="PLOWS, cliente, ticket, IMEI…"
                           value="<?= htmlspecialchars($buscar) ?>">
                </div>
                <div class="filter-group">
                    <label>Sucursal</label>
                    <select name="sucursal" id="select-sucursal">
                        <option value="">Todas</option>
                        <?php foreach ($sucursales as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $sucFil == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="filter-buttons">
                <a href="lista_garantias_telefonos.php" class="btn-clear"
                   style="padding:9px 18px;border-radius:6px;font-size:13px;
                          font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <span class="icon icon-sm">close</span> Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla — usa .table-container del csstabla.css -->
    <div class="table-container">
    <?php if (count($registros) === 0): ?>
        <p class="no-results"><span class="icon icon-lg">search_off</span> No se encontraron registros con ese criterio.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Folio</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th>Ticket</th>
                    <th>PLOWS</th>
                    <th>IMEI</th>
                    <th>Sucursal</th>
                    <th>Vendedor</th>
                    <th>Recibió</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($registros as $r):
                $tipoKey    = strtolower(trim($r['tipo_venta']));
                $badgeClass = match($tipoKey) {
                    'contado'        => 'badge-contado',
                    'credito'        => 'badge-credito',
                    'financiamiento' => 'badge-financiamiento',
                    'apartado'       => 'badge-apartado',
                    default          => 'badge-otro',
                };
            ?>
            <tr>
                <td class="col-folio"><?= str_pad($r['id_caso'], 5, '0', STR_PAD_LEFT) ?></td>
                <td class="col-fecha"><?= date('d/m/Y H:i', strtotime($r['fecha_registro'])) ?></td>
                <td class="col-bold"><?= htmlspecialchars($r['nombre_cliente']) ?></td>
                <td><?= htmlspecialchars($r['numero_contacto'] ?? '—') ?></td>
                <td class="col-mono"><?= htmlspecialchars($r['numero_ticket']) ?></td>
                <td class="col-plows"><?= htmlspecialchars($r['plows']) ?></td>
                <td class="col-imei"><?= htmlspecialchars($r['imei']) ?></td>
                <td><?= htmlspecialchars($r['sucursal_nombre'] ?? '—') ?></td>
                <td><?= htmlspecialchars($r['vendedor_nombre'] ?? '—') ?></td>
                <td><?= htmlspecialchars($r['vendedor_recibe_nombre'] ?? '—') ?></td>
                <td>
                    <span class="badge <?= $badgeClass ?>">
                        <?= strtoupper($r['tipo_venta']) ?>
                    </span>
                </td>
                <td>
                    <div class="celda-acciones">
                        <button class="btn-accion btn-pdf"
                                onclick="descargarPDF(<?= $r['id_caso'] ?>, '<?= str_pad($r['id_caso'], 5, '0', STR_PAD_LEFT) ?>')">
                            <span class="icon icon-sm">picture_as_pdf</span> PDF
                        </button>
                        <button class="btn-accion btn-editar"
                                onclick='abrirEditar(<?= json_encode($r) ?>)'>
                            <span class="icon icon-sm">edit</span>
                        </button>
                        <button class="btn-accion btn-borrar"
                                onclick="confirmarEliminar(<?= $r['id_caso'] ?>, '<?= htmlspecialchars($r['nombre_cliente'], ENT_QUOTES) ?>')">
                            <span class="icon icon-sm">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    </div><!-- /table-container -->

    <!-- Paginación -->
    <?php if ($totalPag > 1):
        $qs  = http_build_query(['buscar' => $buscar, 'sucursal' => $sucFil]);
        $ini = max(1, $pagina - 2);
        $fin = min($totalPag, $pagina + 2);
    ?>
    <div class="paginacion">
        <?php if ($pagina > 1): ?>
            <a href="?<?= $qs ?>&pagina=<?= $pagina - 1 ?>">‹ Anterior</a>
        <?php else: ?>
            <span class="pag-off">‹ Anterior</span>
        <?php endif; ?>

        <?php for ($p = $ini; $p <= $fin; $p++): ?>
            <?php if ($p == $pagina): ?>
                <span class="pag-activa"><?= $p ?></span>
            <?php else: ?>
                <a href="?<?= $qs ?>&pagina=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagina < $totalPag): ?>
            <a href="?<?= $qs ?>&pagina=<?= $pagina + 1 ?>">Siguiente ›</a>
        <?php else: ?>
            <span class="pag-off">Siguiente ›</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- /container -->

<!-- ══ Modal Editar ══ -->
<div id="modal-editar" class="modal-overlay">
    <div class="modal-inner">
        <button class="modal-cerrar" onclick="cerrarModal('modal-editar')"><span class="icon icon-md">close</span></button>
        <h3><span class="icon icon-md">edit</span> Editar garantía</h3>
        <input type="hidden" id="edit-id">

        <label class="modal-label">Nombre del cliente</label>
        <input type="text" id="edit-nombre" class="modal-field">

        <label class="modal-label">Número de contacto</label>
        <input type="text" id="edit-contacto" maxlength="10" class="modal-field">

        <label class="modal-label">Número de ticket</label>
        <input type="text" id="edit-ticket" class="modal-field">

        <label class="modal-label">IMEI</label>
        <input type="text" id="edit-imei" maxlength="50" class="modal-field">

        <label class="modal-label">Tipo de venta</label>
        <select id="edit-tipo" class="modal-field">
            <option value="contado">Contado</option>
            <option value="credito">PayJoy / Crédito</option>
            <option value="financiamiento">Financiamiento</option>
            <option value="apartado">Apartado</option>
        </select>

        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModal('modal-editar')">Cancelar</button>
            <button class="btn-ok"       onclick="guardarEdicion()">Guardar cambios</button>
        </div>
    </div>
</div>

<!-- ══ Modal Confirmar Eliminar ══ -->
<div id="modal-confirm" class="modal-overlay">
    <div class="modal-inner" style="max-width:360px; text-align:center;">
        <div class="icon icon-lg" style="color:#b91c1c; margin-bottom:10px;">warning</div>
        <h3 style="justify-content:center;">Eliminar registro</h3>
        <p id="confirm-texto" style="color:var(--muted); font-size:.9rem; line-height:1.6; margin:0 0 24px;"></p>
        <div class="modal-footer" style="justify-content:center;">
            <button class="btn-cancelar" onclick="cerrarModal('modal-confirm')">Cancelar</button>
            <button class="btn-danger"   onclick="ejecutarEliminar()">Sí, eliminar</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast"></div>

<script>
/* ── Menú hamburguesa (móvil) ── */
function toggleMenu() {
    const menuScroll = document.getElementById('menu-scroll');
    const overlay     = document.getElementById('menu-overlay');
    const abrir       = !menuScroll.classList.contains('abierto');
    menuScroll.classList.toggle('abierto', abrir);
    overlay.classList.toggle('abierto', abrir);
    overlay.style.display = abrir ? 'block' : 'none';
}
function cerrarMenu() {
    document.getElementById('menu-scroll').classList.remove('abierto');
    const overlay = document.getElementById('menu-overlay');
    overlay.classList.remove('abierto');
    overlay.style.display = 'none';
}
function scrollMenu(direccion) {
    document.getElementById('menu').scrollBy({ left: direccion * 150, behavior: 'smooth' });
}

/* ── Toast ── */
function toast(msg, tipo) {
    const t = document.getElementById('toast');
    t.innerHTML         = msg;
    t.style.background = tipo === 'error' ? '#b91c1c' : '#155e75';
    t.style.transform  = 'translateY(0)';
    t.style.opacity    = '1';
    clearTimeout(t._t);
    t._t = setTimeout(() => {
        t.style.transform = 'translateY(80px)';
        t.style.opacity   = '0';
    }, 3000);
}

/* ── Modales ── */
function cerrarModal(id) {
    document.getElementById(id).classList.remove('activo');
}
function abrirModal(id) {
    document.getElementById(id).classList.add('activo');
}
['modal-editar','modal-confirm'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) cerrarModal(id);
    });
});

/* ── Editar ── */
function abrirEditar(r) {
    document.getElementById('edit-id').value       = r.id_caso;
    document.getElementById('edit-nombre').value   = r.nombre_cliente;
    document.getElementById('edit-contacto').value = r.numero_contacto || '';
    document.getElementById('edit-ticket').value   = r.numero_ticket;
    document.getElementById('edit-tipo').value     = r.tipo_venta;
    document.getElementById('edit-imei').value     = r.imei;
    abrirModal('modal-editar');
}

async function guardarEdicion() {
    const datos = new FormData();
    datos.append('accion',          'editar');
    datos.append('id',              document.getElementById('edit-id').value);
    datos.append('nombre_cliente',  document.getElementById('edit-nombre').value);
    datos.append('numero_contacto', document.getElementById('edit-contacto').value);
    datos.append('numero_ticket',   document.getElementById('edit-ticket').value);
    datos.append('tipo_venta',      document.getElementById('edit-tipo').value);
    datos.append('imei',            document.getElementById('edit-imei').value);
    try {
        const res  = await fetch('lista_garantias_telefonos.php', { method: 'POST', body: datos });
        const json = await res.json();
        if (json.ok) {
            cerrarModal('modal-editar');
            toast('<span class="icon icon-sm">check_circle</span> Registro actualizado');
            setTimeout(() => location.reload(), 1200);
        } else {
            toast('<span class="icon icon-sm">error</span> Error al guardar', 'error');
        }
    } catch(e) {
        toast('<span class="icon icon-sm">error</span> Error de conexión', 'error');
    }
}

/* ── Eliminar ── */
let _eliminarId = 0;
function confirmarEliminar(id, nombre) {
    _eliminarId = id;
    document.getElementById('confirm-texto').textContent =
        `¿Seguro que deseas eliminar la garantía de "${nombre}"? Esta acción no se puede deshacer.`;
    abrirModal('modal-confirm');
}
async function ejecutarEliminar() {
    const datos = new FormData();
    datos.append('accion', 'eliminar');
    datos.append('id',     _eliminarId);
    try {
        const res  = await fetch('lista_garantias_telefonos.php', { method: 'POST', body: datos });
        const json = await res.json();
        if (json.ok) {
            cerrarModal('modal-confirm');
            toast('<span class="icon icon-sm">delete</span> Registro eliminado');
            setTimeout(() => location.reload(), 1200);
        } else {
            toast('<span class="icon icon-sm">error</span> Error al eliminar', 'error');
        }
    } catch(e) {
        toast('<span class="icon icon-sm">error</span> Error de conexión', 'error');
    }
}

/* ══════════════════════════════════════════════════════
   Auto-filtrado sin botón
   — buscar: debounce 450 ms al escribir
   — sucursal: inmediato al cambiar
   ══════════════════════════════════════════════════════ */
(function () {
    const form    = document.querySelector('.filters-container form');
    const buscar  = document.getElementById('input-buscar');
    const sucursal = document.getElementById('select-sucursal');

    function enviar() {
        form.submit();
    }

    let debounceTimer;
    buscar.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(enviar, 450);
    });

    sucursal.addEventListener('change', enviar);
})();

/* ══════════════════════════════════════════════════════
   Descargar PDF — apunta a formato_garantia.php?id=X
   ══════════════════════════════════════════════════════ */
async function descargarPDF(id, folio) {
    const overlay = document.getElementById('overlay-carga');
    const txtEl   = document.getElementById('texto-carga');
    const barraEl = document.getElementById('barra-fill');
    const subEl   = document.getElementById('subtexto');

    /* Resetear overlay */
    barraEl.style.width      = '0%';
    barraEl.style.background = '#fff';
    txtEl.textContent        = 'Generando PDF...';
    subEl.textContent        = 'Por favor espera';
    overlay.classList.add('activo');

    /* Barra animada mientras espera (máx 82%) */
    const pasos = [
        { pct: 18, txt: 'Generando PDF...',      sub: 'Preparando el documento', delay: 350  },
        { pct: 40, txt: 'Generando PDF...',       sub: 'Armando secciones',       delay: 800  },
        { pct: 62, txt: 'Generando PDF...',       sub: 'Aplicando formato',       delay: 1400 },
        { pct: 80, txt: 'Preparando descarga...', sub: 'Casi listo…',             delay: 2100 },
    ];
    const timers = pasos.map(p =>
        setTimeout(() => {
            if ((parseFloat(barraEl.style.width) || 0) < 83) {
                barraEl.style.width = p.pct + '%';
                txtEl.textContent   = p.txt;
                subEl.textContent   = p.sub;
            }
        }, p.delay)
    );

    try {
        /* ✅ URL corregida: formato_garantia.php */
        const resp = await fetch('formato_garantia.php?id=' + id, { method: 'GET' });

        timers.forEach(t => clearTimeout(t));

        if (!resp.ok) {
            const errTxt = await resp.text().catch(() => '');
            console.error('Error servidor:', errTxt);
            throw new Error('HTTP ' + resp.status + ' — revisa la consola para más detalles.');
        }

        const ct = resp.headers.get('Content-Type') || '';
        if (!ct.includes('pdf') && !ct.includes('octet-stream')) {
            const errTxt = await resp.text().catch(() => ct);
            console.error('Content-Type inesperado:', ct, '\nRespuesta:', errTxt);
            throw new Error('La respuesta no es un PDF. Revisa la consola (F12).');
        }

        const blob = await resp.blob();

        barraEl.style.width = '100%';
        txtEl.textContent   = 'Descargando...';
        subEl.textContent   = 'Tu PDF está listo';

        await new Promise(r => setTimeout(r, 350));

        const url = URL.createObjectURL(blob);
        const a   = document.createElement('a');
        a.href     = url;
        a.download = 'Garantia-' + folio + '.pdf';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        txtEl.textContent = '¡Listo!';
        subEl.textContent = 'PDF descargado correctamente';
        setTimeout(() => overlay.classList.remove('activo'), 1000);

    } catch (err) {
        timers.forEach(t => clearTimeout(t));
        console.error('descargarPDF error:', err);
        txtEl.textContent        = 'Error al generar el PDF';
        subEl.textContent        = err.message;
        barraEl.style.width      = '100%';
        barraEl.style.background = '#e74c3c';
        setTimeout(() => overlay.classList.remove('activo'), 3500);
    }
}
</script>
</body>
</html>