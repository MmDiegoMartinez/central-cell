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
        <div class="nav-inner">
            <!-- Botón hamburguesa -->
            <label class="bar-menu">
                <input type="checkbox" id="menu-check">
                <span class="top"></span>
                <span class="middle"></span>
                <span class="bottom"></span>
            </label>

            <ul id="nav-menu">
                <li><a href="#">Inicio</a></li>
                <li><a href="../compatibilidades/consultar.php">Compatibilidades</a></li>
                <li><a href="../capacitados/capa.php">Capacitaciones</a></li>
                <li><a href="../garantias/validador/validador.php">Validar Mermas</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Bienvenido al Panel de Ventas</h1>
        <p>Visualiza los análisis de ventas por categoría, sucursal y vendedor de manera clara y sencilla.</p>
        <div class="grid-sections">

            <a href="semanaventas.php" class="card">
                <div class="card-title">Ventas Generales</div>
                <img src="../recursos/img/ventas.png" alt="Icono Ventas" style="width:200px; height:130px; margin-bottom:8px;">
                <div class="card-desc">Analiza un archivo de ventas en Excel para mostrar el rendimiento de vendedores y tiendas comparado con sus metas</div>
            </a>

            <a href="telefonos.php" class="card">
                <div class="card-title">Ventas Telefonos</div>
                <img src="../recursos/img/cel.png" alt="Icono Ventas" style="width:200px; height:130px; margin-bottom:8px;">
                <div class="card-desc">Analiza, las ventas de Smartphone</div>
            </a>

            <a href="analisis_general.php" class="card">
                <div class="card-title">Analizador Multisemana</div>
                <img src="../recursos/img/ventasemanas.png" alt="Icono Ventas" style="width:200px; height:150px; margin-bottom:8px;">
                <div class="card-desc">Analiza ventas por varias semanas para comparar el desempeño de vendedores y sucursales en Innovación Móvil y Tecnología Móvil</div>
            </a>
			 <a href="analisisproductos.html" class="card">
                <div class="card-title">Análisis Inventario y Ventas</div>
                <img src="../recursos/img/scrip.png" alt="Icono Ventas" style="width:200px; height:150px; margin-bottom:8px;">
                <div class="card-desc">Analiza existencias, ventas y compras para detectar productos con baja rotación, exceso de stock y sugerencias de reabasto entre sucursales.</div>
            </a>
            <a href="protectores.php" class="card">
                <div class="card-title">Análisis Ventas de Protectores</div>
                 <img src="../recursos/img/mica.png" alt="Icono Ventas" style="width:200px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Analiza las ventas de protectores por sucursal y vendedor a partir de un archivo de ventas en Excel.</div>
            </a>
            
            <a href="ventasdiarias.php" class="card">
                <div class="card-title">Análisis Semanal de Ventas</div>
                 <img src="../recursos/img/89875.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Detalle de las ventas individuales por día y por vendedor durante el mes de ventas.</div>
            </a>
             <a href="analisis_fundas_ventas_existencias.php" class="card">
                <div class="card-title">Fundas</div>
                 <img src="../recursos/img/case.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Muestra dónde están las fundas, cuánto se vende y cómo se relacionan las ventas con las existencias.</div>
            </a>

            <a href="analisis_celulares_ventas_existencias.php" class="card">
                <div class="card-title">Telefonos</div>
                 <img src="../recursos/img/cel.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Muestra dónde están las telefonos, cuánto se vende y cómo se relacionan las ventas con las existencias.</div>
            </a>

            <a href="comparativo_mensual.php" class="card">
                <div class="card-title">Comparación de Meses</div>
                 <img src="../recursos/img/balanza.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Compara dos meses para ver cómo cambiaron las ventas por categoría en cada tienda.</div>
            </a>

            <a href="comparativo_semanal.php" class="card">
                <div class="card-title">Comparación Semanal</div>
                 <img src="../recursos/img/balanza.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Compara dos semanas para ver cómo cambiaron las ventas por categoría en cada tienda.</div>
            </a>

            <a href="topproductos.php" class="card">
                <div class="card-title">Productos Más Vendidos </div>
                 <img src="../recursos/img/top.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Analiza los productos más vendidos por categoría, tipo y sucursal a partir de un archivo de ventas.</div>
            </a>
            <a href="ticket.php" class="card">
                <div class="card-title">Tickets de Mayor Precio</div>
                 <img src="../recursos/img/ticket.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Analiza los tickets con mayor valor de venta y revisa sus productos en IM, TM o mixtos.</div>
            </a>
            <a href="reporte_garantias.php" class="card">
                <div class="card-title">Mermas Vs Ventas</div>
                 <img src="../recursos/img/mermas.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Compara las ventas de protectores con las mermas registradas para calcular porcentajes y analizar causas por sucursal.</div>
            </a>

            <a href="analisis_mermas.php" class="card">
                <div class="card-title">Mermas Frecuentes</div>
                 <img src="../recursos/img/topmermas.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Analiza qué productos generan más mermas en un periodo de tiempo y descarga el reporte.</div>
            </a>
            <a href="hidrogel-polimero.php" class="card">
                <div class="card-title">Ventas Protectores por Sucursal</div>
                 <img src="../recursos/img/micas.png" alt="Icono Ventas" style="width:200px; height:150px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Analiza cuántos protectores Hidrogel y Polímero se vendieron en cada sucursal a partir de un archivo de ventas.</div>
            </a>
            
           <a href="descuentos/" class="card">
                <div class="card-title">Promociones</div>
                 <img src="../recursos/img/descuento.png" alt="Icono Ventas" style="width:180px; height:130px; display:block; margin: 0 auto 8px;">
                <div class="card-desc">Este apartado es ideal para épocas de descuentos, donde se aplican códigos promocionales y se pueden visualizar claramente las ventas reales y los descuentos aplicados.</div>
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