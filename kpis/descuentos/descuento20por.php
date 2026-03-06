<?php

include_once '../funciones.php'; 

?>
<!DOCTYPE html>
<html lang="es">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
<head>
  <meta charset="UTF-8">
  <title>Tickets de Mayor Precio — INNOVACION MOVIL & TECNOLOGIA MOVIL</title>
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
        <a href="index.php" class="menu-link">
          <span class="logo-container">
            <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />

          </span>
          Atras
        </a>
      </li>
            </ul>
        </div>
    </nav>
</header>
 
<div class="container">
  <h1>📄 Tickets de Mayor Precio — INNOVACION MOVIL & TECNOLOGIA MOVIL</h1>
  <!-- SIMBOLOGÍA COMPACTA -->
  <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h3 style="margin-top: 0; color: #2f6fa6;">🎨 Simbología de Colores</h3>
    <table style="width: 100%; border: none; box-shadow: none;">
      <tr>
        <td style="background: #d0e7ff; padding: 10px; border: 2px solid #007bff; text-align: left;">
          <strong>🔵 AZUL:</strong> INNOVACIÓN MÓVIL - Cupón 20% válido (desde 1 producto)
        </td>
        <td style="background: #ffc0e3; padding: 10px; border: 2px solid #ff69b4; text-align: left;">
          <strong>🌸 ROSA:</strong> TECNOLOGÍA MÓVIL - Cupón 10% válido (desde 1 producto)
        </td>
      </tr>
      <tr>
        <td colspan="2" style="background: #f8d7da; padding: 10px; border: 2px solid #dc3545; text-align: left;">
          <strong>🔴 ROJO:</strong> Ticket sospechoso (mezcla de productos, descuento incorrecto >$1)
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
            <th>N1</th>
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
      <p id="detallesValidacion" style="background:#f0f0f0;padding:10px;border-radius:5px;font-size:12px;"></p>
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
    const n1Value = String(r[idxN1]||"").trim();
    
    // Filtrar INNOVACION MOVIL o TECNOLOGIA MOVIL
    if(n1Value === "INNOVACION MOVIL" || n1Value === "TECNOLOGIA MOVIL"){
      registros.push({
        n1: n1Value,
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
      metodos: [],
      tiposN1: new Set() // Para detectar mezclas
    };
    ticketsMap[key].total += isNaN(r.totalVenta) ? 0 : r.totalVenta;
    ticketsMap[key].productos.push({
      n1: r.n1,
      producto: r.producto,
      cantidad: r.cantidad,
      totalVenta: r.totalVenta,
      precioVenta: r.precioVenta
    });
    ticketsMap[key].metodos.push(r.metodoPago || "");
    ticketsMap[key].tiposN1.add(r.n1);
  }

  const ticketsArray = Object.values(ticketsMap);

  // Clasificar por color según las NUEVAS REGLAS
  for(const t of ticketsArray){
    // CANTIDAD TOTAL DE PRODUCTOS (sumar columna Cantidad)
    const cantidadTotal = t.productos.reduce((sum, p) => sum + (Number(p.cantidad) || 0), 0);
    
    // Buscar monto de CÓDIGO PROMOCIONAL
    let promoAmount = null;
    for(const m of t.metodos){
      const found = extractPromoAmount(m);
      if(found !== null){
        promoAmount = found;
        break;
      }
    }

    // Calcular total SIN el código promocional (total real de productos)
    const totalProductos = t.total;
    const totalSinPromo = promoAmount !== null ? totalProductos + promoAmount : totalProductos;

    // Verificar si es mezcla de productos
    const esMezcla = t.tiposN1.size > 1;
    
    // Determinar tipo predominante
    const esInnovacion = t.tiposN1.has("INNOVACION MOVIL");
    const esTecnologia = t.tiposN1.has("TECNOLOGIA MOVIL");

    // Calcular descuentos esperados
    const descuento20 = totalSinPromo * 0.20;
    const descuento10 = totalSinPromo * 0.10;
    
    // Margen de tolerancia ($1.00)
    const margen = 1.00;

    // Guardar info adicional para debug
    t.cantidadTotal = cantidadTotal;
    t.promoAmount = promoAmount;
    t.totalSinPromo = totalSinPromo;
    t.descuento20Esperado = descuento20;
    t.descuento10Esperado = descuento10;
    t.esMezcla = esMezcla;
    t.tipoProducto = esMezcla ? "MEZCLA" : (esInnovacion ? "INNOVACION MOVIL" : "TECNOLOGIA MOVIL");
    t.detallesValidacion = "";
    t.observaciones = "";

    // CLASIFICACIÓN POR PRIORIDAD:
    
    // 🔴 ROJO - Casos sospechosos
    if(esMezcla){
      // 1. Mezcla de productos INNOVACION y TECNOLOGIA
      t.color = "Rojo";
      t.detallesValidacion = "❌ Mezcla de productos INNOVACION MOVIL y TECNOLOGIA MOVIL (descuentos diferentes)";
    }
    else if(esInnovacion && promoAmount !== null){
      // 3. INNOVACION con código - verificar 20%
      const diferencia = Math.abs(promoAmount - descuento20);
      
      if(diferencia > margen){
        // ❌ Diferencia mayor a $1.00 = ROJO
        t.color = "Rojo";
        t.detallesValidacion = `❌ INNOVACION MOVIL con código promocional incorrecto. Esperado: $${descuento20.toFixed(2)} (20%), Aplicado: $${promoAmount.toFixed(2)}, Diferencia: $${diferencia.toFixed(2)}`;
      } else {
        // ✅ Diferencia menor o igual a $1.00 = AZUL con observación
        t.color = "Azul";
        if(diferencia > 0){
          t.detallesValidacion = `✅ INNOVACION MOVIL - Descuento 20% válido ($${promoAmount.toFixed(2)})`;
          t.observaciones = `⚠️ Diferencia de $${diferencia.toFixed(2)} respecto al 20% esperado ($${descuento20.toFixed(2)})`;
        } else {
          t.detallesValidacion = `✅ INNOVACION MOVIL - Descuento 20% exacto ($${promoAmount.toFixed(2)})`;
        }
      }
    }
    else if(esTecnologia && promoAmount !== null){
      // 4. TECNOLOGIA con código - verificar 10%
      const diferencia = Math.abs(promoAmount - descuento10);
      
      if(diferencia > margen){
        // ❌ Diferencia mayor a $1.00 = ROJO
        t.color = "Rojo";
        t.detallesValidacion = `❌ TECNOLOGIA MOVIL con código promocional incorrecto. Esperado: $${descuento10.toFixed(2)} (10%), Aplicado: $${promoAmount.toFixed(2)}, Diferencia: $${diferencia.toFixed(2)}`;
      } else {
        // ✅ Diferencia menor o igual a $1.00 = ROSA con observación
        t.color = "Rosa";
        if(diferencia > 0){
          t.detallesValidacion = `✅ TECNOLOGIA MOVIL - Descuento 10% válido ($${promoAmount.toFixed(2)})`;
          t.observaciones = `⚠️ Diferencia de $${diferencia.toFixed(2)} respecto al 10% esperado ($${descuento10.toFixed(2)})`;
        } else {
          t.detallesValidacion = `✅ TECNOLOGIA MOVIL - Descuento 10% exacto ($${promoAmount.toFixed(2)})`;
        }
      }
    }
    else {
      // Sin código promocional
      t.color = "Normal";
      t.detallesValidacion = "Sin código promocional aplicado";
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
        <th>Tipo Producto</th>
        <th>Cantidad Total</th>
        <th>Total Venta</th>
        <th>Color</th>
        <th>Observaciones</th>
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
    } else if (t.color === "Azul") {
      estiloFila = 'style="background:#d0e7ff;"';
      colorEmoji = "🔵";
    } else if (t.color === "Rosa") {
      estiloFila = 'style="background:#ffc0e3;"';
      colorEmoji = "🌸";
    } else {
      colorEmoji = "⚪";
    }

    html += `<tr ${estiloFila}>
      <td>${t.ticket}</td>
      <td>${t.almacen}</td>
      <td>${t.vendedor}</td>
      <td>${t.tipoProducto}</td>
      <td>${t.cantidadTotal}</td>
      <td>$${Number(t.total||0).toFixed(2)}</td>
      <td>${colorEmoji} ${t.color}</td>
      <td style="font-size:11px;">${t.observaciones || "-"}</td>
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
      <td>${p.n1}</td>
      <td>${p.producto}</td>
      <td>${p.cantidad}</td>
      <td>$${Number(p.precioVenta||0).toFixed(2)}</td>
      <td>$${Number(p.totalVenta||0).toFixed(2)}</td>
    </tr>`;
  }
  document.getElementById("modalBody").innerHTML = html;
  document.getElementById("cantidadTotal").innerText = `📦 Cantidad total de productos: ${tk.cantidadTotal}`;
  document.getElementById("promoInfo").innerText = tk.promoAmount !== null ? `🎟️ Monto CÓDIGO PROMOCIONAL aplicado: $${Number(tk.promoAmount).toFixed(2)}` : "🎟️ No se aplicó CÓDIGO PROMOCIONAL";
  document.getElementById("totalTicket").innerText = `💰 Total del ticket: $${Number(tk.total||0).toFixed(2)} | Total sin promo: $${Number(tk.totalSinPromo||0).toFixed(2)}`;
  
  let detallesHTML = `<strong>Validación:</strong><br>${tk.detallesValidacion}`;
  if(tk.observaciones){
    detallesHTML += `<br><br><strong>Observaciones:</strong><br>${tk.observaciones}`;
  }
  document.getElementById("detallesValidacion").innerHTML = detallesHTML;
  
  document.getElementById("modal").style.display = "flex";
}

