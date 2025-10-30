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
        header('Location: capa.php');
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
    <link rel="stylesheet" href="../">
    <style>
        :root {
  --bg: #f5f7fa;
  --surface: #ffffff;
  --muted: #6b7280;
  --text: #0f1724;
  --primary-600: #0f5476;
  --primary-400: #16729a;
  --accent: #1f9a8a;
  --shadow-md: 0 10px 30px rgba(12,18,26,0.09);
  --transition-fast: 220ms cubic-bezier(.2,.9,.2,1);
}

body {
  font-family: Inter, "Segoe UI", Roboto, system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
  background: linear-gradient(180deg, var(--bg), #eef3f7 80%);
  padding: 50px;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  margin: 0;
}

.container {
  max-width: 350px;
  background: linear-gradient(180deg, var(--surface), #f2f8fb 90%);
  border-radius: 40px;
  padding: 25px 35px;
  border: 2px solid var(--primary-400);
  box-shadow: var(--shadow-md);
  backdrop-filter: blur(6px);
  transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.container:hover {
  transform: translateY(-6px);
  box-shadow: 0 16px 32px rgba(15,84,118,0.15);
}

.heading {
  text-align: center;
  font-weight: 900;
  font-size: 30px;
  color: var(--primary-600);
  text-shadow: 0 1px 3px rgba(15, 84, 118, 0.25);
  letter-spacing: -0.02em;
}

.form {
  margin-top: 20px;
}

.form .input {
  width: 90%;
  background: #ffffff; /* Blanco puro */
  border: none;
  padding: 15px 20px;
  border-radius: 20px;
  margin-top: 15px;
  box-shadow: 0 6px 12px rgba(15, 84, 118, 0.08);
  border-inline: 2px solid transparent;
  color: var(--text);
  transition: all var(--transition-fast);
}

.form .input::placeholder {
  color: var(--muted);
}

.form .input:focus {
  outline: none;
  border-inline: 2px solid var(--primary-400);
  box-shadow: 0 0 8px rgba(22, 114, 154, 0.4);
  transform: scale(1.02);
}

/* Botón principal */
.form .login-button {
  display: block;
  width: 100%;
  font-weight: bold;
  background: linear-gradient(45deg, var(--primary-600) 0%, var(--primary-400) 100%);
  color: white;
  padding-block: 15px;
  margin: 20px auto;
  border-radius: 20px;
  box-shadow: 0 8px 20px rgba(15,84,118,0.15);
  border: none;
  transition: all var(--transition-fast);
}

.form .login-button:hover {
  transform: translateY(-3px) scale(1.03);
  box-shadow: 0 12px 24px rgba(15,84,118,0.25);
  background: linear-gradient(45deg, var(--primary-400), var(--accent));
}

.form .login-button:active {
  transform: scale(0.95);
  box-shadow: 0 6px 16px rgba(15,84,118,0.25);
}

.error {
  color: var(--primary-400);
  margin-top: 10px;
  text-align: center;
  font-size: 14px;
  text-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
}

/* Animación sutil de entrada */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.container, .heading, .form {
  animation: fadeInUp 0.8s var(--transition-fast);
}

.logo {
  display: block;
  margin: 10px auto 0 auto;
  max-width: 200px;
  height: auto;
  filter: drop-shadow(0 4px 6px rgba(15,84,118,0.2));
  transition: transform var(--transition-fast);
}

.logo:hover {
  transform: scale(1.05);
}

/* 🔧 Evita el color amarillo en inputs autocompletados por Chrome/Edge */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
  box-shadow: 0 0 0 1000px #ffffff inset !important;
  -webkit-text-fill-color: var(--text) !important;
  transition: background-color 9999s ease-in-out 0s !important;
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
