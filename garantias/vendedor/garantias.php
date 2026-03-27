<?php 
include_once '../../funciones.php';  
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
$mensaje = "";  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {     
    try {         
        guardarGarantia($_POST);         
        $mensaje = "✅ Garantía registrada correctamente.";     
    } catch (Exception $e) {         
        $mensaje = "❌ Error al guardar: " . $e->getMessage();     
    } 
} 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Garantía</title>
    
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../../css.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <style>
        /* ── Toggle discreto de departamento ── */
        .dpto-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            font-size: .9em;
            color: #555;
        }
        .dpto-toggle span { font-weight: 600; }
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
            cursor: pointer;
        }
        .toggle-switch input { display: none; }
        .toggle-track {
            width: 100%;
            height: 100%;
            border-radius: 12px;
            background: #f5576c;
            transition: background .25s;
        }
        .toggle-switch input:checked ~ .toggle-track { background: #4facfe; }
        .toggle-thumb {
            position: absolute;
            top: 3px; left: 3px;
            width: 18px; height: 18px;
            border-radius: 50%;
            background: #fff;
            transition: left .25s;
            pointer-events: none;
        }
        .toggle-switch input:checked ~ .toggle-thumb { left: 27px; }
        .dpto-label-im { color: #f5576c; }
        .dpto-label-tm { color: #4facfe; }

        /* Fotos */
        .foto-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #4a90d9;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            cursor: pointer;
            font-size: .95em;
            margin-bottom: 6px;
        }
        .foto-btn:hover { background: #357abd; }
        .foto-contador   { font-size: .85em; color: #555; margin-left: 8px; }
        .foto-estado     { font-size: .85em; margin-top: 4px; }
        .foto-estado.ok       { color: #27ae60; }
        .foto-estado.error    { color: #e74c3c; }
        .foto-estado.cargando { color: #e67e22; }

        /* Bloques: visibilidad manejada inline */
    </style>

    <script>
        /* ── Huella digital ── */
        async function generarHuella() {
            const ua = navigator.userAgent;
            const partes = [];
            let modelo = '';
            if (/iPhone/.test(ua)) {
                const m = ua.match(/iPhone OS ([\d_]+)/);
                modelo = 'iPhone' + (m ? ' iOS '+m[1].replace(/_/g,'.') : '');
            } else if (/iPad/.test(ua)) {
                const m = ua.match(/CPU OS ([\d_]+)/);
                modelo = 'iPad' + (m ? ' iOS '+m[1].replace(/_/g,'.') : '');
            } else if (/Android/.test(ua)) {
                if (navigator.userAgentData?.getHighEntropyValues) {
                    try {
                        const info = await navigator.userAgentData.getHighEntropyValues(['model','brands']);
                        const mod = (info.model||'').trim();
                        let marca = '';
                        if (info.brands) {
                            const b = info.brands.find(x=> !/chromium|not.a.brand|google/i.test(x.brand));
                            if (b) marca = b.brand;
                        }
                        if (mod && mod.length>1) modelo = (marca?marca+' ':'')+mod;
                    } catch(e){}
                }
                if (!modelo) {
                    const m = ua.match(/Android[^;]*;\s*([^)]+)\)/);
                    if (m) {
                        let raw = m[1].replace(/Build\/.*/i,'').replace(/\bwv\b/gi,'').replace(/\s+/g,' ').trim();
                        const marcas = [
                            [/motorola|moto[\s_-]/i,'Motorola'],[/samsung|sm-|sch-/i,'Samsung'],
                            [/xiaomi|redmi|poco/i,'Xiaomi'],[/huawei/i,'Huawei'],
                            [/oneplus/i,'OnePlus'],[/oppo/i,'OPPO'],[/vivo/i,'Vivo'],
                            [/realme/i,'Realme'],[/nokia/i,'Nokia'],[/asus/i,'ASUS'],
                        ];
                        for (const [test,nombre] of marcas) { if(test.test(raw)){modelo=nombre+' '+raw;break;} }
                        if (!modelo) modelo = 'Android '+raw;
                    }
                }
                const av = ua.match(/Android ([\d.]+)/);
                if (av) modelo += ' (Android '+av[1]+')';
            } else {
                if      (/Edg\//.test(ua))     modelo='Edge escritorio';
                else if (/OPR\//.test(ua))     modelo='Opera escritorio';
                else if (/Chrome\//.test(ua))  modelo='Chrome escritorio';
                else if (/Firefox\//.test(ua)) modelo='Firefox escritorio';
                else if (/Safari\//.test(ua))  modelo='Safari escritorio';
                else modelo='Escritorio';
            }
            partes.push(modelo||'Disp. desconocido');
            partes.push(screen.width+'x'+screen.height+'@'+(window.devicePixelRatio||1)+'x');
            if (navigator.deviceMemory)        partes.push(navigator.deviceMemory+'GB RAM');
            if (navigator.hardwareConcurrency) partes.push(navigator.hardwareConcurrency+'cores');
            partes.push(Intl.DateTimeFormat().resolvedOptions().timeZone);
            return partes.join(' | ');
        }

        /* ── Toggle departamento ── */
        function cambiarDpto() {
            const esTM = document.getElementById('toggle-dpto').checked;
            document.getElementById('dpto_input').value = esTM ? 'tm' : 'im';

            document.getElementById('bloque-im').style.display = esTM ? 'none' : '';
            document.getElementById('bloque-tm').style.display = esTM ? '' : 'none';

            // required dinámico — solo activa los del bloque visible
            document.getElementById('tipo_im').required   = !esTM;
            document.getElementById('causa_im').required  = !esTM;
            document.getElementById('piezas_im').required = !esTM;
            document.getElementById('sucursal_im').required = !esTM;
            document.getElementById('apasionado').required  = !esTM;
            document.getElementById('fecha_im').required    = !esTM;

            document.getElementById('tipo_tm').required     = esTM;
            document.getElementById('causa_tm').required    = esTM;
            document.getElementById('piezas_tm').required   = esTM;
            document.getElementById('sucursal_tm').required = esTM;
            document.getElementById('apasionado_tm').required = esTM;
            document.getElementById('fecha_tm').required    = esTM;

            // label activo
            document.getElementById('lbl-im').className = esTM ? '' : 'dpto-label-im';
            document.getElementById('lbl-tm').className = esTM ? 'dpto-label-tm' : '';
        }

        $(function() {
            generarHuella().then(h => { document.getElementById('dispositivo').value = h; });

            $("#apasionado").autocomplete({
                source: function(req, res) {
                    $.ajax({ url:"buscar_colaborador.php", dataType:"json",
                        data:{ term: req.term },
                        success:function(data){res(data);},
                        error:function(){res([]);}
                    });
                },
                minLength:1, delay:300, autoFocus:true,
                focus: function(e,ui){ e.preventDefault(); $("#preview-apasionado").text("Seleccionando: "+ui.item.label); },
                select: function(e,ui){
                    e.preventDefault();
                    $("#apasionado").val(ui.item.label);
                    $("#apasionado_id").val(ui.item.value);
                    $("#preview-apasionado").text("Seleccionado: "+ui.item.label);
                },
                open:function(){ $(this).autocomplete("widget").find("li:first .ui-menu-item-wrapper").addClass("ui-state-active"); }
            });

            // Mostrar bloque IM por defecto
            cambiarDpto();
        });

        function actualizarSerie() {
            const tipo  = document.getElementById('tipo_tm').value;
            const campo = document.getElementById('numero_serie');
            const hint  = document.getElementById('serie-hint');
            if (tipo === 'Smartwatch') {
                campo.required = false;
                hint.textContent = '(opcional)';
                hint.style.color = '#888';
                hint.style.fontWeight = 'normal';
            } else {
                campo.required = true;
                hint.textContent = '*obligatorio';
                hint.style.color = '#e74c3c';
                hint.style.fontWeight = '600';
            }
        }

        function validarPlows(input) {
            const valor = input.value.toUpperCase();
            input.value = valor;
            if (!/^PLOWS\d{6}$/.test(valor)) {
                input.value = '';
                new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg').play();
            }
        }

        /* ── Fotos ── */
        const IMGBB_API_KEY = '1ce477aacdd4f13a74282f8746e9edcf';
        const MAX_FOTOS = 2;
        let fotosSubidas = [];

        document.addEventListener('DOMContentLoaded', function() {
            async function manejarFotos(files, inputEl) {
                const archivos = Array.from(files);
                const disponibles = MAX_FOTOS - fotosSubidas.length;
                const aSubir = archivos.slice(0, disponibles);
                if (archivos.length > disponibles)
                    alert('Solo se pueden agregar '+disponibles+' foto(s) más. Se subirán las primeras '+disponibles+'.');
                for (let archivo of aSubir) await subirFotoImgBB(archivo);
                if (inputEl) inputEl.value = '';
                actualizarContador();
            }
            document.getElementById('inputFotoCamara').addEventListener('change', function(){ manejarFotos(this.files, this); });
            document.getElementById('inputFotoGaleria').addEventListener('change', function(){ manejarFotos(this.files, this); });

            document.querySelector('form').addEventListener('submit', function(e) {
                const esTM = document.getElementById('toggle-dpto').checked;
                const campoAp = esTM ? document.getElementById('apasionado_tm') : document.getElementById('apasionado');
                if (!campoAp.value.trim()) {
                    alert('Por favor ingresa el nombre del colaborador.');
                    e.preventDefault(); return false;
                }
                if (!document.getElementById('dispositivo').value.trim()) {
                    alert('Espera un momento, detectando dispositivo...');
                    e.preventDefault(); return false;
                }
                const estado = document.getElementById('fotoEstado');
                if (estado && estado.classList.contains('cargando')) {
                    alert('Espera a que terminen de subir las fotos.');
                    e.preventDefault(); return false;
                }
            });
        });

        async function subirFotoImgBB(archivo) {
            const estado = document.getElementById('fotoEstado');
            estado.textContent = '⏳ Subiendo foto...';
            estado.className = 'foto-estado cargando';
            const reader = new FileReader();
            return new Promise((resolve) => {
                reader.onload = async function(e) {
                    const base64 = e.target.result.split(',')[1];
                    const formData = new FormData();
                    formData.append('key', IMGBB_API_KEY);
                    formData.append('image', base64);
                    try {
                        const response = await fetch('https://api.imgbb.com/1/upload', {method:'POST',body:formData});
                        const data = await response.json();
                        if (data.success) {
                            fotosSubidas.push(data.data.url);
                            actualizarCamposOcultos();
                            estado.textContent = '✅ Foto '+fotosSubidas.length+' subida correctamente';
                            estado.className = 'foto-estado ok';
                        } else {
                            estado.textContent = '❌ Error: '+(data.error?.message||'Error desconocido');
                            estado.className = 'foto-estado error';
                        }
                    } catch(err) {
                        estado.textContent = '❌ No se pudo conectar con el servidor de imágenes.';
                        estado.className = 'foto-estado error';
                    }
                    resolve();
                };
                reader.readAsDataURL(archivo);
            });
        }

        function actualizarCamposOcultos() {
            document.querySelectorAll('.foto-hidden').forEach(el => el.remove());
            const form = document.querySelector('form');
            fotosSubidas.forEach(url => {
                const input = document.createElement('input');
                input.type='hidden'; input.name='foto_url[]';
                input.value=url; input.className='foto-hidden';
                form.appendChild(input);
            });
        }

        function actualizarContador() {
            const c = document.getElementById('fotoContador');
            if (c) c.textContent = fotosSubidas.length+'/'+MAX_FOTOS+' fotos';
        }
    </script>
</head>
<body>
   <nav>
    <h1 id="nombre">Innovación Móvil</h1>
    <input type="checkbox" id="check">
    <label class="bar" for="check">
        <span class="top"></span><span class="middle"></span><span class="bottom"></span>
    </label>
    <ul id="menu">
        <li>
            <a href="garantias.php" style="display:flex;align-items:center;gap:12px;">
                <span style="display:inline-flex;width:40px;height:40px;background:white;border-radius:50%;justify-content:center;align-items:center;overflow:visible;position:relative;">
                    <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" alt="Logo" style="width:30px;height:30px;object-fit:contain;" />
                </span>
                Home
            </a>
        </li>
        <li>
            <a href="metas.php" style="display:flex;align-items:center;gap:12px;">
                <img src="../../recursos/img/Metas.png" alt="Metas" style="width:40px;height:40px;object-fit:contain;" />
                Metas IM
            </a>
        </li>
        <li>
            <a href="../../bitacora/Vendedores/index.php" style="display:flex;align-items:center;gap:12px;">
                <img src="../../recursos/img/productosNegados.png" alt="Productos Negados" style="width:40px;height:40px;object-fit:contain;" />
                Productos negados
            </a>
        </li>
        <li>
            <a href="../../compatibilidades/consultar.php" style="display:flex;align-items:center;gap:12px;">
                <img src="../../recursos/img/compatibilidades.png" alt="Compatibilidades" style="width:40px;height:40px;object-fit:contain;" />
                Compatibilidades
            </a>
        </li>
        <li>
            <a href="../../Evaluacion/mermas.php" style="display:flex;align-items:center;gap:12px;">
                <img src="../../recursos/img/tuto.png" alt="Tutorial" style="width:40px;height:40px;object-fit:contain;" />
                Cómo Enviar
            </a>
        </li>
        <li>
            <a href="tabla.php" style="display:flex;align-items:center;gap:12px;">
                <img src="../../recursos/img/merma.png" alt="Mermas" style="width:40px;height:40px;object-fit:contain;" />
                Garantías / Mermas
            </a>
        </li>
    </ul>
</nav>

    <div class="contenedor">
        
        <div class="formulario">
            <h1>Garantías y Mermas</h1>

            <?php if ($mensaje): ?>
                <p><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" id="dispositivo" name="dispositivo" value="">
                <input type="hidden" id="dpto_input"  name="dpto" value="im"><br>

                <!-- ── Toggle discreto de departamento ── -->
                <div class="dpto-toggle">
                    <span id="lbl-im" class="dpto-label-im">📱 Accesorios</span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="toggle-dpto" onchange="cambiarDpto()">
                        <div class="toggle-track"></div>
                        <div class="toggle-thumb"></div>
                    </label>
                    <span id="lbl-tm">📲 Telefonía</span>
                </div>

                <!-- ── PLOWS ── -->
                <label for="plows">PLOWS:</label>
                <input type="text" name="plows" id="plows" maxlength="11"
                       onblur="validarPlows(this)" required><br><br>

                <!-- ═══════════════════════════
                     BLOQUE ACCESORIOS — IM
                     (sin cambios respecto al original)
                ═══════════════════════════ -->
                <div id="bloque-im" style="display:block">
                    <label for="tipo_im">Tipo de producto:</label>
                    <select name="tipo_im" id="tipo_im">
                        <option value="">Seleccione</option>
                        <option>Caratula Case</option>
                        <option>Hidrogel</option>
                        <option>Kits de Carga</option>
                        <option>Protection Pro</option>
                        <option>Glass Full</option>
                        <option>Glass Mobo</option>
                        <option>Cable USB</option>
                        <option>Funda Tablet</option>
                        <option>Electronico</option>
                        <option>Adaptador de carga</option>
                        <option>Otros</option>
                    </select><br><br>

                    <label for="causa_im">Causa:</label>
                    <select name="causa_im" id="causa_im">
                        <option value="">Seleccione</option>
                        <option>Cambio de producto (Garantia)</option>
                        <option>Defecto de fabrica</option>
                        <option>Mala instalacion de producto (garantia)</option>
                        <option>Error (Nuevo Ingreso)</option>
                        <option>Se encontro roto o descompuesto</option>
                        <option>Mala instalacion del producto (merma)</option>
                        <option>Fallo de la maquina</option>
                    </select><br><br>

                    <label for="piezas_im">Piezas:</label>
                    <input type="number" name="piezas" id="piezas_im" min="1"><br><br>

                    <?php $sucursales = obtenerSucursales(); ?>
                    <label for="sucursal_im">Sucursal:</label>
                    <select name="sucursal" id="sucursal_im">
                        <option value="">Seleccione una sucursal</option>
                        <?php foreach ($sucursales as $s): ?>
                            <option value="<?= htmlspecialchars($s['id']) ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <input type="hidden" id="apasionado_id" name="apasionado_id">
                    <label for="apasionado">Nombre del colaborador:</label>
                    <input type="text" name="apasionado" id="apasionado" autocomplete="off" required>
                    <div id="preview-apasionado" style="margin-top:5px;color:#555;font-size:.9em;"></div><br>

                    <label for="fecha_im">Fecha:</label>
                    <input type="date" name="fecha" id="fecha_im" value="<?= date('Y-m-d') ?>" required><br><br>

                    <label for="anotaciones_vendedor">Anotaciones (opcional):</label><br>
                    <textarea name="anotaciones_vendedor" rows="4" cols="50" maxlength="2000"></textarea><br><br>

                    <!-- Fotos IM -->
                    <label>Fotos (opcional, máx. 2):</label><br>
                    <input type="file" id="inputFotoCamara" accept="image/*" capture="environment" style="display:none;">
                    <input type="file" id="inputFotoGaleria" accept="image/*" style="display:none;">
                    <button type="button" class="foto-btn" onclick="document.getElementById('inputFotoCamara').click()">📷 Tomar foto</button>
                    <button type="button" class="foto-btn" onclick="document.getElementById('inputFotoGaleria').click()">🖼️ Abrir galería</button>
                    <span class="foto-contador" id="fotoContador">0/2 fotos</span>
                    <div id="fotoEstado" class="foto-estado"></div>
                    <br><br>
                </div>

                <!-- ═══════════════════════════
                     BLOQUE TELEFONÍA — TM
                ═══════════════════════════ -->
                <div id="bloque-tm" style="display:none">
                    <label for="tipo_tm">Tipo de producto:</label>
                    <select name="tipo_tm" id="tipo_tm" onchange="actualizarSerie()">
                        <option value="">Seleccione</option>
                        <option>Chips</option>
                        <option>Smartphone</option>
                        <option>Tablet</option>
                        <option>Módem</option>
                        <option>Terminal</option>
                        <option>Smartwatch</option>
                        <option>Equipo Basico</option>
                    </select><br><br>

                    <label for="numero_serie">
                        Número de serie / IMEI / ICC:
                        <small id="serie-hint" style="color:#e74c3c;font-weight:600;">*obligatorio</small>
                    </label>
                    <input type="text" name="numero_serie" id="numero_serie"
                           maxlength="100"
                           placeholder="IMEI, ICC, SN según aplique"><br><br>

                    <label for="causa_tm">Causa:</label>
                    <select name="causa_tm" id="causa_tm">
                        <option value="">Seleccione</option>
                        <option>Defecto de fábrica</option>
                        <option>Accesorios faltantes</option>
                        <option>Se encontró roto o dañado</option>
                        <option>Otro</option>
                    </select><br><br>

                    <label for="piezas_tm">Piezas:</label>
                    <input type="number" name="piezas_tm" id="piezas_tm" min="1"><br><br>

                    <label for="sucursal_tm">Sucursal:</label>
                    <select name="sucursal_tm" id="sucursal_tm">
                        <option value="">Seleccione una sucursal</option>
                        <?php foreach ($sucursales as $s): ?>
                            <option value="<?= htmlspecialchars($s['id']) ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select><br><br>

                    <label for="apasionado_tm">Nombre del colaborador:</label>
                    <input type="text" name="apasionado_tm" id="apasionado_tm" autocomplete="off">
                    <div id="preview-apasionado-tm" style="margin-top:5px;color:#555;font-size:.9em;"></div><br>

                    <label for="fecha_tm">Fecha:</label>
                    <input type="date" name="fecha_tm" id="fecha_tm" value="<?= date('Y-m-d') ?>"><br><br>

                    <label for="anotaciones_tm">Anotaciones (opcional):</label><br>
                    <textarea name="anotaciones_tm" id="anotaciones_tm" rows="4" cols="50" maxlength="2000"
                              placeholder="Describe la falla. Especifica si la compra fue al contado o Pay Joy."></textarea><br><br>
                </div>

                <input type="submit" value="Guardar garantía">
            </form>
        </div>
    </div>

    <script>
    // Autocomplete también para TM
    $(function(){
        $("#apasionado_tm").autocomplete({
            source: function(req, res) {
                $.ajax({ url:"buscar_colaborador.php", dataType:"json",
                    data:{ term: req.term },
                    success:function(data){res(data);},
                    error:function(){res([]);}
                });
            },
            minLength:1, delay:300, autoFocus:true,
            focus: function(e,ui){ e.preventDefault(); $("#preview-apasionado-tm").text("Seleccionando: "+ui.item.label); },
            select: function(e,ui){
                e.preventDefault();
                $("#apasionado_tm").val(ui.item.label);
                $("#apasionado_id").val(ui.item.value);
                $("#preview-apasionado-tm").text("Seleccionado: "+ui.item.label);
            }
        });
    });
    </script>
</body>
</html>