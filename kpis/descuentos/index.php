<!DOCTYPE html>
<html lang="es">
    
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Innovación Móvil Epoca de Descuentos</title>
    <link rel="stylesheet" href="../estilos.css">
</head>
<body>
    <header>
  <nav>
        <div class="nav-inner">
            <!-- Botón hamburguesa -->
            <label class="bar-menu">
                <input type="checkbox" id="menu-check">
                <span class="top"></span>
                <span class="middle"></span>
                <span class="bottom"></span>
            </label>

            <ul id="nav-menu">
                <li>
        <a href="../index.php" class="menu-link">
          <span class="logo-container">
            <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png" alt="Logo Central Cell" class="logo" width="25" height="25" />

          </span>
          Home
        </a>
      </li>
            </ul>
        </div>
    </nav>
</header

    <div class="container">
        <h1>Bienvenido al Panel de Ventas Epoca de Descuentos</h1>
        <p>Visualiza la venta real por categoría, sucursal y vendedor de manera clara y sencilla. sin contar al Código promocional como metodo de pago</p>
        <div class="grid-sections">

           
         <a href="semanaventas.php" class="card">
                <div class="card-title">Ventas Generales</div>
                <img src="../../recursos/img/ventas.png" alt="Icono Ventas" style="width:230px; height:130px; margin-bottom:8px;">
                <div class="card-desc">Aquí se mostrará el análisis de la semana de ventas reales de Innovación Móvil por sucursal y vendedor. sin que afecte el código promocional</div>
            </a>
             <a href="codigopromocional.php" class="card">
                <div class="card-title">Control de Promociones en Tickets</div>
                <img src="../../recursos/img/ticket.png" alt="Icono Ventas" style="width:230px; height:130px; margin-bottom:8px;">
                <div class="card-desc">Este apartado permite visualizar los tickets y mostrar el estado de aquellos donde las promociones fueron aplicadas correctamente, así como identificar los que se usaron de forma incorrecta.</div>
            </a>
            <a href="comparativo_mensual.php" class="card">
                <div class="card-title">Comparación de Meses</div>
                 <img src="../../recursos/img/balanza.png" alt="Icono Ventas" style="width:180px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Analiza dos meses y observa en qué categoría subimos o bajamos sin contar los códigos promocionales.</div>
            </a>

           

            

        </div>
    </div>
      <script>
    // Controlar menú hamburguesa
    document.getElementById('menu-check').addEventListener('change', function() {
        const menu = document.getElementById('nav-menu');
        if (this.checked) {
            menu.style.opacity = '1';
            menu.style.visibility = 'visible';
            menu.style.pointerEvents = 'auto';
        } else {
            menu.style.opacity = '0';
            menu.style.visibility = 'hidden';
            menu.style.pointerEvents = 'none';
        }
    });
</script>
</body>
</html>
