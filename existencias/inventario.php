<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>InventaScan — Inventario Físico</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<style>:root {
  /* Paleta Neutra Premium (Escala Zinc/Slate Corp) */
  --ink:         #09090b; /* Fondo base ultra oscuro y profundo */
  --ink-2:       #18181b; /* Fondo de contenedores y tarjetas */
  --ink-3:       #27272a; /* Elementos interactivos secundarios o inputs */
  --border:      #27272a; /* Bordes estructurales discretos */
  --border-hi:   #3f3f46; /* Bordes destacados o estados hover */
  --text:        #fafafa; /* Texto principal de alta legibilidad */
  --text-muted:  #a1a1aa; /* Texto secundario descriptivo */
  --text-dim:    #71717a; /* Texto auxiliar o deshabilitado */

  /* Identidad Corporativa y Semántica (Menos saturados, más estables) */
  --cyan:        #3b82f6; /* Azul Royal Corporativo para acciones principales */
  --cyan-glow:   rgba(59, 130, 246, 0.12);
  --cyan-dim:    rgba(59, 130, 246, 0.06);
  --amber:       #f59e0b; /* Alertas de atención estables */
  --amber-glow:  rgba(245, 158, 11, 0.08);
  --red:         #ef4444; /* Errores corporativos */
  --red-glow:    rgba(239, 68, 68, 0.08);
  --green:       #10b981; /* Éxitos y estados positivos */
  --green-glow:  rgba(16, 185, 129, 0.08);
  --purple:      #6366f1; /* Índices complementarios o extras */

  /* Radios de Curvatura Moderados (Look de software robusto, no lúdico) */
  --r-sm:  4px;
  --r-md:  8px;
  --r-lg:  12px;
  --r-xl:  14px;

  /* Sombras Arquitectónicas (Efecto de elevación realista) */
  --sh-sm: 0 1px 2px rgba(0, 0, 0, 0.5);
  --sh-md: 0 4px 12px rgba(0, 0, 0, 0.4), 0 1px 3px rgba(0, 0, 0, 0.2);
  --sh-lg: 0 12px 24px -4px rgba(0, 0, 0, 0.6), 0 4px 12px -2px rgba(0, 0, 0, 0.4);
  --sh-glow-cyan: 0 0 0 2px rgba(59, 130, 246, 0.3);

  /* Tipografía Profesional (Optimización de lectura en pantalla) */
  --font:  'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --mono:  'Geist Mono', 'SF Mono', 'JetBrains Mono', monospace;

  --topbar-h: 64px;
  --trans: 160ms cubic-bezier(0.4, 0, 0.2, 1);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { height: 100%; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

body {
  font-family: var(--font);
  background-color: var(--ink);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
  letter-spacing: -0.01em;
  /* Patrón micro-grid sutil para agregar textura técnica madura */
  background-image: radial-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px);
  background-size: 24px 24px;
}

/* Scrollbars Integradas */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--ink-3); border-radius: 99px; }
::-webkit-scrollbar-thumb:hover { background: var(--border-hi); }

/* TOPBAR */
.topbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: var(--topbar-h);
  background: rgba(9, 9, 11, 0.8);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  z-index: 200;
}
.brand {
  display: flex; align-items: center; gap: 12px;
  font-weight: 600; font-size: .95rem; letter-spacing: -0.02em; color: var(--text);
}
.brand-mark {
  width: 28px; height: 28px; border-radius: var(--r-sm);
  background: var(--cyan);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: #fff;
}
.brand span { color: var(--text-muted); font-weight: 400; }
.topbar-right { display: flex; align-items: center; gap: 10px; font-size: .8rem; color: var(--text-muted); font-family: var(--mono); }
.status-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--green);
  animation: pulse 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .4; transform: scale(0.95); } }

/* SCREENS & LAYOUT */
.screen {
  display: none;
  padding: calc(var(--topbar-h) + 40px) 32px 64px;
  max-width: 1140px;
  margin: 0 auto;
  animation: fadeUp 240ms cubic-bezier(0.16, 1, 0.3, 1) both;
}
.screen.active { display: block; }
@keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* STEP TRAIL (Línea de procesos empresarial) */
.step-trail { display: flex; align-items: center; gap: 8px; margin-bottom: 32px; flex-wrap: wrap; }
.step-trail .st {
  font-size: .75rem; font-weight: 500; letter-spacing: 0.02em;
  padding: 6px 14px; border-radius: var(--r-md); border: 1px solid var(--border);
  color: var(--text-dim); background: transparent; transition: all var(--trans);
}
.step-trail .st.active { background: var(--cyan-dim); border-color: var(--cyan); color: var(--cyan); font-weight: 600; }
.step-trail .st.done  { border-color: rgba(16, 185, 129, 0.3); color: var(--green); background: var(--green-glow); }
.step-trail .sep { color: var(--border-hi); font-size: .7rem; }

/* SECTIONS */
.section-title { margin-bottom: 28px; }
.section-title h1 { font-size: 1.75rem; font-weight: 600; letter-spacing: -0.03em; line-height: 1.2; color: var(--text); }
.section-title h1 em { font-style: normal; color: var(--cyan); font-weight: 600; }
.section-title p { margin-top: 8px; font-size: .875rem; color: var(--text-muted); line-height: 1.5; }

/* CARDS */
.card { 
  background: var(--ink-2); 
  border: 1px solid var(--border); 
  border-radius: var(--r-xl); 
  overflow: hidden; 
  box-shadow: var(--sh-sm); 
  margin-bottom: 24px; 
}
.card-head { 
  padding: 20px 24px; 
  border-bottom: 1px solid var(--border); 
  display: flex; 
  align-items: center; 
  gap: 14px; 
  background: rgba(255,255,255,0.01);
}
.card-head-icon { font-size: 18px; line-height: 1; flex-shrink: 0; color: var(--text-muted); }
.card-head h2 { font-size: .95rem; font-weight: 600; color: var(--text); letter-spacing: -0.01em; }
.card-head p  { font-size: .8rem; color: var(--text-muted); margin-top: 2px; }
.card-body { padding: 24px; }

/* BOTONES PREMIUM (Planos, limpios y de alta respuesta) */
.btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 8px 16px; border-radius: var(--r-md);
  font-family: var(--font); font-size: .85rem; font-weight: 500;
  cursor: pointer; border: 1px solid transparent; outline: none;
  transition: all var(--trans); text-decoration: none;
  white-space: nowrap; user-select: none;
}
.btn:disabled { opacity: .35; cursor: not-allowed; pointer-events: none; }

