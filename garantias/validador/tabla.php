<?php
session_start();

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

$validador_id = $_SESSION['validador_id'];

include_once '../../funciones.php';
$garantias = verTablanoguardados();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabla de Garantías y Mermas</title>
    <link rel="stylesheet" href="../../csstabla.css">
    <style>
        .badge-dpto {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: .78em;
            font-weight: 700;
            white-space: nowrap;
        }
        .badge-im { background: #fde8ec; color: #c0392b; }
        .badge-tm { background: #e8f4fd; color: #1a6fa8; }

        .btn-fotos {
            border: none; border-radius: 6px;
            padding: 4px 8px; cursor: pointer; font-size: .85em;
        }
        .btn-fotos.con-foto     { background: #e67e22; color: #fff; }
        .btn-fotos.con-foto:hover { background: #ca6f1e; }
        .btn-fotos.sin-foto     { background: #ccc; color: #888; cursor: default; }

        .modal-fotos {
            display: none; position: fixed; z-index: 2000;
            left: 0; top: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.88);
            justify-content: center; align-items: center; flex-direction: column;
        }
        .modal-fotos.activo { display: flex; }
        .modal-fotos-contenido {
            position: relative; display: flex;
            align-items: center; gap: 12px; max-width: 90vw;
        }
        .modal-fotos img {
            max-width: 80vw; max-height: 80vh;
            border-radius: 8px; object-fit: contain; background: #111;
        }
        .modal-fotos .flecha {
            background: rgba(255,255,255,0.2); color: #fff;
            border: none; border-radius: 50%; width: 44px; height: 44px;
            font-size: 1.5em; cursor: pointer; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }
        .modal-fotos .flecha:hover    { background: rgba(255,255,255,0.4); }
        .modal-fotos .flecha:disabled { opacity: .2; cursor: default; }
        .modal-fotos .cerrar-fotos {
            position: absolute; top: -22px; right: -8px;
            background: none; border: none; color: #fff;
            font-size: 1.8em; cursor: pointer; line-height: 1;
        }
        .contador-foto { color: #fff; font-size: .9em; margin-top: 10px; }
    </style>
</head>
<body>

<nav style="background:#0F5476; padding:10px;">
    <h1 id="nombre">­ </h1>
    <input type="checkbox" id="check">
    <label class="bar" for="check">
        <span class="top"></span>
        <span class="middle"></span>
        <span class="bottom"></span>
    </label>
    <ul id="menu">
        <li>
            <a href="validador.php" style="display:flex;align-items:center;gap:12px;">
                <span style="display:inline-flex;width:40px;height:40px;background:#fff;border-radius:50%;justify-content:center;align-items:center;">
                    <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>"
                         style="width:30px;height:30px;object-fit:contain;" alt="Logo">
                </span>
                Home
            </a>
        </li>
    </ul>
</nav>
<br>

<div class="container">
    <h2>Historial de Garantías y Mermas No Registradas</h2>

    <div class="filters-container">
        <h3>Filtros por Columna</h3>
        <div class="filter-row">
            <div class="filter-group">
                <label>PLOWS:</label>
                <input type="text" id="filter-plows" placeholder="Escanea o escribe el código">
            </div>
            <div class="filter-group">
                <label>Departamento:</label>
                <select id="filter-dpto">
                    <option value="">Todos</option>
                    <option value="Accesorios">Accesorios</option>
                    <option value="Telefonia">Telefonia</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Tipo:</label>
                <select id="filter-tipo"><option value="">Todos</option></select>
            </div>
            <div class="filter-group">
                <label>Causa:</label>
                <select id="filter-causa"><option value="">Todas</option></select>
            </div>
            <div class="filter-group">
                <label>Sucursal:</label>
                <select id="filter-sucursal"><option value="">Todas</option></select>
            </div>
            <div class="filter-group">
                <label>Colaborador:</label>
                <select id="filter-colaborador"><option value="">Todos</option></select>
            </div>
        </div>
        <div class="filter-row">
            <div class="filter-group">
                <label>Estatus:</label>
                <select id="filter-estatus"><option value="">Todos</option></select>
            </div>
            <div class="filter-group">
                <label>Validador:</label>
                <select id="filter-validador"><option value="">Todos</option></select>
            </div>
            <div class="filter-group">
                <label>Fecha Desde:</label>
                <input type="date" id="filter-fecha-desde">
            </div>
            <div class="filter-group">
                <label>Fecha Hasta:</label>
                <input type="date" id="filter-fecha-hasta">
            </div>
        </div>
        <div class="filter-buttons">
            <button class="btn-clear" onclick="clearAllFilters()">🔄 Limpiar Filtros</button>
        </div>
    </div>

    <div class="results-info">
        <span id="results-count">Cargando datos...</span>
    </div>

    <div class="table-container">
        <table border="1" cellpadding="5" cellspacing="0" id="garantias-table">
            <thead>
                <tr style="background:#ddd;">
                    <th>PLOWS</th>
                    <th>Depto</th>
                    <th>Tipo</th>
                    <th>Causa</th>
                    <th>Piezas</th>
                    <th>Sucursal</th>
                    <th>Colaborador</th>
                    <th>Fecha de Registro</th>
                    <th>Eliminar</th>
                    <th>A garant. reg.</th>
                    <th>Fotos</th>
                    <th>Anotación del Vendedor</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <?php foreach ($garantias as $g): ?>
                <?php
                    $dpto       = strtolower($g['dpto'] ?? 'im');
                    $dptoNombre = $g['dpto_nombre'] ?? ($dpto === 'tm' ? 'Telefonia' : 'Accesorios');
                    $badgeClass = $dpto === 'tm' ? 'badge-tm' : 'badge-im';

                    $fotosUrls = [];
                    if (!empty($g['foto'])) {
                        foreach (explode(',', $g['foto']) as $pf) {
                            $pf = trim($pf);
                            $fotosUrls[] = strpos($pf,'|') !== false ? explode('|',$pf,2)[1] : $pf;
                        }
                        $fotosUrls = array_values(array_filter($fotosUrls));
                    }
                    $fotosJson    = htmlspecialchars(json_encode($fotosUrls), ENT_QUOTES);
                    $tieneFoto    = count($fotosUrls) > 0;
                    $fotoBtnClass = $tieneFoto ? 'btn-fotos con-foto' : 'btn-fotos sin-foto';
                    $fotoOnClick  = $tieneFoto ? 'abrirFotos(this)' : "alert('Este registro no tiene fotos.')";
                ?>
                <tr data-id="<?= $g['id'] ?>"
                    data-plows="<?= htmlspecialchars($g['plows']) ?>"
                    data-dpto="<?= htmlspecialchars($dptoNombre) ?>"
                    data-tipo="<?= htmlspecialchars($g['tipo']) ?>"
                    data-causa="<?= htmlspecialchars($g['causa']) ?>"
                    data-piezas="<?= htmlspecialchars($g['piezas']) ?>"
                    data-sucursal="<?= htmlspecialchars($g['sucursal']) ?>"
                    data-colaborador="<?= htmlspecialchars($g['apasionado']) ?>"
                    data-fecha="<?= htmlspecialchars($g['fecha']) ?>"
                    data-validador="<?= $g['validador_nombre'] ? htmlspecialchars($g['validador_nombre'].' '.$g['validador_apellido']) : 'No validado' ?>">

                    <td><?= htmlspecialchars($g['plows']) ?></td>
                    <td><span class="badge-dpto <?= $badgeClass ?>"><?= htmlspecialchars($dptoNombre) ?></span></td>
                    <td><?= htmlspecialchars($g['tipo']) ?></td>
                    <td><?= htmlspecialchars($g['causa']) ?></td>
                    <td><?= htmlspecialchars($g['piezas']) ?></td>
                    <td><?= htmlspecialchars($g['sucursal']) ?></td>
                    <td><?= htmlspecialchars($g['apasionado']) ?></td>
                    <td><?= htmlspecialchars($g['fecha']) ?></td>

                    <td class="action-links">
                        <?php if (!$g['validador_nombre']): ?>
                            <a href="eliminar.php?id=<?= $g['id'] ?>"
                               onclick="return confirm('¿Seguro que quieres eliminar esta garantía?')">🗑️</a>
                        <?php else: ?>
                            <span class="validated">(Validado)</span>
                        <?php endif; ?>
                    </td>

                    <td class="action-links">
                        <?php if (!$g['validador_nombre']): ?>
                            <a href="Mover.php?id=<?= $g['id'] ?>"
                               onclick="return confirm('¿Seguro que quieres mover esta garantía?')">🔄</a>
                        <?php else: ?>
                            <span class="validated">(Validado)</span>
                        <?php endif; ?>
                    </td>

                    <td style="text-align:center;">
                        <button type="button" class="<?= $fotoBtnClass ?>"
                            onclick="<?= $fotoOnClick ?>"
                            data-fotos="<?= $fotosJson ?>">🖼️ Fotos</button>
                    </td>

                    <td><?= htmlspecialchars($g['anotaciones_vendedor']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Fotos -->
<div id="modalFotos" class="modal-fotos" onclick="cerrarFotosFondo(event)">
    <div class="modal-fotos-contenido">
        <button class="cerrar-fotos" onclick="cerrarFotos()">✕</button>
        <button class="flecha" id="flechaIzq" onclick="cambiarFoto(-1)">&#8249;</button>
        <img id="fotoActual" src="" alt="Foto de merma">
        <button class="flecha" id="flechaDer" onclick="cambiarFoto(1)">&#8250;</button>
    </div>
    <div class="contador-foto" id="contadorFoto"></div>
</div>

<script>
    let allRows = [], filteredRows = [];

    function initializeData() {
        allRows = Array.from(document.querySelectorAll('#table-body tr'));
        filteredRows = [...allRows];

        const uniq = key => [...new Set(allRows.map(r => r.dataset[key]))].filter(v => v).sort();

        // Fijos: se pueblan una sola vez sin depender del depto
        repopulateSelect('filter-sucursal',    uniq('sucursal'));
        repopulateSelect('filter-colaborador', uniq('colaborador'));
        repopulateSelect('filter-estatus',     uniq('estatus'));
        repopulateSelect('filter-validador',   uniq('validador'));

        // Tipo y Causa: dependen del depto seleccionado
        refreshDependentFilters();

        document.getElementById('filter-plows').addEventListener('input', applyFilters);
        ['filter-dpto','filter-tipo','filter-causa','filter-sucursal','filter-colaborador',
         'filter-estatus','filter-validador','filter-fecha-desde','filter-fecha-hasta']
            .forEach(id => document.getElementById(id).addEventListener('change', applyFilters));

        document.getElementById('filter-dpto').addEventListener('change', refreshDependentFilters);

        updateResultsCount();
    }

    // Solo Tipo y Causa se recalculan según el depto
    function refreshDependentFilters() {
        const dptoSel  = document.getElementById('filter-dpto').value;
        const baseRows = dptoSel ? allRows.filter(r => r.dataset.dpto === dptoSel) : allRows;
        const uniqFrom = key => [...new Set(baseRows.map(r => r.dataset[key]))].filter(v => v).sort();

        const tipoActual  = document.getElementById('filter-tipo').value;
        const causaActual = document.getElementById('filter-causa').value;

        repopulateSelect('filter-tipo',  uniqFrom('tipo'));
        repopulateSelect('filter-causa', uniqFrom('causa'));

        const tipoOpts  = [...document.getElementById('filter-tipo').options].map(o => o.value);
        const causaOpts = [...document.getElementById('filter-causa').options].map(o => o.value);
        document.getElementById('filter-tipo').value  = tipoOpts.includes(tipoActual)   ? tipoActual  : '';
        document.getElementById('filter-causa').value = causaOpts.includes(causaActual) ? causaActual : '';
    }

    // Limpia y repuebla manteniendo el primer option vacío
    function repopulateSelect(id, values) {
        const sel   = document.getElementById(id);
        const first = sel.options[0];
        sel.innerHTML = '';
        sel.appendChild(first);
        values.forEach(v => {
            const o = document.createElement('option');
            o.value = o.textContent = v;
            sel.appendChild(o);
        });
    }

    function applyFilters() {
        refreshDependentFilters();
        const f = {
            plows:       document.getElementById('filter-plows').value.toLowerCase(),
            dpto:        document.getElementById('filter-dpto').value,
            tipo:        document.getElementById('filter-tipo').value,
            causa:       document.getElementById('filter-causa').value,
            sucursal:    document.getElementById('filter-sucursal').value,
            colaborador: document.getElementById('filter-colaborador').value,
            estatus:     document.getElementById('filter-estatus').value,
            validador:   document.getElementById('filter-validador').value,
            desde:       document.getElementById('filter-fecha-desde').value,
            hasta:       document.getElementById('filter-fecha-hasta').value,
        };
        filteredRows = allRows.filter(row => {
            if (f.plows       && !row.dataset.plows.toLowerCase().includes(f.plows)) return false;
            if (f.dpto        && row.dataset.dpto        !== f.dpto)        return false;
            if (f.tipo        && row.dataset.tipo        !== f.tipo)        return false;
            if (f.causa       && row.dataset.causa       !== f.causa)       return false;
            if (f.sucursal    && row.dataset.sucursal    !== f.sucursal)    return false;
            if (f.colaborador && row.dataset.colaborador !== f.colaborador) return false;
            if (f.estatus     && row.dataset.estatus     !== f.estatus)     return false;
            if (f.validador   && row.dataset.validador   !== f.validador)   return false;
            if (f.desde       && row.dataset.fecha       <  f.desde)        return false;
            if (f.hasta       && row.dataset.fecha       >  f.hasta)        return false;
            return true;
        });
        renderFilteredTable();
    }

    function renderFilteredTable() {
        const tbody = document.getElementById('table-body');
        allRows.forEach(r => r.style.display = 'none');
        if (filteredRows.length === 0) {
            if (!document.getElementById('no-results-row')) {
                const tr = document.createElement('tr');
                tr.id = 'no-results-row';
                tr.innerHTML = '<td colspan="12" class="no-results">No se encontraron resultados</td>';
                tbody.appendChild(tr);
            }
            document.getElementById('no-results-row').style.display = '';
        } else {
            const nr = document.getElementById('no-results-row');
            if (nr) nr.style.display = 'none';
            filteredRows.forEach(r => r.style.display = '');
        }
        updateResultsCount();
    }

    function updateResultsCount() {
        const el = document.getElementById('results-count');
        el.textContent = `Mostrando ${filteredRows.length} de ${allRows.length} registros`;

        let info = document.getElementById('info-text');
        if (!info) {
            info = document.createElement('p');
            info.id = 'info-text';
            info.style.cssText = 'font-size:.9em;color:#555;';
            el.parentNode.insertBefore(info, el.nextSibling);
        }
        info.textContent = "Estos datos corresponden a mermas que los vendedores no registraron. Se manejan por separado de las mermas que sí fueron registradas y no son visibles para los vendedores. Se mantienen aquí únicamente para llevar un control de este tipo de mermas no registradas.";
    }

    function clearAllFilters() {
        ['filter-plows','filter-dpto','filter-tipo','filter-causa','filter-sucursal',
         'filter-colaborador','filter-estatus','filter-validador',
         'filter-fecha-desde','filter-fecha-hasta']
            .forEach(id => document.getElementById(id).value = '');
        refreshDependentFilters();
        filteredRows = [...allRows];
        renderFilteredTable();
    }

    document.addEventListener('DOMContentLoaded', initializeData);

    /* Modal Fotos */
    let fotosActuales = [], fotoIdx = 0;

    function abrirFotos(btn) {
        try { fotosActuales = JSON.parse(btn.dataset.fotos || '[]'); } catch(e) { fotosActuales = []; }
        if (!fotosActuales.length) { alert('Este registro no tiene fotos.'); return; }
        fotoIdx = 0;
        mostrarFoto();
        document.getElementById('modalFotos').classList.add('activo');
    }
    function mostrarFoto() {
        document.getElementById('fotoActual').src = fotosActuales[fotoIdx];
        document.getElementById('contadorFoto').textContent = (fotoIdx+1) + ' / ' + fotosActuales.length;
        document.getElementById('flechaIzq').disabled = fotoIdx === 0;
        document.getElementById('flechaDer').disabled = fotoIdx === fotosActuales.length - 1;
    }
    function cambiarFoto(d) {
        fotoIdx = Math.max(0, Math.min(fotosActuales.length - 1, fotoIdx + d));
        mostrarFoto();
    }
    function cerrarFotos() {
        document.getElementById('modalFotos').classList.remove('activo');
        document.getElementById('fotoActual').src = '';
    }
    function cerrarFotosFondo(e) {
        if (e.target === document.getElementById('modalFotos')) cerrarFotos();
    }
</script>

<footer style="text-align:center;padding:10px;font-size:.85rem;color:#777;margin-top:50px;">
    <p>&copy; <span id="year"></span> Diego – Innovación Móvil</p>
</footer>
<script>document.getElementById('year').textContent = new Date().getFullYear();</script>
</body>
</html>