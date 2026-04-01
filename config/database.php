<?php
$host     = getenv('MYSQL_HOST')     ?: 'localhost';
$port     = getenv('MYSQL_PORT')     ?: '3306';
$dbname   = getenv('MYSQL_DATABASE') ?: 'dbhermes';
$user     = getenv('MYSQL_USER')     ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Auto-migración: crea tablas si no existen
$conn->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`               INT(11)       NOT NULL AUTO_INCREMENT,
    `nombre`           VARCHAR(100)  NOT NULL,
    `correo`           VARCHAR(150)  NOT NULL,
    `password_hash`    VARCHAR(255)  DEFAULT NULL,
    `google_id`        VARCHAR(255)  DEFAULT NULL,
    `matricula`        VARCHAR(50)   DEFAULT NULL,
    `apellido_paterno` VARCHAR(100)  DEFAULT NULL,
    `apellido_materno` VARCHAR(100)  DEFAULT NULL,
    `rol`              ENUM('alumno','admin') NOT NULL DEFAULT 'alumno',
    `twofa_secret`     VARCHAR(255)  DEFAULT NULL,
    `twofa_enabled`    TINYINT(1)    NOT NULL DEFAULT 0,
    `fecha_registro`   TIMESTAMP     NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$conn->exec("CREATE TABLE IF NOT EXISTS `reportes` (
    `id`           INT(11)       NOT NULL AUTO_INCREMENT,
    `categoria`    ENUM('Accidente','Robo','Falla electrica','SOS') NOT NULL,
    `subcategoria` VARCHAR(100)  NOT NULL,
    `descripcion`  TEXT          DEFAULT NULL,
    `latitud`      DECIMAL(10,8) NOT NULL,
    `longitud`     DECIMAL(11,8) NOT NULL,
    `visibilidad`  ENUM('publica','interna') NOT NULL DEFAULT 'publica',
    `estado`       ENUM('pendiente','en_proceso','resuelto') NOT NULL DEFAULT 'pendiente',
    `fecha_hora`   TIMESTAMP     NOT NULL DEFAULT current_timestamp(),
    `usuario_id`   INT(11)       DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `usuario_id` (`usuario_id`),
    CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$conn->exec("CREATE TABLE IF NOT EXISTS `alertas` (
    `id`               INT(11)   NOT NULL AUTO_INCREMENT,
    `reporte_id`       INT(11)   DEFAULT NULL,
    `estado`           ENUM('activa','inactiva','resuelta') NOT NULL DEFAULT 'activa',
    `fecha_creacion`   TIMESTAMP NOT NULL DEFAULT current_timestamp(),
    `fecha_expiracion` DATETIME  DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `reporte_id` (`reporte_id`),
    CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Insertar admin por defecto si no existe
$conn->exec("INSERT IGNORE INTO `usuarios` (`nombre`, `correo`, `password_hash`, `matricula`, `rol`)
VALUES ('Administrador', 'admin@uteq.edu.mx',
'\$2y\$10\$nzHsrH8iXIsl2BzGvQe7.uT6Xa5D.1/RNN1HbIu2BFvWe8BWbVFFq',
'ADMIN-001', 'admin')");
?>