function cerrarModal(){
  document.getElementById("modal").style.display = "none";
}

document.getElementById("btnDescargar").addEventListener('click', ()=>{
  const conteoPorColor = { "Azul":0, "Rosa":0, "Rojo":0, "Normal":0 };
  
  // CONTEO POR VENDEDOR
  const conteoAzulPorVendedor = {};
  const conteoRosaPorVendedor = {};
  const conteoRojoPorVendedor = {};

  // CONTEO POR ALMACÉN
  const conteoAzulPorAlmacen = {};
  const conteoRosaPorAlmacen = {};
  const conteoRojoPorAlmacen = {};

  for (const t of registrosFiltrados) {
    if(conteoPorColor[t.color] !== undefined) conteoPorColor[t.color]++;
    else conteoPorColor["Normal"]++;

    // Contar por VENDEDOR para cada color
    if (t.color === "Azul") {
      if (!conteoAzulPorVendedor[t.vendedor]) conteoAzulPorVendedor[t.vendedor] = 0;
      conteoAzulPorVendedor[t.vendedor]++;
      
      if (!conteoAzulPorAlmacen[t.almacen]) conteoAzulPorAlmacen[t.almacen] = 0;
      conteoAzulPorAlmacen[t.almacen]++;
    }
    if (t.color === "Rosa") {
      if (!conteoRosaPorVendedor[t.vendedor]) conteoRosaPorVendedor[t.vendedor] = 0;
      conteoRosaPorVendedor[t.vendedor]++;
      
      if (!conteoRosaPorAlmacen[t.almacen]) conteoRosaPorAlmacen[t.almacen] = 0;
      conteoRosaPorAlmacen[t.almacen]++;
    }
    if (t.color === "Rojo") {
      if (!conteoRojoPorVendedor[t.vendedor]) conteoRojoPorVendedor[t.vendedor] = 0;
      conteoRojoPorVendedor[t.vendedor]++;
      
      if (!conteoRojoPorAlmacen[t.almacen]) conteoRojoPorAlmacen[t.almacen] = 0;
      conteoRojoPorAlmacen[t.almacen]++;
    }
  }

  // Convertir a arrays y ordenar - VENDEDORES
  const topAzulVendedor = Object.entries(conteoAzulPorVendedor)
    .map(([vendedor, cantidad]) => ({ vendedor, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topRosaVendedor = Object.entries(conteoRosaPorVendedor)
    .map(([vendedor, cantidad]) => ({ vendedor, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topRojoVendedor = Object.entries(conteoRojoPorVendedor)
    .map(([vendedor, cantidad]) => ({ vendedor, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  // Convertir a arrays y ordenar - ALMACENES
  const topAzulAlmacen = Object.entries(conteoAzulPorAlmacen)
    .map(([almacen, cantidad]) => ({ almacen, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topRosaAlmacen = Object.entries(conteoRosaPorAlmacen)
    .map(([almacen, cantidad]) => ({ almacen, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  const topRojoAlmacen = Object.entries(conteoRojoPorAlmacen)
    .map(([almacen, cantidad]) => ({ almacen, cantidad }))
    .sort((a,b)=> b.cantidad - a.cantidad);

  // Crear libro de Excel
  const wb = XLSX.utils.book_new();

  // Hoja 1: Resumen general de todos los tickets
  const wsResumen = XLSX.utils.json_to_sheet(registrosFiltrados.map(t=>({
    Ticket: t.ticket,
    Almacen: t.almacen,
    Vendedor: t.vendedor,
    TipoProducto: t.tipoProducto,
    CantidadTotal: t.cantidadTotal,
    TotalVenta: t.total,
    TotalSinPromo: t.totalSinPromo,
    Color: t.color,
    Promo: t.promoAmount !== null ? Number(t.promoAmount).toFixed(2) : "",
    Validacion: t.detallesValidacion,
    Observaciones: t.observaciones || ""
  })));
  XLSX.utils.book_append_sheet(wb, wsResumen, "Resumen Tickets");

  // Hoja 2: Resumen por colores (conteo total)
  const resumenColoresArr = Object.entries(conteoPorColor).map(([color,cantidad])=>({ Color: color, Cantidad: cantidad }));
  const wsColores = XLSX.utils.json_to_sheet(resumenColoresArr);
  XLSX.utils.book_append_sheet(wb, wsColores, "Resumen_Colores");

  // HOJAS POR VENDEDOR
  const wsAzulVend = XLSX.utils.json_to_sheet(topAzulVendedor.length ? topAzulVendedor : [{vendedor: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsAzulVend, "Azul_Vendedor");

  const wsRosaVend = XLSX.utils.json_to_sheet(topRosaVendedor.length ? topRosaVendedor : [{vendedor: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsRosaVend, "Rosa_Vendedor");

  const wsRojoVend = XLSX.utils.json_to_sheet(topRojoVendedor.length ? topRojoVendedor : [{vendedor: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsRojoVend, "Rojo_Vendedor");

  // HOJAS POR ALMACÉN
  const wsAzulAlm = XLSX.utils.json_to_sheet(topAzulAlmacen.length ? topAzulAlmacen : [{almacen: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsAzulAlm, "Azul_Almacen");

  const wsRosaAlm = XLSX.utils.json_to_sheet(topRosaAlmacen.length ? topRosaAlmacen : [{almacen: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsRosaAlm, "Rosa_Almacen");

  const wsRojoAlm = XLSX.utils.json_to_sheet(topRojoAlmacen.length ? topRojoAlmacen : [{almacen: "Sin datos", cantidad: 0}]);
  XLSX.utils.book_append_sheet(wb, wsRojoAlm, "Rojo_Almacen");

  XLSX.writeFile(wb, "Tickets_Innovacion_Tecnologia_Movil.xlsx");
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