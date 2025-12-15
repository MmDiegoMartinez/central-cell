<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Innovación Móvil Epoca de Descuentos</title>
    <link rel="stylesheet" href="../estilos.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="#">Inicio</a></li>
            <li><a href="../../compatibilidades/consultar.php">Compatibilidades</a></li>
            <li><a href="../../capacitados/capa.php">Capacitaciones</a></li>
            <li><a href="../../validador/validador.php">Validar Mermas</a></li>
        </ul>
    </nav>

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
</body>
</html>
