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
    <title>curvaturas</title>
    <style>
        .section {
            display: none;
        }
        .section .barra{
            
            margin-top: -300px;
    
        }
        .section .contenedorpartdos .imagendos{
            transform: scale(0.8); /* Ajusta el valor para el tamaño deseado */
            max-width: 100%; /* Opcional: ajusta el ancho máximo */
           
            
            
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
           
                <h1>📱 ¿Por qué algunas micas UV no sirven en pantallas curvas?</h1><br>
                <p><b>Cuando nos llega un equipo con pantalla curva, como apasionados sabemos que la mica de hidrogel UV suele ser una de las mejores opciones.
Esto se debe a que, al curarla en la máquina con luz ultravioleta, la mica se endurece más y se adhiere mejor a la pantalla.</b></p>
        
</div>
            <div class="imagendos">
    <div class="inicio">
       <br><br><br><br><br><iframe 
    src="https://drive.google.com/file/d/1egMjB7c28vGVaQZiVq4XJHT8mmlVM-Mx/preview" 
    width="400" 
    height="200" 
    allow="autoplay">
</iframe>

    </div>
    
</div>
 
        </div>
        <div class="barra">
            <br><p>¿Desea descargar el video? Haga clic en el ícono naranja:</p><br><br>
             <button class="button" id="myButton">
  <svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="26px">
    <path d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6z" fill="currentColor"></path>
  </svg>
  
</button>
<script>
document.getElementById("myButton").addEventListener("click", function() {
  window.open("https://drive.google.com/file/d/1egMjB7c28vGVaQZiVq4XJHT8mmlVM-Mx/view?usp=drive_link", "_blank");
});
</script>

            <br><br><p>Sin embargo, no todos los teléfonos curvos son compatibles con este tipo de mica.<br><br>
Existen dos tipos de pantallas curvas:</p><br>

<ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
  <li><strong>Curvatura solo en los laterales (arriba y abajo planos).</strong><br>
      En estos equipos, la mica UV suele quedar muy bien.</li><br>
  <li><strong>Curvatura en los cuatro lados (laterales, parte superior e inferior).</strong><br>
      Aquí aparece un problema: en las esquinas se genera un levantamiento o burbuja que impide que el hidrogel UV se adhiera, por más que se intente.</li><br>
</ul>

<img src="img/vs.png" alt="Equipos curvos" style="max-width:100%; height:auto;"><br><br>

<h4>✅ ¿Cuál es la solución?</h4><br>

<p>Revisar en la máquina de hidrogel si existe el corte “mariposa” (generalmente aparece como UV-1).</p><br>

<p>Este corte especial evita que se forme la burbuja en las esquinas.<br>
Antes de aplicarlo, verifica en la bitácora si ese modelo está en la lista roja (equipos donde no funciona).<br>
Ejemplo: el Motorola Edge 60 Fusion, incluso con corte mariposa, no queda bien.<br>
Si sí funciona, anótalo en la lista verde para que todos sepan que en ese modelo sí se puede aplicar.</p><br>

<p>Si el corte mariposa no funciona o no está disponible:<br>
La mejor alternativa es usar Protection Pro, ya que se adapta mejor a pantallas curvas en cuatro lados.<br>
En caso de no tenerlo, se puede usar hidrogel transparente (es más flexible y se adapta mejor que otros tipos de hidrogel).</p><br>



<h4>📌 En resumen:</h4><br>

<ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
  <li>Si el equipo solo es curvo en los laterales → mica UV funciona bien.</li><br>
  <li>Si es curvo en los cuatro lados → usar corte mariposa (si aplica), y si no, mejor usar Protection Pro o hidrogel transparente.</li><br>
</ul><br>
<img src="img/diagrama_mica_uv_v2.png" alt="Equipos curvos" style="max-width:100%; height:auto;"><br><br>
            
</div>

        

        
</body>
<footer>
        <p>&copy; <span id="year"></span> Diego- Innovación Móvil.</p>
    </footer>
<script>
     document.getElementById("year").textContent = new Date().getFullYear();
</script>
</html>
