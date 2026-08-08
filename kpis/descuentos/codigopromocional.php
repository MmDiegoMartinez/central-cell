<?php

include_once '../funciones.php'; 

?>
<!DOCTYPE html>
<html lang="es">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
  <meta charset="UTF-8">
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
  <link rel="stylesheet" href="../estilos.css">
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
        <a href="../../garantias/validador/validador.php" class="menu-link">
          <span class="logo-container">
            <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" class="logo" width="25" height="25"/>
          </span>
          Home
        </a>
      </li>
      <li>
        <li>
        <a href="../index.php" class="menu-link">
          Panel KPIs
        </a>
      </li>
        <a href="index.php" class="menu-link">
          Atras
        </a>
      </li>
            </ul>
        </div>
    </nav>
</header>
 
<div class="container">
  <h1>📄 Tickets de Mayor Precio — INNOVACION MOVIL</h1>
  <!-- SIMBOLOGÍA COMPACTA -->
  <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #2f6fa6;">🎨 Simbología de Colores</h3>
    <table style="width: 100%; border: none; box-shadow: none;">
      <tr>
        <td style="background: #fff3bf; padding: 10px; border: 2px solid #ffd700; text-align: left;">
          <strong>🟨 AMARILLO:</strong> Promo centavo válida
        </td>
        <td style="background: #d0e7ff; padding: 10px; border: 2px solid #007bff; text-align: left;">
          <strong>🔵 AZUL:</strong> Cupón válido
        </td>
      </tr>
      <tr>
        <td style="background: #ffe5b4; padding: 10px; border: 2px solid #ff8c00; text-align: left;">
          <strong>🟧 NARANJA:</strong> Promo no aplicada
        </td>
        <td style="background: #f8d7da; padding: 10px; border: 2px solid #dc3545; text-align: left;">
          <strong>🔴 ROJO:</strong> Ticket sospechoso (ver detalles)
        </td>
      </tr>
    </table>
</div>

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
       background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999;">
    <div style="background:#fff; padding:20px; border-radius:10px; width:70%; max-height:80%; overflow:auto;">
      <h3 id="modalTitulo"></h3>
      <table border="1" width="100%" cellspacing="0" cellpadding="5">
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>PrecioVenta (unitario)</th>
            <th>Total Venta</th>
          </tr>
        </thead>
        <tbody id="modalBody"></tbody>
      </table>
      <p id="cantidadTotal"></p>
      <p id="promoInfo"></p>
      <p id="totalTicket"></p>
      <button id="btnDescargarTicket" class="btn" >Descargar este Ticket</button>
      <button  class="btn" onclick="cerrarModal()">Cerrar</button>
    </div>
  </div>

<script>
let registrosFiltrados = [];
let ticketActual = null;

// Normaliza encabezados (quita acentos, espacios y pasa a minúsculas)
function normalizeHeaderText(t){
  if(t === undefined || t === null) return "";
  return String(t).normalize("NFD").replace(/[\u0300-\u036f]/g,"").replace(/\s+/g,"").toLowerCase();
}

function findHeaderIndex(encabezados, candidates){
  for(let i=0;i<encabezados.length;i++){
    const normalized = normalizeHeaderText(encabezados[i]);
    for(const c of candidates){
      if(normalized === normalizeHeaderText(c)) return i;
    }
  }
  return -1;
}

// Extrae monto de "CÓDIGO PROMOCIONAL" en un texto
function extractPromoAmount(text){
  if(!text) return null;
  const regex = /c[oó]digo\s*promocional[^0-9$,-]*\$?\s*([\d.,]+)/i;
  const m = String(text).match(regex);
  if(!m) return null;
  let num = m[1];
  num = num.replace(/\s/g, '').replace(/\$/g, '');
  if((num.match(/,/g) || []).length > 0 && (num.match(/\./g) || []).length === 0){
    num = num.replace(/\./g, '').replace(/,/g, '.');
  } else {
    num = num.replace(/,/g, '');
  }
  const parsed = parseFloat(num);
  return isNaN(parsed) ? null : parsed;
}

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
    document.getElementById('loader').style.display = 'none';
  };
  reader.readAsArrayBuffer(file);
}

