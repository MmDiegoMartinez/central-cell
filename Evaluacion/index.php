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
   
    <title>Espacio de Capacitacion</title>
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
        <h1 id="titulo">Innovación móvil Capacitación</h1>
        <input id="checkbox2" type="checkbox">
        <label class="toggle toggle2" for="checkbox2">
            <div id="bar4" class="bars"></div>
            <div id="bar5" class="bars"></div>
            <div id="bar6" class="bars"></div>
        </label>
        <ul id="menu">
            <li><a href="index.php">Inicio</a></li>
            <li><a href="material.php" >Material</a></li>
            <li><a href="examen.php">Cuestionario</a></li>
        </ul>
    </nav>
    
    <script>function closeMenu() {
        document.getElementById("checkbox2").checked = false;
    }
    </script>
    
    <!-- Sección Inicio -->
    
        <div class="inicio">
            <h1>✨ ¡Bienvenido, nuevo apasionado de la telefonía! ✨</h1><br>
            <h2></h2><br>
            <p>Este espacio está diseñado especialmente para ti, donde podrás poner en práctica todo lo aprendido en tu capacitación y seguir fortaleciendo tus habilidades. Aquí encontrarás material valioso que te ayudará a crecer, adaptarte rápidamente y formar parte de nuestro gran equipo en el departamento de Innovación Móvil.

</p><br><p>Recuerda: cada día es una nueva oportunidad para aprender, mejorar y brillar en lo que haces. 🌟📱</p><br><br><br><br><br><br><br>
        </div>
        <script src="js/escribirTexto.js"></script>
        </div>
</body>
<footer>
        <p>&copy; <span id="year"></span> Diego- Innovación Móvil.</p>
    </footer>
<script>
     document.getElementById("year").textContent = new Date().getFullYear();
</script>
</html>
