<?php

include_once '../funciones.php'; 

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ticket Promedio por Vendedor — INNOVACION MOVIL</title>
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
.popup{ z-index: 9999;}
.highlight { background-color: #ffffcc; font-weight: bold; }
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
  <h1>📊 Ticket Promedio por Vendedor — INNOVACION MOVIL</h1>

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
  <button id="btnProcesar" class="btn" disabled>Procesar Archivo</button>
  <button id="btnDescargar"  class="btn" style="display:none;">Descargar Análisis Excel</button></div>

  <div id="estado"></div>
  <div id="tablaContainer"></div>
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

  <!-- Ventana emergente para ver tickets del vendedor -->
  <div id="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
       background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999;">
    <div style="background:#fff; padding:20px; border-radius:10px; width:80%; max-height:80%; overflow:auto;">
      <h3 id="modalTitulo"></h3>
      <table border="1" width="100%" cellspacing="0" cellpadding="5">
        <thead>
          <tr>
            <th>Ticket</th>
            <th>Almacén</th>
            <th>Total Venta</th>
          </tr>
        </thead>
        <tbody id="modalBody"></tbody>
      </table>
      <p id="totalVendedor"></p>
      <button id="btnDescargarVendedor" class="btn">Descargar Tickets de este Vendedor</button>
      <button class="btn" onclick="cerrarModal()">Cerrar</button>
    </div>
  </div>

<script>
let analisisVendedores = [];
let vendedorActual = null;

document.getElementById('inputFile').addEventListener('change', e=>{
  document.getElementById('btnProcesar').disabled = !e.target.files.length;
});

document.getElementById('btnProcesar').addEventListener('click', ()=>{
  const file = document.getElementById('inputFile').files[0];
  if(!file) return alert("Selecciona un archivo Excel primero.");
  procesarArchivo(file);
});

function procesarArchivo(file){
  document.getElementById("estado").innerText = "📊 Cargando archivo, por favor espera...";
  document.getElementById('loader').style.display = 'flex';
  const reader = new FileReader();
  reader.onload = function(e){
    const data = new Uint8Array(e.target.result);
    const workbook = XLSX.read(data, {type:'array'});
    const hoja = workbook.Sheets[workbook.SheetNames[0]];
    const json = XLSX.utils.sheet_to_json(hoja, {header:1});
    if(json.length < 2){
      document.getElementById("estado").innerText = "⚠️ El archivo está vacío o no tiene datos válidos.";
      document.getElementById('loader').style.display = 'none';
      return;
    }
    analizarDatos(json);
  };
  reader.readAsArrayBuffer(file);
}

function analizarDatos(json){
  document.getElementById("estado").innerText = "🧮 Procesando información...";
  const encabezados = json[0];
  const idxN1 = encabezados.indexOf("N1");
  const idxTicket = encabezados.indexOf("NoMov");
  const idxVendedor = encabezados.indexOf("Vendedor");
  const idxAlmacen = encabezados.indexOf("Almacen");
  const idxProd = encabezados.indexOf("ProdConcat");
  const idxCantidad = encabezados.indexOf("Cantidad");
  const idxTotal = encabezados.indexOf("TotalVenta");

  let registros = [];
  for(let i=1;i<json.length;i++){
    const r = json[i];
    if(String(r[idxN1]||"").trim() === "INNOVACION MOVIL"){
      registros.push({
        ticket: String(r[idxTicket]||"").trim(),
        vendedor: String(r[idxVendedor]||"").trim(),
        almacen: String(r[idxAlmacen]||"").trim(),
        producto: String(r[idxProd]||"").trim(),
        cantidad: Number(r[idxCantidad]||0),
        totalVenta: Number(r[idxTotal]||0)
      });
    }
  }

  // Agrupar por ticket + almacén
  const ticketsMap = {};
  for(const r of registros){
    const key = `${r.ticket}__${r.almacen}`;
    if(!ticketsMap[key]) ticketsMap[key] = {ticket:r.ticket, almacen:r.almacen, vendedor:r.vendedor, total:0, productos:[]};
    ticketsMap[key].total += r.totalVenta;
    ticketsMap[key].productos.push({producto:r.producto, cantidad:r.cantidad, totalVenta:r.totalVenta});
  }

  const tickets = Object.values(ticketsMap);

  // Agrupar por vendedor
  const vendedoresMap = {};
  for(const t of tickets){
    if(!vendedoresMap[t.vendedor]){
      vendedoresMap[t.vendedor] = {
        vendedor: t.vendedor,
        cantidadTickets: 0,
        montoTotal: 0,
        tickets: []
      };
    }
    vendedoresMap[t.vendedor].cantidadTickets++;
    vendedoresMap[t.vendedor].montoTotal += t.total;
    vendedoresMap[t.vendedor].tickets.push({
      ticket: t.ticket,
      almacen: t.almacen,
      total: t.total
    });
  }

  // Calcular ticket promedio
  const vendedores = Object.values(vendedoresMap).map(v => ({
    ...v,
    ticketPromedio: v.montoTotal / v.cantidadTickets
  }));

  // Ordenar por ticket promedio (mayor a menor)
  vendedores.sort((a,b) => b.ticketPromedio - a.ticketPromedio);

  analisisVendedores = vendedores;
  mostrarTabla(vendedores);

  document.getElementById("estado").innerText = "✅ Análisis completado.";
  document.getElementById("btnDescargar").style.display = "inline-block";
  document.getElementById('loader').style.display = 'none';
}

function mostrarTabla(vendedores){
  let html = `<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
      <tr>
        <th>Vendedor</th>
        <th>Cantidad de Tickets</th>
        <th>Monto Total</th>
        <th>Ticket Promedio</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>`;
  for(const v of vendedores){
    html += `<tr>
      <td>${v.vendedor}</td>
      <td>${v.cantidadTickets}</td>
      <td>$${v.montoTotal.toFixed(2)}</td>
      <td class="highlight">$${v.ticketPromedio.toFixed(2)}</td>
      <td><button class="btn" onclick='verTicketsVendedor("${v.vendedor}")'>Ver tickets</button></td>
    </tr>`;
  }
  html += `</tbody></table>`;
  document.getElementById("tablaContainer").innerHTML = html;
}

function verTicketsVendedor(vendedor){
  const v = analisisVendedores.find(x=>x.vendedor===vendedor);
  if(!v) return alert("Vendedor no encontrado.");
  vendedorActual = v;

  document.getElementById("modalTitulo").innerText = `Tickets de ${vendedor}`;
  let html = "";
  for(const t of v.tickets){
    html += `<tr>
      <td>${t.ticket}</td>
      <td>${t.almacen}</td>
      <td>$${t.total.toFixed(2)}</td>
    </tr>`;
  }
  document.getElementById("modalBody").innerHTML = html;
  document.getElementById("totalVendedor").innerHTML = `
    📊 <strong>Total tickets:</strong> ${v.cantidadTickets}<br>
    💰 <strong>Monto total:</strong> $${v.montoTotal.toFixed(2)}<br>
    📈 <strong>Ticket promedio:</strong> $${v.ticketPromedio.toFixed(2)}
  `;
  document.getElementById("modal").style.display = "flex";
}

function cerrarModal(){
  document.getElementById("modal").style.display = "none";
}

document.getElementById("btnDescargar").addEventListener('click', ()=>{
  const ws = XLSX.utils.json_to_sheet(analisisVendedores.map(v=>({
    Vendedor: v.vendedor,
    CantidadTickets: v.cantidadTickets,
    MontoTotal: v.montoTotal,
    TicketPromedio: v.ticketPromedio
  })));
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Análisis por Vendedor");
  XLSX.writeFile(wb, "Ticket_Promedio_Vendedores.xlsx");
});

document.getElementById("btnDescargarVendedor").addEventListener('click', ()=>{
  if(!vendedorActual) return alert("No hay vendedor seleccionado.");
  const ws = XLSX.utils.json_to_sheet(vendedorActual.tickets.map(t=>({
    Ticket: t.ticket,
    Almacen: t.almacen,
    TotalVenta: t.total
  })));
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, `Tickets_${vendedorActual.vendedor}`);
  XLSX.writeFile(wb, `Tickets_${vendedorActual.vendedor}.xlsx`);
});
</script>
</body>
</html>