.btn-cyan  { background: var(--cyan); color: #fff; }
.btn-cyan:hover  { background: #2563eb; transform: translateY(-0.5px); }
.btn-cyan:active { background: #1d4ed8; transform: translateY(0); }

.btn-ghost { background: var(--ink-3); color: var(--text); border-color: var(--border); }
.btn-ghost:hover { background: var(--border); border-color: var(--border-hi); color: #fff; }
.btn-ghost:active { background: var(--ink-2); }

.btn-red   { background: var(--red); color: #fff; }
.btn-red:hover   { background: #dc2626; transform: translateY(-0.5px); }

.btn-green { background: var(--green); color: #fff; }
.btn-green:hover { background: #059669; transform: translateY(-0.5px); }

.btn-amber { background: var(--amber); color: #fff; }
.btn-amber:hover { background: #d97706; transform: translateY(-0.5px); }

.btn-lg   { padding: 10px 24px; font-size: .9rem; border-radius: var(--r-lg); }
.btn-full { width: 100%; justify-content: center; }

/* ALERTAS SOBRIAS */
.alert {
  padding: 14px 18px; border-radius: var(--r-md);
  display: flex; align-items: flex-start; gap: 12px;
  font-size: .85rem; margin-bottom: 20px; border: 1px solid; line-height: 1.5;
}
.alert-icon { font-size: 1.05rem; flex-shrink: 0; }
.alert.error   { background: var(--red-glow);   border-color: rgba(239, 68, 68, 0.25);  color: #fca5a5; }
.alert.warn    { background: var(--amber-glow); border-color: rgba(245, 158, 11, 0.25); color: #fcd34d; }
.alert.info    { background: var(--cyan-dim);   border-color: rgba(59, 130, 246, 0.25);  color: #93c5fd; }
.alert.success { background: var(--green-glow); border-color: rgba(16, 185, 129, 0.25); color: #a7f3d0; }

/* LOADING OVERLAY */
#loadingOverlay {
  position: fixed; inset: 0; background: rgba(9, 9, 11, 0.75);
  backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); z-index: 500;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 16px; opacity: 0; pointer-events: none; transition: opacity .2s;
}
#loadingOverlay.show { opacity: 1; pointer-events: auto; }
.spinner-ring {
  width: 32px; height: 32px; border: 2.5px solid var(--ink-3);
  border-top-color: var(--cyan); border-radius: 50%;
  animation: spin .6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
#loadingText { font-size: .85rem; color: var(--text-muted); font-family: var(--mono); }

/* MODALES ARQUITECTÓNICOS */
.modal-backdrop {
  position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); z-index: 400;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; pointer-events: none; transition: opacity 200ms cubic-bezier(0.4, 0, 0.2, 1);
}
.modal-backdrop.show { opacity: 1; pointer-events: auto; }
.modal-box {
  background: var(--ink-2); border: 1px solid var(--border-hi);
  border-radius: var(--r-xl); padding: 28px;
  max-width: 480px; width: 90%; box-shadow: var(--sh-lg);
  transform: scale(.98) translateY(4px);
  transition: transform 200ms cubic-bezier(0.16, 1, 0.3, 1);
}
.modal-backdrop.show .modal-box { transform: scale(1) translateY(0); }
.modal-box h2 { font-size: 1.15rem; font-weight: 600; margin-bottom: 8px; letter-spacing: -0.02em; }
.modal-box p  { font-size: .85rem; color: var(--text-muted); margin-bottom: 24px; line-height: 1.5; }

.modal-type-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
.modal-type-btn {
  padding: 16px; border: 1px solid var(--border);
  border-radius: var(--r-lg); background: rgba(255,255,255,0.01);
  cursor: pointer; text-align: left; transition: all var(--trans); font-family: var(--font);
}
.modal-type-btn:hover { border-color: var(--border-hi); background: var(--ink-3); }
.modal-type-btn .mt-icon  { font-size: 22px; margin-bottom: 10px; display: block; color: var(--text-muted); }
.modal-type-btn .mt-title { font-size: .85rem; font-weight: 500; color: var(--text); display: block; }
.modal-type-btn .mt-count { font-size: .75rem; color: var(--text-dim); display: block; margin-top: 4px; font-family: var(--mono); }

#modalConfirm .modal-box { max-width: 400px; text-align: center; }
.modal-confirm-icon { font-size: 2rem; margin-bottom: 16px; display: block; }
#modalConfirm h2, #modalConfirm p { text-align: center; }
.modal-btn-row { display: flex; gap: 12px; }
.modal-btn-row .btn { flex: 1; justify-content: center; }

/* MODALES DE ESTRUCTURA COMPLEJA (REVISIÓN Y MARCAS) */
#modalRevision .modal-box, #modalMarcas .modal-box {
  max-width: 640px; max-height: 85vh;
  display: flex; flex-direction: column; padding: 0; overflow: hidden;
}
.modal-rev-head, .modal-marcas-head {
  padding: 24px 28px; border-bottom: 1px solid var(--border); flex-shrink: 0;
  background: rgba(255,255,255,0.005);
}
.modal-rev-head h2, .modal-marcas-head h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 4px 0; }
.modal-rev-head p, .modal-marcas-head p { font-size: .82rem; color: var(--text-muted); margin: 0; line-height: 1.4; }

.modal-rev-body, .modal-marcas-body { flex: 1; overflow-y: auto; padding: 24px 28px; }

.modal-rev-footer, .modal-marcas-footer {
  padding: 18px 28px; border-top: 1px solid var(--border); flex-shrink: 0;
  display: flex; gap: 12px; align-items: center; justify-content: flex-end;
  background: rgba(0,0,0,0.1);
}
.modal-marcas-footer { justify-content: space-between; }

/* SELECCIÓN DE MARCAS REFINADA */
.marca-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; }
.marca-btn {
  padding: 10px 14px; border: 1px solid var(--border); border-radius: var(--r-md);
  background: var(--ink-2); cursor: pointer; font-family: var(--font);
  font-size: .82rem; font-weight: 500; color: var(--text-muted);
  text-align: left; transition: all var(--trans); display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
.marca-btn:hover { border-color: var(--border-hi); color: var(--text); background: var(--ink-3); }
.marca-btn.selected { border-color: var(--cyan); background: var(--cyan-dim); color: var(--cyan); }
.marca-btn .marca-chk {
  width: 14px; height: 14px; border: 1px solid var(--border-hi);
  border-radius: var(--r-sm); flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 9px; transition: all var(--trans); color: transparent;
}
.marca-btn.selected .marca-chk { background: var(--cyan); border-color: var(--cyan); color: #fff; }
.marca-count-badge { font-size: .7rem; font-family: var(--mono); color: var(--text-dim); margin-top: 2px; }
.marca-sel-info { font-size: .8rem; color: var(--text-muted); }
.marca-sel-info strong { color: var(--text); font-family: var(--mono); }
.marca-selall-btn {
  background: transparent; border: 1px solid var(--border); color: var(--text-muted);
  font-size: .75rem; font-weight: 500; padding: 4px 10px; border-radius: var(--r-sm);
  cursor: pointer; font-family: var(--font); transition: all var(--trans);
}
.marca-selall-btn:hover { border-color: var(--border-hi); color: var(--text); background: var(--ink-3); }

/* TABLAS EMPRESARIALES (Limpieza absoluta, alineaciones perfectas) */
.rev-table, .prod-table { width: 100%; border-collapse: collapse; font-size: .82rem; text-align: left; }
.rev-table th, .prod-table th {
  padding: 10px 12px; font-size: .7rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: .05em; color: var(--text-dim); border-bottom: 1px solid var(--border);
  position: sticky; top: 0; background: var(--ink-2); z-index: 10;
}
.rev-table td, .prod-table td { padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-muted); vertical-align: middle; }
.rev-table tr:last-child td, .prod-table tr:last-child td { border-bottom: none; }
.rev-table tr:hover td, .prod-table tr:hover td { background: rgba(255,255,255,0.015); color: var(--text); }

.rev-table .mono, .prod-table .plows-cell, .prod-table .num-cell, .rev-table .num { font-family: var(--mono); font-size: .78rem; color: var(--text); }
.rev-table .desc, .prod-table .desc-cell { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text); }
.rev-table .num, .prod-table .num-cell { text-align: right; font-variant-numeric: tabular-nums; }

/* BADGES SEMÁNTICOS REFINADOS */
.rev-badge-extra, .sb-extra     { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: .65rem; font-weight: 500; background: rgba(99, 102, 241, 0.1); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.2); }
.rev-badge-missing, .sb-missing { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: .65rem; font-weight: 500; background: var(--amber-glow); color: #fcd34d; border: 1px solid rgba(245, 158, 11, 0.2); }
.sb-ok, .sb-pending             { display: inline-block; padding: 2px 8px; border-radius: 99px; font-size: .65rem; font-weight: 500; }
.sb-ok { background: var(--green-glow); color: #a7f3d0; border: 1px solid rgba(16, 185, 129, 0.2); }
.sb-pending { background: var(--ink-3); color: var(--text-muted); border: 1px solid var(--border); }

.btn-rev-fix, .btn-del-row {
  background: transparent; border: 1px solid var(--border); color: var(--text-muted);
  font-size: .75rem; font-weight: 500; padding: 4px 10px; border-radius: var(--r-sm);
  cursor: pointer; font-family: var(--font); transition: all var(--trans); white-space: nowrap;
}
.btn-rev-fix:hover { border-color: var(--border-hi); color: var(--text); background: var(--ink-3); }
.btn-rev-fix.applied { border-color: rgba(16, 185, 129, 0.3); color: var(--green); background: var(--green-glow); pointer-events: none; }
.btn-del-row:hover { background: var(--red-glow); border-color: rgba(239, 68, 68, 0.2); color: #fca5a5; }

/* RESÚMENES DE CONTROL */
.rev-summary { display: flex; gap: 8px; flex-wrap: wrap; padding: 12px 0; margin-bottom: 12px; }
.rev-sum-badge { font-size: .75rem; font-weight: 500; padding: 6px 12px; border-radius: var(--r-md); border: 1px solid; font-family: var(--mono); }
.rev-sum-badge.extra   { background: rgba(99, 102, 241, 0.08); border-color: rgba(99, 102, 241, 0.2); color: #a5b4fc; }
.rev-sum-badge.missing { background: var(--amber-glow); border-color: rgba(245, 158, 11, 0.2); color: #fcd34d; }
.rev-sum-badge.ok      { background: var(--green-glow); border-color: rgba(16, 185, 129, 0.2); color: #a7f3d0; }

.rev-empty, .empty { text-align: center; padding: 48px 0; color: var(--text-dim); font-size: .85rem; }
.rev-empty .rev-empty-icon, .empty .e-ico { font-size: 24px; display: block; margin-bottom: 12px; color: var(--text-dim); }

.phase-label, .scan-zone-label {
  font-size: .7rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: .08em; color: var(--text-dim); margin-bottom: 8px;
}

/* PANTALLA 1: CONFIGURACIÓN Y ÁRBOLES */
.sucursal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
.sucursal-btn {
  padding: 16px; background: rgba(255,255,255,0.01); border: 1px solid var(--border);
  border-radius: var(--r-lg); color: var(--text-muted);
  font-family: var(--font); font-size: .85rem; font-weight: 500;
  cursor: pointer; text-align: left; transition: all var(--trans); line-height: 1.4;
}
.sucursal-btn:hover  { border-color: var(--border-hi); color: var(--text); background: var(--ink-3); }
.sucursal-btn.selected { border-color: var(--cyan); background: var(--cyan-dim); color: var(--cyan); }
.sucursal-btn .sb-id { font-family: var(--mono); font-size: .7rem; color: var(--text-dim); display: block; margin-bottom: 6px; }

/* ÁRBOL DE CATEGORÍAS TIPO EXPLORADOR DE ARCHIVOS */
.cat-tree { user-select: none; }
.cat-root { margin-bottom: 8px; }
.cat-root-label {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 14px; background: rgba(255,255,255,0.01); border: 1px solid var(--border);
  border-radius: var(--r-md); cursor: pointer; transition: all var(--trans);
  font-size: .85rem; font-weight: 500; color: var(--text-muted);
}
.cat-root-label:hover { border-color: var(--border-hi); color: var(--text); }
.cat-root-label.fully-checked { color: var(--cyan); border-color: var(--cyan); background: var(--cyan-dim); }
.cat-children { padding: 6px 0 4px 22px; display: none; }
.cat-children.open { display: block; }
.cat-sub { margin-bottom: 4px; }
.cat-sub-label {
  display: flex; align-items: center; gap: 10px; padding: 8px 10px;
  border-radius: var(--r-sm); cursor: pointer; font-size: .82rem; color: var(--text-muted); transition: all var(--trans);
}
.cat-sub-label:hover { background: var(--ink-3); color: var(--text); }
.cat-sub-label.checked { color: var(--cyan); }
.cat-sub-children { padding-left: 20px; display: none; }
.cat-sub-children.open { display: block; }
.cat-leaf {
  display: flex; align-items: center; gap: 10px; padding: 6px 10px;
  border-radius: var(--r-sm); cursor: pointer; font-size: .8rem; color: var(--text-dim); transition: all var(--trans);
}
.cat-leaf:hover { background: var(--ink-3); color: var(--text-muted); }
.cat-leaf.checked { color: var(--cyan); }

/* CONTROLES CHECKBOX */
.chk {
  width: 14px; height: 14px; border: 1px solid var(--border-hi); border-radius: var(--r-sm);
  flex-shrink: 0; display: flex; align-items: center; justify-content: center;
  font-size: 8px; transition: all var(--trans); color: transparent; background: transparent;
}
.chk.checked { background: var(--cyan); border-color: var(--cyan); color: #fff; }
.chk.partial { background: var(--amber); border-color: var(--amber); color: #fff; }
.toggle-arrow { margin-left: auto; font-size: .75rem; color: var(--text-dim); transition: transform var(--trans); }
.toggle-arrow.open { transform: rotate(90deg); color: var(--text-muted); }

.selection-summary {
  padding: 14px 18px; background: var(--ink-2); border-radius: var(--r-md);
  border: 1px solid var(--border); font-size: .82rem; color: var(--text-muted);
  margin-top: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
}
.selection-summary.has-data { border-color: rgba(59, 130, 246, 0.3); background: var(--cyan-dim); color: var(--text); }
.sel-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.chip {
  font-size: .7rem; font-weight: 500; padding: 2px 8px; border-radius: var(--r-sm);
  background: var(--ink-3); border: 1px solid var(--border); color: var(--text-muted); font-family: var(--mono);
}

/* PANTALLA 2: DASHBOARD DE CONTROL DE MTR / KPIs */
.kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
.kpi {
  background: var(--ink-2); border: 1px solid var(--border);
  border-radius: var(--r-xl); padding: 20px; position: relative;
}
.kpi::after { content:''; position:absolute; bottom:0; left:16px; right:16px; height:1px; background: transparent; }
.kpi-label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--text-dim); margin-bottom: 10px; }
.kpi-value { font-size: 1.85rem; font-weight: 600; letter-spacing: -0.02em; font-variant-numeric: tabular-nums; line-height: 1; }
.kpi.total .kpi-value   { color: var(--text); }
.kpi.found .kpi-value   { color: var(--green); }
.kpi.missing .kpi-value { color: var(--amber); }
.kpi.extra .kpi-value   { color: var(--purple); }

/* BARRA DE PROGRESO REFINADA */
.prog-bar-wrap { margin-bottom: 24px; }
.prog-bar-head { display: flex; justify-content: space-between; font-size: .8rem; color: var(--text-muted); margin-bottom: 8px; font-family: var(--mono); }
.prog-track { height: 4px; background: var(--ink-3); border-radius: 99px; overflow: hidden; }
.prog-fill { height: 100%; background: var(--cyan); border-radius: 99px; transition: width .4s cubic-bezier(0.16, 1, 0.3, 1); min-width: 0; }

/* ZONA DE ESCANEO ACTIVA */
.scan-zone {
  background: var(--ink-2); border: 1px solid var(--border-hi);
  border-radius: var(--r-xl); padding: 24px; margin-bottom: 20px;
}
.scan-zone-label { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
.active-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); animation: pulse 2s ease infinite; }

.scan-input-row { display: flex; gap: 12px; align-items: center; }
.scan-input {
  flex: 1; height: 48px; background: var(--ink-3); border: 1px solid var(--border);
  border-radius: var(--r-md); padding: 0 16px; font-family: var(--mono);
  font-size: .95rem; font-weight: 400; color: var(--text); letter-spacing: .02em;
  outline: none; transition: all var(--trans);
}
.scan-input:focus     { border-color: var(--cyan); box-shadow: var(--sh-glow-cyan); background: var(--ink); }
/* Destellos de feedback sin bordes exagerados */
.scan-input.ok-flash  { border-color: var(--green) !important; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2) !important; }
.scan-input.err-flash { border-color: var(--red) !important; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important; }
.scan-input.dup-flash { border-color: var(--amber) !important; box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2) !important; }

.scan-result {
  margin-top: 16px; padding: 12px 16px; border-radius: var(--r-md);
  font-size: .85rem; font-weight: 500; display: flex; align-items: flex-start;
  gap: 12px; border: 1px solid transparent; opacity: 0; transition: opacity .15s; min-height: 44px;
}
.scan-result.show { opacity: 1; }
.scan-result.ok   { background: var(--green-glow); border-color: rgba(16, 185, 129, 0.2); color: #a7f3d0; }
.scan-result.err  { background: var(--red-glow);   border-color: rgba(239, 68, 68, 0.2);  color: #fca5a5; }
.scan-result.dup  { background: var(--amber-glow); border-color: rgba(245, 158, 11, 0.2); color: #fcd34d; }
.scan-result.warn { background: var(--amber-glow); border-color: rgba(245, 158, 11, 0.2); color: #fcd34d; }
.scan-result-sub  { font-size: .75rem; font-weight: 400; opacity: .8; margin-top: 4px; font-family: var(--mono); color: var(--text-muted); }

.undo-bar {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 10px 16px; background: var(--ink-3); border: 1px solid var(--border);
  border-radius: var(--r-md); margin-top: 16px; font-size: .8rem; color: var(--text-muted);
  opacity: 0; pointer-events: none; transition: opacity .15s;
}
.undo-bar.show { opacity: 1; pointer-events: auto; }
.undo-bar strong { color: var(--text); font-family: var(--mono); font-weight: 500; }

/* TABS DE FILTRADO COMPONENTIZADAS */
.filter-tabs { display: flex; gap: 6px; margin-bottom: 16px; flex-wrap: wrap; }
.ftab {
  font-size: .75rem; font-weight: 500; padding: 6px 12px; border-radius: 99px;
  background: transparent; border: 1px solid var(--border); color: var(--text-muted);
  cursor: pointer; transition: all var(--trans); user-select: none;
}
.ftab:hover { border-color: var(--border-hi); color: var(--text); }
.ftab.active { background: var(--border); border-color: var(--text-dim); color: #fff; }

.prod-table-wrap { max-height: 400px; overflow-y: auto; border: 1px solid var(--border); border-radius: var(--r-md); }

/* CONTROLES DE ACCIÓN DE FLUJO */
.action-row { display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap; position: relative; z-index: 10; }
.action-row .btn { pointer-events: auto !important; }

/* CONTENEDORES AUXILIARES */
.series-tag-wrap { display: flex; flex-wrap: wrap; gap: 6px; max-height: 140px; overflow-y: auto; padding: 4px 0; }
.series-tag { font-family: var(--mono); font-size: .7rem; padding: 2px 6px; border-radius: var(--r-sm); border: 1px solid; transition: all var(--trans); }
.series-tag.pending { background: var(--ink-3); border-color: var(--border); color: var(--text-dim); }
.series-tag.found   { background: var(--green-glow); border-color: rgba(16, 185, 129, 0.2); color: var(--green); }
.series-tag.extra   { background: rgba(99, 102, 241, 0.08); border-color: rgba(99, 102, 241, 0.2); color: var(--purple); }

/* CONTEXTO DE ESCANEO BAR */
.scan-context {
  background: var(--ink-2); border: 1px solid var(--border); border-radius: var(--r-lg);
  padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
}
.scan-context-store { font-size: .95rem; font-weight: 600; color: var(--text); white-space: nowrap; }
.scan-context-sep   { color: var(--border-hi); font-size: .8rem; }
.scan-context-cats  { font-size: .78rem; color: var(--text-muted); font-family: var(--mono); }
.scan-context-marcas { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; flex: 1; }
.marca-tag-ctx {
  font-size: .65rem; font-weight: 500; padding: 2px 8px; border-radius: 99px;
  background: var(--ink-3); border: 1px solid var(--border-hi); color: var(--text-muted);
  font-family: var(--mono); text-transform: uppercase;
}

/* PANTALLA 3: PANTALLA DE RESULTADOS HERO */
.result-hero { text-align: center; margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto; }
.result-hero h1 { font-size: 2rem; font-weight: 600; letter-spacing: -0.03em; }
.result-hero h1 em { font-style: normal; color: var(--green); font-weight: 600; }
.result-hero p { color: var(--text-muted); font-size: .9rem; margin-top: 10px; line-height: 1.5; }

.result-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 40px; }
.rkpi { background: var(--ink-2); border: 1px solid var(--border); border-radius: var(--r-xl); padding: 28px 20px; text-align: center; }
.rkpi-num { font-size: 2.5rem; font-weight: 600; letter-spacing: -0.03em; font-variant-numeric: tabular-nums; line-height: 1; margin-bottom: 10px; }
.rkpi-lbl { font-size: .7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
.rkpi.g .rkpi-num { color: var(--green); }
.rkpi.a .rkpi-num { color: var(--amber); }
.rkpi.p .rkpi-num { color: var(--purple); }

.export-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 16px; }

/* SNACKBAR DE AVISOS NOTIFICACIONES */
#snackbar { position: fixed; bottom: 32px; right: 32px; z-index: 999; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
.snack {
  background: var(--ink-2); border: 1px solid var(--border-hi); color: var(--text);
  padding: 12px 20px; border-radius: var(--r-md); font-size: .85rem; font-weight: 400;
  box-shadow: var(--sh-lg); display: flex; align-items: center; gap: 10px; pointer-events: auto;
  animation: snackIn 200ms cubic-bezier(0.16, 1, 0.3, 1) both; max-width: 320px;
}
@keyframes snackIn  { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: none; } }
.snack.warn { border-color: rgba(245, 158, 11, 0.4); }
.snack.err  { border-color: rgba(239, 68, 68, 0.4); }
.snack.good { border-color: rgba(16, 185, 129, 0.4); }

#unknownSection { display: none; margin-top: 20px; }

/* COMPORTAMIENTO MÓVIL Y TABLET RESPONSIVE COMPLETO */
@media (max-width: 900px) {
  .kpi-row { grid-template-columns: repeat(2, 1fr); gap: 12px; }
}

@media (max-width: 760px) {
  .screen { padding-left: 20px; padding-right: 20px; padding-top: calc(var(--topbar-h) + 24px); }
  .result-kpis { grid-template-columns: 1fr; gap: 12px; }
  .modal-type-grid { grid-template-columns: 1fr; }
  .topbar { padding: 0 20px; }
  .section-title h1 { font-size: 1.5rem; }
  .modal-rev-head, .modal-rev-body, .modal-rev-footer { padding-left: 20px; padding-right: 20px; }
  .marca-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); }
}

@media (max-width: 480px) {
  .kpi-row { grid-template-columns: 1fr; }
  .kpi-value { font-size: 1.65rem; }
  .btn { width: 100%; justify-content: center; }
  .action-row { flex-direction: column; }
}</style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
  <div class="brand">
    <div class="brand-mark">📦</div>
    Inventa<span>Scan</span>
  </div>
  <div class="topbar-right">
    <div class="status-dot" id="statusDot"></div>
    <span id="statusLabel">Conectando…</span>
  </div>
</header>

<!-- LOADING OVERLAY -->
<div id="loadingOverlay">
  <div class="spinner-ring"></div>
  <div id="loadingText">Cargando…</div>
</div>

<!-- MODAL: tipo de inventario -->
<div class="modal-backdrop" id="modalTipo">
  <div class="modal-box">
    <h2>¿Qué tipo de inventario?</h2>
    <p>La selección contiene productos de ambos tipos. Elige uno para continuar, o ajusta las categorías.</p>
    <div class="modal-type-grid">
      <button class="modal-type-btn" id="modalBtnSinSeries">
        <span class="mt-icon">🏷️</span>
        <span class="mt-title">Sin serie</span>
        <span class="mt-count" id="modalCountSin">0 productos</span>
      </button>
      <button class="modal-type-btn" id="modalBtnConSeries">
        <span class="mt-icon">📱</span>
        <span class="mt-title">Con serie (IMEI)</span>
        <span class="mt-count" id="modalCountCon">0 productos</span>
      </button>
    </div>
    <button class="btn btn-ghost btn-full" id="modalBtnCancel">← Volver a categorías</button>
  </div>
</div>

<!-- MODAL: confirmación -->
<div class="modal-backdrop" id="modalConfirm">
  <div class="modal-box">
    <span class="modal-confirm-icon" id="confirmIcon">⚠️</span>
    <h2 id="confirmTitle">¿Confirmar acción?</h2>
    <p id="confirmMsg">Texto del mensaje.</p>
    <div class="modal-btn-row">
      <button class="btn btn-ghost" id="confirmBtnCancel">Cancelar</button>
      <button class="btn btn-red"   id="confirmBtnOk">Confirmar</button>
    </div>
  </div>
</div>

<!-- MODAL: revisión sobrantes / faltantes -->
<div class="modal-backdrop" id="modalRevision">
  <div class="modal-box">
    <div class="modal-rev-head">
      <h2 id="revTitle">Revisión antes de exportar</h2>
      <p id="revSubtitle">Verifica y corrige antes de generar el reporte.</p>
    </div>
    <div class="modal-rev-body" id="revBody"></div>
    <div class="modal-rev-footer">
      <button class="btn btn-ghost" id="revBtnVolver">← Seguir escaneando</button>
      <button class="btn btn-green" id="revBtnConfirm">Confirmar y exportar →</button>
    </div>
  </div>
</div>

<!-- MODAL: selección de marcas (CASE) -->
<div class="modal-backdrop" id="modalMarcas">
  <div class="modal-box">
    <div class="modal-marcas-head">
      <h2>🏷️ Selecciona las marcas</h2>
      <p>Elige una o varias marcas para inventariar. Solo se incluirán fundas de las marcas seleccionadas.</p>
    </div>
    <div class="modal-marcas-body">
      <div id="marcaAlert">⚠ Debes seleccionar al menos una marca para continuar.</div>
      <div id="marcaGrid" class="marca-grid"></div>
    </div>
    <div class="modal-marcas-footer">
      <span class="marca-sel-info"><strong id="marcaSelCount">0</strong> marcas seleccionadas</span>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="marca-selall-btn" id="btnMarcaSelAll">Seleccionar todas</button>
        <button class="btn btn-ghost" id="btnMarcaCancel">Cancelar</button>
        <button class="btn btn-cyan" id="btnMarcaConfirm">Continuar →</button>
      </div>
    </div>
  </div>
</div>

<!-- SCREEN 0 — BLOQUEADO -->
<section class="screen active" id="screen-blocked">
  <div class="section-title" style="text-align:center;margin-top:60px">
    <h1>⚠ Existencias <em>desactualizadas</em></h1>
    <p id="blockedMsg">Verificando…</p>
  </div>
</section>

<!-- SCREEN 1 — CONFIG -->
<section class="screen" id="screen-config">
  <div class="step-trail">
    <span class="st active">1 — Configurar</span>
    <span class="sep">›</span>
    <span class="st">2 — Escanear</span>
    <span class="sep">›</span>
    <span class="st">3 — Exportar</span>
  </div>
  <div class="section-title">
    <h1>Configura tu <em>auditoría</em></h1>
    <p>Elige sucursal y categorías antes de comenzar el inventario.</p>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="card-head-icon">🏪</div>
      <div><h2>Sucursal</h2><p>Selecciona la tienda a inventariar</p></div>
    </div>
    <div class="card-body">
      <div id="sucursalGrid" class="sucursal-grid">
        <div class="empty"><p>Cargando sucursales…</p></div>
      </div>
    </div>
  </div>

  <div class="card" id="catCard" style="display:none">
    <div class="card-head">
      <div class="card-head-icon">📂</div>
      <div><h2>Categorías</h2><p>Selecciona qué productos incluir en la auditoría</p></div>
    </div>
    <div class="card-body">
      <div id="catTree" class="cat-tree"></div>
      <div class="selection-summary" id="selSummary">
        <span id="selSummaryText">Ninguna categoría seleccionada</span>
        <div class="sel-chips" id="selChips"></div>
      </div>
    </div>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <button class="btn btn-ghost" id="btnVolverSucursal" style="display:none">← Cambiar sucursal</button>
    <button class="btn btn-cyan btn-lg" id="btnIniciarConfig" disabled style="margin-left:auto">Cargar productos →</button>
  </div>
</section>

<!-- SCREEN 2A — PLOWS -->
<section class="screen" id="screen-plows">
  <div class="step-trail">
    <span class="st done">1 — Configurar</span>
    <span class="sep">›</span>
    <span class="st active">2 — Escanear</span>
    <span class="sep">›</span>
    <span class="st">3 — Exportar</span>
  </div>
  <div class="scan-context">
    <span class="scan-context-store" id="ctxStorePlows">—</span>
    <span class="scan-context-sep">·</span>
    <span id="ctxCatsPlowsWrap" style="display:contents">
      <span class="scan-context-cats" id="ctxCatsPlows">—</span>
    </span>
  </div>

  <div class="kpi-row">
    <div class="kpi total">  <div class="kpi-label">Esperados</div>  <div class="kpi-value" id="kpiTotal">0</div></div>
    <div class="kpi found">  <div class="kpi-label">Escaneados</div> <div class="kpi-value" id="kpiFound">0</div></div>
    <div class="kpi missing"><div class="kpi-label">Pendientes</div> <div class="kpi-value" id="kpiMissing">0</div></div>
    <div class="kpi extra">  <div class="kpi-label">Sobrantes</div>  <div class="kpi-value" id="kpiExtra">0</div></div>
  </div>

  <div class="prog-bar-wrap">
    <div class="prog-bar-head"><span>Progreso del inventario</span><strong id="progPct">0%</strong></div>
    <div class="prog-track"><div class="prog-fill" id="progFill" style="width:0"></div></div>
  </div>

  <div class="scan-zone">
    <div class="scan-zone-label"><div class="active-dot"></div>Zona de escaneo — PLOWS</div>
    <div class="scan-input-row">
      <input type="text" class="scan-input" id="inputPlows"
        placeholder="PLOWS + 6 dígitos (ej. PLOWS123456)"
        autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
      <button class="btn btn-ghost" id="btnManualPlows" type="button">↵</button>
    </div>
    <div class="scan-result" id="resultPlows">
      <div>
        <div id="resultPlowsMsg">—</div>
        <div class="scan-result-sub" id="resultPlowsSub">—</div>
      </div>
    </div>
    <div class="undo-bar" id="undoBarPlows">
      <span id="undoPlowsText">—</span>
      <button class="btn btn-ghost" style="padding:5px 14px;font-size:.75rem" id="btnUndoPlows" type="button">↩ Deshacer</button>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="card-head-icon">📋</div>
      <div><h2>Historial de productos</h2><p id="histSubtitlePlows">0 productos en auditoría</p></div>
    </div>
    <div class="card-body">
      <div class="filter-tabs" id="filterTabs">
        <div class="ftab active" data-filter="all">Todos</div>
        <div class="ftab" data-filter="pending">Pendientes</div>
        <div class="ftab" data-filter="ok">Coincidentes</div>
        <div class="ftab" data-filter="missing">Faltantes</div>
        <div class="ftab" data-filter="extra">Sobrantes</div>
      </div>
      <div class="prod-table-wrap">
        <table class="prod-table">
          <thead>
            <tr>
              <th>PLOWS</th><th>Descripción</th>
              <th class="num-cell">BD</th><th class="num-cell">Contado</th>
              <th class="num-cell">Dif.</th><th>Estado</th><th></th>
            </tr>
          </thead>
          <tbody id="prodTableBody"></tbody>
        </table>
      </div>
      <div id="unknownSection">
        <div class="alert warn">
          <span class="alert-icon">⚠</span>
          <div><strong>Productos no identificados:</strong> <span id="unknownCount">0</span> PLOWS no encontrados en el inventario.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="action-row">
    <button class="btn btn-green btn-lg" id="btnFinalizarPlows" type="button">📥 Finalizar y exportar</button>
    <button class="btn btn-ghost" id="btnCancelarPlows" type="button">✕ Cancelar auditoría</button>
  </div>
</section>

<!-- SCREEN 2B — SERIES -->
<section class="screen" id="screen-series">
  <div class="step-trail">
    <span class="st done">1 — Configurar</span>
    <span class="sep">›</span>
    <span class="st active">2 — Escanear</span>
    <span class="sep">›</span>
    <span class="st">3 — Exportar</span>
  </div>
  <div class="scan-context">
    <span class="scan-context-store" id="ctxStoreSeries">—</span>
    <span class="scan-context-sep">·</span>
    <span class="scan-context-cats" id="ctxCatsSeries">—</span>
  </div>

  <div class="kpi-row">
    <div class="kpi total">  <div class="kpi-label">Total series</div>   <div class="kpi-value" id="skpiTotal">0</div></div>
    <div class="kpi found">  <div class="kpi-label">Encontradas</div>    <div class="kpi-value" id="skpiFound">0</div></div>
    <div class="kpi missing"><div class="kpi-label">Faltantes</div>      <div class="kpi-value" id="skpiMissing">0</div></div>
    <div class="kpi extra">  <div class="kpi-label">No registradas</div> <div class="kpi-value" id="skpiExtra">0</div></div>
  </div>

  <div class="prog-bar-wrap">
    <div class="prog-bar-head"><span>Progreso</span><strong id="sprogPct">0%</strong></div>
    <div class="prog-track"><div class="prog-fill" id="sprogFill" style="width:0"></div></div>
  </div>

  <div class="scan-zone">
    <div class="scan-zone-label"><div class="active-dot"></div>Zona de escaneo — IMEI / Series</div>
    <div class="scan-input-row">
      <input type="text" class="scan-input" id="inputSeries"
        placeholder="Escanea un IMEI o número de serie…"
        autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
      <button class="btn btn-ghost" id="btnManualSeries" type="button">↵</button>
    </div>
    <div class="scan-result" id="resultSeries">
      <div>
        <div id="resultSeriesMsg">—</div>
        <div class="scan-result-sub" id="resultSeriesSub">—</div>
      </div>
    </div>
    <div class="undo-bar" id="undoBarSeries">
      <span id="undoSeriesText">—</span>
      <button class="btn btn-ghost" style="padding:5px 14px;font-size:.75rem" id="btnUndoSeries" type="button">↩ Deshacer</button>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div class="card-head-icon">📱</div>
      <div><h2>Series</h2><p id="histSubtitleSeries">0 series en auditoría</p></div>
    </div>
    <div class="card-body">
      <div class="filter-tabs" id="filterTabsSeries">
        <div class="ftab active" data-filter="all">Todas</div>
        <div class="ftab" data-filter="pending">Pendientes</div>
        <div class="ftab" data-filter="found">Encontradas</div>
        <div class="ftab" data-filter="extra">No registradas</div>
      </div>
      <div class="series-tag-wrap" id="seriesTagWrap">
        <div class="empty"><span class="e-ico">🔍</span><p>Las series aparecerán aquí</p></div>
      </div>
    </div>
  </div>

  <div class="action-row">
    <button class="btn btn-green btn-lg" id="btnFinalizarSeries" type="button">📥 Finalizar y exportar</button>
    <button class="btn btn-ghost" id="btnCancelarSeries" type="button">✕ Cancelar auditoría</button>
  </div>
</section>

<!-- SCREEN 3 — RESULTADO -->
<section class="screen" id="screen-result">
  <div class="step-trail">
    <span class="st done">1 — Configurar</span>
    <span class="sep">›</span>
    <span class="st done">2 — Escanear</span>
    <span class="sep">›</span>
    <span class="st active">3 — Exportar</span>
  </div>
  <div class="result-hero">
    <h1>Inventario <em>completado</em></h1>
    <p id="resultSubtitle">Resumen del proceso</p>
  </div>
  <div class="result-kpis">
    <div class="rkpi g"><div class="rkpi-num" id="rFound">0</div><div class="rkpi-lbl">Coincidentes</div></div>
    <div class="rkpi a"><div class="rkpi-num" id="rMissing">0</div><div class="rkpi-lbl">Faltantes</div></div>
    <div class="rkpi p"><div class="rkpi-num" id="rExtra">0</div><div class="rkpi-lbl">Sobrantes / No reg.</div></div>
  </div>
  <div class="export-actions">
    <button class="btn btn-green btn-lg" id="btnExport" type="button">📊 Descargar Excel</button>
    <button class="btn btn-cyan" id="btnNewAudit" type="button">↺ Nueva auditoría</button>
  </div>
</section>

<div id="snackbar"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
'use strict';

const API = 'api.php';
const SESSION_KEY = 'inventascan_session';

// Categorías que requieren filtro por marca
const CATS_CON_MARCA = [
  'INNOVACION MOVIL>CASE>CELULAR',
  'INNOVACION MOVIL>CASE>TABLET',
  'INNOVACION MOVIL>CASE>AUDIFONO',
];

function esCategoriaConMarca(cats) {
  return cats.some(c => {
    const normalizada = c.trim().toUpperCase().replace(/\s*>\s*/g, '>');
    return CATS_CON_MARCA.some(ref => {
      const refNorm = ref.trim().toUpperCase().replace(/\s*>\s*/g, '>');
      return normalizada === refNorm || normalizada.startsWith(refNorm);
    });
  });
}

// Extrae la marca desde el campo descripcion
// Formato dentro de paréntesis: "Nombre producto / MARCA / modelo"
function extraerMarcaDeDescripcion(descripcion) {
  if (!descripcion) return null;
  const match = descripcion.match(/\(([^)]+)\)/);
  if (!match) return null;
  const partes = match[1].split('/').map(s => s.trim());
  if (partes.length >= 2) return partes[partes.length - 2].toUpperCase();
  return null;
}

// Obtiene marcas únicas de una lista de productos
function obtenerMarcasUnicas(productos) {
  const marcasMap = {};
  productos.forEach(p => {
    const marca = extraerMarcaDeDescripcion(p.descripcion);
    if (marca) {
      if (!marcasMap[marca]) marcasMap[marca] = 0;
      marcasMap[marca]++;
    }
  });
  return marcasMap; // { SAMSUNG: 34, IPHONE: 22, ... }
}

// ── Estado global ──────────────────────────────────────────────
const S = {
  sucursal:       null,
  categorias:     [],
  modo:           null,
  locked:         false,
  marcasSeleccionadas: [], // solo para inventarios CASE

  // Plows
  productos:       {},
  sobrantes:       [],
  noIdentificados: [],
  filterActive:    'all',
  lastScanPlows:   null,

  // Series
  seriesMap:          new Set(),
  seriesProducto:     {},
  seriesEncontradas:  new Set(),
  seriesExtra:        [],
  filterActiveSeries: 'all',
  lastScanSeries:     null,

  lockout:    false,
  LOCKOUT_MS: 300,
  audioCtx:   null,
};

// ── DOM helpers ───────────────────────────────────────────────
const $ = id => document.getElementById(id);

function showScreen(name) {
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  $('screen-' + name).classList.add('active');
  if (name === 'plows')  setTimeout(() => focusScan('inputPlows'), 80);
  if (name === 'series') setTimeout(() => focusScan('inputSeries'), 80);
}

function showLoad(txt='Cargando…') { $('loadingText').textContent=txt; $('loadingOverlay').classList.add('show'); }
function hideLoad() { $('loadingOverlay').classList.remove('show'); }

function snack(msg, type='') {
  const el=document.createElement('div'); el.className='snack '+type; el.textContent=msg;
  $('snackbar').appendChild(el);
  setTimeout(()=>{ el.style.animation='snackOut .2s ease both'; setTimeout(()=>el.remove(),200); },3000);
}

async function api(action, params={}, method='GET') {
  try {
    let url=API+'?action='+action, opts={};
    if (method==='POST') {
      const fd=new FormData(); fd.append('action',action);
      Object.entries(params).forEach(([k,v])=>fd.append(k,typeof v==='object'?JSON.stringify(v):v));
      opts={method:'POST',body:fd};
    } else if (Object.keys(params).length) url+='&'+new URLSearchParams(params).toString();
    const r=await fetch(url,opts); return await r.json();
  } catch(e) { return {ok:false,error:'Error de red: '+e.message}; }
}

// ── Audio ─────────────────────────────────────────────────────
function getAudio() { if (!S.audioCtx) S.audioCtx=new(window.AudioContext||window.webkitAudioContext)(); return S.audioCtx; }
function playTone(freq,type,dur,vol=.15) {
  try {
    const ctx=getAudio(),osc=ctx.createOscillator(),gain=ctx.createGain();
    osc.connect(gain); gain.connect(ctx.destination);
    osc.type=type; osc.frequency.setValueAtTime(freq,ctx.currentTime);
    gain.gain.setValueAtTime(vol,ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(.001,ctx.currentTime+dur);
    osc.start(); osc.stop(ctx.currentTime+dur);
  } catch(e){}
}
const sndOK  = ()=>{ playTone(880,'sine',.1); setTimeout(()=>playTone(1100,'sine',.09),75); };
const sndErr = ()=>playTone(260,'square',.22,.12);
const sndDup = ()=>playTone(580,'triangle',.18,.1);
const vibrate= ms=>navigator.vibrate&&navigator.vibrate(ms);

// ══════════════════════════════════════════════════════════════
//  MODAL CONFIRMACIÓN
// ══════════════════════════════════════════════════════════════
function showConfirm({ icon='⚠️', title, message, okLabel='Confirmar', okClass='btn-red', onOk, onCancel }) {
  $('confirmIcon').textContent  = icon;
  $('confirmTitle').textContent = title;
  $('confirmMsg').textContent   = message;
  const okBtn = $('confirmBtnOk');
  okBtn.textContent = okLabel;
  okBtn.className   = 'btn ' + okClass;
  $('modalConfirm').classList.add('show');

  const cleanup = () => {
    $('modalConfirm').classList.remove('show');
    okBtn.replaceWith(okBtn.cloneNode(true));
    $('confirmBtnCancel').replaceWith($('confirmBtnCancel').cloneNode(true));
  };

  $('confirmBtnOk').addEventListener('click', () => { cleanup(); onOk && onOk(); },   { once:true });
  $('confirmBtnCancel').addEventListener('click', () => { cleanup(); onCancel && onCancel(); }, { once:true });
}

// ══════════════════════════════════════════════════════════════
//  MODAL MARCAS
// ══════════════════════════════════════════════════════════════
function abrirModalMarcas(productos, onConfirm, onCancel) {
  const marcasMap = obtenerMarcasUnicas(productos);
  const marcas = Object.keys(marcasMap).sort();

  if (marcas.length === 0) {
    // Sin marcas detectables → continuar directo
    onConfirm([]);
    return;
  }

  const grid = $('marcaGrid');
  grid.innerHTML = '';
  $('marcaAlert').style.display = 'none';

  let seleccionadas = new Set();

  function actualizarUI() {
    $('marcaSelCount').textContent = seleccionadas.size;
    grid.querySelectorAll('.marca-btn').forEach(btn => {
      const m = btn.dataset.marca;
      btn.classList.toggle('selected', seleccionadas.has(m));
      btn.querySelector('.marca-chk').textContent = seleccionadas.has(m) ? '✓' : '';
    });
    $('btnMarcaSelAll').textContent =
      seleccionadas.size === marcas.length ? 'Quitar todas' : 'Seleccionar todas';
  }

  marcas.forEach(marca => {
    const btn = document.createElement('button');
    btn.className = 'marca-btn';
    btn.type = 'button';
    btn.dataset.marca = marca;
    btn.innerHTML = `<span class="marca-chk"></span><span>${marca}<span class="marca-count-badge">${marcasMap[marca]} prod.</span></span>`;
    btn.addEventListener('click', () => {
      if (seleccionadas.has(marca)) seleccionadas.delete(marca);
      else seleccionadas.add(marca);
      $('marcaAlert').style.display = 'none';
      actualizarUI();
    });
    grid.appendChild(btn);
  });

  actualizarUI();
  $('modalMarcas').classList.add('show');

  // Seleccionar / quitar todas
  $('btnMarcaSelAll').onclick = () => {
    if (seleccionadas.size === marcas.length) seleccionadas.clear();
    else marcas.forEach(m => seleccionadas.add(m));
    $('marcaAlert').style.display = 'none';
    actualizarUI();
  };

  // Cancelar
  $('btnMarcaCancel').onclick = () => {
    $('modalMarcas').classList.remove('show');
    onCancel && onCancel();
  };

  // Confirmar
  $('btnMarcaConfirm').onclick = () => {
    if (seleccionadas.size === 0) {
      $('marcaAlert').style.display = 'block';
      return;
    }
    $('modalMarcas').classList.remove('show');
    onConfirm([...seleccionadas]);
  };
}

// Filtra productos por marcas seleccionadas
function filtrarPorMarcas(productos, marcas) {
  if (!marcas || marcas.length === 0) return productos;
  const marcasSet = new Set(marcas.map(m => m.toUpperCase()));
  return productos.filter(p => {
    const m = extraerMarcaDeDescripcion(p.descripcion);
    return m && marcasSet.has(m);
  });
}

// ══════════════════════════════════════════════════════════════
//  SESIÓN
// ══════════════════════════════════════════════════════════════
function saveSession() {
  try {
    const session = {
      sucursal:    S.sucursal,
      categorias:  S.categorias,
      modo:        S.modo,
      marcasSeleccionadas: S.marcasSeleccionadas,
      screen:      document.querySelector('.screen.active')?.id || '',
    };
    if (S.modo === 'plows') {
      session.productos       = S.productos;
      session.sobrantes       = S.sobrantes;
      session.noIdentificados = S.noIdentificados;
    } else if (S.modo === 'series') {
      session.seriesProducto    = S.seriesProducto;
      session.seriesEncontradas = [...S.seriesEncontradas];
      session.seriesExtra       = S.seriesExtra;
    }
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));
  } catch(e){}
}

function clearSession() {
  try { localStorage.removeItem(SESSION_KEY); } catch(e){}
}

async function tryRestoreSession() {
  try {
    const raw = localStorage.getItem(SESSION_KEY);
    if (!raw) return false;
    const sess = JSON.parse(raw);
    if (!sess.sucursal || !sess.categorias || !sess.modo) return false;

    S.sucursal           = sess.sucursal;
    S.categorias         = sess.categorias;
    S.modo               = sess.modo;
    S.locked             = true;
    S.marcasSeleccionadas = sess.marcasSeleccionadas || [];

    if (sess.modo === 'plows') {
      S.productos       = sess.productos || {};
      S.sobrantes       = sess.sobrantes || [];
      S.noIdentificados = sess.noIdentificados || [];
      restoreUIPlows();
    } else if (sess.modo === 'series') {
      S.seriesProducto    = sess.seriesProducto || {};
      S.seriesEncontradas = new Set(sess.seriesEncontradas || []);
      S.seriesExtra       = sess.seriesExtra || [];
      S.seriesMap         = new Set(Object.keys(S.seriesProducto).map(k=>k.toUpperCase()));
      restoreUISeries();
    }
    return true;
  } catch(e) { return false; }
}

function renderCtxMarcas(containerId) {
  const el = $(containerId);
  if (!el) return;
  if (S.marcasSeleccionadas && S.marcasSeleccionadas.length > 0) {
    el.innerHTML = S.marcasSeleccionadas.map(m =>
      `<span class="marca-tag-ctx">${m}</span>`
    ).join('');
  }
}

function restoreUIPlows() {
  $('ctxStorePlows').textContent = S.sucursal.nombre;
  $('ctxCatsPlows').textContent  = S.categorias.map(c=>c.split('>').pop()).join(', ');
  renderCtxMarcas('ctxCatsPlowsWrap');
  updateKpisPlows();
  renderProdTable();
  updateUnknownBanner();
  showScreen('plows');
  snack('📂 Sesión restaurada — continúa desde donde la dejaste', 'good');
}

function restoreUISeries() {
  $('ctxStoreSeries').textContent = S.sucursal.nombre;
  $('ctxCatsSeries').textContent  = S.categorias.map(c=>c.split('>').pop()).join(', ');
  updateKpisSeries();
  renderSeriesTags();
  showScreen('series');
  snack('📂 Sesión restaurada — continúa desde donde la dejaste', 'good');
}

// ══════════════════════════════════════════════════════════════
//  INICIO
// ══════════════════════════════════════════════════════════════
(async function init() {
  showLoad('Verificando existencias…');
  const res = await api('check_fecha');

  if (!res.ok) {
    hideLoad();
    $('statusDot').style.background = 'var(--red)';
    $('statusDot').style.boxShadow  = '0 0 6px var(--red)';
    $('statusLabel').textContent    = 'Existencias desactualizadas';
    $('blockedMsg').textContent     = res.error;
    showScreen('blocked');
    return;
  }

  $('statusLabel').textContent = 'Existencias al día · ' + res.data.fecha;

  const restored = await tryRestoreSession();
  if (restored) { hideLoad(); return; }

  const resSuc = await api('get_sucursales');
  hideLoad();

  if (!resSuc.ok) { $('blockedMsg').textContent = resSuc.error; showScreen('blocked'); return; }

  renderSucursales(resSuc.data);
  showScreen('config');
})();

// ══════════════════════════════════════════════════════════════
//  SCREEN 1 — CONFIGURACIÓN
// ══════════════════════════════════════════════════════════════
async function cargarSucursales() {
  $('sucursalGrid').innerHTML = '<div class="empty"><p>Cargando sucursales…</p></div>';
  $('catCard').style.display = 'none';
  $('btnIniciarConfig').disabled = true;
  $('btnVolverSucursal').style.display = 'none';
  catState = {};
  updateSelSummary();
  const resSuc = await api('get_sucursales');
  if (!resSuc.ok) { snack('⚠ ' + resSuc.error, 'warn'); return; }
  renderSucursales(resSuc.data);
}

function renderSucursales(list) {
  const grid = $('sucursalGrid');
  grid.innerHTML = '';
  list.forEach(s => {
    const btn = document.createElement('button');
    btn.className = 'sucursal-btn';
    btn.dataset.id = s.almacen; btn.dataset.nombre = s.nombre;
    btn.innerHTML = `<span class="sb-id">ID ${s.almacen}</span>${s.nombre}`;
    btn.addEventListener('click', () => selectSucursal(s));
    grid.appendChild(btn);
  });
}

async function selectSucursal(s) {
  if (S.locked) return;
  S.sucursal = s;
  document.querySelectorAll('.sucursal-btn').forEach(b=>b.classList.remove('selected'));
  document.querySelector(`.sucursal-btn[data-id="${s.almacen}"]`)?.classList.add('selected');

  $('catCard').style.display = 'none';
  $('btnIniciarConfig').disabled = true;
  $('btnVolverSucursal').style.display = 'none';

  showLoad('Cargando categorías…');
  const res = await api('get_categorias', { sucursal: s.almacen });
  hideLoad();

  if (!res.ok) { snack('⚠ ' + res.error, 'warn'); return; }

  catState = {};
  renderCatTree(res.data);
  $('catCard').style.display = 'block';
  $('btnVolverSucursal').style.display = 'inline-flex';
  $('catCard').scrollIntoView({ behavior:'smooth', block:'start' });
}

$('btnVolverSucursal').addEventListener('click', () => {
  $('catCard').style.display = 'none';
  $('btnIniciarConfig').disabled = true;
  $('btnVolverSucursal').style.display = 'none';
  catState = {};
  updateSelSummary();
  $('sucursalGrid').scrollIntoView({ behavior:'smooth', block:'start' });
});

// ─ Árbol categorías ──────────────────────────────────────────
let catState = {};

function renderCatTree(tree, parentPath='', container=null) {
  const wrap = container || $('catTree');
  if (!container) wrap.innerHTML = '';
  catState = {};
  Object.keys(tree).forEach(root => {
    const path = parentPath ? parentPath+'>'+root : root;
    const div = document.createElement('div'); div.className='cat-root';
    const label = document.createElement('div'); label.className='cat-root-label';
    label.innerHTML = `<span class="chk" id="chk_${cssId(path)}">✓</span><span>${root}</span><span class="toggle-arrow" id="arr_${cssId(path)}">›</span>`;
    div.appendChild(label);
    const children = document.createElement('div'); children.className='cat-children open'; children.id='ch_'+cssId(path);
    buildCatChildren(tree[root], path, children);
    div.appendChild(children);
    wrap.appendChild(div);
    label.addEventListener('click', ()=>toggleCatNode(path));
    $('arr_'+cssId(path)).addEventListener('click', e=>{ e.stopPropagation(); children.classList.toggle('open'); $('arr_'+cssId(path)).classList.toggle('open'); });
  });
}

function buildCatChildren(subtree, parentPath, container) {
  if (!subtree || Object.keys(subtree).length===0) return;
  Object.keys(subtree).forEach(key => {
    const path = parentPath+'>'+key;
    const div = document.createElement('div'); div.className='cat-sub';
    const grandchildren = subtree[key]; const hasChildren = grandchildren && Object.keys(grandchildren).length>0;
    const label = document.createElement('div'); label.className=hasChildren?'cat-sub-label':'cat-leaf';
    label.innerHTML = `<span class="chk" id="chk_${cssId(path)}">✓</span><span>${key}</span>${hasChildren?`<span class="toggle-arrow" id="arr_${cssId(path)}">›</span>`:''}`;
    div.appendChild(label);
    if (hasChildren) {
      const subCh=document.createElement('div'); subCh.className='cat-sub-children open'; subCh.id='ch_'+cssId(path);
      buildCatChildren(grandchildren, path, subCh); div.appendChild(subCh);
      label.querySelector('.toggle-arrow')?.addEventListener('click', e=>{ e.stopPropagation(); subCh.classList.toggle('open'); $('arr_'+cssId(path)).classList.toggle('open'); });
    }
    label.addEventListener('click', e=>{ if (e.target.classList.contains('toggle-arrow')) return; toggleCatNode(path); });
    container.appendChild(div);
  });
}

function cssId(path) { return path.replace(/[^a-zA-Z0-9]/g,'_'); }

function toggleCatNode(path) {
  const yaSeleccionado = !!catState[path];
  if (yaSeleccionado) {
    Object.keys(catState).forEach(k=>{ if (k===path||k.startsWith(path+'>')) catState[k]=false; });
  } else {
    catState[path]=true;
    const partes=path.split('>');
    for (let i=1;i<partes.length;i++) { const anc=partes.slice(0,i).join('>'); if (catState[anc]) catState[anc]=false; }
    Object.keys(catState).forEach(k=>{ if (k!==path&&k.startsWith(path+'>')) catState[k]=false; });
  }
  updateCatVisuals(); updateSelSummary();
}

function updateCatVisuals() {
  document.querySelectorAll('[id^="chk_"]').forEach(el=>{
    const matched=Object.keys(catState).find(k=>cssId(k)===el.id.replace('chk_',''));
    if (!matched) return;
    el.classList.toggle('checked',!!catState[matched]);
    el.textContent=catState[matched]?'✓':'';
  });
}

function getSelectedCategories() { return Object.keys(catState).filter(k=>catState[k]); }

function updateSelSummary() {
  const sel=getSelectedCategories(); const sum=$('selSummary');
  if (sel.length===0) {
    sum.className='selection-summary'; $('selSummaryText').textContent='Ninguna categoría seleccionada';
    $('selChips').innerHTML=''; $('btnIniciarConfig').disabled=true;
  } else {
    sum.className='selection-summary has-data'; $('selSummaryText').textContent=sel.length+' categoría(s) seleccionada(s)';
    $('selChips').innerHTML=sel.map(c=>`<span class="chip">${c.split('>').pop()}</span>`).join('');
    $('btnIniciarConfig').disabled=false;
  }
}

$('btnIniciarConfig').addEventListener('click', async () => {
  const cats=getSelectedCategories(); if (!cats.length||!S.sucursal) return;
  S.categorias=cats;
  showLoad('Cargando productos…');
  const res=await api('get_productos',{sucursal:S.sucursal.almacen,categorias:cats},'POST');
  hideLoad();
  if (!res.ok) { snack('⚠ '+res.error,'warn'); return; }

  const {productos, conSeries, sinSeries} = res.data;

  // ¿Requiere filtro por marca?
  const necesitaMarca = esCategoriaConMarca(cats);

  function continuar(prodsFiltrados, marcasSel) {
    S.marcasSeleccionadas = marcasSel || [];
    const pf = prodsFiltrados;
    const cS = pf.filter(p=>!!p.ListaSeries).length;
    const sS = pf.filter(p=>!p.ListaSeries).length;

    if (cS > 0 && sS > 0) {
      $('modalCountSin').textContent = sS + ' producto(s)';
      $('modalCountCon').textContent = cS + ' producto(s)';
      $('modalTipo').classList.add('show');
      S._productosTemp = pf;
    } else if (cS > 0) {
      iniciarModoSeries(pf);
    } else {
      iniciarModoPlows(pf);
    }
  }

  if (necesitaMarca) {
    abrirModalMarcas(
      productos,
      (marcasSel) => {
        const filtrados = filtrarPorMarcas(productos, marcasSel);
        if (filtrados.length === 0) {
          snack('⚠ No se encontraron productos para las marcas seleccionadas', 'warn');
          return;
        }
        continuar(filtrados, marcasSel);
      },
      () => { /* canceló → se queda en config */ }
    );
  } else {
    continuar(productos, []);
  }
});

$('modalBtnSinSeries').addEventListener('click', ()=>{
  $('modalTipo').classList.remove('show');
  iniciarModoPlows(S._productosTemp.filter(p=>!p.ListaSeries));
});
$('modalBtnConSeries').addEventListener('click', ()=>{
  $('modalTipo').classList.remove('show');
  iniciarModoSeries(S._productosTemp.filter(p=>p.ListaSeries));
});
$('modalBtnCancel').addEventListener('click', ()=>{ $('modalTipo').classList.remove('show'); });

// ══════════════════════════════════════════════════════════════
//  MODO A — PLOWS
// ══════════════════════════════════════════════════════════════
function iniciarModoPlows(productos) {
  S.modo='plows'; S.locked=true;
  S.productos={}; S.sobrantes=[]; S.noIdentificados=[]; S.filterActive='all'; S.lastScanPlows=null;

  productos.forEach(p=>{
    const plows=(p.plows||'').trim().toUpperCase(); if (!plows) return;
    S.productos[plows]={
      id:p.id, descripcion:p.descripcion, existencia:parseInt(p.existencia)||0,
      contado:0, almacenId:S.sucursal.almacen, categoria:p.categoria, precio:p.publico_general,
    };
  });

  $('ctxStorePlows').textContent = S.sucursal.nombre;
  // Mostrar categorías + marcas en el contexto
  const ctxWrap = $('ctxCatsPlowsWrap');
  if (S.marcasSeleccionadas && S.marcasSeleccionadas.length > 0) {
    ctxWrap.innerHTML =
      `<span class="scan-context-cats">${S.categorias.map(c=>c.split('>').pop()).join(', ')}</span>` +
      `<span class="scan-context-sep">·</span>` +
      `<span class="scan-context-marcas">${S.marcasSeleccionadas.map(m=>`<span class="marca-tag-ctx">${m}</span>`).join('')}</span>`;
  } else {
    ctxWrap.innerHTML = `<span class="scan-context-cats" id="ctxCatsPlows">${S.categorias.map(c=>c.split('>').pop()).join(', ')}</span>`;
  }

  updateKpisPlows(); renderProdTable();
  hideUndoPlows();
  saveSession();
  showScreen('plows');
  snack('✅ '+Object.keys(S.productos).length+' productos cargados');
}

// ── Escaneo PLOWS ─────────────────────────────────────────────
const inputPlows = $('inputPlows');
inputPlows.addEventListener('input', () => {
  const PREFIJO = 'PLOWS';
  let v = inputPlows.value.toUpperCase().replace(/[\s\n\r\t]/g, '');
  let resultado = '';
  let hayInvalido = false;

  for (let i = 0; i < v.length && i < 11; i++) {
    const c = v[i];
    if (i < 5) {
      if (c === PREFIJO[i]) { resultado += c; }
      else { hayInvalido = true; break; }
    } else {
      if (/\d/.test(c)) { resultado += c; }
      else { hayInvalido = true; break; }
    }
  }

  if (hayInvalido) {
    inputPlows.value = '';
    flashInput('inputPlows', 'err-flash');
    sndErr(); vibrate(80);
    return;
  }

  if (inputPlows.value !== resultado) inputPlows.value = resultado;

  if (/^PLOWS\d{6}$/.test(resultado)) {
    procesarPlows(resultado);
    inputPlows.value = '';
  }
});

inputPlows.addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    const v = inputPlows.value.trim().toUpperCase();
    if (!/^PLOWS\d{6}$/.test(v)) {
      flashInput('inputPlows', 'err-flash'); sndErr(); vibrate(80);
      showScanResult('resultPlows', 'warn', '⚠ Código incompleto', 'Formato requerido: PLOWS + 6 dígitos (ej. PLOWS123456)');
      return;
    }
    procesarPlows(v); inputPlows.value = '';
  }
});

$('btnManualPlows').addEventListener('click', () => {
  const v = inputPlows.value.trim().toUpperCase();
  if (!/^PLOWS\d{6}$/.test(v)) {
    flashInput('inputPlows', 'err-flash'); sndErr(); vibrate(80);
    showScanResult('resultPlows', 'warn', '⚠ Código incompleto', 'Formato requerido: PLOWS + 6 dígitos');
    focusScan('inputPlows'); return;
  }
  procesarPlows(v); inputPlows.value = ''; focusScan('inputPlows');
});

function procesarPlows(raw) {
  if (S.lockout) return;
  const plows=raw.toUpperCase().replace(/\s/g,'');
  if (!/^PLOWS\d{6}$/.test(plows)) return;
  S.lockout=true; setTimeout(()=>{ S.lockout=false; }, S.LOCKOUT_MS);

  const now=new Date();
  const hora=now.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  const fecha=now.toLocaleDateString('es-MX');

  if (S.productos[plows]) {
    S.productos[plows].contado++;
    S.lastScanPlows = { plows, type:'contado', prevContado: S.productos[plows].contado - 1 };
    showScanResult('resultPlows','ok','✔ '+S.productos[plows].descripcion, plows+' — piezas: '+S.productos[plows].contado);
    flashInput('inputPlows','ok-flash'); sndOK();
    showUndoPlows(`Revertir: ${plows} (${S.productos[plows].descripcion})`);
  } else {
    const idx = S.noIdentificados.length;
    S.noIdentificados.push({plows,fecha,hora});
    S.lastScanPlows = { plows, type:'noIdentificado', idx };
    updateUnknownBanner();
    showScanResult('resultPlows','err','✕ Producto no identificado', plows+' — no existe en el inventario de esta sucursal');
    flashInput('inputPlows','err-flash'); sndErr(); vibrate(200);
    showUndoPlows(`Revertir: ${plows} (no identificado)`);
  }
  updateKpisPlows(); renderProdTable(); saveSession();
}

function showUndoPlows(label) { $('undoPlowsText').textContent=label; $('undoBarPlows').classList.add('show'); }
function hideUndoPlows()       { $('undoBarPlows').classList.remove('show'); S.lastScanPlows=null; }

$('btnUndoPlows').addEventListener('click', ()=>{
  if (!S.lastScanPlows) return;
  const ls = S.lastScanPlows;
  if (ls.type === 'contado') {
    if (S.productos[ls.plows] && S.productos[ls.plows].contado > 0) {
      S.productos[ls.plows].contado = ls.prevContado;
      snack('↩ Escaneo revertido: '+ls.plows);
    }
  } else if (ls.type === 'noIdentificado') {
    S.noIdentificados.splice(ls.idx, 1);
    updateUnknownBanner();
    snack('↩ Escaneo no identificado revertido: '+ls.plows);
  }
  hideUndoPlows();
  updateKpisPlows(); renderProdTable(); saveSession();
});

function updateKpisPlows() {
  const prods=Object.values(S.productos);
  const total=prods.length;
  const totalContado=prods.reduce((s,p)=>s+p.contado,0);
  const totalEsp=prods.reduce((s,p)=>s+p.existencia,0);
  const pendientes=prods.filter(p=>p.contado<p.existencia).length;
  const extras=prods.filter(p=>p.contado>p.existencia).length;
  $('kpiTotal').textContent   = total.toLocaleString();
  $('kpiFound').textContent   = totalContado.toLocaleString();
  $('kpiMissing').textContent = pendientes.toLocaleString();
  $('kpiExtra').textContent   = extras.toLocaleString();
  const pct=totalEsp>0?Math.round(Math.min(totalContado/totalEsp,1)*100):0;
  $('progFill').style.width=$('progPct').textContent=pct+'%';
}

function getStatusPlows(p) {
  if (p.contado===0&&p.existencia>0) return 'pending';
  if (p.contado>0&&p.contado<p.existencia) return 'missing';
  if (p.contado===p.existencia) return 'ok';
  if (p.contado>p.existencia)  return 'extra';
  return 'pending';
}

function renderProdTable() {
  const tbody=$('prodTableBody'); const filter=S.filterActive;
  let rows=Object.entries(S.productos);
  if (filter!=='all') rows=rows.filter(([,p])=>getStatusPlows(p)===filter);
  rows.sort((a,b)=>b[1].contado-a[1].contado);
  if (rows.length===0) {
    tbody.innerHTML=`<tr><td colspan="7" style="text-align:center;padding:24px;color:var(--text-dim)">Sin registros para este filtro</td></tr>`; return;
  }
  tbody.innerHTML=rows.map(([plows,p])=>{
    const diff=p.contado-p.existencia; const status=getStatusPlows(p);
    const diffClass=diff>0?'diff-pos':diff<0?'diff-neg':'diff-zero';
    const badgeClass={pending:'sb-pending',ok:'sb-ok',missing:'sb-missing',extra:'sb-extra'}[status];
    const badgeLabel={pending:'Pendiente',ok:'OK',missing:'Faltante',extra:'Sobrante'}[status];
    const diffStr=diff>0?'+'+diff:String(diff);
    return `<tr>
      <td class="plows-cell">${plows}</td>
      <td class="desc-cell" title="${p.descripcion}">${p.descripcion}</td>
      <td class="num-cell">${p.existencia}</td>
      <td class="num-cell">${p.contado}</td>
      <td class="num-cell ${diffClass}">${diffStr}</td>
      <td><span class="status-badge ${badgeClass}">${badgeLabel}</span></td>
      <td>${p.contado>0?`<button class="btn-del-row" data-plows="${plows}" title="Restar una unidad">−1</button>`:''}</td>
    </tr>`;
  }).join('');
  tbody.querySelectorAll('.btn-del-row').forEach(btn=>{
    btn.addEventListener('click',()=>{
      const plows=btn.dataset.plows;
      if (S.productos[plows]&&S.productos[plows].contado>0) {
        S.productos[plows].contado--;
        updateKpisPlows(); renderProdTable(); saveSession();
        snack('↩ Restada 1 unidad de '+plows);
      }
    });
  });
  $('histSubtitlePlows').textContent=Object.keys(S.productos).length+' productos en auditoría';
}

function updateUnknownBanner() {
  $('unknownSection').style.display = S.noIdentificados.length>0 ? 'block' : 'none';
  $('unknownCount').textContent = S.noIdentificados.length;
}

document.querySelectorAll('#filterTabs .ftab').forEach(tab=>{
  tab.addEventListener('click',()=>{
    document.querySelectorAll('#filterTabs .ftab').forEach(t=>t.classList.remove('active'));
    tab.classList.add('active'); S.filterActive=tab.dataset.filter; renderProdTable();
  });
});

// ─ Cancelar / Finalizar PLOWS ────────────────────────────────
$('btnCancelarPlows').addEventListener('click', (e)=>{
  e.stopPropagation();
  showConfirm({
    icon: '🗑️',
    title: 'Cancelar auditoría',
    message: 'Se perderá toda la información capturada hasta ahora. Esta acción no se puede deshacer.',
    okLabel: 'Sí, cancelar',
    okClass: 'btn-red',
    onOk: () => {
      S.locked=false; clearSession(); S.modo=null; S.sucursal=null;
      S.categorias=[]; S.marcasSeleccionadas=[];
      showScreen('config');
      cargarSucursales();
    }
  });
});

$('btnFinalizarPlows').addEventListener('click', (e)=>{
  e.stopPropagation();
  const pendientes=Object.values(S.productos).filter(p=>p.contado===0&&p.existencia>0).length;
  if (pendientes>0) {
    showConfirm({
      icon: '📋',
      title: '¿Finalizar con pendientes?',
      message: `Hay ${pendientes} producto(s) que nunca fueron escaneados y se registrarán como FALTANTES. ¿Deseas continuar?`,
      okLabel: 'Sí, finalizar',
      okClass: 'btn-green',
      onOk: () => abrirRevisionPlows(),
    });
  } else {
    abrirRevisionPlows();
  }
});

// ══════════════════════════════════════════════════════════════
//  MODAL REVISIÓN — PLOWS
// ══════════════════════════════════════════════════════════════
function abrirRevisionPlows() {
  const prods = Object.entries(S.productos);
  const sobrantes = prods.filter(([,p])=>p.contado>p.existencia);
  const faltantes = prods.filter(([,p])=>p.contado<p.existencia);

  let html = '';
  const totalOk = prods.filter(([,p])=>p.contado===p.existencia).length;
  html += `<div class="rev-summary">
    <span class="rev-sum-badge ok">✔ ${totalOk} coincidentes</span>
    <span class="rev-sum-badge missing">↓ ${faltantes.length} faltantes</span>
    <span class="rev-sum-badge extra">↑ ${sobrantes.length} sobrantes</span>
  </div>`;

  if (sobrantes.length>0) {
    html += `<div class="phase-label" style="margin-top:16px">⬆ Sobrantes detectados</div>
    <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:10px;line-height:1.5">
      Si contaste un producto de más por error, usa <strong>"Corregir"</strong> para descontar unidades antes de exportar.
    </p>
    <table class="rev-table">
      <thead><tr><th>PLOWS</th><th>Descripción</th><th class="num">BD</th><th class="num">Contado</th><th class="num">Dif.</th><th></th></tr></thead>
      <tbody>`;
    sobrantes.forEach(([plows,p])=>{
      const diff=p.contado-p.existencia;
      html+=`<tr><td class="mono">${plows}</td><td class="desc">${p.descripcion}</td><td class="num">${p.existencia}</td><td class="num">${p.contado}</td><td class="num" style="color:var(--purple)">+${diff}</td><td><button class="btn-rev-fix" data-mode="plows-sobrante" data-plows="${plows}">Corregir −1</button></td></tr>`;
    });
    html+=`</tbody></table>`;
  } else {
    html+=`<div style="margin-top:12px"><div class="rev-empty"><span class="rev-empty-icon">✅</span>Sin sobrantes detectados</div></div>`;
  }

  if (faltantes.length>0) {
    html+=`<div class="phase-label" style="margin-top:20px">⬇ Faltantes detectados</div>
    <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:10px;line-height:1.5">
      Si encontraste el producto después de escanear, usa <strong>"Registrar +1"</strong> para agregarlo antes de exportar.
    </p>
    <table class="rev-table">
      <thead><tr><th>PLOWS</th><th>Descripción</th><th class="num">BD</th><th class="num">Contado</th><th class="num">Dif.</th><th></th></tr></thead>
      <tbody>`;
    faltantes.forEach(([plows,p])=>{
      const diff=p.contado-p.existencia;
      html+=`<tr><td class="mono">${plows}</td><td class="desc">${p.descripcion}</td><td class="num">${p.existencia}</td><td class="num">${p.contado}</td><td class="num" style="color:var(--amber)">${diff}</td><td><button class="btn-rev-fix" data-mode="plows-faltante" data-plows="${plows}">Registrar +1</button></td></tr>`;
    });
    html+=`</tbody></table>`;
  } else {
    html+=`<div style="margin-top:12px"><div class="rev-empty"><span class="rev-empty-icon">✅</span>Sin faltantes detectados</div></div>`;
  }

  $('revTitle').textContent    = 'Revisión antes de exportar';
  $('revSubtitle').textContent = 'Verifica sobrantes y faltantes. Puedes corregir antes de generar el reporte.';
  $('revBody').innerHTML = html;

  $('revBody').querySelectorAll('.btn-rev-fix').forEach(btn=>{
    btn.addEventListener('click', ()=>aplicarRevisionPlows(btn));
  });

  $('revBtnVolver').onclick  = ()=>{ $('modalRevision').classList.remove('show'); focusScan('inputPlows'); };
  $('revBtnConfirm').onclick = ()=>{ $('modalRevision').classList.remove('show'); finalizarAuditoria(); };
  $('modalRevision').classList.add('show');
}

function aplicarRevisionPlows(btn) {
  if (btn.classList.contains('applied')) return;
  const mode=btn.dataset.mode; const plows=btn.dataset.plows;
  if (!S.productos[plows]) return;
  if (mode==='plows-sobrante') {
    if (S.productos[plows].contado>0) S.productos[plows].contado--;
    btn.textContent='✔ Corregido'; btn.classList.add('applied');
  } else if (mode==='plows-faltante') {
    S.productos[plows].contado++;
    btn.textContent='✔ Registrado'; btn.classList.add('applied');
  }
  const row = btn.closest('tr');
  if (row) {
    const cells=row.querySelectorAll('td');
    const p=S.productos[plows];
    cells[3].textContent=p.contado;
    const diff=p.contado-p.existencia;
    cells[4].textContent=(diff>0?'+':'')+diff;
    cells[4].style.color=diff>0?'var(--purple)':diff<0?'var(--amber)':'var(--green)';
    if (diff===0) { btn.textContent='✔ OK'; btn.classList.add('applied'); }
  }
  updateKpisPlows(); saveSession();
}

// ══════════════════════════════════════════════════════════════
//  MODO B — SERIES
// ══════════════════════════════════════════════════════════════
function iniciarModoSeries(productos) {
  S.modo='series'; S.locked=true;
  S.seriesMap=new Set(); S.seriesProducto={}; S.seriesEncontradas=new Set(); S.seriesExtra=[];
  S.filterActiveSeries='all'; S.lastScanSeries=null;

  productos.forEach(p=>{
    if (!p.ListaSeries) return;
    p.ListaSeries.split(',').map(s=>s.trim().toUpperCase()).filter(s=>s.length>0).forEach(serie=>{
      S.seriesMap.add(serie);
      S.seriesProducto[serie]={descripcion:p.descripcion,plows:p.plows,productoId:p.id};
    });
  });

  $('ctxStoreSeries').textContent = S.sucursal.nombre;
  $('ctxCatsSeries').textContent  = S.categorias.map(c=>c.split('>').pop()).join(', ');
  updateKpisSeries(); renderSeriesTags();
  hideUndoSeries();
  saveSession();
  showScreen('series');
  snack('✅ '+S.seriesMap.size+' series cargadas');
}

// ── Escaneo Series ────────────────────────────────────────────
const inputSeries=$('inputSeries');
let seriesScanTimer=null;

inputSeries.addEventListener('input',()=>{
  clearTimeout(seriesScanTimer); const v=inputSeries.value.trim();
  seriesScanTimer=setTimeout(()=>{ if(v.length>4){ procesarSerie(v); inputSeries.value=''; } },180);
});
inputSeries.addEventListener('keydown', e=>{
  if (e.key==='Enter') { clearTimeout(seriesScanTimer); procesarSerie(inputSeries.value.trim()); inputSeries.value=''; }
});
$('btnManualSeries').addEventListener('click',()=>{
  clearTimeout(seriesScanTimer); procesarSerie(inputSeries.value.trim()); inputSeries.value=''; focusScan('inputSeries');
});

function procesarSerie(raw) {
  if (S.lockout) return;
  const serie=raw.toUpperCase().replace(/\s/g,''); if (!serie) return;
  S.lockout=true; setTimeout(()=>{ S.lockout=false; },S.LOCKOUT_MS);
  const now=new Date();
  const hora=now.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit',second:'2-digit'});

  if (S.seriesMap.has(serie)) {
    if (S.seriesEncontradas.has(serie)) {
      showScanResult('resultSeries','dup','⚠ Serie ya escaneada',serie);
      flashInput('inputSeries','dup-flash'); sndDup();
      S.lastScanSeries = { serie, type:'dup' };
      showUndoSeries(`Revertir duplicado: ${serie}`);
    } else {
      S.seriesEncontradas.add(serie);
      S.lastScanSeries = { serie, type:'found' };
      const prod=S.seriesProducto[serie];
      showScanResult('resultSeries','ok','✔ '+(prod?.descripcion||serie), serie+' — '+(prod?.plows||''));
      flashInput('inputSeries','ok-flash'); sndOK();
      showUndoSeries(`Revertir: ${serie}`);
      updateKpisSeries(); renderSeriesTags(); saveSession();
    }
  } else {
    S.seriesExtra.push({serie,hora});
    S.lastScanSeries = { serie, type:'extra', idx:S.seriesExtra.length-1 };
    showScanResult('resultSeries','err','✕ Serie no registrada',serie+' — no existe en la lista');
    flashInput('inputSeries','err-flash'); sndErr(); vibrate(200);
    showUndoSeries(`Revertir: ${serie} (no registrada)`);
    updateKpisSeries(); renderSeriesTags(); saveSession();
  }
}

function showUndoSeries(label) { $('undoSeriesText').textContent=label; $('undoBarSeries').classList.add('show'); }
function hideUndoSeries()       { $('undoBarSeries').classList.remove('show'); S.lastScanSeries=null; }

$('btnUndoSeries').addEventListener('click',()=>{
  if (!S.lastScanSeries) return;
  const ls=S.lastScanSeries;
  if (ls.type==='found') {
    S.seriesEncontradas.delete(ls.serie);
    snack('↩ Escaneo revertido: '+ls.serie);
    updateKpisSeries(); renderSeriesTags(); saveSession();
  } else if (ls.type==='extra') {
    S.seriesExtra.splice(ls.idx,1);
    snack('↩ Serie extra eliminada: '+ls.serie);
    updateKpisSeries(); renderSeriesTags(); saveSession();
  } else if (ls.type==='dup') {
    snack('ℹ No hay nada que revertir (duplicado no registrado)');
  }
  hideUndoSeries();
});

function updateKpisSeries() {
  const total=S.seriesMap.size, found=S.seriesEncontradas.size;
  const missing=total-found, extra=S.seriesExtra.length;
  const pct=total>0?Math.round((found/total)*100):0;
  $('skpiTotal').textContent=total.toLocaleString(); $('skpiFound').textContent=found.toLocaleString();
  $('skpiMissing').textContent=missing.toLocaleString(); $('skpiExtra').textContent=extra.toLocaleString();
  $('sprogFill').style.width=pct+'%'; $('sprogPct').textContent=pct+'%';
}

function renderSeriesTags() {
  const wrap=$('seriesTagWrap'); const filter=S.filterActiveSeries;
  let allSeries=[];
  S.seriesMap.forEach(s=>{ if(!S.seriesEncontradas.has(s)) allSeries.push({serie:s,type:'pending'}); });
  S.seriesEncontradas.forEach(s=>allSeries.push({serie:s,type:'found'}));
  S.seriesExtra.forEach(e=>allSeries.push({serie:e.serie,type:'extra'}));
  if (filter!=='all') allSeries=allSeries.filter(s=>s.type===filter);
  if (allSeries.length===0) {
    wrap.innerHTML=`<div class="empty"><span class="e-ico">🔍</span><p>Sin series para este filtro</p></div>`; return;
  }
  wrap.innerHTML=allSeries.slice(0,500).map(({serie,type})=>`<span class="series-tag ${type}">${serie}</span>`).join('');
  $('histSubtitleSeries').textContent=S.seriesMap.size+' series en auditoría';
}

document.querySelectorAll('#filterTabsSeries .ftab').forEach(tab=>{
  tab.addEventListener('click',()=>{
    document.querySelectorAll('#filterTabsSeries .ftab').forEach(t=>t.classList.remove('active'));
    tab.classList.add('active'); S.filterActiveSeries=tab.dataset.filter; renderSeriesTags();
  });
});

// ─ Cancelar / Finalizar Series ───────────────────────────────
$('btnCancelarSeries').addEventListener('click', (e)=>{
  e.stopPropagation();
  showConfirm({
    icon:'🗑️',
    title:'Cancelar auditoría',
    message:'Se perderá toda la información capturada hasta ahora. Esta acción no se puede deshacer.',
    okLabel:'Sí, cancelar',
    okClass:'btn-red',
    onOk: () => {
      S.locked=false; clearSession(); S.modo=null; S.sucursal=null;
      S.categorias=[]; S.marcasSeleccionadas=[];
      showScreen('config');
      cargarSucursales();
    }
  });
});

$('btnFinalizarSeries').addEventListener('click', (e)=>{
  e.stopPropagation();
  const pendientes=S.seriesMap.size-S.seriesEncontradas.size;
  if (pendientes>0) {
    showConfirm({
      icon:'📋',
      title:'¿Finalizar con pendientes?',
      message:`Hay ${pendientes} serie(s) que no fueron escaneadas y se registrarán como FALTANTES. ¿Deseas continuar?`,
      okLabel:'Sí, finalizar',
      okClass:'btn-green',
      onOk:()=>abrirRevisionSeries(),
    });
  } else {
    abrirRevisionSeries();
  }
});

// ══════════════════════════════════════════════════════════════
//  MODAL REVISIÓN — SERIES
// ══════════════════════════════════════════════════════════════
function abrirRevisionSeries() {
  const total=S.seriesMap.size, found=S.seriesEncontradas.size;
  const faltantes=[...S.seriesMap].filter(s=>!S.seriesEncontradas.has(s));
  const extras=S.seriesExtra;

  let html=`<div class="rev-summary">
    <span class="rev-sum-badge ok">✔ ${found} encontradas</span>
    <span class="rev-sum-badge missing">↓ ${faltantes.length} faltantes</span>
    <span class="rev-sum-badge extra">↑ ${extras.length} no registradas</span>
  </div>`;

  if (extras.length>0) {
    html+=`<div class="phase-label" style="margin-top:16px">⬆ Series no registradas (sobrantes)</div>
    <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:10px;line-height:1.5">
      Si escaneaste una serie por error, usa <strong>"Eliminar"</strong> para quitarla antes de exportar.
    </p>
    <table class="rev-table">
      <thead><tr><th>Serie</th><th>Hora escaneo</th><th></th></tr></thead>
      <tbody>`;
    extras.forEach((e,i)=>{
      html+=`<tr><td class="mono">${e.serie}</td><td style="color:var(--text-muted);font-size:.76rem">${e.hora}</td><td><button class="btn-rev-fix" data-mode="series-extra" data-idx="${i}">Eliminar</button></td></tr>`;
    });
    html+=`</tbody></table>`;
  } else {
    html+=`<div style="margin-top:12px"><div class="rev-empty"><span class="rev-empty-icon">✅</span>Sin series sobrantes</div></div>`;
  }

  if (faltantes.length>0) {
    html+=`<div class="phase-label" style="margin-top:20px">⬇ Series faltantes</div>
    <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:10px;line-height:1.5">
      Si encontraste la serie después, usa <strong>"Marcar encontrada"</strong> para registrarla antes de exportar.
    </p>
    <table class="rev-table">
      <thead><tr><th>Serie</th><th>Descripción</th><th></th></tr></thead>
      <tbody>`;
    faltantes.slice(0,200).forEach(s=>{
      const prod=S.seriesProducto[s];
      html+=`<tr><td class="mono">${s}</td><td class="desc">${prod?.descripcion||'—'}</td><td><button class="btn-rev-fix" data-mode="series-faltante" data-serie="${s}">Marcar encontrada</button></td></tr>`;
    });
    if (faltantes.length>200) html+=`<tr><td colspan="3" style="text-align:center;color:var(--text-dim);padding:10px;font-size:.75rem">… y ${faltantes.length-200} más</td></tr>`;
    html+=`</tbody></table>`;
  } else {
    html+=`<div style="margin-top:12px"><div class="rev-empty"><span class="rev-empty-icon">✅</span>Sin faltantes detectados</div></div>`;
  }

  $('revTitle').textContent    = 'Revisión antes de exportar';
  $('revSubtitle').textContent = 'Verifica sobrantes y faltantes. Puedes corregir antes de generar el reporte.';
  $('revBody').innerHTML=html;

  $('revBody').querySelectorAll('.btn-rev-fix').forEach(btn=>{
    btn.addEventListener('click',()=>aplicarRevisionSeries(btn));
  });

  $('revBtnVolver').onclick  = ()=>{ $('modalRevision').classList.remove('show'); focusScan('inputSeries'); };
  $('revBtnConfirm').onclick = ()=>{ $('modalRevision').classList.remove('show'); finalizarAuditoria(); };
  $('modalRevision').classList.add('show');
}

function aplicarRevisionSeries(btn) {
  if (btn.classList.contains('applied')) return;
  const mode=btn.dataset.mode;
  if (mode==='series-extra') {
    const idx=parseInt(btn.dataset.idx);
    if (!isNaN(idx) && S.seriesExtra[idx]) {
      S.seriesExtra.splice(idx,1);
      btn.textContent='✔ Eliminado'; btn.classList.add('applied');
      updateKpisSeries(); saveSession();
      const rows=btn.closest('tbody').querySelectorAll('tr');
      rows.forEach((row,i)=>{
        const b=row.querySelector('.btn-rev-fix');
        if (b && !b.classList.contains('applied')) b.dataset.idx=String(i);
      });
    }
  } else if (mode==='series-faltante') {
    const serie=btn.dataset.serie;
    if (serie && S.seriesMap.has(serie) && !S.seriesEncontradas.has(serie)) {
      S.seriesEncontradas.add(serie);
      btn.textContent='✔ Registrada'; btn.classList.add('applied');
      updateKpisSeries(); renderSeriesTags(); saveSession();
    }
  }
}

// ══════════════════════════════════════════════════════════════
//  FINALIZACIÓN COMÚN
// ══════════════════════════════════════════════════════════════
function finalizarAuditoria() {
  let found=0, missing=0, extra=0;
  if (S.modo==='plows') {
    Object.values(S.productos).forEach(p=>{
      const diff=p.contado-p.existencia;
      if(diff===0) found++; else if(diff<0) missing+=Math.abs(diff); else extra+=diff;
    });
    extra+=S.noIdentificados.length;
  } else {
    found=S.seriesEncontradas.size; missing=S.seriesMap.size-found; extra=S.seriesExtra.length;
  }
  $('rFound').textContent   = found.toLocaleString();
  $('rMissing').textContent = missing.toLocaleString();
  $('rExtra').textContent   = extra.toLocaleString();
  $('resultSubtitle').textContent = `${S.sucursal.nombre} · ${found} coincidentes · ${missing} faltantes · ${extra} sobrantes`;
  showScreen('result');
}

// ── Exportar Excel ────────────────────────────────────────────
$('btnExport').addEventListener('click', ()=>{
  const marcasSufijo = S.marcasSeleccionadas && S.marcasSeleccionadas.length > 0
    ? '_' + S.marcasSeleccionadas.join('-')
    : '';
  const nombreDefault = 'inventario_' + S.sucursal.nombre.replace(/\s/g,'_') + marcasSufijo + '_' + formatDate();
  const nombre = prompt('Nombre del archivo (sin extensión):', nombreDefault);
  if (nombre === null) return;
  const wb = XLSX.utils.book_new();
  const now = new Date();
  const meta = [
    [`InventaScan — Auditoría ${S.sucursal.nombre}`],
    [`Fecha: ${now.toLocaleDateString('es-MX')}  Hora: ${now.toLocaleTimeString('es-MX')}`],
    ['Categorías: '+S.categorias.join(', ')],
    S.marcasSeleccionadas && S.marcasSeleccionadas.length > 0
      ? ['Marcas: ' + S.marcasSeleccionadas.join(', ')]
      : [],
    [],
  ].filter(r => r.length > 0 || r === []);
  if (S.modo==='plows') exportPlowsExcel(wb, meta);
  else                  exportSeriesExcel(wb, meta);
  XLSX.writeFile(wb,(nombre||nombreDefault)+'.xlsx');
  snack('📊 Excel descargado','good');
  clearSession(); S.locked=false;
});

function exportPlowsExcel(wb, meta) {
  const prods=Object.entries(S.productos);
  const mainData=[
    ...meta,
    ['PLOWS','Descripción','Categoría','Existencia BD','Contado','Diferencia','Estado'],
    ...prods.map(([plows,p])=>{
      const diff=p.contado-p.existencia; const status=getStatusPlows(p);
      const label={pending:'Faltante',ok:'OK',missing:'Faltante',extra:'Sobrante'}[status];
      return [plows,p.descripcion,p.categoria,p.existencia,p.contado,diff,label];
    }),
    [],[,'TOTAL','',prods.reduce((s,[,p])=>s+p.existencia,0),prods.reduce((s,[,p])=>s+p.contado,0)],
  ];
  const ws1=XLSX.utils.aoa_to_sheet(mainData);
  ws1['!cols']=[{wch:14},{wch:40},{wch:30},{wch:14},{wch:10},{wch:10},{wch:12}];
  XLSX.utils.book_append_sheet(wb,ws1,'Inventario');

  const faltantes=prods.filter(([,p])=>p.contado<p.existencia);
  if (faltantes.length>0) {
    const ws2=XLSX.utils.aoa_to_sheet([...meta,['PLOWS','Descripción','Existencia BD','Contado','Faltante'],...faltantes.map(([plows,p])=>[plows,p.descripcion,p.existencia,p.contado,p.existencia-p.contado])]);
    ws2['!cols']=[{wch:14},{wch:40},{wch:14},{wch:10},{wch:10}];
    XLSX.utils.book_append_sheet(wb,ws2,'Faltantes');
  }
  const sobrantes=prods.filter(([,p])=>p.contado>p.existencia);
  if (sobrantes.length>0) {
    const ws3=XLSX.utils.aoa_to_sheet([...meta,['PLOWS','Descripción','Existencia BD','Contado','Sobrante'],...sobrantes.map(([plows,p])=>[plows,p.descripcion,p.existencia,p.contado,p.contado-p.existencia])]);
    ws3['!cols']=[{wch:14},{wch:40},{wch:14},{wch:10},{wch:10}];
    XLSX.utils.book_append_sheet(wb,ws3,'Sobrantes');
  }
  if (S.noIdentificados.length>0) {
    const ws4=XLSX.utils.aoa_to_sheet([...meta,['PLOWS','Fecha','Hora'],...S.noIdentificados.map(n=>[n.plows,n.fecha,n.hora])]);
    ws4['!cols']=[{wch:16},{wch:14},{wch:12}];
    XLSX.utils.book_append_sheet(wb,ws4,'No Identificados');
  }
}

function exportSeriesExcel(wb, meta) {
  const data1=[...meta,['#','Serie','Descripción','PLOWS'],...[...S.seriesEncontradas].map((s,i)=>{ const p=S.seriesProducto[s]; return [i+1,s,p?.descripcion||'',p?.plows||'']; }),[],['TOTAL',S.seriesEncontradas.size]];
  const sws1=XLSX.utils.aoa_to_sheet(data1);
  sws1['!cols']=[{wch:6},{wch:24},{wch:40},{wch:14}];
  XLSX.utils.book_append_sheet(wb,sws1,'Encontradas');

  const faltantes=[...S.seriesMap].filter(s=>!S.seriesEncontradas.has(s));
  if (faltantes.length>0) {
    const data2=[...meta,['#','Serie','Descripción','PLOWS'],...faltantes.map((s,i)=>{ const p=S.seriesProducto[s]; return [i+1,s,p?.descripcion||'',p?.plows||'']; }),[],['TOTAL',faltantes.length]];
    const sws2=XLSX.utils.aoa_to_sheet(data2);
    sws2['!cols']=[{wch:6},{wch:24},{wch:40},{wch:14}];
    XLSX.utils.book_append_sheet(wb,sws2,'Faltantes');
  }
  if (S.seriesExtra.length>0) {
    const data3=[...meta,['#','Serie','Hora'],...S.seriesExtra.map((e,i)=>[i+1,e.serie,e.hora]),[],['TOTAL',S.seriesExtra.length]];
    const sws3=XLSX.utils.aoa_to_sheet(data3);
    sws3['!cols']=[{wch:6},{wch:24},{wch:14}];
    XLSX.utils.book_append_sheet(wb,sws3,'No registradas');
  }
}

function formatDate() {
  const d=new Date(); return d.getFullYear()+pad(d.getMonth()+1)+pad(d.getDate())+'_'+pad(d.getHours())+pad(d.getMinutes());
}
const pad = n => String(n).padStart(2,'0');

$('btnNewAudit').addEventListener('click', ()=>{
  showConfirm({
    icon:'🔄',
    title:'Iniciar nueva auditoría',
    message:'Se perderá el historial de esta auditoría. ¿Deseas continuar?',
    okLabel:'Sí, nueva auditoría',
    okClass:'btn-cyan',
    onOk: () => {
      S.locked=false; S.modo=null; clearSession(); S.sucursal=null;
      S.categorias=[]; S.marcasSeleccionadas=[];
      showScreen('config');
      cargarSucursales();
    }
  });
});

// ══════════════════════════════════════════════════════════════
//  HELPERS UI
// ══════════════════════════════════════════════════════════════
function showScanResult(elId,type,msg,sub) {
  $(elId+'Msg').textContent=msg; $(elId+'Sub').textContent=sub;
  $(elId).className='scan-result show '+type;
}
function flashInput(inputId,cls) {
  const el=$(inputId); el.classList.remove('ok-flash','err-flash','dup-flash');
  void el.offsetWidth; el.classList.add(cls);
  setTimeout(()=>el.classList.remove(cls),700);
}
function focusScan(inputId) {
  const el=$(inputId);
  if (el) { el.focus(); el.select(); }
}

// ── Foco permanente en pantallas de escaneo ───────────────────
// Elementos que NO deben retornar el foco al input de escaneo
function esElementoInteractivo(target) {
  return target.matches('button, a, input, select, textarea, [tabindex]') ||
         target.closest('.modal-backdrop.show') !== null ||
         target.closest('.action-row') !== null ||
         target.closest('#undoBarPlows') !== null ||
         target.closest('#undoBarSeries') !== null;
}

document.addEventListener('click', e => {
  const screen = document.querySelector('.screen.active');
  if (!screen) return;
  if (esElementoInteractivo(e.target)) return;

  const id = screen.id;
  if (id === 'screen-plows')  setTimeout(() => focusScan('inputPlows'), 50);
  if (id === 'screen-series') setTimeout(() => focusScan('inputSeries'), 50);
}, true); // capture=true para interceptar antes que otros handlers

document.addEventListener('visibilitychange', () => {
  if (document.hidden) return;
  const screen = document.querySelector('.screen.active');
  if (!screen) return;
  if (screen.id === 'screen-plows')  setTimeout(() => focusScan('inputPlows'), 120);
  if (screen.id === 'screen-series') setTimeout(() => focusScan('inputSeries'), 120);
});

// Intervalo de reenfoque (solo cuando no hay modal abierto)
setInterval(() => {
  const screen = document.querySelector('.screen.active');
  if (!screen || document.hidden) return;
  const modalAbierto = document.querySelector('.modal-backdrop.show');
  if (modalAbierto) return;

  if (screen.id === 'screen-plows'  && document.activeElement !== inputPlows)  focusScan('inputPlows');
  if (screen.id === 'screen-series' && document.activeElement !== inputSeries) focusScan('inputSeries');
}, 1200);

// Prevenir que el blur robe el foco mientras hay modales
[inputPlows, inputSeries].forEach(inp => {
  inp.addEventListener('blur', () => {
    const targetScreen = inp === inputPlows ? 'screen-plows' : 'screen-series';
    setTimeout(() => {
      const modalAbierto = document.querySelector('.modal-backdrop.show');
      if (modalAbierto) return;
      if (document.querySelector('.screen.active')?.id === targetScreen) inp.focus();
    }, 120);
  });
});
</script>
</body>
</html>