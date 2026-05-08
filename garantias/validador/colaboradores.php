<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../funciones.php';

$mensaje_flash = null;
$accion = $_POST['accion'] ?? '';

switch ($accion) {

    case 'crear':
        $res = crearColaborador($_POST['nombre'] ?? '', $_POST['fecha_ingreso'] ?? '');
        $mensaje_flash = ['tipo' => $res['ok'] ? 'success' : 'error', 'texto' => $res['mensaje']];
        break;

    case 'actualizar':
        $res = actualizarColab(
            (int) ($_POST['id'] ?? 0),
            $_POST['nombre'] ?? '',
            $_POST['fecha_ingreso'] ?? '',
            $_POST['fecha_capacitacion'] ?: null,
            (int) ($_POST['payjoy_int'] ?? 0)
        );
        $mensaje_flash = ['tipo' => $res['ok'] ? 'success' : 'error', 'texto' => $res['mensaje']];
        break;

    case 'eliminar':
        $res = eliminarColaborador((int) ($_POST['id'] ?? 0));
        $mensaje_flash = ['tipo' => $res['ok'] ? 'success' : 'error', 'texto' => $res['mensaje']];
        break;

    case 'importar_excel':
        if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['archivo_excel']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'xlsx') {
                $mensaje_flash = ['tipo' => 'error', 'texto' => 'Solo se aceptan archivos .xlsx'];
            } else {
                $res     = importarColaboradoresDesdeExcel($_FILES['archivo_excel']['tmp_name']);
                $resumen = "Insertados: {$res['insertados']} | Actualizados: {$res['actualizados']} | Sin cambios: {$res['sin_cambios']}";
                if (!empty($res['errores'])) $resumen .= ' | Errores: ' . implode('; ', $res['errores']);
                $mensaje_flash = ['tipo' => empty($res['errores']) ? 'success' : 'warning', 'texto' => $resumen];
            }
        } else {
            $mensaje_flash = ['tipo' => 'error', 'texto' => 'No se recibió ningún archivo o hubo un error al subir.'];
        }
        break;

    case 'fusionar':
        $res = fusionarColaboradores((int) ($_POST['id_origen'] ?? 0), (int) ($_POST['id_destino'] ?? 0));
        $mensaje_flash = ['tipo' => $res['ok'] ? 'success' : 'error', 'texto' => $res['mensaje']];
        break;
}

