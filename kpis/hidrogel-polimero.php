<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Análisis de Protectores — INNOVACION MOVIL</title>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 18px;
            background:#f7f7f7;
            color:#222;
        }
        h1 {
            margin-top:0;
        }
        .controls {
            display:flex;
            gap:12px;
            align-items:center;
            margin-bottom:12px;
            flex-wrap:wrap;
        }
        input[type=file] {
            padding:6px;
        }
        button.btn {
            background:#007bff;
            color:white;
            border:none;
            padding:8px 12px;
            border-radius:6px;
            cursor:pointer;
        }
        button.btn:disabled {
            background:#999;
            cursor:not-allowed;
        }
        .tables {
            display:flex;
            flex-direction:column;
            gap:20px;
            margin-top:14px;
        }
        table {
            border-collapse:collapse;
            width:100%;
            background:white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.07);
        }
        th, td {
            padding:8px 6px;
            border:1px solid #e1e1e1;
            text-align:center;
            font-size:13px;
        }
        th {
            background:#2f6fa6;
            color:white;
            position:sticky;
            top:0;
            z-index:1;
        }
        .rojo {
            background:#ffdad6;
        }
        .amarillo {
            background:#fff3cc;
        }
        .verde {
            background:#dff7df;
        }
        caption {
            text-align:left;
            font-weight:600;
            padding:8px;
        }
        .small {
            font-size:12px;
            color:#444;
        }
        .note {
            font-size:13px;
            color:#333;
            margin-top:6px;
        }
        .download-link {
            margin-left:8px;
        }
        .summary {
            margin-top:8px;
            padding:10px;
            background:#fff;
            border:1px solid #eee;
        }
        .nowrap {
            white-space:nowrap;
        }
        #debugBox {
            margin-top:8px;
            font-size:13px;
            color:#111;
            background:#fff;
            border:1px solid #eee;
            padding:8px;
        }
    </style>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <header>
        <nav>
            <ul id="menu">
                <li>
                    <a href="index.php" class="menu-link">
                        <span class="logo-container">
                            <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />
                        </span>
                        Home
                    </a>
                </li>
            </ul>
        </nav>
    </header>
    <div class="container">
        <h1>📊 Análisis de Protectores — INNOVACION MOVIL</h1>
        <div class="controls">
            <div class="file-upload">
                <input id="inputFile" type="file" accept=".xlsx,.xls" />
                <button class="boton" id="fileButton" type="button">
                    <div class="contenedorCarpeta">
                        <div class="folder folder_one"></div>
                        <div class="folder folder_two"></div>
                        <div class="folder folder_three"></div>
                        <div class="folder folder_four"></div>
                    </div>
                    <div class="active_line"></div>
                    <span class="text">Seleccionar Archivo</span>
                </button>
            </div>
            <script>
                // Conectamos el botón animado con el input oculto
                document.getElementById("fileButton").addEventListener("click", () => {
                    document.getElementById("inputFile").click();
                });
            </script>
            <button id="procesarBtn" class="btn" disabled>Procesar archivo</button>
            <button id="descargarBtn" class="btn" disabled>Descargar resultados</button>
        </div>

        <div class="center-container">
            <!-- Loader animado mientras se procesa el archivo -->
            <div id="loader" class="loader-container" style="display:none;">
                <div class="cloud front">
                    <span class="left-front"></span>
                    <span class="right-front"></span>
                </div>
                <span class="sun sunshine"></span>
                <span class="sun"></span>
                <div class="cloud back">
                    <span class="left-back"></span>
                    <span class="right-back"></span>
                </div>
            </div>
        </div>

        <div id="mensajes" class="note"></div>
        <div class="tables">
            <div id="tablaResumen"></div>
        </div>
    </div>

    <script>
        let registros = [];
        let resumenAlmacenes = [];
        const inputFile = document.getElementById('inputFile');
        const procesarBtn = document.getElementById('procesarBtn');
        const descargarBtn = document.getElementById('descargarBtn');
        const mensajes = document.getElementById('mensajes');
        const tablaResumenDiv = document.getElementById('tablaResumen');

        inputFile.addEventListener('change', () => {
            procesarBtn.disabled = !inputFile.files.length;
            mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : "";
        });

        procesarBtn.addEventListener('click', () => {
            if (!inputFile.files.length) return;
            leerExcel(inputFile.files[0]);
        });

        descargarBtn.addEventListener('click', descargaResultados);

        function leerExcel(file) {
            mensajes.innerText = 'Leyendo archivo...';
            document.getElementById('loader').style.display = 'flex';

            const reader = new FileReader();
            reader.onload = (e) => {
                const data = new Uint8Array(e.target.result);
                const wb = XLSX.read(data, { type: 'array' });
                const sheet = wb.Sheets[wb.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: "" });

                if (!rows || rows.length < 2) {
                    mensajes.innerText = 'Archivo vacío o sin datos.';
                    document.getElementById('loader').style.display = 'none';
                    return;
                }

                const headerRow = rows[0].map(h => String(h || "").trim());
                const idx = {
                    almacen: headerRow.findIndex(h => /almacen/i.test(h)),
                    n1: headerRow.findIndex(h => /n1/i.test(h)),
                    n3: headerRow.findIndex(h => /n3/i.test(h)),
                    tipoProducto: headerRow.findIndex(h => /tipoproducto/i.test(h)),
                    cantidad: headerRow.findIndex(h => /cantidad/i.test(h))
                };

                const dataObjs = [];
                for (let r = 1; r < rows.length; r++) {
                    const row = rows[r];
                    if (row[idx.n1] === "INNOVACION MOVIL" && row[idx.n3] === "PROTECTOR") {
                        dataObjs.push({
                            almacen: row[idx.almacen] || "(SIN ALMACÉN)",
                            tipoProducto: row[idx.tipoProducto] || "(SIN CATEGORÍA)",
                            cantidad: Number(row[idx.cantidad] || 0)
                        });
                    }
                }

                registros = dataObjs;
                mensajes.innerText = `Filtradas ${registros.length} filas. Procesando...`;
                procesarDatos();
                document.getElementById('loader').style.display = 'none';
            };
            reader.readAsArrayBuffer(file);
        }

        function procesarDatos() {
            const almacenesMap = {};
            registros.forEach(r => {
                if (!almacenesMap[r.almacen]) {
                    almacenesMap[r.almacen] = {
                        "Hidrogel Cantidad": 0,
                        "Polimero Cantidad": 0
                    };
                }

                if (r.tipoProducto.toUpperCase().includes("HIDROGEL")) {
                    almacenesMap[r.almacen]["Hidrogel Cantidad"] += r.cantidad;
                } else if (r.tipoProducto.toUpperCase().includes("POLIMERO")) {
                    almacenesMap[r.almacen]["Polimero Cantidad"] += r.cantidad;
                }
            });

            resumenAlmacenes = [];
            Object.entries(almacenesMap).forEach(([almacen, cantidades]) => {
                resumenAlmacenes.push({
                    Almacen: almacen,
                    "Hidrogel Cantidad": cantidades["Hidrogel Cantidad"],
                    "Polimero Cantidad": cantidades["Polimero Cantidad"]
                });
            });

            mostrarTabla(resumenAlmacenes, tablaResumenDiv, "Resumen de Stock por Almacén");
            mensajes.innerText = "Procesamiento completado.";
            descargarBtn.disabled = false;
        }

        function mostrarTabla(arr, contenedor, titulo) {
            if (!arr.length) {
                contenedor.innerHTML = "<div class='note'>No hay datos.</div>";
                return;
            }

            let html = `<table><caption>${titulo}</caption><thead><tr>`;
            Object.keys(arr[0]).forEach(k => html += `<th>${k}</th>`);
            html += "</tr></thead><tbody>";

            arr.forEach(row => {
                html += "<tr>";
                Object.keys(row).forEach(k => {
                    html += `<td>${row[k] || 0}</td>`;
                });
                html += "</tr>";
            });

            html += "</tbody></table>";
            contenedor.innerHTML = html;
        }

        function descargaResultados() {
            if (!resumenAlmacenes.length) {
                alert("No hay resultados para descargar.");
                return;
            }

            const wb = XLSX.utils.book_new();
            const ws1 = XLSX.utils.json_to_sheet(resumenAlmacenes);

            // Ajustar ancho de columnas
            const wscols = Object.keys(resumenAlmacenes[0]).map(k => ({ wch: Math.max(k.length + 2, 12) }));
            ws1['!cols'] = wscols;

            XLSX.utils.book_append_sheet(wb, ws1, "Resumen_Stock");
            XLSX.writeFile(wb, "Resultados_Stock.xlsx");
        }
    </script>
</body>
</html>