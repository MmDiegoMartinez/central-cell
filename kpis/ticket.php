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

  <link rel="stylesheet" href="../styles.css">

  
  <style>
    /* ── Contenedor de controles ── */
    .controls{
      display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-md);
      margin-top:var(--space-lg);
    }

    /* ── Botón de subir archivo (reemplaza la animación de carpetas) ── */
    .file-upload{display:flex;}
    .boton{
      display:inline-flex;align-items:center;gap:8px;
      padding:10px 18px;border-radius:var(--radius-lg);
      background:var(--surface-container-high);
      border:1px solid var(--outline-variant);
      color:var(--on-surface);font-size:14px;font-weight:600;
      transition:background .15s ease, border-color .15s ease;
    }
    .boton:hover{background:var(--surface-container-highest);border-color:var(--primary);}
    .boton .material-symbols-outlined{font-size:20px;color:var(--primary);}

    /* ── Mensaje / nota de estado ── */
    .note{
      margin-top:var(--space-md);
      font-size:14px;color:var(--on-surface-variant);
      min-height:20px;
      display:flex;align-items:center;gap:6px;
    }
    .note .material-symbols-outlined{font-size:18px;color:var(--primary);}

    /* ── Loader simple (reemplaza la animación de sol/nube) ── */
    .center-container{display:flex;justify-content:center;}
    .loader-container{
      display:flex;align-items:center;gap:var(--space-sm);
      padding:var(--space-md) 0;
      color:var(--primary);font-size:14px;font-weight:600;
    }
    .loader-container .material-symbols-outlined{
      font-size:22px;animation:spin 1s linear infinite;
    }
    @keyframes spin{ from{transform:rotate(0deg);} to{transform:rotate(360deg);} }

    /* ── Tabs de tipo de ticket ── */
    .tipo-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-top:var(--space-lg);}
    .tipo-tab{
      display:inline-flex;align-items:center;gap:8px;
      padding:10px 16px;border-radius:var(--radius-lg);
      border:1px solid var(--outline-variant);
      background:var(--surface-container-lowest);
      color:var(--on-surface-variant);font-size:14px;font-weight:600;
      transition:background .15s ease,border-color .15s ease,color .15s ease;
    }
    .tipo-tab .material-symbols-outlined{font-size:18px;}
    .tipo-tab:hover{border-color:var(--primary);color:var(--primary);}
    .tipo-tab.active-mix{background:var(--primary-fixed);border-color:var(--primary);color:var(--on-primary-fixed);}
    .tipo-tab.active-im {background:var(--primary-container);border-color:var(--primary);color:var(--on-primary-container);}
    .tipo-tab.active-tm {background:var(--surface-container-high);border-color:var(--tertiary);color:var(--tertiary);}

    /* ── Badges ── */
    .badge{
      display:inline-flex;align-items:center;gap:4px;
      padding:3px 10px;border-radius:var(--radius-full);
      font-size:11px;font-weight:700;
    }
    .badge .material-symbols-outlined{font-size:14px;}
    .badge-mix{background:var(--primary-fixed);color:var(--on-primary-fixed);}
    .badge-im {background:var(--primary-container);color:var(--on-primary-container);}
    .badge-tm {background:var(--surface-container-high);color:var(--tertiary);}

    /* ── Tabla de tickets ── */
    #tablaContainer{margin-top:var(--space-lg);overflow-x:auto;}
    #tablaContainer table{width:100%;border-collapse:collapse;font-size:13px;}
    #tablaContainer thead th{
      text-align:center;padding:10px 12px;white-space:nowrap;
      background:var(--surface-container-high);
      color:var(--on-surface-variant);
      font-size:11px;text-transform:uppercase;letter-spacing:0.04em;
      border-bottom:1px solid var(--outline-variant);
    }
    #tablaContainer tbody td{
      padding:9px 12px;text-align:center;white-space:nowrap;
      border-bottom:1px solid var(--outline-variant);
      color:var(--on-surface);
    }
    #tablaContainer tbody tr:hover{background:var(--surface-container-low);}
    #tablaContainer .btn{padding:6px 12px;font-size:12px;}

    /* ── Modal de detalle ── */
    .modal-overlay{
      display:none;position:fixed;inset:0;z-index:1000;
      background:rgba(17,28,45,0.5);backdrop-filter:blur(4px);
      align-items:center;justify-content:center;padding:var(--space-md);
    }
    .modal-box{
      background:var(--surface-container-lowest);
      border-radius:var(--radius-xl);
      padding:var(--space-xl);
      max-width:640px;width:100%;max-height:85vh;overflow-y:auto;
      box-shadow:0 20px 60px rgba(12,18,26,0.25);
    }
    .modal-box h3{margin:0 0 var(--space-md);font-size:17px;color:var(--on-surface);}
    .modal-box table{width:100%;border-collapse:collapse;font-size:13px;}
    .modal-box thead th{
      text-align:center;padding:8px 10px;
      background:var(--surface-container-high);
      color:var(--on-surface-variant);
      font-size:11px;text-transform:uppercase;letter-spacing:0.04em;
    }
    .modal-box tbody td{
      padding:8px 10px;text-align:center;
      border-bottom:1px solid var(--outline-variant);
      color:var(--on-surface);
    }
    #totalTicket{
      display:flex;align-items:center;gap:6px;
      font-weight:700;margin-top:var(--space-md);color:var(--on-surface);
    }
    #totalTicket .material-symbols-outlined{font-size:18px;color:var(--secondary);}
  </style>
