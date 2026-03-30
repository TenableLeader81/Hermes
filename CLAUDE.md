# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Running the project

Requires XAMPP. Start Apache and MySQL from the XAMPP Control Panel, then access the app at:
- `http://localhost/HERMES/public/` (landing page)
- `http://localhost/HERMES/public/login.php`

Import the database schema via phpMyAdmin or CLI:
```bash
mysql -u root dbhermes < db/dbhermes.sql
```

> **Note:** The SQL dump in `db/dbhermes.sql` is outdated. The `usuarios` table is missing the columns `matricula VARCHAR(50)`, `twofa_secret VARCHAR(255)`, and `twofa_enabled TINYINT(1) DEFAULT 0` that the application code actively uses. Add them manually after importing.

## Architecture

Vanilla PHP on XAMPP (MariaDB 10.4, PHP 8.2). No framework, no Composer, no autoloading.

```
config/database.php          — PDO connection, exposes global $conn
controllers/AuthController.php — Single file handling register/login/logout via POST/GET
libs/GoogleAuthenticator.php  — PHPGangsta TOTP library (vendored)
public/                       — All browser-accessible pages
public/assets/css/style.css   — Styles for auth pages (login, register)
public/assets/css/dashboard.css — Styles for dashboard and crear_reporte
db/dbhermes.sql               — DB schema (see note above)
```

### Request flow

1. **Public pages** (`index.php`, `nosotros.php`) — no auth required.
2. **Auth actions** — forms POST to `../controllers/AuthController.php`, which validates, writes to session, and redirects back to `public/`.
3. **Protected pages** — each page checks `$_SESSION['user_id']` at the top and redirects to `login.php` if missing. They require `config/database.php` directly via `require_once "../config/database.php"`.
4. **2FA flow** — on login with 2FA enabled, user is redirected to `verificar_2fa.php` with `$_SESSION['temp_user_2fa']` set. After TOTP verification, the session is promoted to `user_id`.

### Database schema (3 tables)

- `usuarios` — id, nombre, correo, password_hash (bcrypt), matricula, google_id, twofa_secret, twofa_enabled, fecha_registro
- `reportes` — id, categoria (enum: Accidente/Robo/Falla electrica), subcategoria, latitud, longitud, fecha_hora, usuario_id
- `alertas` — id, reporte_id, estado (enum: activa/inactiva), fecha_creacion, fecha_expiracion

### Known issues / incomplete features

- `crear_reporte.php` UI is built but the form does not submit data to the DB yet.
- Dashboard alerts section shows hardcoded placeholder data, not real DB records.
- The map is a CSS placeholder — interactive map integration is pending.
- `confirmar_2fa.php` (activating 2FA) lacks a session auth check before processing.
- `desactivar_2fa.php` is referenced in the sidebar but does not exist yet.
