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
<style>
body{font-family:Arial,sans-serif;margin:18px;background:#f7f7f7;color:#222}
h1{margin-top:0}
.controls{display:flex;gap:12px;align-items:center;margin-bottom:12px;flex-wrap:wrap}
button.btn{background:#007bff;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer}
button.btn:disabled{background:#999;cursor:not-allowed}
table{border-collapse:collapse;width:100%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.07);margin-bottom:20px}
th,td{padding:8px 6px;border:1px solid #e1e1e1;text-align:center;font-size:13px}
th{background:#2f6fa6;color:#fff;position:sticky;top:0;z-index:1}
tr.total-row{font-weight:700;background:#eee}
.note{font-size:13px;color:#333;margin-top:6px}

.filter-bar{display:flex;gap:14px;align-items:center;flex-wrap:wrap;
            padding:12px 16px;background:#fff;border:1px solid #ddd;
            border-radius:10px;margin-bottom:16px}
.filter-bar label{font-size:13px;font-weight:600;display:flex;align-items:center;gap:6px}
.filter-bar select{padding:6px 10px;border-radius:6px;border:1px solid #ccc;font-size:13px}

/* Badge departamento */
select#fDepto option[value="IM"]  { background:#fff0f3 }
select#fDepto option[value="TM"]  { background:#f0f8ff }
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
  <h1>📊 Productos Más Vendidos — IM & TM</h1>

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
    <button id="procesarBtn" class="btn" disabled>Procesar archivo</button>
    <button id="descargarBtn" class="btn" disabled>⬇ Descargar Excel</button>
  </div>

  <div id="mensajes" class="note"></div>

  <div id="loader" class="loader-container" style="display:none;">
    <div class="cloud front"><span class="left-front"></span><span class="right-front"></span></div>
    <span class="sun sunshine"></span><span class="sun"></span>
    <div class="cloud back"><span class="left-back"></span><span class="right-back"></span></div>
  </div>

  <!-- ── Filtros unificados ── -->
  <div class="filter-bar" id="filterBar" style="display:none">
    <label>Departamento:
      <select id="fDepto">
        <option value="">Ambos</option>
        <option value="IM">📱 Innovación Móvil</option>
        <option value="TM">📲 Tecnología Móvil</option>
      </select>
    </label>
    <label>Almacén:
      <select id="fAlmacen"><option value="">Todos</option></select>
    </label>
    <label>Categoría:
      <select id="fCategoria"><option value="">Todas</option></select>
    </label>
    <label>Tipo:
      <select id="fTipo"><option value="">Todos</option></select>
    </label>
  </div>

  <div id="tablesContainer"></div>
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
      ? `<span style="background:#f5576c;color:#fff;padding:2px 7px;border-radius:10px;font-size:11px">IM</span>`
      : `<span style="background:#4facfe;color:#fff;padding:2px 7px;border-radius:10px;font-size:11px">TM</span>`;
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