$colaboradores = obtenerColab();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Gestión de Colaboradores</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:            #f5f7fa;
            --surface:       #ffffff;
            --muted:         #6b7280;
            --text:          #0f1724;
            --primary-600:   #0f5476;
            --primary-400:   #16729a;
            --accent:        #1f9a8a;
            --glass:         rgba(15, 23, 36, 0.04);
            --border:        rgba(15, 23, 36, 0.10);
            --radius-lg:     14px;
            --radius-md:     10px;
            --radius-sm:     7px;
            --shadow-sm:     0 6px 18px rgba(12, 18, 26, 0.06);
            --shadow-md:     0 10px 30px rgba(12, 18, 26, 0.09);
            --transition:    220ms cubic-bezier(.2, .9, .2, 1);
            --success:       #059669;
            --success-bg:    #d1fae5;
            --warning:       #d97706;
            --warning-bg:    #fef3c7;
            --danger:        #dc2626;
            --danger-bg:     #fee2e2;
            --neutral:       #6b7280;
            --neutral-bg:    #f3f4f6;
            --blocked:       #7c3aed;
            --blocked-bg:    #ede9fe;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            min-height: 100vh;
            padding-bottom: 60px;
        }

        /* ── HEADER ─────────────────────────────────── */
        .page-header {
            background: var(--primary-600);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-md);
        }

        .page-header h1 {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.3px;
        }

        .page-header h1 span { color: #7ee8df; }

        .header-meta {
            margin-left: auto;
            font-size: 12px;
            color: rgba(255,255,255,.65);
            white-space: nowrap;
        }

        /* ── CONTAINER ───────────────────────────────── */
        .container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 24px 20px;
        }

        /* ── GRID ────────────────────────────────────── */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* ── PANEL ───────────────────────────────────── */
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .panel-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            background: var(--glass);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-title {
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: var(--primary-600);
        }

        .panel-body { padding: 20px; }
        .section-gap { margin-top: 16px; }

        /* ── FLASH ───────────────────────────────────── */
        .flash {
            margin-bottom: 16px;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            font-size: 13px;
            border-left: 4px solid;
            line-height: 1.5;
        }

        .flash.success { background: var(--success-bg); border-color: var(--success); color: #065f46; }
        .flash.error   { background: var(--danger-bg);  border-color: var(--danger);  color: #991b1b; }
        .flash.warning { background: var(--warning-bg); border-color: var(--warning); color: #92400e; }

        /* ── FORMS ───────────────────────────────────── */
        .form-group { margin-bottom: 14px; }

        label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--muted);
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="date"],
        input[type="file"],
        select {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            padding: 9px 12px;
            transition: border-color var(--transition), box-shadow var(--transition);
            outline: none;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        select:focus {
            border-color: var(--primary-400);
            box-shadow: 0 0 0 3px rgba(15, 84, 118, 0.12);
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .form-hint {
            font-size: 11px;
            color: var(--muted);
            margin-bottom: 14px;
            line-height: 1.8;
        }

        /* ── BUTTONS ─────────────────────────────────── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all var(--transition);
            white-space: nowrap;
        }

        .btn:active { transform: scale(.97); }

        .btn-primary { background: var(--primary-600); color: #fff; }
        .btn-primary:hover { background: var(--primary-400); }

        .btn-accent { background: var(--accent); color: #fff; }
        .btn-accent:hover { background: #178a7b; }

        .btn-ghost {
            background: transparent;
            color: var(--primary-600);
            border: 1.5px solid var(--border);
        }
        .btn-ghost:hover { border-color: var(--primary-400); background: var(--glass); }

        .btn-danger {
            background: transparent;
            color: var(--danger);
            border: 1.5px solid rgba(220,38,38,.25);
        }
        .btn-danger:hover { background: var(--danger-bg); }

        .btn-sm   { padding: 5px 12px; font-size: 11px; }
        .btn-full { width: 100%; }

        /* ── TABLE ───────────────────────────────────── */
        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }

        thead th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--muted);
            padding: 11px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            background: var(--glass);
            white-space: nowrap;
        }

        tbody tr { border-bottom: 1px solid var(--border); transition: background var(--transition); }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--glass); }
        tbody td { padding: 11px 16px; vertical-align: middle; }

        .name-bold   { font-weight: 700; color: var(--primary-600); }
        .name-normal { color: var(--text); }
        .text-muted  { color: var(--muted); font-size: 12px; }

        /* ── BADGES ──────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-activo     { background: var(--success-bg); color: var(--success); }
        .badge-pendiente  { background: var(--warning-bg); color: var(--warning); }
        .badge-sin-cuenta { background: var(--danger-bg);  color: var(--danger);  }
        .badge-sin-fecha  { background: var(--neutral-bg); color: var(--neutral); }
        .badge-bloqueada  { background: var(--blocked-bg); color: var(--blocked); }
        .badge-inactivo   { background: var(--neutral-bg); color: var(--neutral); }

        /* ── ACTIONS ─────────────────────────────────── */
        .actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }

        /* ── MODAL ───────────────────────────────────── */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 36, 0.45);
            backdrop-filter: blur(3px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
            padding: 16px;
        }

        .modal-backdrop.open { display: flex; }

        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            width: min(500px, 100%);
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-md);
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--glass);
        }

        .modal-title { font-weight: 700; font-size: 15px; color: var(--primary-600); }

        .modal-close {
            background: none; border: none; color: var(--muted);
            font-size: 20px; cursor: pointer; line-height: 1; padding: 0 4px;
            transition: color var(--transition);
        }
        .modal-close:hover { color: var(--text); }

        .modal-body { padding: 20px; }

        .modal-footer { display: flex; gap: 10px; margin-top: 6px; }

        /* ── EMPTY ───────────────────────────────────── */
        .empty-state { text-align: center; padding: 40px 20px; color: var(--muted); font-size: 13px; }

        /* ── SCROLLBAR ───────────────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        /* ── NAV DEL HEADER ──────────────────────────── */
.header-nav ul {
    list-style: none;
    display: flex;
    align-items: center;
    gap: 4px;
}

.header-nav a {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,.85);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    padding: 6px 12px 6px 6px;
    border-radius: 999px;
    transition: background var(--transition), color var(--transition);
    white-space: nowrap;
}

.header-nav a:hover {
    background: rgba(255,255,255,.12);
    color: #fff;
}

.nav-logo-wrap {
    display: inline-flex;
    width: 34px;
    height: 34px;
    background: #fff;
    border-radius: 50%;
    justify-content: center;
    align-items: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,.15);
}

