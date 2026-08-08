<?php
include_once '../funciones.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Productos Más Vendidos — IM & TM</title>
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
  }

  /* ── Loader simple (reemplaza la animación de sol/nube) ── */
  .loader-container{
    display:flex;align-items:center;gap:var(--space-sm);
    padding:var(--space-md) 0;
    color:var(--primary);font-size:14px;font-weight:600;
  }
  .loader-container .material-symbols-outlined{
    font-size:22px;animation:spin 1s linear infinite;
  }
  @keyframes spin{ from{transform:rotate(0deg);} to{transform:rotate(360deg);} }

  /* ── Barra de filtros ── */
  .filter-bar{
    display:flex;flex-wrap:wrap;gap:var(--space-md);
    margin-top:var(--space-lg);
    padding:var(--space-md) var(--space-lg);
    background:var(--surface-container-low);
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
  }
  .filter-bar label{
    display:flex;flex-direction:column;gap:4px;
    font-size:12px;font-weight:600;color:var(--on-surface-variant);
    text-transform:uppercase;letter-spacing:0.03em;
  }
  .filter-bar select{
    padding:8px 12px;border-radius:var(--radius);
    border:1px solid var(--outline-variant);
    background:var(--surface-container-lowest);
    color:var(--on-surface);font-size:13px;font-weight:500;
    text-transform:none;letter-spacing:normal;
    cursor:pointer;min-width:160px;
  }
  .filter-bar select:focus-visible{outline:2px solid var(--primary);outline-offset:1px;}

  /* ── Tabla de resultados ── */
  #tablesContainer{margin-top:var(--space-lg);overflow-x:auto;}
  #tablesContainer table{width:100%;border-collapse:collapse;font-size:13px;}
  #tablesContainer thead th{
    text-align:center;padding:10px 12px;white-space:nowrap;
    background:var(--surface-container-high);
    color:var(--on-surface-variant);
    font-size:11px;text-transform:uppercase;letter-spacing:0.04em;
    border-bottom:1px solid var(--outline-variant);
  }
  #tablesContainer thead th:first-child{text-align:left;}
  #tablesContainer tbody td{
    padding:9px 12px;text-align:center;
    border-bottom:1px solid var(--outline-variant);
    color:var(--on-surface);
  }
  #tablesContainer tbody tr:hover{background:var(--surface-container-low);}
  #tablesContainer tbody tr.total-row{
    background:var(--surface-container-high);
    font-weight:700;color:var(--on-surface);
  }
  #tablesContainer tbody tr.total-row td{border-bottom:none;}

  /* ── Badges de departamento (reemplazan los colores hex inline) ── */
  .badge-depto{
    display:inline-flex;align-items:center;justify-content:center;
    padding:2px 10px;border-radius:var(--radius-full);
    font-size:11px;font-weight:700;
  }
  .badge-depto.im{background:var(--primary-container);color:var(--on-primary-container);}
  .badge-depto.tm{background:var(--surface-container-high);color:var(--tertiary);}
</style>
</head>
<body>

<!-- TOP HEADER -->
<header class="topheader">
  <div class="topheader-left">
    <img src="../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo" width="26" height="26" style="border-radius:6px;">
    <h2 class="text-headline-sm">Productos Más Vendidos — IM &amp; TM</h2>
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
        <div class="eyebrow">Ranking de productos</div>
        <h1 class="text-headline-lg" style="margin:8px 0 4px;display:flex;align-items:center;gap:10px;">
          <span class="material-symbols-outlined" style="font-size:30px;color:var(--primary);">leaderboard</span>
          Productos Más Vendidos — IM &amp; TM
        </h1>
        <p class="text-body-md" style="color:var(--on-surface-variant);margin:0;">
          Sube tu archivo Excel para ver el ranking de productos por cantidad y monto vendido
        </p>

        <div class="controls">
          <div class="file-upload">
            <input id="inputFile" type="file" accept=".xlsx,.xls" style="display:none;"/>
            <button class="boton" id="fileButton" type="button">
              <span class="material-symbols-outlined">attach_file</span>
              <span class="text">Seleccionar Archivo</span>
            </button>
          </div>
          <button id="procesarBtn" class="btn btn-primary" disabled>
            <span class="material-symbols-outlined">play_arrow</span>
            Procesar archivo
          </button>
          <button id="descargarBtn" class="btn btn-secondary" disabled>
            <span class="material-symbols-outlined">download</span>
            Descargar Excel
          </button>
        </div>

        <div id="loader" class="loader-container" style="display:none;">
          <span class="material-symbols-outlined">progress_activity</span>
          <span>Procesando archivo...</span>
        </div>

        <div id="mensajes" class="note"></div>
      </div>
    </div>

    <!-- ── Filtros unificados ── -->
    <div class="filter-bar" id="filterBar" style="display:none">
      <label>Departamento
        <select id="fDepto">
          <option value="">Ambos</option>
          <option value="IM">Innovación Móvil</option>
          <option value="TM">Tecnología Móvil</option>
        </select>
      </label>
      <label>Almacén
        <select id="fAlmacen"><option value="">Todos</option></select>
      </label>
      <label>Categoría
        <select id="fCategoria"><option value="">Todas</option></select>
      </label>
      <label>Tipo
        <select id="fTipo"><option value="">Todos</option></select>
      </label>
    </div>

    <div id="tablesContainer"></div>

  </div>

  
