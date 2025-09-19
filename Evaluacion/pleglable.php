<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portafolio de Diego Fernando Martínez Santiago: especialista en desarrollo web, mantenimiento de redes y optimización de sistemas.">
    <meta name="keywords" content="Diego Fernando Martínez Santiago, desarrollo web, mantenimiento de redes, portafolio de proyectos">
    <meta name="author" content="Diego Fernando Martínez Santiago">
    <link rel="stylesheet" href="css/css.css">
    <link rel="stylesheet" href="css/modelos.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>telefonos plegables</title>
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
        <h1 id="titulo">Capacitación Tiendas</h1>
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
                <h1>Hidrogel en teléfonos plegables: precauciones y recomendaciones</h1><br>
                <p><b>Existen teléfonos plegables, pero es importante saber que no es recomendable aplicarles hidrogel, y en Central Cell no realizamos este procedimiento. Te explicamos por qué:</b></p>
            </div>
            <div class="imagendos">
                <div class="inicio">
                <div class="imagentres">
                <div class="macbook">
                    <div class="inner">
                        <div class="screen">
                            <div class="face-one">
                                <div class="camera"></div>
                                <div class="display">
                                    <div class="shade"></div>
                                </div>
                                <span>Innovación Móvil</span>
                            </div>
                        </div>
                        <div class="macbody">
                            <div class="face-one">
                                <div class="touchpad"></div>
                                <div class="keyboard">
                                    <!-- Teclas de la Macbook -->
                                    <div class="key"></div>
                                    <!-- Repite el div class="key" según sea necesario -->
                                    <div class="key space"></div>
                                    <!-- Teclas adicionales aquí -->
                                    <div class="key f"></div>
                                    <!-- Más teclas 'f' si es necesario -->
                                </div>
                            </div>
                            <div class="pad one"></div>
                            <div class="pad two"></div>
                            <div class="pad three"></div>
                            <div class="pad four"></div>
                        </div>
                    </div>
                    <div class="shadow"></div>
                </div>
            </div>
                </div>
            </div>
        </div>

        
    
        <div class="barra">
        
        <h4>Problemas al aplicar hidrogel</h4><br>
<p>
Al usar hidrogel en teléfonos plegables, la zona donde se dobla la pantalla hace que el hidrogel se levante muy fácilmente. 
Incluso si se intenta dividir la mica en dos para evitar el doblez, el hidrogel solo puede adherirse correctamente en las partes planas de la pantalla, nunca en la zona del pliegue.
</p><br><br>

<h4>Fragilidad de la pantalla</h4><br>
<p>
Las pantallas plegables están hechas de materiales como plástico o vidrio ultradelgado (ultrathin glass), que son más flexibles pero también más sensibles a rayones, presión y golpes. 
La bisagra y el pliegue son los puntos más vulnerables, ya que la pantalla se dobla y estira constantemente. 
Por eso, aplicar hidrogel en estos equipos puede ser riesgoso y dañarlos si no se hace con extrema precaución.
</p><br><br>

<h4>Opciones seguras</h4><br>
<ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
  <li>
    Si el cliente trae la mica de fábrica, lo más recomendable es dejarla. 
    Estas micas están diseñadas específicamente para su equipo y duran mucho más que un hidrogel, que incluso puede despegarse a los pocos días.
  </li>
  <li>
    La otra opción es usar la mica <b>Protection Pro</b>, preferiblemente transparente, ya que es más flexible en la zona del doblez. 
    Debe aplicarse con cuidado y una cantidad moderada de gel. 
    Es importante informar al cliente que no debe cerrar su teléfono hasta que hayan pasado al menos 2 horas de secado. 
    Una vez pasado ese tiempo, la mica queda bien protegida y se adapta mejor al doblez, cubriendo incluso la zona más delicada de la pantalla.
  </li>
</ul><br><br>

<p>
⚠️ <b>Importante:</b> Solo en casos excepcionales, como garantía o si el cliente insiste en aplicar hidrogel en la pantalla exterior donde no hay doblez, se puede hacer. 
Debe aplicarse con mucho cuidado, siguiendo todas las precauciones mencionadas, para evitar daños en el equipo.
</p><br><br>


   
    
           
        </div>
    
</body>
<footer>
        <p>&copy; <span id="year"></span> Diego- Innovación Móvil.</p>
    </footer>
<script>
     document.getElementById("year").textContent = new Date().getFullYear();
</script>
</html>
