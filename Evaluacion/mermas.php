<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portafolio de Diego Fernando Martínez Santiago: especialista en desarrollo web, mantenimiento de redes y optimización de sistemas.">
    <meta name="keywords" content="Diego Fernando Martínez Santiago, desarrollo web, mantenimiento de redes, portafolio de proyectos">
    <meta name="author" content="Diego Fernando Martínez Santiago">
    <link rel="stylesheet" href="css/css.css">
    <link rel="stylesheet" href="css/videos.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>mermas y garantias</title>
    <style>
        .section {
            display: none;
        }
        .section .barra{
            
            margin-top: -300px;
    
        }
        .section .contenedorpartdos .imagendos{
            transform: scale(0.8); 
            max-width: 100%; 
           
            
            
        }

        .section.active {
            display: block;
        }
    </style>
</head>

<body>
    <nav>
        <h1 id="titulo">Capacitación Innovación móvil</h1>
        <input id="checkbox2" type="checkbox">
        <label class="toggle toggle2" for="checkbox2">
            <div id="bar4" class="bars"></div>
            <div id="bar5" class="bars"></div>
            <div id="bar6" class="bars"></div>
        </label>
        <ul id="menu">
            <li><a href="index.php">Inicio</a></li>
            <li><a href="material.php" >◀️ Atras</a></li>
        </ul>
    </nav>
    
    <script>function closeMenu() {
        document.getElementById("checkbox2").checked = false;
    }
    </script>
    
    
        <div class="contenedorpartdos">
        
            <div class="inicio">
           
                <h1>📦 Forma Correcta de Enviar Garantías de Protector de Pantalla</h1><br>
                <p><b>El objetivo es hacer bien el registro y envío para que tu garantía sea válida y no tengas descuentos innecesarios.</b></p>
            </div>
            <div class="imagendos">
    <div class="inicio">
    <iframe 
    width="400" 
    height="270"
    src="https://www.youtube.com/embed/EV5Pcn5YUNE"
    title="YouTube video player"
    frameborder="0"
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
    allowfullscreen>
</iframe>
    </div>
</div>
 
        </div>
        <div class="barra">

        <h4>✅ Pasos Correctos</h4><br>

<h4>1️⃣ Registrar en la página </h4><br>
<p>- Ingresa y anota tu garantía antes del cierre de caja.</p><br><br>
<p>- Toca el ícono naranja y te dirigirá a la página para anotar tu merma.</p><br>
<button class="button" id="myButton">
  <svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="26px">
    <path d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6z" fill="currentColor"></path>
  </svg>
  
</button>
<script>
document.getElementById("myButton").addEventListener("click", function() {
  window.open("../garantias/vendedor/garantias.php", "_blank");
});
</script><br><br>

<h4>2️⃣ Enviar evidencia en el grupo oficial</h4><br>
<p>- Sube la foto del producto defectuoso al grupo de <b>Garantías y Merma</b>.<br>
- Incluye la <b>hora exacta</b> de la validación.</p><br><br>

<h4>3️⃣ Datos en el reverso de la garantía</h4><br>
<p>
  <ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
    <li>Nombre del colaborador</li>
    <li>Motivo de la garantía</li>
    <li>Fecha y hora exacta (para validar en cámaras)</li>
    <li>Sucursal</li>
    <li>En caso de corte completo, anota el modelo</li>
    <li>Pega la mica completa</li>
  </ul>
</p><br><br>

<h4>4️⃣ Empaquetar y enviar</h4><br>
<p>- La garantía debe ir empaquetada y sellada.</p><br><br>
<p>Incluye la ficha con los siguientes datos:</p><br><br>
<p>
  <ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
    <li>Sucursal</li>
    <li>Departamento: <b>INNOVACIÓN MÓVIL</b></li>
    <li>Fecha de envío</li>
  </ul>
</p><br><br>

<h4>❌ Importante</h4><br>
<p>- Si el envío de mermas o garantías está incompleto o mal hecho, se aplicará un descuento correspondiente.<br>
- Si no registras tu merma en la página, el producto no se dará de baja.</p><br><br>

<h4>💡 Tip Extra</h4><br>
<p>Cuando un protector tiene un pequeño detalle visual pero sigue funcionando bien, recuerda:</p><br><br>
<p>
  <ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
    <li>Consulta en el grupo de Fuerzas de Venta qué <b>porcentaje de descuento</b> se le puede ofrecer al cliente.</li>
    <li>Así evitas perder la venta y el cliente se va contento.</li>
  </ul>
</p><br><br>

<h4>✍️ ATTE. INNOVACIÓN MÓVIL</h4><br>
            
            
            
</div>

        
        
</body>
<footer>
        <p>&copy; <span id="year"></span> Diego- Innovación Móvil.</p>
    </footer>
<script>
     document.getElementById("year").textContent = new Date().getFullYear();
</script>
</html>