</div>

<script>
/* ── Índices ── */
const IDX_ALMACEN  = 0;
const IDX_N1       = 1;
const IDX_N2       = 2;   // fallback categoría
const IDX_N3       = 3;   // categoría principal
const IDX_PROD     = 11;
const IDX_TIPO     = 12;
const IDX_CANTIDAD = 14;
const IDX_TOTAL    = 18;

/* ── Datos ── */
let allData = []; // todos los registros IM + TM

/* ── DOM ── */
const inputFile    = document.getElementById('inputFile');
const procesarBtn  = document.getElementById('procesarBtn');
const descargarBtn = document.getElementById('descargarBtn');
const mensajes     = document.getElementById('mensajes');
const tablesContainer = document.getElementById('tablesContainer');
const fDepto    = document.getElementById('fDepto');
const fAlmacen  = document.getElementById('fAlmacen');
const fCategoria= document.getElementById('fCategoria');
const fTipo     = document.getElementById('fTipo');

document.getElementById('fileButton').addEventListener('click', ()=> inputFile.click());
inputFile.addEventListener('change', ()=>{
  procesarBtn.disabled = !inputFile.files.length;
  mensajes.innerText   = inputFile.files.length ? `Archivo listo: ${inputFile.files[0].name}` : '';
});
procesarBtn.addEventListener('click',  ()=>{ if(inputFile.files.length) leerExcel(inputFile.files[0]); });
descargarBtn.addEventListener('click', ()=> descargarExcel());

/* Al cambiar Departamento → actualizar los otros filtros y re-renderizar */
fDepto.addEventListener('change', ()=>{ actualizarFiltrosSecundarios(); aplicarFiltros(); });
fAlmacen.addEventListener('change',   aplicarFiltros);
fCategoria.addEventListener('change', aplicarFiltros);
fTipo.addEventListener('change',      aplicarFiltros);

/* ══════════════════════════════════════════════════
   LECTURA
══════════════════════════════════════════════════ */
function leerExcel(file){
  mensajes.innerText='Leyendo archivo...';
  document.getElementById('loader').style.display='flex';
  const reader=new FileReader();
  reader.onload=e=>{
    const wb=XLSX.read(new Uint8Array(e.target.result),{type:'array'});
    const rows=XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]],{header:1,defval:""});
    if(!rows||rows.length<2){ mensajes.innerText='Archivo vacío.'; document.getElementById('loader').style.display='none'; return; }

    allData=[];
    for(let i=1;i<rows.length;i++){
      const r=rows[i];
      const n1=String(r[IDX_N1]||'').trim();
      if(n1!=='INNOVACION MOVIL'&&n1!=='TECNOLOGIA MOVIL') continue;
      const n3=String(r[IDX_N3]||'').trim();
      const n2=String(r[IDX_N2]||'').trim();
      allData.push({
        depto:     n1==='INNOVACION MOVIL' ? 'IM' : 'TM',
        almacen:   String(r[IDX_ALMACEN]||'').trim()||'(SIN ALMACEN)',
        categoria: n3||n2||'(SIN CATEGORIA)',
        producto:  String(r[IDX_PROD]||'').trim()||'(SIN PRODUCTO)',
        tipo:      String(r[IDX_TIPO]||'').trim()||'(SIN TIPO)',
        cantidad:  Number(r[IDX_CANTIDAD]||0),
        total:     Number(r[IDX_TOTAL]||0)
      });
    }

    const cIM=allData.filter(r=>r.depto==='IM').length;
    const cTM=allData.filter(r=>r.depto==='TM').length;
    mensajes.innerText=`IM: ${cIM} registros | TM: ${cTM} registros.`;

    /* Mostrar filtros y poblarlos */
    document.getElementById('filterBar').style.display='';
    fDepto.value=''; // empezar en "Ambos"
    actualizarFiltrosSecundarios();
    aplicarFiltros();
    descargarBtn.disabled=false;
    document.getElementById('loader').style.display='none';
  };
  reader.readAsArrayBuffer(file);
}

/* ══════════════════════════════════════════════════
   FILTROS
══════════════════════════════════════════════════ */