</head>
<body>

<!-- TOP HEADER -->
<header class="topheader">
  <div class="topheader-left">
    <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="26" height="26" style="border-radius:6px;">
    <h2 class="text-headline-sm">Tickets de Mayor Precio — IM &amp; TM</h2>
  </div>
  <nav class="topnav">
    <a href="../garantias/validador/validador.php">Home</a>
    <a href="modulos.html">Panel de Herramientas</a>
  </nav>
</header>

<div class="main" style="margin-left:0;">
  <div class="container">

    <div class="lesson">
      <div class="lesson-body">
        <div class="eyebrow">Tickets destacados</div>
        <h1 class="text-headline-lg" style="margin:8px 0 4px;display:flex;align-items:center;gap:10px;">
          <span class="material-symbols-outlined" style="font-size:30px;color:var(--primary);">receipt_long</span>
          Tickets de Mayor Precio — IM &amp; TM
        </h1>
        <p class="text-body-md" style="color:var(--on-surface-variant);margin:0;">
          Sube tu archivo Excel para ver los tickets con mayor monto, separados por tipo
        </p>

        <div class="controls">
          <div class="file-upload">
            <input id="inputFile" type="file" accept=".xlsx,.xls" style="display:none;"/>
            <button class="boton" id="fileButton" type="button">
              <span class="material-symbols-outlined">attach_file</span>
              <span class="text">Seleccionar Archivo</span>
            </button>
          </div>
          <button id="btnProcesar" class="btn btn-primary" disabled>
            <span class="material-symbols-outlined">play_arrow</span>
            Procesar Archivo
          </button>
          <button id="btnDescargar" class="btn btn-secondary" style="display:none">
            <span class="material-symbols-outlined">download</span>
            Descargar Resumen Excel
          </button>
        </div>

        <div id="estado" class="note"></div>

        <div class="center-container">
          <div id="loader" class="loader-container" style="display:none;">
            <span class="material-symbols-outlined">progress_activity</span>
            <span>Procesando archivo...</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Selector de tipo de ticket ── -->
    <div class="tipo-tabs" id="tipoTabs" style="display:none">
      <button class="tipo-tab active-mix" data-tipo="MIX" onclick="cambiarTipo('MIX',this)">
        <span class="material-symbols-outlined">call_split</span>
        Tickets Mixtos <span id="cntMIX" class="badge badge-mix">0</span>
      </button>
      <button class="tipo-tab" data-tipo="IM" onclick="cambiarTipo('IM',this)">
        <span class="material-symbols-outlined">smartphone</span>
        Solo IM <span id="cntIM" class="badge badge-im">0</span>
      </button>
      <button class="tipo-tab" data-tipo="TM" onclick="cambiarTipo('TM',this)">
        <span class="material-symbols-outlined">devices</span>
        Solo TM <span id="cntTM" class="badge badge-tm">0</span>
      </button>
    </div>

    <div id="tablaContainer"></div>

  </div>

 
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
    <p id="totalTicket"></p>
    <div style="display:flex;gap:8px;margin-top:10px">
      <button id="btnDescargarTicket" class="btn btn-secondary">
        <span class="material-symbols-outlined">download</span>
        Descargar este Ticket
      </button>
      <button class="btn btn-outline" onclick="cerrarModal()">
        <span class="material-symbols-outlined">close</span>
        Cerrar
      </button>
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
  estado.innerHTML='<span class="material-symbols-outlined">bar_chart</span> Cargando archivo...';
  document.getElementById('loader').style.display='flex';
  const reader=new FileReader();
  reader.onload=e=>{
    const wb=XLSX.read(new Uint8Array(e.target.result),{type:'array'});
    const json=XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]],{header:1});
    if(json.length<2){ estado.innerHTML='<span class="material-symbols-outlined">warning</span> Archivo sin datos.'; document.getElementById('loader').style.display='none'; return; }
    analizarDatos(json);
    document.getElementById('loader').style.display='none';
  };
  reader.readAsArrayBuffer(file);
}

