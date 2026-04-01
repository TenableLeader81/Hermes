# PRD — HERMES: Sistema de Seguridad Campus UTEQ

**Versión:** 1.0
**Fecha:** 30 de marzo de 2026
**Universidad:** Universidad Tecnológica de Querétaro (UTEQ)

---

## 1. Resumen ejecutivo

HERMES es una plataforma digital de seguridad universitaria diseñada para la UTEQ. Permite a estudiantes y personal reportar incidentes en tiempo real, recibir alertas mediante correo electrónico, y activar emergencias a través de un dispositivo físico IoT (ESP32 + GPS). Su objetivo es reducir el tiempo de respuesta ante situaciones de riesgo dentro y alrededor del campus.

---

## 2. Problema

El campus de la UTEQ carece de un canal centralizado para:

- Reportar incidentes de seguridad (robos, accidentes, fallas de servicios)
- Alertar a la comunidad estudiantil en tiempo real
- Localizar emergencias con coordenadas GPS precisas
- Dar a la Dirección visibilidad del estado de seguridad del campus

Los métodos actuales (llamadas, mensajes informales) son lentos, no trazables y no generan historial de incidentes.

---

## 3. Objetivos del producto

| Objetivo | Métrica de éxito |
|---|---|
| Reducir el tiempo de reporte de incidentes | Reporte enviado en < 60 segundos |
| Notificar a todos los usuarios registrados | 100% de usuarios reciben correo en < 30 s |
| Activar emergencia SOS desde hardware físico | Botón → notificación en < 10 s |
| Dar visibilidad a Dirección | Panel admin con historial en tiempo real |

---

## 4. Usuarios objetivo

### 4.1 Alumno / Personal UTEQ
- Correo institucional `@uteq.edu.mx`
- Matrícula UTEQ válida
- Puede reportar incidentes, ver el mapa de alertas y recibir notificaciones

### 4.2 Dirección (Admin)
- Rol administrador asignado manualmente en BD
- Ve todos los reportes (públicos e internos)
- Puede marcar fallas de servicio como resueltas
- Recibe notificaciones de nuevos incidentes

---

## 5. Funcionalidades

### 5.1 Autenticación

| Funcionalidad | Descripción |
|---|---|
| Registro | Nombre, apellidos, matrícula, correo `@uteq.edu.mx`, contraseña |
| Validación de dominio | Solo se aceptan correos `@uteq.edu.mx` |
| Validación de matrícula | La matrícula debe coincidir con el prefijo del correo |
| Contraseña segura | Mínimo 8 caracteres, 1 mayúscula, 1 carácter especial |
| Login | Correo + contraseña + 2FA obligatorio |
| 2FA obligatorio | Google Authenticator (TOTP) requerido para todos los usuarios |
| Bloqueo de cuenta | Bloqueo automático tras 3 intentos fallidos |
| Desbloqueo por correo | Enlace de desbloqueo enviado al correo del usuario |

### 5.2 Dashboard del Alumno

| Funcionalidad | Descripción |
|---|---|
| Mapa interactivo | OpenStreetMap centrado en UTEQ (lat: 20.65636, lon: -100.40507) |
| Marcadores por categoría | 🚨 Robo · 🚑 Accidente · ⚡ Falla · 🆘 SOS |
| Alertas en tiempo real | Panel con polling cada 10 segundos |
| Expiración de alertas | Las alertas desaparecen después de 8 horas |
| Toast de notificación | Notificación visual al recibir nuevas alertas |
| Diseño responsivo | Menú colapsable en móvil con overlay |

### 5.3 Crear Reporte

| Funcionalidad | Descripción |
|---|---|
| Categorías | Robo · Accidente · Falla de Servicio |
| Subcategorías | Específicas por categoría |
| Ubicación GPS | Captura automática desde el dispositivo del usuario |
| Visibilidad | Robo/Accidente → pública · Falla → interna (solo Dirección) |
| Notificación automática | Correo HTML a todos los usuarios al crearse reporte público |

### 5.4 Botón de Pánico (IoT)

| Funcionalidad | Descripción |
|---|---|
| Hardware | ESP32 + módulo GPS (TinyGPS++), botón en pin 4 |
| Red | WiFi `UTEQ-Alumnos` (red institucional) |
| Flujo | Botón presionado → GPS → GET a `api/sos.php` → alerta SOS → correo a todos |
| Correo SOS | Incluye coordenadas GPS y enlace directo a Google Maps |
| Prioridad | Máxima — categoría SOS con estilo visual diferenciado |

### 5.5 Panel de Dirección (Admin)