/* Actualiza almacén/categoría/tipo según el departamento seleccionado */
function actualizarFiltrosSecundarios(){
  const dep=fDepto.value;
  const base=dep ? allData.filter(r=>r.depto===dep) : allData;
  const uniq=arr=>Array.from(new Set(arr)).sort();

  const fill=(sel, arr, placeholder)=>{
    const prev=sel.value;
    sel.innerHTML=`<option value="">${placeholder}</option>`+
      arr.map(a=>`<option value="${esc(a)}">${esc(a)}</option>`).join('');
    /* mantener selección previa si sigue disponible */
    if(arr.includes(prev)) sel.value=prev; else sel.value='';
  };

  fill(fAlmacen,  uniq(base.map(r=>r.almacen)),  'Todos');
  fill(fCategoria,uniq(base.map(r=>r.categoria)),'Todas');
  fill(fTipo,     uniq(base.map(r=>r.tipo)),      'Todos');
}

function getFiltrados(){
  return allData.filter(r=>
    (!fDepto.value    || r.depto===fDepto.value) &&
    (!fAlmacen.value  || r.almacen===fAlmacen.value) &&
    (!fCategoria.value|| r.categoria===fCategoria.value) &&
    (!fTipo.value     || r.tipo===fTipo.value)
  );
}

/* ══════════════════════════════════════════════════
   RENDER
══════════════════════════════════════════════════ */
function aplicarFiltros(){
  const filtrados=getFiltrados();
  if(!filtrados.length){
    tablesContainer.innerHTML='<p class="note">Sin datos para los filtros seleccionados.</p>';
    return;
  }
  const resumen=agrupar(filtrados);
  tablesContainer.innerHTML=buildTabla(resumen);
}

function agrupar(data){
  const map={};
  data.forEach(r=>{
    const k=`${r.depto}||${r.producto}||${r.categoria}||${r.tipo}`;
    if(!map[k]) map[k]={depto:r.depto,producto:r.producto,categoria:r.categoria,tipo:r.tipo,cantidad:0,total:0};
    map[k].cantidad+=r.cantidad;
    map[k].total+=r.total;
  });
  return Object.values(map).sort((a,b)=>b.cantidad-a.cantidad);
}

function buildTabla(arr){
  const mostrarDepto = !fDepto.value; // solo si es "Ambos"
  let h=`<table><thead><tr>`;
  if(mostrarDepto) h+=`<th>Depto</th>`;
  h+=`<th>Producto</th><th>Categoría</th><th>Tipo</th><th>Cantidad vendida</th><th>Total vendido</th>
  </tr></thead><tbody>`;

  arr.forEach(r=>{
    const badge = r.depto==='IM'
      ? `<span class="badge-depto im">IM</span>`
      : `<span class="badge-depto tm">TM</span>`;
    h+=`<tr>`;
    if(mostrarDepto) h+=`<td>${badge}</td>`;
    h+=`<td style="text-align:left">${esc(r.producto)}</td>
        <td>${esc(r.categoria)}</td>
        <td>${esc(r.tipo)}</td>
        <td>${r.cantidad}</td>
        <td>${r.total.toFixed(2)}</td>
    </tr>`;
  });

  const totCant=arr.reduce((s,x)=>s+x.cantidad,0);
  const totVent=arr.reduce((s,x)=>s+x.total,0);
  const colspan=mostrarDepto?3:2;
  h+=`<tr class="total-row">
    <td colspan="${colspan+1}">Total</td>
    <td>${totCant}</td><td>${totVent.toFixed(2)}</td>
  </tr>`;
  return h+'</tbody></table>';
}

/* ══════════════════════════════════════════════════
   EXPORT
══════════════════════════════════════════════════ */
function descargarExcel(){
  const filtrados=getFiltrados();
  if(!filtrados.length) return;
  const arr=agrupar(filtrados);
  const mostrarDepto=!fDepto.value;

  const wb=XLSX.utils.book_new();
  const header=[...(mostrarDepto?['Departamento']:[]),'Producto','Categoría','Tipo','Cantidad Vendida','Total Vendido'];
  const aoa=[header,...arr.map(r=>[
    ...(mostrarDepto?[r.depto==='IM'?'Innovación Móvil':'Tecnología Móvil']:[]),
    r.producto, r.categoria, r.tipo, r.cantidad, r.total
  ])];
  aoa.push([...(mostrarDepto?['']:[]),'Total','','',
    arr.reduce((s,x)=>s+x.cantidad,0),
    arr.reduce((s,x)=>s+x.total,0)
  ]);

  const depLabel = fDepto.value ? (fDepto.value==='IM'?'IM':'TM') : 'IM_TM';
  XLSX.utils.book_append_sheet(wb,XLSX.utils.aoa_to_sheet(aoa),`Productos ${depLabel}`);
  XLSX.writeFile(wb,`ProductosMasVendidos_${depLabel}.xlsx`);
}

/* ── Helpers ── */
function esc(s){ return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
</script>
</body>
</html>