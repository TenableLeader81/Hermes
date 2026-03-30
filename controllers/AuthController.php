<?php
session_start();
require_once "../config/database.php";

/* ===================================================== */
/* ===================== REGISTRO ====================== */
/* ===================================================== */

if(isset($_POST['register'])){

    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $matricula = trim($_POST['matricula']);
    $password = $_POST['password'];

    if(empty($nombre) || empty($correo) || empty($password)){
        $_SESSION['error'] = "Todos los campos son obligatorios.";
        header("Location: ../public/register.php");
        exit;
    }

    if(!filter_var($correo, FILTER_VALIDATE_EMAIL)){
        $_SESSION['error'] = "Correo no válido.";
        header("Location: ../public/register.php");
        exit;
    }

    // Solo correos institucionales
    if(!str_ends_with($correo, '@uteq.edu.mx')){
        $_SESSION['error'] = "Solo se permiten correos institucionales (@uteq.edu.mx).";
        header("Location: ../public/register.php");
        exit;
    }

    // La matrícula debe coincidir con el prefijo del correo
    $prefijo_correo = explode('@', $correo)[0];
    if($matricula !== $prefijo_correo){
        $_SESSION['error'] = "La matrícula debe coincidir con el correo institucional.";
        header("Location: ../public/register.php");
        exit;
    }

    if(strlen($password) < 6){
        $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres.";
        header("Location: ../public/register.php");
        exit;
    }

    // Verificar si ya existe el correo
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE correo = :correo");
    $stmt->execute([':correo' => $correo]);

    if($stmt->rowCount() > 0){
        $_SESSION['error'] = "Este correo ya está registrado.";
        header("Location: ../public/register.php");
        exit;
    }

    // Encriptar contraseña
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insertar usuario
    $stmt = $conn->prepare("
        INSERT INTO usuarios (nombre, correo, password_hash, matricula)
VALUES (:nombre, :correo, :password_hash, :matricula)
    ");

    $stmt->execute([
        ':nombre' => $nombre,
        ':matricula' => $matricula,
        ':correo' => $correo,
        ':password_hash' => $password_hash
    ]);

    $nuevo_id = $conn->lastInsertId();
    $_SESSION['setup_2fa_user_id'] = $nuevo_id;
    header("Location: ../public/activar_2fa.php");
    exit;
}

/* ===================================================== */
/* ======================= LOGIN ======================= */
/* ===================================================== */

if(isset($_POST['login'])){

    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    if(empty($correo) || empty($password)){
        $_SESSION['error'] = "Debes ingresar correo y contraseña.";
        header("Location: ../public/login.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = :correo");
    $stmt->execute([':correo' => $correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user && password_verify($password, $user['password_hash'])){

        $_SESSION['temp_user_2fa'] = $user['id'];
        $_SESSION['temp_user_2fa_rol'] = $user['rol'];

        // Si no tiene 2FA configurado, forzar configuración
        if(!$user['twofa_enabled']){
            $_SESSION['setup_2fa_user_id'] = $user['id'];
            header("Location: ../public/activar_2fa.php");
        } else {
            header("Location: ../public/verificar_2fa.php");
        }
        exit;

    } else {

        $_SESSION['error'] = "Correo o contraseña incorrectos.";
        header("Location: ../public/login.php");
        exit;
    }
}

/* ===================================================== */
/* ======================= LOGOUT ====================== */
/* ===================================================== */

if(isset($_GET['logout'])){

    session_unset();
    session_destroy();

    header("Location: ../public/login.php");
    exit;
}