function analizarDatos(json){
  estado.innerHTML='<span class="material-symbols-outlined">calculate</span> Procesando...';
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
  btnDescargar.style.display='inline-flex';
  tipoActual='MIX';

  /* Activar tab MIX visualmente */
  document.querySelectorAll('.tipo-tab').forEach(el=>{
    el.classList.remove('active-mix','active-im','active-tm');
    if(el.dataset.tipo==='MIX') el.classList.add('active-mix');
  });

  mostrarTabla();
  estado.innerHTML=`<span class="material-symbols-outlined">check_circle</span> ${todosTickets.length} tickets · Mixtos: ${todosTickets.filter(t=>t.tipo==='MIX').length} · Con IM: ${todosTickets.filter(t=>t.tieneIM).length} · Con TM: ${todosTickets.filter(t=>t.tieneTM).length}`;
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
    if(tipo==='MIX')  return `<span class="badge badge-mix"><span class="material-symbols-outlined">call_split</span>Mixto</span>`;
    if(tipo==='IM')   return `<span class="badge badge-im"><span class="material-symbols-outlined">smartphone</span>IM</span>`;
    return                  `<span class="badge badge-tm"><span class="material-symbols-outlined">devices</span>TM</span>`;
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
      <td><button class="btn btn-outline" onclick='verTicket("${esc(tk.ticket)}","${esc(tk.almacen)}","${tipoActual}")'>Ver detalles</button></td>
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

  const tipoLabel = tk.tipo==='MIX'
    ? '<span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">call_split</span> Mixto'
    : (tk.tipo==='IM'
        ? '<span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">smartphone</span> Solo IM'
        : '<span class="material-symbols-outlined" style="font-size:15px;vertical-align:-3px;">devices</span> Solo TM');
  document.getElementById('modalTitulo').innerHTML=
    `Ticket <strong>${esc(tk.ticket)}</strong> — ${esc(tk.almacen)} (${esc(tk.vendedor)}) &nbsp;<span style="font-size:13px;color:var(--on-surface-variant)">${tipoLabel}</span>`;

  let html='';
  /* Filtrar productos según el depto de la pestaña activa */
  const prodsModal = deptoFiltro==='MIX'
    ? [...tk.productos].sort((a,b)=>a.depto.localeCompare(b.depto))
    : tk.productos.filter(p=>p.depto===deptoFiltro);
  const prods=prodsModal;
  prods.forEach(p=>{
    const badge=p.depto==='IM'
      ?`<span class="badge badge-im"><span class="material-symbols-outlined">smartphone</span>IM</span>`
      :`<span class="badge badge-tm"><span class="material-symbols-outlined">devices</span>TM</span>`;
    html+=`<tr>
      <td>${badge}</td>
      <td style="text-align:left">${esc(p.producto)}</td>
      <td>${p.cantidad}</td>
      <td>$${p.totalVenta.toFixed(2)}</td>
    </tr>`;
  });
  document.getElementById('modalBody').innerHTML=html;
  document.getElementById('totalTicket').innerHTML=`<span class="material-symbols-outlined">payments</span> Total del ticket: $${tk.total.toFixed(2)}`;
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
</script>
</body>
</html>