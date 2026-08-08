<?php
ob_start();
include_once '../../funciones.php';




$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    generarPDFGarantiaTelefono($id);
    exit;
}

$mensaje  = '';
$tipo_msg = '';
$nuevo_id = 0;

$sucursales = obtenerSucursales();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nuevo_id = guardarGarantiaTelefono($_POST);
        $mensaje  = ' Garantía registrada correctamente. El PDF se está descargando...';
        $tipo_msg = 'ok';
    } catch (Exception $e) {
        $mensaje  = ' Error: ' . htmlspecialchars($e->getMessage());
        $tipo_msg = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Garantías Telefonía</title>
<link rel="stylesheet" href="../../css.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<style>
.msg-ok {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 18px;
    font-weight: 600;
}
.msg-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 18px;
    font-weight: 600;
}

/* ── Overlay de carga ── */
#overlay-carga {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(10, 30, 70, 0.72);
    z-index: 9999;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 22px;
}
#overlay-carga.activo {
    display: flex;
}
#overlay-carga .spinner {
    width: 56px;
    height: 56px;
    border: 6px solid rgba(255,255,255,0.25);
    border-top-color: #fff;
    border-radius: 50%;
    animation: girar 0.75s linear infinite;
}
@keyframes girar { to { transform: rotate(360deg); } }

#overlay-carga .texto-carga {
    color: #fff;
    font-size: 17px;
    font-weight: 600;
    font-family: sans-serif;
    letter-spacing: 0.3px;
}
#overlay-carga .barra-wrap {
    width: 260px;
    height: 8px;
    background: rgba(255,255,255,0.2);
    border-radius: 99px;
    overflow: hidden;
}
#overlay-carga .barra-fill {
    height: 100%;
    width: 0%;
    background: #fff;
    border-radius: 99px;
    transition: width 0.35s ease;
}
#overlay-carga .subtexto {
    color: rgba(255,255,255,0.7);
    font-size: 12px;
    font-family: sans-serif;
    margin-top: -10px;
}
</style>
</head>

<body>
    <nav>
    <h1 id="nombre">Tecnología Móvil</h1>
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
            <a href="lista_garantias_telefonos.php" style="display:flex;align-items:center;gap:12px;">
                <img src="../../recursos/img/tel.png" alt="Metas" style="width:40px;height:40px;object-fit:contain;" />
                Validaciones
            </a>
        </li>
        
    </ul>
</nav>


<!-- Overlay de carga -->
<div id="overlay-carga">
    <div class="spinner"></div>
    <div class="texto-carga" id="texto-carga">Guardando garantía...</div>
    <div class="barra-wrap"><div class="barra-fill" id="barra-fill"></div></div>
    <div class="subtexto" id="subtexto">Por favor espera</div>
</div>

<div class="contenedor">
<div class="formulario">

<h1>Validación Telefonía</h1>

<?php if ($mensaje): ?>
<p class="msg-<?= $tipo_msg ?>"><?= $mensaje ?></p>
<?php endif; ?>

