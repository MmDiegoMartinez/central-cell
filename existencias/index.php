<?php session_start();
include_once '../../funciones.php';

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}

if (!isset($_SESSION['validador_id'])) {
    header('Location: loginvalidador.php');
    exit;
}


$validador_id = $_SESSION['validador_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Existencias</title>

    <link rel="stylesheet" href="../styles.css">
    <style>
      
        .back-button{
            display:inline-flex;align-items:center;gap:6px;
            margin:var(--space-lg) 0 0;
            padding:8px 16px;
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-lg);
            background:var(--surface-container-lowest);
            color:var(--on-surface);
            font-size:14px;font-weight:600;
            text-decoration:none;
            transition:border-color .15s ease, transform .15s ease;
        }
        .back-button:hover{border-color:var(--primary);transform:translateX(-2px);}

        .topbar{margin:var(--space-xl) 0 var(--space-lg);}
        .topbar h1{margin:0 0 6px;}
        .topbar .subtitle{margin:0;color:var(--on-surface-variant);font-size:14px;}

        .menu-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
            gap:var(--space-lg);
            margin-top:var(--space-xl);
        }
        .menu-grid a{text-decoration:none;display:block;}
        .menu-card{
            width:100%;
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            gap:8px;
            padding:var(--space-xl) var(--space-lg);
            border:1px solid var(--outline-variant);
            border-radius:var(--radius-xl);
            background:var(--surface-container-lowest);
            color:var(--on-surface);
            font-size:15px;font-weight:600;text-align:center;
            box-shadow:0 1px 2px rgba(17,28,45,0.04);
            transition:border-color .15s ease, transform .15s ease, box-shadow .15s ease;
        }
        .menu-card .menu-icon{font-size:28px;}
        .menu-card:hover{
            border-color:var(--primary);
            transform:translateY(-2px);
            box-shadow:0 4px 12px rgba(17,28,45,0.08);
        }
    </style>
</head>
<body>

    <div class="container">

   

        <a href="../garantias/validador/validador.php" class="back-button">
            <span class="material-symbols-outlined">home</span>
            Home
        </a>
        <a href="../kpis/modulos.html" class="back-button">
            <span class="material-symbols-outlined">build</span>
            Panel de Herramientas
        </a>

        <div class="topbar">
            <h1 class="text-headline-md">Gestión de Existencias</h1>
            <p class="subtitle">Selecciona una opción para continuar</p>
        </div>

        <div class="menu-grid">
            

           <a href="subir_existencias.php">
                <button class="menu-card">
                    <span class="menu-icon material-symbols-outlined">upload</span>
                    Act. Existencias Full
                </button>
            </a>

               

               <a href="buscador.php">
                    <button class="menu-card">
                        <span class="menu-icon material-symbols-outlined">search</span>
                        Consultar Existencias
                    </button>
                </a>

                <a href="catalogo.php">
                    <button class="menu-card">
                        <span class="menu-icon material-symbols-outlined">smartphone</span>
                        Catálogo de Teléfonos
                    </button>
                </a>
            </div>
    </div>

</body>
</html>