<?php
include_once '../../funciones.php';
session_start();
if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

$validadores = obtenerValidadores();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Lista de Validadores</title>
    <link rel="stylesheet" href="../../csstabla.css">
</head>
<body>


<nav style="background:#1D6C90; padding:10px;">
  <ul id="menu">
    
    <li>
      <a href="validador.php" style="display: flex; align-items: center; gap: 12px;">
        <span style="
          display: inline-flex;
          width: 40px; 
          height: 40px; 
          background: white; 
          border-radius: 50%; 
          justify-content: center; 
          align-items: center; 
          overflow: visible;
          position: relative;
        ">
          <img src="../../recursos/img/Central-Cell-Logo-JUSTCELL.png?v=<?= filemtime('../../recursos/img/Central-Cell-Logo-JUSTCELL.png') ?>" alt="Logo Central Cell" 
               style="
                 width: 30px; 
                 height: 30px; 
                 object-fit: contain;
                 position: relative;
                 top: 0; left: 0;
               " />
        </span>
        Home
      </a>
    </li>

    <li>
      <a href="crear_validador.php" style="display: flex; align-items: center; gap: 12px;">
         ➕ Nuevo Validador
      </a>
    </li>
  </ul>
</nav>

   <br><br>
   <div class="center-container">
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Usuario</th>
                <th>Fecha de Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($validadores as $v): ?>
            <tr>
                <td><?= htmlspecialchars($v['id']) ?></td>
                <td><?= htmlspecialchars($v['nombre']) ?></td>
                <td><?= htmlspecialchars($v['apellido']) ?></td>
                <td><?= htmlspecialchars($v['usuario']) ?></td>
                <td><?= htmlspecialchars($v['created_at']) ?></td>
                <td>
                    <a href="editar_validador.php?id=<?= $v['id'] ?>">✏️ Editar</a> 
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    
</body>
</html>
