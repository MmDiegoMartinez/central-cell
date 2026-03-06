<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manual de registro de Productos Negados - Innovación Móvil">
    <meta name="keywords" content="productos negados, registro, capacitación, innovación móvil">
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
        <h1 id="titulo">Capacitación Innovación Móvil</h1>
        <input id="checkbox2" type="checkbox">
        <label class="toggle toggle2" for="checkbox2">
            <div id="bar4" class="bars"></div>
            <div id="bar5" class="bars"></div>
            <div id="bar6" class="bars"></div>
        </label>
        <ul id="menu">
            <li><a href="index.php">Inicio</a></li>
            <li><a href="material.php">◀️ Atrás</a></li>
        </ul>
    </nav>

    <script>
        function closeMenu() {
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
                    src="https://drive.google.com/file/d/1C4SG0uC9j--SR4tOwuX_ufabii_VMPfX/preview" 
                    width="400" height="200" allow="autoplay">
                </iframe>
                <br><br>
                <button class="button" id="myButton">
  <svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="26px">
    <path d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6z" fill="currentColor"></path>
  </svg>
  
</button>
<script>
document.getElementById("myButton").addEventListener("click", function() {
  window.open("https://drive.google.com/uc?export=download&id=1C4SG0uC9j--SR4tOwuX_ufabii_VMPfX", "_blank");
});
</script><br>
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
        </p><br><br>

        <h4>Autoridad:</h4><br>
        <p>El Apasionado de la Telefonía puede solicitar los productos negados al departamento correspondiente para cubrir la demanda del cliente.</p><br><br>

        <h4>🔹 Pasos para registrar un producto negado</h4><br>
        <p>1.- Abrir la página de registro en la web</p><br><br>
        <p>Ingresa al link y selecciona tu sucursal:</p><br><br>
        <p>
             <button class="button" id="myButton2">
  <svg viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="26px">
    <path d="M424.4 214.7L72.4 6.6C43.8-10.3 0 6.1 0 47.9V464c0 37.5 40.7 60.1 72.4 41.3l352-208c31.4-18.5 31.5-64.1 0-82.6z" fill="currentColor"></path>
  </svg>
  
</button>
<script>
document.getElementById("myButton2").addEventListener("click", function() {
  window.open("../bitacora/Vendedores/index.php", "_blank");
});
</script><br>
        </p><br><br>

        <p>2.- Completar los datos del formulario:</p><br>
        <ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
            <li>Marca y modelo</li>
            <li>Descripción del artículo negado</li>
            <li>Nombre del Apasionado de la Telefonía que hace la solicitud</li>
            <li>Anotaciones (opcional)</li>
        </ul><br><br>

        <p>3.- Guardar el registro y verificar que aparezca el mensaje ✅ “Producto registrado correctamente.”</p><br><br>

        <p>4.- Consultar la bitácora de vendedores:</p><br>
        <ul style="list-style-type: disc; padding-left: 20px; margin: 0;">
            <li>Entra a la sección <b>Productos </b> en la parte superior derecha de la pantalla en la web.</li>
            <li>Busca tu sucursal y tu nombre para filtrar tus registros.</li>
            <li>Verás los productos registrados con colores según su estatus:</li>
        </ul><br>
        <ul style="list-style-type: disc; padding-left: 40px; margin: 0;">
            <li>🔵 <b>Visto</b></li>
            <li>🟡 <b>En pedido</b></li>
            <li>🟢 <b>Surtido</b></li>
            <li>🔴 <b>Tiene en tienda</b></li>
            <li>⚪ Anotado (sin acción)</li>
        </ul><br>
        <p>Filtra por sucursal, colaborador, indicador o fecha para encontrar fácilmente cualquier registro.</p>
    </div>

</body>
<footer>
    <p>&copy; <span id="year"></span> Diego- Innovación Móvil.</p>
</footer>
<script>
     document.getElementById("year").textContent = new Date().getFullYear();
</script>
</html>
