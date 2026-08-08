<?php
session_start();
include_once '../../funciones.php';

if (!isset($_SESSION['validador_id'])) {
header('Location: loginvalidador.php');
exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$datos = [
'nombre' => trim($_POST['nombre'] ?? ''),
'apellido' => trim($_POST['apellido'] ?? ''),
'usuario' => trim($_POST['usuario'] ?? ''),
'password' => $_POST['password'] ?? '',
    ];

if (in_array('', $datos, true)) {
die("Por favor completa todos los campos.");
    }

$resultado = crearValidador($datos);

if ($resultado === true) {
header('Location: validadores.php?creado=1');
exit;
    } else {
die($resultado);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Crear Validador</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../../styles.css">

<style>
  /* ---- Ajustes puntuales que el CSS base no cubre (no se toca styles.css) ---- */

  /* Navbar horizontal (mismo patrón del resto del portal) */
  .navbar{
    position:sticky;top:0;z-index:50;
    display:flex;align-items:center;justify-content:space-between;
    gap:var(--space-md);
    padding:12px var(--space-lg);
    background:var(--surface);
    border-bottom:1px solid var(--outline-variant);
  }
  .navbar-brand{
    display:flex;align-items:center;gap:10px;
    text-decoration:none;color:var(--on-surface);
  }
  .navbar-brand img{border-radius:6px;}
  .navbar-brand-text p{margin:0;line-height:1.2;}
  .navbar-links{
    display:flex;align-items:center;gap:6px;flex-wrap:wrap;
  }
  .navbar-link{
    display:flex;align-items:center;gap:6px;
    padding:8px 14px;border-radius:var(--radius-lg);
    color:var(--on-surface-variant);text-decoration:none;
    font-size:14px;font-weight:600;
    transition:all .15s ease;
  }
  .navbar-link .material-symbols-outlined{font-size:20px;}
  .navbar-link:hover{background:var(--surface-container-low);color:var(--primary);}
  .navbar-link.active{background:var(--primary);color:var(--on-primary,#fff);}
  .navbar-toggle{display:none;}

  @media (max-width:720px){
    .navbar-links{
      display:none;flex-direction:column;align-items:stretch;
      position:absolute;top:100%;left:0;right:0;
      background:var(--surface);border-bottom:1px solid var(--outline-variant);
      padding:var(--space-sm);gap:4px;
    }
    .navbar-links.open{display:flex;}
    .navbar-toggle{
      display:flex;align-items:center;justify-content:center;
      background:none;border:none;cursor:pointer;color:var(--on-surface);
    }
  }

  h1 span{color:var(--primary);}

  .panel{
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    background:var(--surface-container-low);
    overflow:hidden;
    max-width:520px;
  }
  .panel-header{
    padding:14px var(--space-md);
    border-bottom:1px solid var(--outline-variant);
    background:var(--surface);
  }
  .panel-title{
    font-weight:700;font-size:15px;color:var(--on-surface);
    display:flex;align-items:center;gap:8px;
  }
  .panel-body{padding:var(--space-md);}

  /* Formulario */
  .form-group{display:flex;flex-direction:column;gap:6px;margin-bottom:var(--space-md);}
  .form-group label{font-size:13px;font-weight:600;color:var(--on-surface-variant);}
  .form-group input[type="text"],
  .form-group input[type="password"]{
    padding:10px 12px;
    border:1px solid var(--outline-variant);
    border-radius:var(--radius-lg);
    background:var(--surface);
    color:var(--on-surface);
    font-size:14px;font-family:inherit;
    width:100%;
  }
  .form-group input:focus{outline:none;border-color:var(--primary);}

  .btn-full{width:100%;justify-content:center;}
</style>
<script>
document.addEventListener("DOMContentLoaded", function () {
const titulo = document.getElementById("titulo");
const texto = "Crear Nuevo Validador";
let i = 0;
let borrando = false;

function escribirMaquina() {
const inicioInmediato = 1;

if (i === 0 && !borrando) {
titulo.textContent = texto.charAt(0);
i = inicioInmediato + 1;
            } else if (!borrando && i <= texto.length) {
titulo.textContent = texto.slice(0, i);
i++;
            } else if (borrando && i >= 0) {
titulo.textContent = texto.slice(0, i);
i--;
            }

if (i > texto.length) {
borrando = true;
setTimeout(escribirMaquina, 1500);
return;
            } else if (i === 0 && borrando) {
borrando = false;
            }

setTimeout(escribirMaquina, borrando ? 70 : 170);
        }

escribirMaquina();
    });
</script>
</head>
<body>

<!-- ===================== NAVBAR HORIZONTAL ===================== -->
<header class="navbar">
  <a href="validador.php" class="navbar-brand">
    <div class="navbar-brand-text">
      <p class="text-headline-sm">Central Cell</p>
      <p class="text-label-sm" style="color:var(--outline)">Validadores</p>
    </div>
  </a>

  <button class="navbar-toggle" id="navToggle" type="button" aria-label="Abrir menú">
    <span class="material-symbols-outlined">menu</span>
  </button>

  <nav class="navbar-links" id="navLinks">
    <a href="validador.php" class="navbar-link">
      <span class="material-symbols-outlined">home</span>
      Home
    </a>
    <a href="validadores.php" class="navbar-link">
      <span class="material-symbols-outlined">arrow_back</span>
      Atrás
    </a>
  </nav>
</header>

<!-- ===================== MAIN ===================== -->
<div class="container">
  <div class="lesson">
    <div class="lesson-body">

      <span class="eyebrow">Administración</span>
      <h1 class="text-headline-lg" style="margin:6px 0 var(--space-lg)">Crear Nuevo <span>Validador</span></h1>

      <div class="panel">
        <div class="panel-header">
          <span class="panel-title">
            <span class="material-symbols-outlined" style="font-size:18px">person_add</span>
            Datos del validador
          </span>
        </div>
        <div class="panel-body">
          <form method="POST">
            <div class="form-group">
              <label for="nombre1">Nombre</label>
              <input type="text" id="nombre1" name="nombre" required>
            </div>

            <div class="form-group">
              <label for="apellido">Apellido</label>
              <input type="text" id="apellido" name="apellido" required>
            </div>

            <div class="form-group">
              <label for="usuario">Usuario</label>
              <input type="text" id="usuario" name="usuario" required>
            </div>

            <div class="form-group">
              <label for="password">Contraseña</label>
              <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
              <span class="material-symbols-outlined">check</span>
              Crear Validador
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  // Control del menú horizontal en móvil
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');
  navToggle.addEventListener('click', () => {
    navLinks.classList.toggle('open');
  });
</script>

</body>
</html>