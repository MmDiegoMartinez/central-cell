<?php
session_start();
include_once '../funciones.php';

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    $validador = validarLoginValidador($usuario, $password);

    if ($validador) {
        $_SESSION['validador_id'] = $validador['id'];
        $_SESSION['validador_nombre'] = $validador['nombre'];
        $_SESSION['validador_apellido'] = $validador['apellido'];
        header('Location: validador.php');
        exit;
    } else {
        $mensaje = "❌ Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Validador</title>
    <style>
        body {
    font-family: Arial, sans-serif;
    background: #ffffff; /* blanco de fondo */
    padding: 50px;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

.container {
    max-width: 350px;
    background: linear-gradient(0deg, #ffffff 0%, #fff6f5 100%); /* blanco con toque rojizo muy suave */
    border-radius: 40px;
    padding: 25px 35px;
    border: 2px solid #FF4438; /* borde rojo claro */
    box-shadow: rgba(0, 0, 0, 0.15) 0px 30px 30px -20px; /* sombra negra suave */
}

.heading {
    text-align: center;
    font-weight: 900;
    font-size: 30px;
    color: #FF4438; /* rojo claro */
    text-shadow: 1px 1px px rgba(0, 0, 0, 0.3); /* sombra negra sutil */
}

.form {
    margin-top: 20px;
}

.form .input {
    width: 90%;
    background: white;
    border: none;
    padding: 15px 20px;
    border-radius: 20px;
    margin-top: 15px;
    box-shadow: rgba(255, 68, 56, 0.2) 0px 10px 10px -5px; /* sombra rojo claro */
    border-inline: 2px solid transparent;
    color: #000000; /* texto negro */
}

.form .input::placeholder {
    color: #a64c47; /* rojo oscuro suave para placeholder */
}

.form .input:focus {
    outline: none;
    border-inline: 2px solid #FF4438; /* borde rojo claro al enfocar */
    box-shadow: 0 0 8px rgba(255, 68, 56, 0.6);
}

.form .login-button {
    display: block;
    width: 100%;
    font-weight: bold;
    background: linear-gradient(45deg, #FF4438 0%, #d6372a 100%); /* degradado rojo claro a oscuro */
    color: white;
    padding-block: 15px;
    margin: 20px auto;
    border-radius: 20px;
    box-shadow: rgba(0, 0, 0, 0.4) 0px 20px 10px -15px; /* sombra negra intensa */
    border: none;
    transition: all 0.2s ease-in-out;
}

.form .login-button:hover {
    transform: scale(1.03);
    box-shadow: rgba(0, 0, 0, 0.5) 0px 23px 10px -20px;
}

.form .login-button:active {
    transform: scale(0.95);
    box-shadow: rgba(0, 0, 0, 0.35) 0px 15px 10px -10px;
}

.error {
    color: #FF4438;
    margin-top: 10px;
    text-align: center;
    font-size: 14px;
    text-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
}
.logo {
    display: block;
    margin: 10px auto 0 auto; /* margen arriba de 10px, centrado horizontalmente */
    max-width: 200px;
    height: auto;
}


    </style>
</head>
<body>
    <div class="container">
         <img class="logo" src="../central-cell-logo.png" alt="Central Cell Logo" />
        
        <?php if ($mensaje): ?>
            <p class="error"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>
        <form method="POST" class="form">
            <input type="text" name="usuario" class="input" placeholder="Usuario" required>
            <input type="password" name="password" class="input" placeholder="Contraseña" required>
            <input type="submit" class="login-button" value="Iniciar sesión">
        </form>
    </div>
</body>
</html>
