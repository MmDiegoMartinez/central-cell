<?php
include_once '../funciones.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tickets de Mayor Precio — IM & TM</title>
  <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
  <style>
body{font-family:Arial,sans-serif;margin:18px;background:#f7f7f7;color:#222}
h1{margin-top:0}
.controls{display:flex;gap:12px;align-items:center;margin-bottom:12px;flex-wrap:wrap}
button.btn{background:#007bff;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer}
button.btn:disabled{background:#999;cursor:not-allowed}
table{border-collapse:collapse;width:100%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.07);margin-bottom:20px}
th,td{padding:8px 6px;border:1px solid #e1e1e1;text-align:center;font-size:13px}
th{background:#2f6fa6;color:#fff;position:sticky;top:0;z-index:1}
.note{font-size:13px;color:#333;margin-top:6px}

/* ── Filtro de tipo de ticket ── */
.tipo-tabs{display:flex;gap:0;margin-bottom:16px;border-radius:10px;overflow:hidden;
           border:1px solid #ddd;width:fit-content;box-shadow:0 1px 4px rgba(0,0,0,.08)}
.tipo-tab{padding:10px 22px;cursor:pointer;font-weight:600;font-size:13px;
          background:#f0f4f8;color:#555;border:none;transition:background .2s;white-space:nowrap}
.tipo-tab:not(:last-child){border-right:1px solid #ddd}
.tipo-tab.active-mix{background:linear-gradient(135deg,#f5576c,#4facfe);color:#fff}
.tipo-tab.active-im {background:linear-gradient(135deg,#f5576c,#f093fb);color:#fff}
.tipo-tab.active-tm {background:linear-gradient(135deg,#4facfe,#00b4d8);color:#fff}

/* Badges en tabla */
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;color:#fff}
.badge-im{background:#f5576c}.badge-tm{background:#4facfe}.badge-mix{background:linear-gradient(135deg,#f5576c,#4facfe)}

/* Modal */
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;
               background:rgba(0,0,0,.6);justify-content:center;align-items:center;z-index:9999}
.modal-box{background:#fff;padding:24px;border-radius:12px;width:72%;max-height:82%;overflow:auto}
.modal-box h3{margin-top:0}
.modal-box table{font-size:13px}
.modal-box th{background:#2f6fa6;color:#fff}
  </style>
  <link rel="stylesheet" href="estilos.css">
</head>
<body>
<header>
  <nav>
    <div class="nav-inner">
      <label class="bar-menu">
        <input type="checkbox" id="menu-check">
        <span class="top"></span><span class="middle"></span><span class="bottom"></span>
      </label>
      <ul id="nav-menu">
        <li>
          <a href="index.php" class="menu-link">
            <span class="logo-container">
              <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25"/>
            </span>
            Home
          </a>
        </li>
      </ul>
    </div>
  </nav>
</header>

<div class="container">
  <h1>📄 Tickets de Mayor Precio — IM & TM</h1>

  <div class="controls">
    <div class="file-upload">
      <input id="inputFile" type="file" accept=".xlsx,.xls" style="display:none;"/>
      <button class="boton" id="fileButton" type="button">
        <div class="contenedorCarpeta">
          <div class="folder folder_one"></div><div class="folder folder_two"></div>
          <div class="folder folder_three"></div><div class="folder folder_four"></div>
        </div>
        <div class="active_line"></div>
        <span class="text">Seleccionar Archivo</span>
      </button>
    </div>
    <button id="btnProcesar"  class="btn" disabled>Procesar Archivo</button>
    <button id="btnDescargar" class="btn" style="display:none">⬇ Descargar Resumen Excel</button>
  </div>

  <div id="estado" class="note"></div>

  <div class="center-container">
    <div id="loader" class="loader-container" style="display:none;">
      <div class="cloud front"><span class="left-front"></span><span class="right-front"></span></div>
      <span class="sun sunshine"></span><span class="sun"></span>
      <div class="cloud back"><span class="left-back"></span><span class="right-back"></span></div>
    </div>
  </div>

  <!-- ── Selector de tipo de ticket ── -->
  <div class="tipo-tabs" id="tipoTabs" style="display:none">
    <button class="tipo-tab active-mix" data-tipo="MIX" onclick="cambiarTipo('MIX',this)">
      🔀 Tickets Mixtos <span id="cntMIX" class="badge badge-mix">0</span>
    </button>
    <button class="tipo-tab" data-tipo="IM" onclick="cambiarTipo('IM',this)">
      📱 Solo IM <span id="cntIM" class="badge badge-im">0</span>
    </button>
    <button class="tipo-tab" data-tipo="TM" onclick="cambiarTipo('TM',this)">
      📲 Solo TM <span id="cntTM" class="badge badge-tm">0</span>
    </button>
  </div>

  <div id="tablaContainer"></div>
</div>

<!-- ── Modal detalle ticket ── -->
<div class="modal-overlay" id="modal">
  <div class="modal-box">
    <h3 id="modalTitulo"></h3>
    <table border="1" width="100%" cellspacing="0" cellpadding="5">
      <thead>
        <tr><th>Depto</th><th>Producto</th><th>Cantidad</th><th>Total Venta</th></tr>
      </thead>
      <tbody id="modalBody"></tbody>
    </table>
    <p id="totalTicket" style="font-weight:700;margin-top:10px"></p>
    <div style="display:flex;gap:8px;margin-top:10px">
      <button id="btnDescargarTicket" class="btn">⬇ Descargar este Ticket</button>
      <button class="btn" style="background:#888" onclick="cerrarModal()">Cerrar</button>
    </div>
  </div>
</div>

<script>
/* ── Estado ── */
let todosTickets = [];   // todos los tickets procesados
let tipoActual   = 'MIX';
let ticketActual = null;

/* ── DOM ── */
const inputFile   = document.getElementById('inputFile');
const btnProcesar = document.getElementById('btnProcesar');
const btnDescargar= document.getElementById('btnDescargar');
const estado      = document.getElementById('estado');

document.getElementById('fileButton').addEventListener('click', ()=> inputFile.click());
inputFile.addEventListener('change', e=>{ btnProcesar.disabled = !e.target.files.length; });
btnProcesar.addEventListener('click', ()=>{ const f=inputFile.files[0]; if(f) procesarArchivo(f); });
btnDescargar.addEventListener('click', descargarResumen);
document.getElementById('btnDescargarTicket').addEventListener('click', descargarTicket);

/* ══════════════════════════════════════════════════
   PROCESAR
══════════════════════════════════════════════════ */
function procesarArchivo(file){
  estado.innerText='📊 Cargando archivo...';
  document.getElementById('loader').style.display='flex';
  const reader=new FileReader();
  reader.onload=e=>{
    const wb=XLSX.read(new Uint8Array(e.target.result),{type:'array'});
    const json=XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]],{header:1});
    if(json.length<2){ estado.innerText='⚠️ Archivo sin datos.'; document.getElementById('loader').style.display='none'; return; }
    analizarDatos(json);
    document.getElementById('loader').style.display='none';
  };
  reader.readAsArrayBuffer(file);
}

function analizarDatos(json){
  estado.innerText='🧮 Procesando...';
  const enc=json[0];

  /* Detectar índices por nombre de columna */
  const col=name=>enc.indexOf(name);
  const idxN1      = col("N1");
  const idxTicket  = col("NoMov");
  const idxVendedor= col("Vendedor");
  const idxAlmacen = col("Almacen");
  const idxProd    = col("ProdConcat");
  const idxCant    = col("Cantidad");
  const idxTotal   = col("TotalVenta");

  /* Agrupar por ticket+almacén */
  const ticketsMap={};
  for(let i=1;i<json.length;i++){
    const r=json[i];
    const n1=String(r[idxN1]||'').trim();
    if(n1!=='INNOVACION MOVIL'&&n1!=='TECNOLOGIA MOVIL') continue;
    const depto = n1==='INNOVACION MOVIL' ? 'IM' : 'TM';
    const key=`${String(r[idxTicket]||'').trim()}__${String(r[idxAlmacen]||'').trim()}`;
    if(!ticketsMap[key]) ticketsMap[key]={
      ticket:  String(r[idxTicket]||'').trim(),
      almacen: String(r[idxAlmacen]||'').trim(),
      vendedor:String(r[idxVendedor]||'').trim(),
      total:0, tieneIM:false, tieneTM:false, productos:[]
    };
    const tk=ticketsMap[key];
    tk.total+=Number(r[idxTotal]||0);
    if(depto==='IM') tk.tieneIM=true; else tk.tieneTM=true;
    tk.productos.push({
      depto,
      producto:String(r[idxProd]||'').trim(),
      cantidad:Number(r[idxCant]||0),
      totalVenta:Number(r[idxTotal]||0)
    });
  }

  /* Clasificar cada ticket */
  todosTickets=Object.values(ticketsMap).map(tk=>{
    tk.tipo = (tk.tieneIM && tk.tieneTM) ? 'MIX' : (tk.tieneIM ? 'IM' : 'TM');
    return tk;
  }).sort((a,b)=>b.total-a.total);

  /* Contadores:
     MIX = solo mixtos | IM = puros IM + mixtos | TM = puros TM + mixtos */
  document.getElementById('cntMIX').innerText = todosTickets.filter(tk=>tk.tipo==='MIX').length;
  document.getElementById('cntIM').innerText  = todosTickets.filter(tk=>tk.tieneIM).length;
  document.getElementById('cntTM').innerText  = todosTickets.filter(tk=>tk.tieneTM).length;

  document.getElementById('tipoTabs').style.display='';
  btnDescargar.style.display='inline-block';
  tipoActual='MIX';

  /* Activar tab MIX visualmente */
  document.querySelectorAll('.tipo-tab').forEach(el=>{
    el.classList.remove('active-mix','active-im','active-tm');
    if(el.dataset.tipo==='MIX') el.classList.add('active-mix');
  });

  mostrarTabla();
  estado.innerText=`✅ ${todosTickets.length} tickets · Mixtos: ${todosTickets.filter(t=>t.tipo==='MIX').length} · Con IM: ${todosTickets.filter(t=>t.tieneIM).length} · Con TM: ${todosTickets.filter(t=>t.tieneTM).length}`;
}

/* ══════════════════════════════════════════════════
   TABS
══════════════════════════════════════════════════ */
function cambiarTipo(tipo, el){
  tipoActual=tipo;
  document.querySelectorAll('.tipo-tab').forEach(t=>t.classList.remove('active-mix','active-im','active-tm'));
  el.classList.add(`active-${tipo.toLowerCase()}`);
  mostrarTabla();
}

/* ══════════════════════════════════════════════════
   TABLA
══════════════════════════════════════════════════ */
function mostrarTabla(){
  /* IM y TM incluyen los mixtos; filtrar productos por depto en el modal */
  /* Filtrar y ordenar por el total del depto activo, no el total general */
  const filtrados = (tipoActual==='MIX'
    ? todosTickets.filter(tk=>tk.tipo==='MIX')
    : todosTickets.filter(tk=>tipoActual==='IM' ? tk.tieneIM : tk.tieneTM)
  ).map(tk=>{
    const prodsDepto = tipoActual==='MIX'
      ? tk.productos
      : tk.productos.filter(p=>p.depto===tipoActual);
    return {...tk, _totalDepto: prodsDepto.reduce((s,p)=>s+p.totalVenta,0)};
  }).sort((a,b)=>b._totalDepto - a._totalDepto);
  if(!filtrados.length){
    document.getElementById('tablaContainer').innerHTML='<p class="note">Sin tickets de este tipo.</p>';
    return;
  }

  /* Badge de tipo */
  const badgeTipo=(tipo)=>{
    if(tipo==='MIX')  return `<span class="badge badge-mix">🔀 Mixto</span>`;
    if(tipo==='IM')   return `<span class="badge badge-im">📱 IM</span>`;
    return                  `<span class="badge badge-tm">📲 TM</span>`;
  };

  let html=`<table><thead><tr>
    <th>Tipo</th><th>Ticket</th><th>Almacén</th><th>Vendedor</th>
    <th>Total Venta</th><th>Productos</th><th>Acción</th>
  </tr></thead><tbody>`;

  filtrados.forEach(tk=>{
    /* _totalDepto ya calculado en el paso de ordenamiento */
    const prodsVista = tipoActual==='MIX'
      ? tk.productos
      : tk.productos.filter(p=>p.depto===tipoActual);
    const totalVista = tk._totalDepto;
    html+=`<tr>
      <td>${badgeTipo(tk.tipo)}</td>
      <td>${esc(tk.ticket)}</td>
      <td>${esc(tk.almacen)}</td>
      <td>${esc(tk.vendedor)}</td>
      <td>$${totalVista.toFixed(2)}</td>
      <td>${prodsVista.length}</td>
      <td><button class="btn" onclick='verTicket("${esc(tk.ticket)}","${esc(tk.almacen)}","${tipoActual}")'>Ver detalles</button></td>
    </tr>`;
  });
  html+='</tbody></table>';
  document.getElementById('tablaContainer').innerHTML=html;
}

/* ══════════════════════════════════════════════════
   MODAL
══════════════════════════════════════════════════ */
function verTicket(ticket, almacen, depto){
  const tk=todosTickets.find(x=>x.ticket===ticket&&x.almacen===almacen);
  if(!tk) return alert('Ticket no encontrado.');
  /* depto puede ser 'IM', 'TM' o 'MIX'/'undefined' → mostrar todos */
  const deptoFiltro = depto || 'MIX';
  ticketActual={...tk, deptoVista: deptoFiltro};

  const tipoLabel = tk.tipo==='MIX' ? '🔀 Mixto' : (tk.tipo==='IM' ? '📱 Solo IM' : '📲 Solo TM');
  document.getElementById('modalTitulo').innerHTML=
    `Ticket <strong>${esc(tk.ticket)}</strong> — ${esc(tk.almacen)} (${esc(tk.vendedor)}) &nbsp;<span style="font-size:13px;color:#888">${tipoLabel}</span>`;

  let html='';
  /* Filtrar productos según el depto de la pestaña activa */
  const prodsModal = deptoFiltro==='MIX'
    ? [...tk.productos].sort((a,b)=>a.depto.localeCompare(b.depto))
    : tk.productos.filter(p=>p.depto===deptoFiltro);
  const prods=prodsModal;
  prods.forEach(p=>{
    const badge=p.depto==='IM'
      ?`<span class="badge badge-im">📱 IM</span>`
      :`<span class="badge badge-tm">📲 TM</span>`;
    html+=`<tr>
      <td>${badge}</td>
      <td style="text-align:left">${esc(p.producto)}</td>
      <td>${p.cantidad}</td>
      <td>$${p.totalVenta.toFixed(2)}</td>
    </tr>`;
  });
  document.getElementById('modalBody').innerHTML=html;
  document.getElementById('totalTicket').innerText=`💰 Total del ticket: $${tk.total.toFixed(2)}`;
  document.getElementById('modal').style.display='flex';
}

function cerrarModal(){
  document.getElementById('modal').style.display='none';
}

/* Cerrar modal al hacer clic fuera */
document.getElementById('modal').addEventListener('click', e=>{ if(e.target===document.getElementById('modal')) cerrarModal(); });

/* ══════════════════════════════════════════════════
   DESCARGAS
══════════════════════════════════════════════════ */
function descargarResumen(){
  /* IM y TM incluyen los mixtos; filtrar productos por depto en el modal */
  /* Filtrar y ordenar por el total del depto activo, no el total general */
  const filtrados = (tipoActual==='MIX'
    ? todosTickets.filter(tk=>tk.tipo==='MIX')
    : todosTickets.filter(tk=>tipoActual==='IM' ? tk.tieneIM : tk.tieneTM)
  ).map(tk=>{
    const prodsDepto = tipoActual==='MIX'
      ? tk.productos
      : tk.productos.filter(p=>p.depto===tipoActual);
    return {...tk, _totalDepto: prodsDepto.reduce((s,p)=>s+p.totalVenta,0)};
  }).sort((a,b)=>b._totalDepto - a._totalDepto);
  const wb=XLSX.utils.book_new();
  const header=['Tipo','Ticket','Almacen','Vendedor','TotalVenta','#Productos'];
  const aoa=[header,...filtrados.map(tk=>[
    tk.tipo==='MIX'?'Mixto':(tk.tipo==='IM'?'Innovación Móvil':'Tecnología Móvil'),
    tk.ticket, tk.almacen, tk.vendedor, tk.total, tk.productos.length
  ])];
  XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(aoa),`Tickets ${tipoActual}`);
  XLSX.writeFile(wb,`Tickets_${tipoActual}_Mayor_Precio.xlsx`);
}

function descargarTicket(){
  if(!ticketActual) return alert('No hay ticket seleccionado.');
  const prodsDl = ticketActual.deptoVista==='MIX'
    ? ticketActual.productos
    : ticketActual.productos.filter(p=>p.depto===ticketActual.deptoVista);
  const wb=XLSX.utils.book_new();
  const header=['Departamento','Producto','Cantidad','TotalVenta'];
  const aoa=[header,...prodsDl.map(p=>[
    p.depto==='IM'?'Innovación Móvil':'Tecnología Móvil',
    p.producto, p.cantidad, p.totalVenta
  ])];
  aoa.push(['','Total','',prodsDl.reduce((s,p)=>s+p.totalVenta,0)]);
  XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(aoa),`Ticket_${ticketActual.ticket}`);
  XLSX.writeFile(wb,`Ticket_${ticketActual.ticket}_${ticketActual.almacen}.xlsx`);
}

/* ── Helpers ── */
function esc(s){ return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

/* ── Menú hamburguesa ── */
document.getElementById('menu-check').addEventListener('change',function(){
  const m=document.getElementById('nav-menu');
  m.style.opacity=this.checked?'1':'0';
  m.style.visibility=this.checked?'visible':'hidden';
  m.style.pointerEvents=this.checked?'auto':'none';
});
</script>
</body>
</html>