| Funcionalidad | Descripción |
|---|---|
| Vista de fallas | Tab exclusivo con reportes internos (Falla de Servicio) |
| Vista de incidentes | Tab con Robos y Accidentes públicos |
| Mapa admin | CARTO light_all, centrado en UTEQ, sin círculos de radio |
| Marcar como resuelto | Botón por reporte, notifica a alumnos al resolver |
| Polling | Nuevos reportes cada 10 segundos con badge de contador |
| Diseño responsivo | Menú colapsable en móvil |

### 5.6 Notificaciones por correo

| Tipo | Destinatarios | Cuándo |
|---|---|---|
| Alerta de incidente | Todos los usuarios | Al crear Robo o Accidente |
| Alerta SOS | Todos los usuarios | Al presionar botón ESP32 |
| Falla resuelta | Todos los usuarios | Al marcar falla como resuelta (admin) |
| Bloqueo de cuenta | Usuario afectado | Tras 3 intentos fallidos |

---

## 6. Arquitectura técnica

### 6.1 Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 (vanilla, sin framework) |
| Base de datos | MariaDB 10.4 |
| Servidor | Apache (XAMPP local) |
| Frontend | HTML5, CSS3, JavaScript vanilla |
| Mapa (usuario) | Leaflet.js + OpenStreetMap |
| Mapa (admin) | Leaflet.js + CARTO light_all |
| Correo | SMTP Gmail — cliente PHP propio (sin dependencias) |
| 2FA | TOTP / Google Authenticator (PHPGangsta library) |
| IoT firmware | C++ (Arduino IDE) |
| IoT hardware | ESP32 + módulo GPS NEO-6M |
| Comunicación IoT | HTTP GET por WiFi |

### 6.2 Estructura de base de datos

**Tabla `usuarios`**
```
id, nombre, apellido_paterno, apellido_materno, correo, password_hash,
matricula, rol, google_id, twofa_secret, twofa_enabled,
intentos_fallidos, cuenta_bloqueada, token_desbloqueo, fecha_registro
```

**Tabla `reportes`**
```
id, categoria (Accidente|Robo|Falla electrica|SOS),
subcategoria, descripcion, latitud, longitud,
visibilidad (publica|interna), estado (pendiente|en_proceso|resuelto),
fecha_hora, usuario_id
```

**Tabla `alertas`**
```
id, reporte_id, estado (activa|inactiva|resuelta), fecha_creacion, fecha_expiracion
```

### 6.3 Flujo de datos

```
Alumno reporta / ESP32 presionado
        ↓
  PHP valida y guarda en BD (MariaDB)
        ↓
  Genera alerta activa (expira en 8h)
        ↓
  Envía correo HTML a todos los usuarios (SMTP Gmail)
        ↓
  Dashboard se actualiza via polling cada 10s
        ↓
  Admin puede resolver / archivar el reporte
```

---

## 7. Seguridad

| Mecanismo | Implementación |
|---|---|
| Contraseñas | bcrypt (PASSWORD_BCRYPT) |
| 2FA | TOTP obligatorio para todos los usuarios |
| Bloqueo de cuenta | 3 intentos fallidos → bloqueo + correo de desbloqueo |
| Validación de dominio | Solo `@uteq.edu.mx` |
| Protección de rutas | `$_SESSION['user_id']` en cada página protegida |
| Roles | `alumno` y `admin` — separación de vistas y permisos |
| Sanitización | `htmlspecialchars()` en outputs, PDO con parámetros preparados |

---

## 8. Pendientes y mejoras futuras

| Ítem | Prioridad | Descripción |
|---|---|---|
| Subida a la nube | Alta | Migrar de XAMPP local a Railway o Hostinger |
| Docker | Media | Contenedores para entorno reproducible |
| `confirmar_2fa.php` | Media | Reforzar validación de sesión |
| `desactivar_2fa.php` | Media | Crear página para desactivar 2FA |
| Landing page mapa | Baja | Reemplazar mapa CSS decorativo con Leaflet real |
| Notificaciones push | Baja | Web Push API como alternativa al polling |
| WebSockets | Baja | Reemplazar polling con conexión en tiempo real |
| Panel de estadísticas | Baja | Gráficas de incidentes por categoría / zona |

---

## 9. Restricciones

- El sistema opera en la red institucional `UTEQ-Alumnos` para el dispositivo ESP32
- Solo usuarios con correo `@uteq.edu.mx` pueden registrarse
- El correo de alertas depende de la disponibilidad del servidor SMTP de Gmail
- Las alertas públicas expiran automáticamente a las 8 horas

---

## 10. Equipo

| Rol | Responsabilidad |
|---|---|
| Desarrollo web | PHP, BD, frontend, APIs |
| Hardware / IoT | ESP32, GPS, firmware Arduino |
| Seguridad | 2FA, autenticación, validaciones |
| Infraestructura | Servidor local (XAMPP), futura migración a nube |
