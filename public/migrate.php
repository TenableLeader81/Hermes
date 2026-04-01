<?php
// ARCHIVO TEMPORAL — ELIMINAR DESPUÉS DE EJECUTAR
require_once __DIR__ . "/../config/database.php";

$sqls = [
    "SET NAMES utf8mb4",

    "CREATE TABLE IF NOT EXISTS `usuarios` (
        `id`             INT(11)       NOT NULL AUTO_INCREMENT,
        `nombre`         VARCHAR(100)  NOT NULL,
        `correo`         VARCHAR(150)  NOT NULL,
        `password_hash`  VARCHAR(255)  DEFAULT NULL,
        `google_id`      VARCHAR(255)  DEFAULT NULL,
        `matricula`      VARCHAR(50)   DEFAULT NULL,
        `rol`            ENUM('alumno','admin') NOT NULL DEFAULT 'alumno',
        `apellido_paterno` VARCHAR(100) DEFAULT NULL,
        `apellido_materno` VARCHAR(100) DEFAULT NULL,
        `twofa_secret`   VARCHAR(255)  DEFAULT NULL,
        `twofa_enabled`  TINYINT(1)    NOT NULL DEFAULT 0,
        `fecha_registro` TIMESTAMP     NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `correo` (`correo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS `reportes` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "CREATE TABLE IF NOT EXISTS `alertas` (
        `id`               INT(11)   NOT NULL AUTO_INCREMENT,
        `reporte_id`       INT(11)   DEFAULT NULL,
        `estado`           ENUM('activa','inactiva','resuelta') NOT NULL DEFAULT 'activa',
        `fecha_creacion`   TIMESTAMP NOT NULL DEFAULT current_timestamp(),
        `fecha_expiracion` DATETIME  DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `reporte_id` (`reporte_id`),
        CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reportes` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    "INSERT IGNORE INTO `usuarios` (`nombre`, `correo`, `password_hash`, `matricula`, `rol`)
     VALUES ('Administrador', 'admin@uteq.edu.mx',
     '\$2y\$10\$nzHsrH8iXIsl2BzGvQe7.uT6Xa5D.1/RNN1HbIu2BFvWe8BWbVFFq',
     'ADMIN-001', 'admin')",
];

$errores = [];
foreach ($sqls as $sql) {
    try {
        $conn->exec($sql);
    } catch (PDOException $e) {
        $errores[] = $e->getMessage();
    }
}

if (empty($errores)) {
    echo "<h2 style='color:green'>✅ Tablas creadas correctamente.</h2>";
    echo "<p>Admin: <b>admin@uteq.edu.mx</b> / contraseña: <b>admin123</b></p>";
    echo "<p><strong>Ahora elimina este archivo (migrate.php) por seguridad.</strong></p>";
} else {
    echo "<h2 style='color:red'>❌ Errores:</h2><ul>";
    foreach ($errores as $e) echo "<li>" . htmlspecialchars($e) . "</li>";
    echo "</ul>";
}
?>
