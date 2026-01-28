<?php

include_once '../funciones.php'; 

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tickets de Mayor Precio — INNOVACION MOVIL</title>
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
</style>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
     <header>
  <nav>
        <div class="nav-inner">
            <!-- Botón hamburguesa -->
            <label class="bar-menu">
                <input type="checkbox" id="menu-check">
                <span class="top"></span>
                <span class="middle"></span>
                <span class="bottom"></span>
            </label>

            <ul id="nav-menu">
                <li>
        <a href="index.php" class="menu-link">
          <span class="logo-container">
            <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />

          </span>
          Home
        </a>
      </li>
            </ul>
        </div>
    </nav>
</header>
<div class="container">
  <h1>📄 Tickets de Mayor Precio — INNOVACION MOVIL</h1>

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
  <button id="btnDescargar"  class="btn" style="display:none;">Descargar Resumen Excel</button></div>

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

  <!-- Ventana emergente -->
  <div id="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
       background:rgba(0,0,0,0.6); justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; border-radius:10px; width:70%; max-height:80%; overflow:auto;">
      <h3 id="modalTitulo"></h3>
      <table border="1" width="100%" cellspacing="0" cellpadding="5">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Total Venta</th>
          </tr>
        </thead>
        <tbody id="modalBody"></tbody>
      </table>
      <p id="totalTicket"></p>
      <button id="btnDescargarTicket" class="btn" >Descargar este Ticket</button>
      <button  class="btn" onclick="cerrarModal()">Cerrar</button>
    </div>
  </div>

<script>
let registrosFiltrados = [];
let ticketActual = null;

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

  const tickets = Object.values(ticketsMap).sort((a,b)=>b.total - a.total);
  registrosFiltrados = tickets;
  mostrarTabla(tickets);

  document.getElementById("estado").innerText = "✅ Análisis completado.";
  document.getElementById("btnDescargar").style.display = "inline-block";
}

function mostrarTabla(tickets){
  let html = `<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
      <tr>
        <th>Ticket</th>
        <th>Almacén</th>
        <th>Vendedor</th>
        <th>Total Venta</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>`;
  for(const t of tickets){
    html += `<tr>
      <td>${t.ticket}</td>
      <td>${t.almacen}</td>
      <td>${t.vendedor}</td>
      <td>$${t.total.toFixed(2)}</td>
      <td><button class="btn" onclick='verTicket("${t.ticket}","${t.almacen}")'>Ver detalles</button></td>
    </tr>`;
  }
  html += `</tbody></table>`;
  document.getElementById("tablaContainer").innerHTML = html;
}

function verTicket(ticket, almacen){
  const tk = registrosFiltrados.find(x=>x.ticket===ticket && x.almacen===almacen);
  if(!tk) return alert("Ticket no encontrado.");
  ticketActual = tk;

  document.getElementById("modalTitulo").innerText = `Ticket ${ticket} — ${tk.almacen} (${tk.vendedor})`;
  let html = "";
  for(const p of tk.productos){
    html += `<tr>
      <td>${p.producto}</td>
      <td>${p.cantidad}</td>
      <td>$${p.totalVenta.toFixed(2)}</td>
    </tr>`;
  }
  document.getElementById("modalBody").innerHTML = html;
  document.getElementById("totalTicket").innerText = `💰 Total del ticket: $${tk.total.toFixed(2)}`;
  document.getElementById("modal").style.display = "flex";
}

function cerrarModal(){
  document.getElementById("modal").style.display = "none";
}

document.getElementById("btnDescargar").addEventListener('click', ()=>{
  const ws = XLSX.utils.json_to_sheet(registrosFiltrados.map(t=>({
    Ticket: t.ticket,
    Almacen: t.almacen,
    Vendedor: t.vendedor,
    TotalVenta: t.total
  })));
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Resumen Tickets");
  XLSX.writeFile(wb, "Tickets_Mayor_Precio.xlsx");
});

document.getElementById("btnDescargarTicket").addEventListener('click', ()=>{
  if(!ticketActual) return alert("No hay ticket seleccionado.");
  const ws = XLSX.utils.json_to_sheet(ticketActual.productos.map(p=>({
    Producto: p.producto,
    Cantidad: p.cantidad,
    TotalVenta: p.totalVenta
  })));
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, `Ticket_${ticketActual.ticket}`);
  XLSX.writeFile(wb, `Ticket_${ticketActual.ticket}_${ticketActual.almacen}.xlsx`);
});
</script>
  <script>
    // Controlar menú hamburguesa
    document.getElementById('menu-check').addEventListener('change', function() {
        const menu = document.getElementById('nav-menu');
        if (this.checked) {
            menu.style.opacity = '1';
            menu.style.visibility = 'visible';
            menu.style.pointerEvents = 'auto';
        } else {
            menu.style.opacity = '0';
            menu.style.visibility = 'hidden';
            menu.style.pointerEvents = 'none';
        }
    });
</script>
</body>
</html>
