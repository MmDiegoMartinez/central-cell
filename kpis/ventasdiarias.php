<?php
include_once '../funciones.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<title>Análisis Semanal Completo — INNOVACION MOVIL</title>
<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<style>
body { font-family: Arial, sans-serif; margin:18px; background:#f7f7f7; color:#222; }
h1{margin-top:0;}
.controls{display:flex; gap:12px; align-items:center; margin-bottom:12px; flex-wrap:wrap;}
input[type=file]{padding:6px;}
button.btn{background:#007bff;color:white;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;}
button.btn:disabled{background:#999; cursor:not-allowed;}
table{border-collapse:collapse;width:100%;background:white;box-shadow:0 1px 3px rgba(0,0,0,0.07);margin-bottom:20px;}
th,td{padding:8px 6px;border:1px solid #e1e1e1;text-align:center;font-size:13px;}
th{background:#2f6fa6;color:white;position:sticky;top:0;z-index:1;}
caption{text-align:left;font-weight:600;padding:8px;}
.note{font-size:13px;color:#333;margin-top:6px;}
.debug { font-size:12px; color:#666; margin-top:6px; background:#fff; padding:8px; border:1px solid #eee; }
</style>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
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
  <div class="container">
<h1>📈 Análisis Semanal Completo — INNOVACION MOVIL</h1>

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
<button id="descargarBtn" class="btn" disabled>Descargar .xlsx</button>
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
<div id="debugBox" class="debug" style="display:none;"></div>
<div id="tablesContainer"></div>
</div>
<script>
/* Metas desde PHP */
const METAS = <?php echo json_encode(obtenerMetasTiendas(), JSON_PRETTY_PRINT); ?>;

/* Estado */
let registros = [];
let storesResumen = {};
const inputFile = document.getElementById('inputFile');
const procesarBtn = document.getElementById('procesarBtn');
const descargarBtn = document.getElementById('descargarBtn');
const mensajes = document.getElementById('mensajes');
const debugBox = document.getElementById('debugBox');
const tablesContainer = document.getElementById('tablesContainer');

inputFile.addEventListener('change', ()=>{ procesarBtn.disabled = !inputFile.files.length; mensajes.innerText = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : ""; });
procesarBtn.addEventListener('click', ()=>{ if(inputFile.files.length) leerExcel(inputFile.files[0]); });
descargarBtn.addEventListener('click', ()=>descargaResultados());

/* Util */
function safeNumber(x){
  if (x === null || x === undefined || x === "") return 0;
  // eliminar comas, símbolos $
  const s = String(x).replace(/\s+/g,' ').replace(/[,\\$]/g,'').trim();
  const n = parseFloat(s);
  return isNaN(n) ? 0 : n;
}

/* parseFecha robusta:
   - Si es número (serial Excel) intenta usar XLSX.SSF.parse_date_code si existe.
   - Siempre retorna Date local construida con (year,month,day) para evitar desfases por zona horaria.
   - Si la cadena incluye "Sep 5 2025 7:04PM" se extrae día/mes/año y se devuelve date local sin hora.
*/
function parseFecha(fechaRaw){
  if (fechaRaw === null || fechaRaw === undefined || fechaRaw === "") return null;

  // 1) Si viene como número (serial Excel)
  if (typeof fechaRaw === 'number'){
    try {
      if (typeof XLSX !== 'undefined' && XLSX.SSF && typeof XLSX.SSF.parse_date_code === 'function'){
        const d = XLSX.SSF.parse_date_code(fechaRaw);
        if (d && d.y && d.m && d.d) {
          return new Date(d.y, d.m - 1, d.d);
        }
      }
    } catch(e){
      // fallthrough a método alterno
    }
    // fallback: convertir y devolver solo Y-M-D (evita hora/UTC)
    const jsDate = new Date((fechaRaw - 25569) * 86400 * 1000);
    return new Date(jsDate.getFullYear(), jsDate.getMonth(), jsDate.getDate());
  }

  // 2) Si es string, normalizar espacios
  if (typeof fechaRaw === 'string'){
    let s = fechaRaw.replace(/\s+/g,' ').trim();

    // Intentar reconocer formatos tipo "Sep 5 2025" (ing/esp) o "5 Sep 2025"
    // Buscar mes por nombre (3 letras mínimo)
    const regex1 = /([A-Za-z]{3,})\s+(\d{1,2})\s+(\d{4})/; // "Sep 5 2025"
    const regex2 = /(\d{1,2})\s+([A-Za-z]{3,})\s+(\d{4})/; // "5 Sep 2025"
    let m = s.match(regex1) || s.match(regex2);
    if (m){
      // extraer mes (3 letras)
      const monthNames = {
        jan:0,feb:1,mar:2,apr:3,may:4,jun:5,jul:6,aug:7,sep:8,oct:9,nov:10,dec:11,
        ene:0,feb:1,mar:2,abr:3,may:4,jun:5,jul:6,ago:7,sep:8,oct:9,nov:10,dic:11
      };
      // determinar posiciones segun regex
      let monStr, dayStr, yearStr;
      if (s.match(regex1)){
        monStr = m[1];
        dayStr = m[2];
        yearStr = m[3];
      } else {
        monStr = m[2];
        dayStr = m[1];
        yearStr = m[3];
      }
      const monKey = monStr.substring(0,3).toLowerCase();
      const month = (monKey in monthNames) ? monthNames[monKey] : NaN;
      const day = parseInt(dayStr,10);
      const year = parseInt(yearStr,10);
      if (!isNaN(month) && !isNaN(day) && !isNaN(year)){
        return new Date(year, month, day);
      }
    }

    // 3) Intentar parseo con Date, pero devolver solo Y-M-D para evitar offset UTC
    const d = new Date(s);
    if (!isNaN(d.getTime())){
      return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    }

    // Si nada funciona -> null
    return null;
  }

  return null;
}

/* Mapear día: queremos índice 0=Sábado ... 6=Viernes */
function getDiaSemanaIndex(fecha){
  if(!fecha) return null;
  const jsDay = fecha.getDay(); // 0=domingo .. 6=sábado
  const mapa = {6:0, 0:1, 1:2, 2:3, 3:4, 4:5, 5:6};
  return mapa[jsDay];
}

/* Leer archivo */
function leerExcel(file){
  mensajes.innerText = 'Leyendo archivo...';
document.getElementById('loader').style.display = 'flex';
const reader = new FileReader();
  reader.onload = e=>{
    const data = new Uint8Array(e.target.result);
    const wb = XLSX.read(data, { type: 'array' });
    const sheet = wb.Sheets[wb.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(sheet, { header:1, defval: "" });
    if (!rows || rows.length < 2) {
      mensajes.innerText = "Archivo vacío o sin datos.";
      return;
    }

    // índices fijos
    const idxAlmacen = 0; // A
    const idxN1 = 1;      // B
    const idxFecha = 7;   // H
    const idxVendedor = 9;// J
    const idxTotal = 18;  // S (TotalVenta)

    registros = [];
    const badDates = [];
    for (let i = 1; i < rows.length; i++){
      const r = rows[i];
      if (String(r[idxN1] || "").trim() !== "INNOVACION MOVIL") continue;
      const parsed = parseFecha(r[idxFecha]);
      if (!parsed) {
        // Registrar pero no matar; guardamos para debug (no se incluirá)
        badDates.push({row: i+1, raw: r[idxFecha]});
        continue;
      }
      registros.push({
        almacen: String(r[idxAlmacen] || "(SIN ALMACEN)").trim(),
        vendedor: String(r[idxVendedor] || "(SIN VENDEDOR)").trim(),
        fecha: parsed,
        total: safeNumber(r[idxTotal])
      });
    }

    mensajes.innerText = `Filtradas ${registros.length} filas INNOVACION MOVIL. Procesando...`;
    if (badDates.length) {
      debugBox.style.display = 'block';
      debugBox.innerText = `Advertencia: ${badDates.length} filas con fecha no parseable (se omitieron). Ejemplo: fila ${badDates[0].row} -> "${badDates[0].raw}"`;
    } else {
      debugBox.style.display = 'none';
    }

    procesarRegistros();
    document.getElementById('loader').style.display = 'none'; 
  };
  reader.readAsArrayBuffer(file);
}

/* Genera semanas consecutivas: primer inicio = primer fecha (si no es sábado, va al sábado previo)
   Luego semanas completas Sáb→Vie sin solapamiento */
function obtenerSemanas(fechaInicio, fechaFin){
  const semanas = [];
  // Si fechaInicio no es Date válida, regresar vacío
  if (!fechaInicio || !fechaFin) return semanas;
  // Primera semana: inicio = fechaInicio (no lo movemos hacia atrás: la regla tuya original decía
  // que si el primer día es miércoles esa primera semana será de miércoles a viernes). Pero
  // para seguir exactamente esa regla, debemos hacer:
  //   primera.inicio = fechaInicio (sin hora)
  //   primera.fin = próximo viernes (desde fechaInicio)
  // Luego las siguientes semanas empiezan el sábado siguiente y son completas.
  const firstStart = new Date(fechaInicio.getFullYear(), fechaInicio.getMonth(), fechaInicio.getDate());
  // calcular próximo viernes desde firstStart
  const day = firstStart.getDay(); // 0..6
  const daysUntilFriday = (5 - day + 7) % 7; // 0..6
  const firstEnd = new Date(firstStart);
  firstEnd.setDate(firstStart.getDate() + daysUntilFriday);

  semanas.push({ start: firstStart, end: firstEnd });

  // semanas completas posteriores: sábado siguiente
  const nextSaturday = new Date(firstEnd);
  nextSaturday.setDate(firstEnd.getDate() + 1); // sábado siguiente
  while (nextSaturday <= fechaFin) {
    const end = new Date(nextSaturday);
    end.setDate(nextSaturday.getDate() + 6); // viernes
    // si end > fechaFin, lo dejamos (podría terminar antes del viernes final del mes)
    semanas.push({ start: new Date(nextSaturday), end: end });
    nextSaturday.setDate(nextSaturday.getDate() + 7);
  }

  return semanas;
}

/* Procesar registros por tienda y por semana */
function procesarRegistros(){
  storesResumen = {};
  if (!registros.length) { mensajes.innerText = "No hay registros filtrados."; return; }

  // Agrupar por almacen
  const stores = {};
  registros.forEach(r => {
    if (!stores[r.almacen]) stores[r.almacen] = [];
    stores[r.almacen].push(r);
  });

  for (const almacen of Object.keys(stores)) {
    const arr = stores[almacen];
    // ordenar por fecha asc
    arr.sort((a,b) => a.fecha - b.fecha);
    const primera = arr[0].fecha;
    const ultima = arr[arr.length - 1].fecha;
    const semanas = obtenerSemanas(primera, ultima);
    storesResumen[almacen] = [];

    semanas.forEach(s => {
      // inicializar vendedores para la semana
      const weekVendedores = {}; // vendedor -> [7 valores Sáb..Vie]
      // tomar registros dentro de la ventana [s.start, s.end]
      arr.forEach(r => {
        if (r.fecha >= s.start && r.fecha <= s.end) {
          if (!weekVendedores[r.vendedor]) weekVendedores[r.vendedor] = Array(7).fill(0);
          const idx = getDiaSemanaIndex(r.fecha);
          if (idx !== null) weekVendedores[r.vendedor][idx] += safeNumber(r.total);
        }
      });

      // asegurarnos que vendedores sin ventas aparecen? no es necesario
      // calcular vendedores válidos (>3 días con venta)
      const vendedoresValidos = Object.keys(weekVendedores).filter(v => weekVendedores[v].filter(x => x > 0).length > 3);
      const metaDiaria = METAS[almacen] ? (METAS[almacen].diaria || 0) : 0;
      const metaAsignada = vendedoresValidos.length ? (metaDiaria * 7) / vendedoresValidos.length : 0;

      // construir rows: forzar campos D0..D6 con 0 si no existen
      const rows = [];
      Object.keys(weekVendedores).forEach(v => {
        const ventas = weekVendedores[v] || Array(7).fill(0);
        const totalSemana = ventas.reduce((a,b) => a + b, 0);
        rows.push({
          Vendedor: v,
          MetaAsignada: Number(metaAsignada),
          Dias: ventas.map(x => Number(x)),
          TotalSemana: totalSemana,
          Diferencia: totalSemana - metaAsignada,
          Porcentaje: metaAsignada ? (totalSemana / metaAsignada) * 100 : 0
        });
      });

      // si no hay vendedores (semana sin ventas), crear fila vacía para mostrar zeros
      if (rows.length === 0) {
        rows.push({
          Vendedor: "(SIN VENTAS)",
          MetaAsignada: Number(metaAsignada),
          Dias: Array(7).fill(0),
          TotalSemana: 0,
          Diferencia: 0 - metaAsignada,
          Porcentaje: 0
        });
      }

      storesResumen[almacen].push({
        start: s.start,
        end: s.end,
        rows: rows,
        metaDiaria: metaDiaria
      });
    });
  }

  // Mostrar tablas
  mostrarTablas();
  descargarBtn.disabled = false;
  mensajes.innerText = `Procesamiento completado. Tiendas: ${Object.keys(storesResumen).length}`;
}

/* Mostrar tablas en HTML */
function mostrarTablas(){
  tablesContainer.innerHTML = "";
  for (const almacen of Object.keys(storesResumen)) {
    storesResumen[almacen].forEach((weekObj) => {
      const start = weekObj.start;
      const end = weekObj.end;
      const title = `${almacen} — Semana del ${pad(start.getDate())}/${pad(start.getMonth()+1)}/${start.getFullYear()} al ${pad(end.getDate())}/${pad(end.getMonth()+1)}/${end.getFullYear()}`;
      let html = `<table><caption>${title}</caption><thead><tr><th>Vendedor</th><th>MetaAsignada</th>`;
      ["Sábado","Domingo","Lunes","Martes","Miércoles","Jueves","Viernes"].forEach(h=> html += `<th>${h}</th>`);
      html += `<th>TotalSemana</th><th>Diferencia</th><th>% Cumplimiento</th></tr></thead><tbody>`;

      weekObj.rows.forEach(r => {
        html += `<tr>`;
        html += `<td>${escapeHtml(r.Vendedor)}</td>`;
        html += `<td>${Number(r.MetaAsignada).toFixed(2)}</td>`;
        r.Dias.forEach(v => html += `<td>${Number(v).toFixed(2)}</td>`);
        html += `<td>${Number(r.TotalSemana).toFixed(2)}</td>`;
        html += `<td>${Number(r.Diferencia).toFixed(2)}</td>`;
        html += `<td>${Number(r.Porcentaje).toFixed(2)}%</td>`;
        html += `</tr>`;
      });

      // Totales por columna
      const totals = Array(7).fill(0);
      let totalSemana = 0;
      weekObj.rows.forEach(r => {
        r.Dias.forEach((v,i) => totals[i] += v);
        totalSemana += r.TotalSemana;
      });
      const metaTotal = weekObj.metaDiaria * 7;
      const pctCumpl = metaTotal ? (totalSemana / metaTotal) * 100 : 0;

      html += `<tr style="font-weight:bold;background:#eee"><td>Total</td><td></td>`;
      totals.forEach(t => html += `<td>${Number(t).toFixed(2)}</td>`);
      html += `<td>${Number(totalSemana).toFixed(2)}</td><td>${Number(totalSemana - metaTotal).toFixed(2)}</td><td>${Number(pctCumpl).toFixed(2)}%</td></tr>`;

      html += `</tbody></table>`;
      tablesContainer.innerHTML += html;
    });
  }
}

/* Export: una hoja por tienda, en cada hoja se apilan las tablas semanales (uso AOA para conservar bloques) */
function descargaResultados(){
  if (!storesResumen || !Object.keys(storesResumen).length){ alert("Procesa el archivo primero."); return; }
  const wb = XLSX.utils.book_new();

  for (const almacen of Object.keys(storesResumen)) {
    const wsData = [];
    storesResumen[almacen].forEach(weekObj => {
      const start = weekObj.start;
      const end = weekObj.end;
      wsData.push([`${almacen} — Semana del ${pad(start.getDate())}/${pad(start.getMonth()+1)}/${start.getFullYear()} al ${pad(end.getDate())}/${pad(end.getMonth()+1)}/${end.getFullYear()}`]);
      const headers = ["Vendedor","MetaAsignada","Sábado","Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","TotalSemana","Diferencia","%Cumplimiento"];
      wsData.push(headers);
      weekObj.rows.forEach(r => {
        const row = [ 
        r.Vendedor, 
        Number(r.MetaAsignada).toFixed(2), 
        ...r.Dias.map(d => Number(d).toFixed(2)), 
        Number(r.TotalSemana).toFixed(2), 
        Number(r.Diferencia).toFixed(2), 
        (r.MetaAsignada ? (r.TotalSemana / r.MetaAsignada) : 0).toFixed(4) // porcentaje decimal
      ];
        wsData.push(row);
      });
      // totales
      const totals = Array(7).fill(0);
      let totalSemana = 0;
      weekObj.rows.forEach(r=>{
        r.Dias.forEach((d,i)=> totals[i]+=d);
        totalSemana += r.TotalSemana;
      });
      const metaTotal = weekObj.metaDiaria * 7;
      const pctCumpl = metaTotal ? (totalSemana/metaTotal) : 0; 
      wsData.push(["Total","", ...totals.map(t=>Number(t).toFixed(2)), Number(totalSemana).toFixed(2), Number(totalSemana - metaTotal).toFixed(2), pctCumpl.toFixed(4)]);
      wsData.push([]); // fila en blanco entre semanas
    });
    const ws = XLSX.utils.aoa_to_sheet(wsData);
    // limitar nombre de hoja a 31 chars
    const sheetName = almacen.substring(0,31) || "Tienda";
    XLSX.utils.book_append_sheet(wb, ws, sheetName);
  }

  XLSX.writeFile(wb, "Analisis_Semanal_Corregido.xlsx");
}

/* Helpers */
function pad(n){ return String(n).padStart(2,'0'); }
function escapeHtml(s){ return String(s).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]; }); }

</script>
</body>
</html>