function analizarDatos(json){
  document.getElementById("estado").innerText = "🧮 Procesando información...";
  const encabezados = json[0];

  // Buscar índices de columnas
  const idxN1 = findHeaderIndex(encabezados, ["N1"]);
  const idxTicket = findHeaderIndex(encabezados, ["NoMov", "nomov"]);
  const idxVendedor = findHeaderIndex(encabezados, ["Vendedor", "vendedores"]);
  const idxAlmacen = findHeaderIndex(encabezados, ["Almacen", "almacén", "almacen"]);
  const idxProd = findHeaderIndex(encabezados, ["ProdConcat", "Producto", "prodconcat", "descripcion"]);
  const idxCantidad = findHeaderIndex(encabezados, ["Cantidad", "cantidad"]);
  const idxTotal = findHeaderIndex(encabezados, ["TotalVenta", "totalventa", "total venta", "total"]);
  const idxPrecio = findHeaderIndex(encabezados, ["PrecioVenta", "precioventa", "precio venta", "precio"]);
  const idxMetodos = findHeaderIndex(encabezados, ["Métodos De Pago", "Metodos De Pago", "MetodosDePago", "metodosdepago", "metodos de pago", "metodosde pago", "metodos"]);

  // Validaciones básicas
  if(idxN1 === -1 || idxTicket === -1){
    document.getElementById("estado").innerText = "⚠️ El archivo no contiene las columnas mínimas esperadas (N1 y NoMov).";
    return;
  }
  if(idxPrecio === -1 || idxCantidad === -1){
    document.getElementById("estado").innerText = "⚠️ El archivo no contiene las columnas Cantidad o PrecioVenta necesarias.";
    return;
  }

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
        totalVenta: Number(r[idxTotal]||0),
        precioVenta: Number(r[idxPrecio]||0),
        metodoPago: idxMetodos !== -1 ? String(r[idxMetodos]||"").trim() : ""
      });
    }
  }

  // Agrupar por ticket + almacén
  const ticketsMap = {};
  for(const r of registros){
    const key = `${r.ticket}__${r.almacen}`;
    if(!ticketsMap[key]) ticketsMap[key] = {
      ticket: r.ticket,
      almacen: r.almacen,
      vendedor: r.vendedor,
      total: 0,
      productos: [],
      metodos: []
    };
    ticketsMap[key].total += isNaN(r.totalVenta) ? 0 : r.totalVenta;
    ticketsMap[key].productos.push({
      producto: r.producto,
      cantidad: r.cantidad,
      totalVenta: r.totalVenta,
      precioVenta: r.precioVenta
    });
    ticketsMap[key].metodos.push(r.metodoPago || "");
  }

  const ticketsArray = Object.values(ticketsMap);

  // Clasificar por color según las reglas CORREGIDAS
  for(const t of ticketsArray){
    // CANTIDAD TOTAL DE PRODUCTOS (sumar columna Cantidad)
    const cantidadTotal = t.productos.reduce((sum, p) => sum + (Number(p.cantidad) || 0), 0);
    
    // Precio unitario más barato
    const preciosUnitarios = t.productos.map(p => Number(p.precioVenta) || 0).filter(v => v > 0);
    const precioMasBarato = preciosUnitarios.length ? Math.min(...preciosUnitarios) : 0;
    
    // ¿Hay algún producto con precio < $5?
    const hayProductoBarato = preciosUnitarios.some(p => p < 5);

    // Buscar monto de CÓDIGO PROMOCIONAL
    let promoAmount = null;
    for(const m of t.metodos){
      const found = extractPromoAmount(m);
      if(found !== null){
        promoAmount = found;
        break;
      }
    }

    // Guardar info adicional para debug
    t.cantidadTotal = cantidadTotal;
    t.precioMasBarato = precioMasBarato;
    t.promoAmount = promoAmount;

    // CLASIFICACIÓN POR PRIORIDAD:
    
    // 1. AMARILLO: 3+ productos Y al menos uno < $5
    if(cantidadTotal >= 3 && hayProductoBarato){
      t.color = "Amarillo";
    }
    // 2. AZUL: 3+ productos Y código promocional Y monto <= precio más barato
    else if(cantidadTotal >= 3 && promoAmount !== null && promoAmount <= precioMasBarato){
      t.color = "Azul";
    }
    // 3. ROJO (casos sospechosos):
    else if(
      // Caso 1: 1-2 productos Y alguno < $5
      (cantidadTotal <= 2 && hayProductoBarato) ||
      // Caso 2: Código promo con monto > precio más barato
      (promoAmount !== null && precioMasBarato > 0 && promoAmount > precioMasBarato) ||
      // Caso 3: Menos de 3 productos Y usó código promocional
      (cantidadTotal < 3 && promoAmount !== null)
    ){
      t.color = "Rojo";
    }
    // 4. NARANJA: 3+ productos, ninguno < $5, NO código promocional
    else if(cantidadTotal >= 3 && !hayProductoBarato && promoAmount === null){
      t.color = "Naranja";
    }
    // 5. Normal (sin color especial)
    else {
      t.color = "Normal";
    }
  }

  // Ordenar por total descendente
  const tickets = ticketsArray.sort((a,b)=> b.total - a.total);
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
        <th>Cantidad Total</th>
        <th>Total Venta</th>
        <th>Color</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>`;

  for(const t of tickets){
    let estiloFila = "";
    let colorEmoji = "";
    if (t.color === "Rojo") {
      estiloFila = 'style="background:#f8d7da;"';
      colorEmoji = "🔴";
    } else if (t.color === "Naranja") {
      estiloFila = 'style="background:#ffe5b4;"';
      colorEmoji = "🟧";
    } else if (t.color === "Amarillo") {
      estiloFila = 'style="background:#FCFC47;"';
      colorEmoji = "🟨";
    } else if (t.color === "Azul") {
      estiloFila = 'style="background:#d0e7ff;"';
      colorEmoji = "🔵";
    } else {
      colorEmoji = "";
    }

    html += `<tr ${estiloFila}>
      <td>${t.ticket}</td>
      <td>${t.almacen}</td>
      <td>${t.vendedor}</td>
      <td>${t.cantidadTotal}</td>
      <td>$${Number(t.total||0).toFixed(2)}</td>
      <td>${colorEmoji} ${t.color}</td>
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

  document.getElementById("modalTitulo").innerText = `Ticket ${ticket} — ${tk.almacen} (${tk.vendedor}) — ${tk.color}`;
  let html = "";
  for(const p of tk.productos){
    html += `<tr>
      <td>${p.producto}</td>
      <td>${p.cantidad}</td>
      <td>$${Number(p.precioVenta||0).toFixed(2)}</td>
      <td>$${Number(p.totalVenta||0).toFixed(2)}</td>
    </tr>`;
  }
  document.getElementById("modalBody").innerHTML = html;
  document.getElementById("cantidadTotal").innerText = `📦 Cantidad total de productos: ${tk.cantidadTotal}`;
  document.getElementById("promoInfo").innerText = tk.promoAmount !== null ? `🎟️ Monto CÓDIGO PROMOCIONAL aplicado: $${Number(tk.promoAmount).toFixed(2)}` : "🎟️ No se aplicó CÓDIGO PROMOCIONAL";
  document.getElementById("totalTicket").innerText = `💰 Total del ticket: $${Number(tk.total||0).toFixed(2)} | Producto más barato: $${Number(tk.precioMasBarato||0).toFixed(2)}`;
  document.getElementById("modal").style.display = "flex";
}

