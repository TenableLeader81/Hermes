<?php
session_start();
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../libs/Mailer.php";

/* ===================================================== */
/* ===================== REGISTRO ====================== */
/* ===================================================== */

if(isset($_POST['register'])){

    $nombre    = trim($_POST['nombre'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $password  = $_POST['password'] ?? '';
    $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
    $apellido_materno = trim($_POST['apellido_materno'] ?? '');

    if(empty($nombre) || empty($correo) || empty($password)){
        $_SESSION['error'] = "Todos los campos son obligatorios.";
        header("Location: /register.php");
        exit;
    }

    if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
        $_SESSION['error'] = "Correo no válido.";
        header("Location: /register.php");
        exit;
    }

    if(!str_ends_with($correo, '@uteq.edu.mx')){
        $_SESSION['error'] = "Solo se permiten correos institucionales (@uteq.edu.mx).";
        header("Location: /register.php");
        exit;
    }

    $prefijo_correo = explode('@', $correo)[0];
    if($matricula !== $prefijo_correo){
        $_SESSION['error'] = "La matrícula debe coincidir con el correo institucional.";
        header("Location: /register.php");
        exit;
    }

    if(strlen($password) < 6){
        $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres.";
        header("Location: /register.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = :correo");
    $stmt->execute([':correo' => $correo]);

    if($stmt->rowCount() > 0){
        $_SESSION['error'] = "Este correo ya está registrado.";
        header("Location: /register.php");
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("
        INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, correo, password_hash, matricula)
        VALUES (:nombre, :apellido_paterno, :apellido_materno, :correo, :password_hash, :matricula)
    ");

    $stmt->execute([
        ':nombre'           => $nombre,
        ':apellido_paterno' => $apellido_paterno,
        ':apellido_materno' => $apellido_materno,
        ':matricula'        => $matricula,
        ':correo'           => $correo,
        ':password_hash'    => $password_hash,
    ]);

    $nuevo_id = $conn->lastInsertId();
    $_SESSION['setup_2fa_user_id'] = $nuevo_id;
    header("Location: /activar_2fa.php");
    exit;
}

/* ===================================================== */
/* ======================= LOGIN ======================= */
/* ===================================================== */

if(isset($_POST['login'])){

    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if(empty($correo) || empty($password)){
        $_SESSION['error'] = "Debes ingresar correo y contraseña.";
        header("Location: /login.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = :correo");
    $stmt->execute([':correo' => $correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$user){
        $_SESSION['login_correo'] = $correo;
        $_SESSION['error'] = "Correo o contraseña incorrectos.";
        header("Location: /login.php");
        exit;
    }

    // Verificar si la cuenta está bloqueada
    if(!empty($user['login_bloqueado_hasta']) && strtotime($user['login_bloqueado_hasta']) > time()){
        $_SESSION['login_correo'] = $correo;
        $_SESSION['error'] = "Cuenta bloqueada por múltiples intentos fallidos. Revisa tu correo para desbloquearla.";
        header("Location: /login.php");
        exit;
    }

    if(password_verify($password, $user['password_hash'])){

        // Login correcto — resetear intentos
        $conn->prepare("UPDATE usuarios SET login_intentos=0, login_bloqueado_hasta=NULL, desbloqueo_token=NULL WHERE id=:id")
             ->execute([':id' => $user['id']]);

        $_SESSION['temp_user_2fa']     = $user['id'];
        $_SESSION['temp_user_2fa_rol'] = $user['rol'];

        if(!$user['twofa_enabled']){
            $_SESSION['setup_2fa_user_id'] = $user['id'];
            header("Location: /activar_2fa.php");
        } else {
            header("Location: /verificar_2fa.php");
        }
        exit;

    } else {
        $intentos = ($user['login_intentos'] ?? 0) + 1;

        if($intentos >= 3){
            $token = bin2hex(random_bytes(32));
            $conn->prepare("UPDATE usuarios SET login_intentos=:i, login_bloqueado_hasta=DATE_ADD(NOW(), INTERVAL 24 HOUR), desbloqueo_token=:t WHERE id=:id")
                 ->execute([':i' => $intentos, ':t' => $token, ':id' => $user['id']]);

            $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
            $link    = $baseUrl . '/desbloquear.php?token=' . $token;
            $fecha   = date('d/m/Y H:i');
            $asunto  = "Cuenta HERMES bloqueada - Desbloquea tu acceso";
            $html    = "
<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'></head>
<body style='font-family:Arial,sans-serif;background:#f3f4f6;padding:32px;'>
  <div style='max-width:520px;margin:0 auto;background:#fff;border-radius:12px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.08);'>
    <h2 style='color:#dc2626;'>Cuenta bloqueada</h2>
    <p>Hola <b>{$user['nombre']}</b>,</p>
    <p>Tu cuenta fue bloqueada por <b>3 intentos fallidos</b> de inicio de sesion el {$fecha}.</p>
    <p>Haz clic en el boton para desbloquearla:</p>
    <a href='{$link}' style='display:inline-block;background:#2563eb;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:bold;margin:16px 0;'>
      Desbloquear mi cuenta
    </a>
    <p style='font-size:12px;color:#9ca3af;margin-top:24px;'>Si no fuiste tu, ignora este correo. El enlace expira en 24 horas.</p>
  </div>
</body></html>";

            Mailer::enviar($user['correo'], $user['nombre'], $asunto, $html);

            $_SESSION['login_correo'] = $correo;
            $_SESSION['error'] = "Cuenta bloqueada tras 3 intentos fallidos. Te enviamos un correo para desbloquearla.";
        } else {
            $conn->prepare("UPDATE usuarios SET login_intentos=:i WHERE id=:id")
                 ->execute([':i' => $intentos, ':id' => $user['id']]);

            $restantes = 3 - $intentos;
            $_SESSION['login_correo'] = $correo;
            $_SESSION['error'] = "Contrasena incorrecta. Te quedan {$restantes} intento(s) antes de bloquear la cuenta.";
        }

        header("Location: /login.php");
        exit;
    }
}

/* ===================================================== */
/* ======================= LOGOUT ====================== */
/* ===================================================== */

if(isset($_GET['logout'])){
    session_unset();
    session_destroy();
    header("Location: /login.php");
    exit;
}
