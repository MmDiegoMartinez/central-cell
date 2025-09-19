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
    <title>Bitacora</title>
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
           
                <h1>📋 Registro de Productos Negados</h1><br>
                <p><b>Anotar todos los productos que no se pudieron vender durante el día, para cubrir las necesidades de nuestros clientes y no perder oportunidades de venta.</b></p>
            </div>
            <div class="imagendos">
    <div class="inicio">
        <iframe 
            src="https://drive.google.com/file/d/1_1Zf9emfejDb7xWidRpzgUGcXeWutyOT/preview" 
            width="400" height="200" allow="autoplay">
        </iframe>
    </div>
</div>
 
        </div>
        <div class="barra">
            <h4>Por qué es importante:</h4><br>
            <p>Cada producto negado es una oportunidad. Registrarlo te permite solicitarlo y ofrecerlo al cliente, aumentando tus ventas y demostrando profesionalismo.</p><br><br>
            <h4>Quién lo hace:</h4><br>
            <p>El Apasionado de la Telefonía es responsable de registrar cada producto negado y dar seguimiento.</p><br><br>
            <p>Responsabilidades:</p><br>
            <p>
  <ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
    <li>Anotar todos los productos negados en la sucursal cada vez que no estén disponibles.</li>
    <li>Asegurarse de registrar los datos correctos para que el pedido se procese sin problemas.</li>
    
  </ul>
</p><br><br>
<h4>Autoridad:</h4><br>
            <p>El Apasionado de la Telefonía puede solicitar los productos negados al departamento correspondiente para cubrir la demanda del cliente.</p><br><br>
            <h4>🔹 Pasos para registrar un producto negado</h4><br>
            <p>1.- Abrir la lista de productos negados</p><br><br>
            <p>Ingresa al link y selecciona la pestaña de tu sucursal:</p><br><br>
            <button class="button" id="myButton">
  <svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="26px">
    <path d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6z" fill="currentColor"></path>
  </svg>
  
</button>
<script>
document.getElementById("myButton").addEventListener("click", function() {
  window.open("https://docs.google.com/spreadsheets/d/1qbxS7nyVmFLgoB-kYQsy2JA1Xc33b4mWFnqwIoE7J1k/edit?gid=98134084#gid=98134084", "_blank");
});
</script><br>
            
            <p>Haz clic para ver contenido</p><br><br>
            <p>2.- Ofrecer el servicio de solicitud por anticipo del producto negado</p><br><br>
            <p>3.- Si el cliente acepta el pedido del producto negado y te deja un anticipo procede a hacer la solicitud en la lista de productos negados, anotando los siguientes datos:</p><br><br>
            <p>
  <ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
   <li>Fecha de la solicitud
   </li>
   <li>Marca y modelo</li>
   <li>Descripcion del articulo negado 
   </li>
   <li>Nombre del apasionado de la telefonia que hace la solicitud 
   En caso de ser un pedido solicitado con anticipo </li>
   <li>Nombre del cliente</li>
   <li>Número del folio del ticket de ingreso </li>

    
  </ul>
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