<form method="POST" id="form-garantia">

    <label>PLOWS:</label>
    <input type="text" name="plows" maxlength="11" required onblur="validarPlows(this)">
    <br><br>

    <label>Nombre del cliente:</label>
    <input type="text" name="nombre_cliente" required oninput="capitalizarNombre(this)">
    <br><br>

    <label>Número de contacto:</label>
    <input type="text" id="numero_contacto" name="numero_contacto" maxlength="10" required>
    <small id="errorTelefono" style="color:#e74c3c; display:none;">El número debe tener 10 dígitos</small>
    <br><br>

    <label>Número de ticket:</label>
    <input type="text" name="numero_ticket" id="numero_ticket" maxlength="10"
           inputmode="numeric" placeholder="Número de ticket" required>
    <br><br>

    <label>Sucursal:</label>
    <select name="sucursal" required>
        <option value="">Seleccione</option>
        <?php foreach($sucursales as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
        <?php endforeach; ?>
    </select>
    <br><br>

    <input type="hidden" name="vendedor" id="vendedor">
<label>Vendedor:</label>
<input type="text" id="vendedor_texto" name="vendedor_texto" autocomplete="off" required>
<br><br>

<input type="hidden" name="vendedor_recibe" id="vendedor_recibe">
<label>Vendedor que recibe:</label>
<input type="text" id="vendedor_recibe_texto" name="vendedor_recibe_texto" autocomplete="off" required>

    <br><br>
    <label>IMEI:</label>
    <input type="text" name="imei" maxlength="50" required>
    <br><br>

    <label>Tipo de venta:</label>
<div style="display:flex; gap:20px; margin-bottom:20px;">
    <label style="display:flex; align-items:center; gap:8px;">
        <input type="radio" name="tipo_venta" value="contado" required> Contado
    </label>
    <label style="display:flex; align-items:center; gap:8px;">
        <input type="radio" name="tipo_venta" value="credito" required> PayJoy / Crédito
    </label>
</div>

    

    <input type="submit" id="btn-guardar" value="Guardar ">
</form>

</div>
</div>

<script>
/* ═══════════════════════════════════════════
   AUTOCOMPLETE con primer resultado automático
═══════════════════════════════════════════ */
$(function(){
    function autocompleteVendedor(inputId, hiddenId) {
        var primerResultado = null;

        $("#" + inputId).autocomplete({
            source: function(req, res) {
                $.ajax({
                    url: "buscar_colaborador.php",
                    dataType: "json",
                    data: { term: req.term },
                    success: function(data) {
                        primerResultado = data.length > 0 ? data[0] : null;
                        res(data);
                    }
                });
            },
            minLength: 1,
            // Subrayar/resaltar el primer ítem al abrir el menú
            open: function() {
                $(this).autocomplete("widget")
                    .find(".ui-menu-item-wrapper").first()
                    .addClass("ui-state-active");
            },
            // Al navegar con flechas: mostrar solo el label (nombre), nunca el value (id)
            focus: function(e, ui) {
                $("#" + inputId).val(ui.item.label);
                $("#" + hiddenId).val(ui.item.value);
                return false;   // evita que jQuery UI ponga el value en el input
            },
            select: function(e, ui) {
                $("#" + inputId).val(ui.item.label);
                $("#" + hiddenId).val(ui.item.value);
                primerResultado = null;
                return false;
            },
            close: function() {
                if ($(this).val().trim() === '') {
                    $("#" + hiddenId).val('');
                    primerResultado = null;
                }
            }
        });

        // Enter sin ítem activo explícito → tomar el primero
        $("#" + inputId).on('keydown', function(e) {
            if (e.key === 'Enter') {
                var activo = $("#" + inputId).autocomplete("widget").find(".ui-state-active");
                if (activo.length === 0 && primerResultado) {
                    e.preventDefault();
                    $("#" + inputId).val(primerResultado.label);
                    $("#" + hiddenId).val(primerResultado.value);
                    $("#" + inputId).autocomplete("close");
                    primerResultado = null;
                }
            }
        });

        // Blur sin selección → autocompletar con el primero
        $("#" + inputId).on('blur', function() {
            var txt = $(this).val().trim();
            if (txt === '') { $("#" + hiddenId).val(''); primerResultado = null; return; }
            if (primerResultado && $("#" + hiddenId).val() === '') {
                $(this).val(primerResultado.label);
                $("#" + hiddenId).val(primerResultado.value);
                primerResultado = null;
            }
        });
    }

    autocompleteVendedor("vendedor_texto",        "vendedor");
    autocompleteVendedor("vendedor_recibe_texto", "vendedor_recibe");
});

/* ═══════════════════════════════════════════
   VALIDACIONES
═══════════════════════════════════════════ */
function validarPlows(input) {
    let v = input.value.toUpperCase();
    input.value = v;
    if (!/^PLOWS\d{6}$/.test(v)) {
        input.value = '';
        alert('Formato inválido. Ejemplo: PLOWS123456');
    }
}
function capitalizarNombre(input) {
    input.value = input.value.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
}

const telInput    = document.getElementById('numero_contacto');
const errorTelMsg = document.getElementById('errorTelefono');
telInput.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').slice(0, 10);
    const ok = this.value.length === 0 || this.value.length === 10;
    this.style.borderColor    = ok ? '' : '#e74c3c';
    errorTelMsg.style.display = ok ? 'none' : 'block';
});
telInput.addEventListener('blur', function() {
    if (this.value.length > 0 && this.value.length !== 10) {
        this.focus();
        this.style.borderColor    = '#e74c3c';
        errorTelMsg.style.display = 'block';
    }
});

