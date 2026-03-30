<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - HERMES</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="logo-container">
    <div class="logo-box">
        <span>🛡</span>
    </div>
    <h1>HERMES</h1>
    <p>Sistema de Seguridad - Universidad</p>
</div>

<div class="card">
    <h2>Iniciar Sesión</h2>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="../controllers/AuthController.php" method="POST">

        <div class="input-group">
            <label>Correo</label>
            <input type="email" name="correo" placeholder="usuario@universidad.edu" required>
        </div>

        <div class="input-group">
            <label>Contraseña</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" name="login">Iniciar Sesión</button>
    </form>

    <div class="links">
        ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
    </div>
</div>

<div class="footer">
    © 2026 HERMES - Todos los derechos reservados
</div>

</body>
</html>