.nav-logo-wrap img {
    width: 24px;
    height: 24px;
    object-fit: contain;
}


        /* ══════════════════════════════════════════════
           RESPONSIVE — MÓVIL (≤ 700px)
        ══════════════════════════════════════════════ */
        @media (max-width: 700px) {

            .page-header { padding: 14px 16px; }
            .page-header h1 { font-size: 15px; }
            .header-meta { display: none; }

            .container { padding: 14px 12px; }

            .grid-2 { grid-template-columns: 1fr; }

            .form-row { grid-template-columns: 1fr; }

            /* Tabla → tarjetas apiladas */
            table thead { display: none; }
            table, tbody, tr, td { display: block; width: 100%; }

            tbody tr {
                border: 1px solid var(--border);
                border-radius: var(--radius-md);
                margin-bottom: 10px;
                padding: 12px 14px;
                background: var(--surface);
                box-shadow: var(--shadow-sm);
            }

            tbody td {
                padding: 5px 0;
                border: none;
                display: flex;
                align-items: flex-start;
                gap: 10px;
                font-size: 13px;
            }

            tbody td::before {
                content: attr(data-label);
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: var(--muted);
                min-width: 88px;
                flex-shrink: 0;
                padding-top: 2px;
            }

            .actions { flex-wrap: wrap; }

            .modal { width: 96vw; }

            .modal-footer { flex-direction: column; }
            .modal-footer .btn { width: 100%; }

            .header-nav a { padding: 4px; gap: 0; }
    .header-nav a span + * { display: none; } /* oculta "Home" */
    .nav-logo-wrap { width: 30px; height: 30px; }
    .nav-logo-wrap img { width: 20px; height: 20px; }
        }
    </style>
    
</head>
<body>

<header class="page-header">
    

    <nav class="header-nav">
        <ul>
            <li>
                <a href="validador.php">
                    <span class="nav-logo-wrap">
                        <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>"
                             alt="Logo">
                    </span>
                    Home
                </a>
            </li>
        </ul>
        
    </nav><h1>Gestión de <span>Colaboradores</span></h1>

    <span class="header-meta"><?= date('d/m/Y') ?> &nbsp;·&nbsp; <?= count($colaboradores) ?> registros</span>
</header>

<div class="container">

    <?php if ($mensaje_flash): ?>
        <div class="flash <?= htmlspecialchars($mensaje_flash['tipo']) ?>">
            <?= htmlspecialchars($mensaje_flash['texto']) ?>
        </div>
    <?php endif; ?>

    <!-- Nuevo + Importar -->
    <div class="grid-2">

        <div class="panel">
            <div class="panel-header"><span class="panel-title">+ Nuevo colaborador</span></div>
            <div class="panel-body">
                <form method="POST" action="">
                    <input type="hidden" name="accion" value="crear">
                    <div class="form-group">
                        <label for="c_nombre">Nombre completo</label>
                        <input type="text" id="c_nombre" name="nombre" placeholder="Nombre del colaborador" required>
                    </div>
                    <div class="form-group">
                        <label for="c_fecha_ingreso">Fecha de ingreso</label>
                        <input type="date" id="c_fecha_ingreso" name="fecha_ingreso" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Registrar colaborador</button>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header"><span class="panel-title">↑ Importar desde Excel</span></div>
            <div class="panel-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="importar_excel">
                    <div class="form-group">
                        <label>Archivo .xlsx</label>
                        <input type="file" name="archivo_excel" accept=".xlsx" required>
                    </div>
                    <p class="form-hint">
                        Encabezados en fila 4 · Datos desde fila 5<br>
                        B = NOMBRE &nbsp;·&nbsp; E = PUESTO &nbsp;·&nbsp; H = F. INGRESO<br>
                        Puestos: <em>Apasionado de la telefonía</em>, <em>Encargado de Sucursal</em>
                    </p>
                    <button type="submit" class="btn btn-accent btn-full">Actualizar desde Excel</button>
                </form>
            </div>
        </div>

    </div>

   <!-- Fusión -->
    <div class="panel section-gap">
        <div class="panel-header"><span class="panel-title">⇒ Fusionar colaboradores</span></div>
        <div class="panel-body">
            <form method="POST" action=""
                  onsubmit="return confirm('¿Reasignar todas las garantías del ORIGEN al DESTINO?')">
                <input type="hidden" name="accion" value="fusionar">
                <?php
                    $colaboradores_fusion = $colaboradores;
                    usort($colaboradores_fusion, fn($a, $b) => $a['id'] <=> $b['id']);
                ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="f_origen">Origen (será vaciado)</label>
                        <select id="f_origen" name="id_origen" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($colaboradores_fusion as $c): ?>
                                <option value="<?= $c['id'] ?>">[<?= $c['id'] ?>] <?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="f_destino">Destino (recibirá garantías)</label>
                        <select id="f_destino" name="id_destino" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($colaboradores_fusion as $c): ?>
                                <option value="<?= $c['id'] ?>">[<?= $c['id'] ?>] <?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-ghost">Ejecutar fusión</button>
            </form>
        </div>
    </div>
    <!-- Tabla -->
    <div class="panel section-gap">
        <div class="panel-header"><span class="panel-title">Lista de colaboradores</span></div>
        <div class="table-wrap">
            <?php if (empty($colaboradores)): ?>
                <div class="empty-state">No hay colaboradores registrados.</div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Fecha ingreso</th>
                        <th>Capacitación</th>
                        <th>PayJoy</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($colaboradores as $c):
                    $estado       = calcularEstadoPayjoy((int) $c['payjoy_int'], $c['fecha_ingreso']);
                    $nombre_class = $c['tiene_garantias'] > 0 ? 'name-bold' : 'name-normal';
                ?>
                    <tr>
                        <td data-label="#" class="text-muted"><?= $c['id'] ?></td>
                        <td data-label="Nombre" class="<?= $nombre_class ?>"><?= htmlspecialchars($c['nombre']) ?></td>
                        <td data-label="Ingreso">
                            <?= $c['fecha_ingreso'] ? date('d/m/Y', strtotime($c['fecha_ingreso'])) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td data-label="Capacitación">
                            <?= $c['fecha_capacitacion'] ? date('d/m/Y', strtotime($c['fecha_capacitacion'])) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td data-label="PayJoy">
                            <span class="badge <?= $estado['clase_css'] ?>"><?= htmlspecialchars($estado['etiqueta']) ?></span>
                        </td>
                        <td data-label="Acciones">
                            <div class="actions">
                                <button class="btn btn-ghost btn-sm"
                                        onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($c)) ?>)">
                                    Editar
                                </button>
                                <form method="POST" action=""
                                      onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($c['nombre'])) ?>?')">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- MODAL EDITAR -->
