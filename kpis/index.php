<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Innovación Móvil</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="#">Inicio</a></li>
            <li><a href="../compatibilidades/consultar.php">Compatibilidades</a></li>
            <li><a href="../capacitados/lista_colaboradores.php">Capacitaciones</a></li>
            <li><a href="../validador/validador.php">Validar Mermas</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1>Bienvenido al Panel de Ventas</h1>
        <p>Visualiza los análisis de ventas por categoría, sucursal y vendedor de manera clara y sencilla.</p>
; 
        <div class="grid-sections">

            <a href="semanaventas.php" class="card">
                <div class="card-title">Ventas Generales</div>
                <img src="../recursos/img/ventas.png" alt="Icono Ventas" style="width:230px; height:130px; margin-bottom:8px;">
                <div class="card-desc">Aquí se mostrará el análisis de la semana de ventas de Innovación Móvil por sucursal y vendedor.</div>
            </a>

            <a href="protectores.php" class="card">
                <div class="card-title">Ventas de Micas</div>
                 <img src="../recursos/img/mica.png" alt="Icono Ventas" style="width:180px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Visualiza los números de ventas de micas Hidrogel, Protection Pro y Glass por sucursal y vendedor.</div>
            </a>
            
            <a href="ventasdiarias.php" class="card">
                <div class="card-title">Ventas Individuales por Día</div>
                 <img src="../recursos/img/89875.png" alt="Icono Ventas" style="width:180px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Detalle de las ventas individuales por día y por vendedor durante el mes de ventas.</div>
            </a>

            <a href="analisis_fundas.php" class="card">
                <div class="card-title">Venta de Fundas</div>
                 <img src="../recursos/img/case.png" alt="Icono Ventas" style="width:150px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Se muestra el modelo de fundas más vendidas actualmente.</div>
            </a>

            <a href="comparativo_mensual.php" class="card">
                <div class="card-title">Comparación de Meses</div>
                 <img src="../recursos/img/balanza.png" alt="Icono Ventas" style="width:180px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Analiza dos meses y observa en qué categoría subimos o bajamos.</div>
            </a>

            <a href="topproductos.php" class="card">
                <div class="card-title">Top Artículos</div>
                 <img src="../recursos/img/top.png" alt="Icono Ventas" style="width:180px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Visualiza los artículos más vendidos de las diferentes categorías.</div>
            </a>
            <a href="ticket.php" class="card">
                <div class="card-title">Top Ticket</div>
                 <img src="../recursos/img/ticket.png" alt="Icono Ventas" style="width:180px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Visualiza el ticket más grande de un tiempo determinado.</div>
            </a>
            <a href="reporte_garantias.php" class="card">
                <div class="card-title">% Mermas / Garantias</div>
                 <img src="../recursos/img/mermas.png" alt="Icono Ventas" style="width:180px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Visualice el porcentaje de mermas por sucursal en relación con las ventas correspondientes, incluyendo los detalles específicos de cada caso.</div>
            </a>
            <a href="hidrogel-polimero.php" class="card">
                <div class="card-title">Hidrogel y polimero</div>
                 <img src="../recursos/img/mica.png" alt="Icono Ventas" style="width:180px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Visualiza la cantidad de micas Hidrogel, Protection Pro  vendidas por sucursal.</div>
            </a>

            

        </div>
    </div>
</body>
</html>
