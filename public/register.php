<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - HERMES</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="logo-container">
    <div class="logo-box">
        <span>🛡</span>
    </div>
    <h1>HERMES</h1>
</div>

<div class="card">
    <h2>Crear Cuenta</h2>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="error">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <form action="../controllers/AuthController.php" method="POST">

        <div class="input-group">
            <label>Nombre</label>
            <input type="text" name="nombre" required>
        </div>

        <div class="input-group">
            <label>Matrícula</label>
            <input type="text" name="matricula" required>
        </div>

        <div class="input-group">
            <label>Correo</label>
            <input type="email" name="correo" placeholder="matricula@uteq.edu.mx" required>
        </div>

        <div class="input-group">
            <label>Contraseña</label>
            <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
        </div>

        <button type="submit" name="register">Crear Cuenta</button>
    </form>

    <div class="links">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </div>
</div>

</body>
</html>