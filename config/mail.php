<?php
/* ═══════════════════════════════════════
   Configuración Email — Brevo API
   (Railway bloquea SMTP, usamos HTTP API)
   ─────────────────────────────────────
   1. Crea cuenta gratis en brevo.com
   2. My Account → SMTP & API → API Keys
   3. Pega la API key en BREVO_API_KEY
═══════════════════════════════════════ */

define('BREVO_API_KEY',  getenv('BREVO_API_KEY') ?: 'TU_API_KEY_AQUI');
define('MAIL_FROM',      'hermeseguridad19@gmail.com');
define('MAIL_FROM_NAME', 'HERMES – Seguridad UTEQ');
