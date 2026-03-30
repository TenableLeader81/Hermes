<?php
/* ═══════════════════════════════════════
   Configuración SMTP — Gmail
   ─────────────────────────────────────
   1. Ve a myaccount.google.com
   2. Seguridad → Verificación en 2 pasos (actívala)
   3. Seguridad → Contraseñas de aplicaciones
   4. Genera una contraseña para "Correo / Windows"
   5. Pega esa contraseña de 16 caracteres en MAIL_PASS
═══════════════════════════════════════ */

define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USER',      'hermeseguridad19@gmail.com');  // ← tu correo Gmail
define('MAIL_PASS',      'gxfg zikg kjlx kgop');               // ← contraseña de aplicación (16 chars)
define('MAIL_FROM',      'hermeseguridad19@gmail.com'); // ← mismo correo
define('MAIL_FROM_NAME', 'HERMES – Seguridad UTEQ');