/* ═══════════════════════════════════════════
   DESCARGA AUTOMÁTICA del PDF con barra de carga
═══════════════════════════════════════════ */
<?php if ($nuevo_id > 0): ?>
window.addEventListener('DOMContentLoaded', async function () {

    const overlay = document.getElementById('overlay-carga');
    const txtEl   = document.getElementById('texto-carga');
    const barraEl = document.getElementById('barra-fill');
    const subEl   = document.getElementById('subtexto');

    overlay.classList.add('activo');

    // Barra animada MIENTRAS el fetch trabaja (no termina sola)
    const pasos = [
        { pct: 15, txt: 'Generando PDF...',      sub: 'Preparando el documento',  delay: 400  },
        { pct: 35, txt: 'Generando PDF...',       sub: 'Armando secciones',        delay: 800  },
        { pct: 55, txt: 'Generando PDF...',       sub: 'Aplicando formato',        delay: 1200 },
        { pct: 72, txt: 'Preparando descarga...', sub: 'Revisando datos',          delay: 1800 },
        { pct: 85, txt: 'Preparando descarga...', sub: 'Casi listo...',            delay: 2600 },
    ];

    // Avanza hasta 85% con timeouts, pero NUNCA llega al 100% sola
    pasos.forEach(p => {
        setTimeout(() => {
            // Solo avanza si aún no terminó el fetch (barra < 90%)
            const actual = parseFloat(barraEl.style.width) || 0;
            if (actual < 90) {
                barraEl.style.width = p.pct + '%';
                txtEl.textContent   = p.txt;
                subEl.textContent   = p.sub;
            }
        }, p.delay);
    });

    try {
        // Fetch real — espera a que el servidor termine de generar el PDF
        const resp = await fetch('?id=<?= $nuevo_id ?>', { method: 'GET' });

        if (!resp.ok) throw new Error('Error al generar el PDF');

        const blob = await resp.blob();

        // ✅ El PDF llegó completo — ahora sí al 100%
        txtEl.textContent   = 'Descargando...';
        subEl.textContent   = 'Tu PDF está listo';
        barraEl.style.width = '100%';

        // Pequeña pausa para que el usuario vea el 100%
        await new Promise(r => setTimeout(r, 400));

        // Descarga real desde el blob (ya está en memoria)
        const url = URL.createObjectURL(blob);
        const a   = document.createElement('a');
        a.href     = url;
        a.download = 'Garantia-<?= str_pad($nuevo_id, 5, '0', STR_PAD_LEFT) ?>.pdf';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        txtEl.textContent = '¡Listo!';
        subEl.textContent = 'PDF descargado correctamente';

        setTimeout(() => overlay.classList.remove('activo'), 1000);

    } catch (err) {
        txtEl.textContent   = 'Error al generar el PDF';
        subEl.textContent   = err.message;
        barraEl.style.width = '100%';
        barraEl.style.background = '#e74c3c';
        setTimeout(() => overlay.classList.remove('activo'), 2500);
    }
});
<?php endif; ?>
</script>
</body>
</html>