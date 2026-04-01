<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . "/../config/database.php";

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

    if($user && password_verify($password, $user['password_hash'])){

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
        $_SESSION['login_correo'] = $correo;
        $_SESSION['error'] = "Correo o contraseña incorrectos.";
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