<div id="modal-editar" class="modal-backdrop" onclick="cerrarModalEditar(event)">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Editar colaborador</span>
            <button class="modal-close" onclick="cerrarModalEditar()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="accion" value="actualizar">
                <input type="hidden" name="id" id="e_id">

                <div class="form-group">
                    <label for="e_nombre">Nombre</label>
                    <input type="text" id="e_nombre" name="nombre" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="e_fecha_ingreso">Fecha de ingreso</label>
                        <input type="date" id="e_fecha_ingreso" name="fecha_ingreso">
                    </div>
                    <div class="form-group">
                        <label for="e_fecha_capacitacion">Fecha capacitación</label>
                        <input type="date" id="e_fecha_capacitacion" name="fecha_capacitacion">
                    </div>
                </div>

                <div class="form-group">
                    <label for="e_payjoy">Estado PayJoy</label>
                    <select id="e_payjoy" name="payjoy_int">
                        <option value="0">0 — No tiene cuenta</option>
                        <option value="1">1 — Cuenta activa</option>
                        <option value="2">2 — Bloqueada / Inactiva</option>
                        <option value="3">3 — Ya no labora</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary" style="flex:1">Guardar cambios</button>
                    <button type="button" class="btn btn-ghost" onclick="cerrarModalEditar()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalEditar(col) {
    document.getElementById('e_id').value                 = col.id;
    document.getElementById('e_nombre').value             = col.nombre;
    document.getElementById('e_fecha_ingreso').value      = col.fecha_ingreso      ?? '';
    document.getElementById('e_fecha_capacitacion').value = col.fecha_capacitacion ?? '';
    document.getElementById('e_payjoy').value             = col.payjoy_int;
    document.getElementById('modal-editar').classList.add('open');
}

function cerrarModalEditar(event) {
    if (!event || event.target === document.getElementById('modal-editar')) {
        document.getElementById('modal-editar').classList.remove('open');
    }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModalEditar(); });

document.querySelector('[name="id_destino"]')?.closest('form')
    ?.addEventListener('submit', function(e) {
        if (document.getElementById('f_origen').value === document.getElementById('f_destino').value) {
            e.preventDefault();
            alert('El colaborador origen y destino no pueden ser el mismo.');
        }
    });
</script>

</body>
</html>