function cerrarModal(){
  document.getElementById("modal").style.display = "none";
}

document.getElementById("btnDescargar").addEventListener('click', ()=>{
  const conteoPorColor = { "Amarillo":0, "Azul":0, "Rojo":0, "Naranja":0, "Normal":0 };
  
  // CONTEO POR VENDEDOR
  const conteoAmarilloPorVendedor = {};
  const conteoAzulPorVendedor = {};
  const conteoRojoPorVendedor = {};
  const conteoNaranjaPorVendedor = {};

  // CONTEO POR ALMACÉN
  const conteoAmarilloPorAlmacen = {};
  const conteoAzulPorAlmacen = {};
  const conteoRojoPorAlmacen = {};
  const conteoNaranjaPorAlmacen = {};

  for (const t of registrosFiltrados) {
    if(conteoPorColor[t.color] !== undefined) conteoPorColor[t.color]++;
    else conteoPorColor["Normal"]++;

    // Contar por VENDEDOR para cada color
    if (t.color === "Amarillo") {
      if (!conteoAmarilloPorVendedor[t.vendedor]) conteoAmarilloPorVendedor[t.vendedor] = 0;
      conteoAmarilloPorVendedor[t.vendedor]++;
      
      if (!conteoAmarilloPorAlmacen[t.almacen]) conteoAmarilloPorAlmacen[t.almacen] = 0;
      conteoAmarilloPorAlmacen[t.almacen]++;
    }
    if (t.color === "Azul") {
      if (!conteoAzulPorVendedor[t.vendedor]) conteoAzulPorVendedor[t.vendedor] = 0;
      conteoAzulPorVendedor[t.vendedor]++;
      
      if (!conteoAzulPorAlmacen[t.almacen]) conteoAzulPorAlmacen[t.almacen] = 0;
      conteoAzulPorAlmacen[t.almacen]++;
    }
    if (t.color === "Rojo") {
      if (!conteoRojoPorVendedor[t.vendedor]) conteoRojoPorVendedor[t.vendedor] = 0;
      conteoRojoPorVendedor[t.vendedor]++;
      
      if (!conteoRojoPorAlmacen[t.almacen]) conteoRojoPorAlmacen[t.almacen] = 0;
      conteoRojoPorAlmacen[t.almacen]++;
    }
    if (t.color === "Naranja") {
      if (!conteoNaranjaPorVendedor[t.vendedor]) conteoNaranjaPorVendedor[t.vendedor] = 0;
      conteoNaranjaPorVendedor[t.vendedor]++;
      
      if (!conteoNaranjaPorAlmacen[t.almacen]) conteoNaranjaPorAlmacen[t.almacen] = 0;
      conteoNaranjaPorAlmacen[t.almacen]++;
    }
  }

  // Convertir a arrays y ordenar - VENDEDORES
  const topAmarilloVendedor = Object.entries(conteoAmarilloPorVendedor)
    .map(([vendedor, cantidad]) => ({ vendedor, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topAzulVendedor = Object.entries(conteoAzulPorVendedor)
    .map(([vendedor, cantidad]) => ({ vendedor, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topRojoVendedor = Object.entries(conteoRojoPorVendedor)
    .map(([vendedor, cantidad]) => ({ vendedor, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topNaranjaVendedor = Object.entries(conteoNaranjaPorVendedor)
    .map(([vendedor, cantidad]) => ({ vendedor, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  // Convertir a arrays y ordenar - ALMACENES
  const topAmarilloAlmacen = Object.entries(conteoAmarilloPorAlmacen)
    .map(([almacen, cantidad]) => ({ almacen, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topAzulAlmacen = Object.entries(conteoAzulPorAlmacen)
    .map(([almacen, cantidad]) => ({ almacen, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topRojoAlmacen = Object.entries(conteoRojoPorAlmacen)
    .map(([almacen, cantidad]) => ({ almacen, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topNaranjaAlmacen = Object.entries(conteoNaranjaPorAlmacen)
    .map(([almacen, cantidad]) => ({ almacen, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  // Crear libro de Excel
  const wb = XLSX.utils.book_new();

  // Hoja 1: Resumen general de todos los tickets
  const wsResumen = XLSX.utils.json_to_sheet(registrosFiltrados.map(t=>({
    Ticket: t.ticket,
    Almacen: t.almacen,
    Vendedor: t.vendedor,
    CantidadTotal: t.cantidadTotal,
    TotalVenta: t.total,
    Color: t.color,
    PrecioMasBarato: t.precioMasBarato,
    Promo: t.promoAmount !== null ? Number(t.promoAmount).toFixed(2) : ""
  })));
  XLSX.utils.book_append_sheet(wb, wsResumen, "Resumen Tickets");

  // Hoja 2: Resumen por colores (conteo total)
  const resumenColoresArr = Object.entries(conteoPorColor).map(([color,cantidad])=>({ Color: color, Cantidad: cantidad }));
  const wsColores = XLSX.utils.json_to_sheet(resumenColoresArr);
  XLSX.utils.book_append_sheet(wb, wsColores, "Resumen_Colores");

  // HOJAS POR VENDEDOR
  const wsAmarilloVend = XLSX.utils.json_to_sheet(topAmarilloVendedor.length ? topAmarilloVendedor : [{vendedor: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsAmarilloVend, "Amarillo_Vendedor");

  const wsAzulVend = XLSX.utils.json_to_sheet(topAzulVendedor.length ? topAzulVendedor : [{vendedor: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsAzulVend, "Azul_Vendedor");

  const wsRojoVend = XLSX.utils.json_to_sheet(topRojoVendedor.length ? topRojoVendedor : [{vendedor: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsRojoVend, "Rojo_Vendedor");

  const wsNaranjaVend = XLSX.utils.json_to_sheet(topNaranjaVendedor.length ? topNaranjaVendedor : [{vendedor: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsNaranjaVend, "Naranja_Vendedor");

  // HOJAS POR ALMACÉN
  const wsAmarilloAlm = XLSX.utils.json_to_sheet(topAmarilloAlmacen.length ? topAmarilloAlmacen : [{almacen: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsAmarilloAlm, "Amarillo_Almacen");

  const wsAzulAlm = XLSX.utils.json_to_sheet(topAzulAlmacen.length ? topAzulAlmacen : [{almacen: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsAzulAlm, "Azul_Almacen");

  const wsRojoAlm = XLSX.utils.json_to_sheet(topRojoAlmacen.length ? topRojoAlmacen : [{almacen: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsRojoAlm, "Rojo_Almacen");

  const wsNaranjaAlm = XLSX.utils.json_to_sheet(topNaranjaAlmacen.length ? topNaranjaAlmacen : [{almacen: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsNaranjaAlm, "Naranja_Almacen");

  XLSX.writeFile(wb, "Tickets_Mayor_Precio_Colores.xlsx");
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