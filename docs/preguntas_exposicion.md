# Preguntas y Respuestas — Exposición HERMES

---

## 1. ¿Qué arquitectura de software están utilizando?

**Arquitectura MVC simplificada (sin framework)** sobre una stack LAMP:

- **Frontend:** HTML/CSS/JavaScript con Leaflet.js para el mapa
- **Backend:** PHP vanilla con patrón MVC manual — las vistas están en `public/`, los controladores en `controllers/`, y los modelos son consultas PDO directas a MySQL
- **Base de datos:** MySQL (MariaDB) con 3 tablas: usuarios, reportes y alertas
- **Desplegado** en Railway con Docker y Apache

> En una línea: *"Usamos arquitectura MVC en PHP puro sobre LAMP, desplegado en la nube con Docker."*

---

## 2. ¿Qué es MVC?

**MVC = Modelo, Vista, Controlador** — es una forma de organizar el código en 3 partes:

- **Modelo** — los datos (la base de datos, las consultas SQL)
- **Vista** — lo que ve el usuario (las páginas PHP en `public/`)
- **Controlador** — la lógica que conecta ambos (los archivos en `controllers/`)

> En una línea: *"Es una forma de separar los datos, la interfaz y la lógica para que el código esté más organizado."*

---

## 3. ¿Cómo funciona el desplegado?

1. **El código está en GitHub** — ahí vive todo el proyecto
2. **Railway se conecta a GitHub** — cada vez que haces `git push`, Railway detecta el cambio y redespliega automáticamente
3. **Docker construye el servidor** — Railway lee el `Dockerfile` y crea un contenedor con Ubuntu + Apache + PHP instalados
4. **Apache sirve la app** — el contenedor arranca Apache que sirve los archivos PHP en el puerto que Railway asigne
5. **La base de datos está separada** — Railway también tiene un servicio MySQL aparte, y la app se conecta a él mediante variables de entorno

> En una línea: *"Subimos el código a GitHub, Railway lo detecta, construye un contenedor Docker con Apache y PHP, y lo pone disponible en internet automáticamente."*

---

## 4. ¿Por qué esos colores en las notificaciones?

Los colores siguen un estándar universal de semáforo de emergencias:

- **Rojo** — Robo y SOS, porque representa peligro inmediato
- **Amarillo/Naranja** — Accidente, porque es una situación de precaución
- **Azul** — Falla eléctrica, porque es un problema de servicio, no una emergencia de seguridad

> En una línea: *"Usamos un código de colores intuitivo donde el rojo es peligro, amarillo es precaución y azul es falla de servicio, para que el usuario identifique la gravedad de un vistazo."*

---

## 5. ¿Cómo funciona el agente de IA? ¿Gasta dinero?

**¿Cómo funciona?**

Cuando escribes un mensaje en el chat, la app:
1. Consulta los últimos 30 reportes de la base de datos
2. Se los manda a Gemini como contexto junto con tu pregunta
3. Gemini responde tomando en cuenta esos reportes

`chat.php` actúa como puente — saca los reportes de la BD, se los da a Gemini como contexto, y Gemini responde con esa información.

**¿Gasta dinero?**

No. Usamos **Google AI Studio** con capa gratuita:
- **Gemini 2.5 Flash** — gratuito hasta cierto límite de peticiones por día
- Para el uso de una escuela como UTEQ no se llegaría a ese límite

---

## 6. ¿Cómo podemos saber si las alertas son falsas o verdaderas?

**A futuro se puede implementar:**

- **Verificación por la comunidad** — otros usuarios confirman o desmienten la alerta, similar a Waze
- **Validación por administradores** — el admin puede marcar una alerta como verificada o falsa
- **Cruce con el IoT** — una alerta del ESP32 es más difícil de falsificar que una del celular
- **Historial del usuario** — usuarios con alertas falsas previas quedan en revisión automática

> En una línea: *"Combinando verificación comunitaria con validación de administradores, igual a como funciona Waze con sus reportes de tráfico."*

---

## 7. ¿Qué pasa si meten el ESP32 a la mochila y manda alertas falsas constantemente?

**Soluciones:**

- **Hardware:** Cambiar a un botón que requiera mantenerlo presionado 3 segundos para activarse
- **Cooldown de software:** Bloquear el dispositivo X minutos después de cada SOS (actualmente solo 10 segundos)
- **Confirmación:** El ESP32 enciende un LED y espera 5 segundos; si presionas de nuevo cancela, si no cancela manda la alerta
- **Control admin:** El administrador puede silenciar un dispositivo específico desde el panel

> En una línea: *"Implementaríamos un tiempo de espera y un botón de cancelación de 5 segundos, igual a como funcionan las alarmas de seguridad en casas."*

---

## 8. ¿Cuánto costaría mantener la aplicación en producción?

| Servicio | Costo |
|----------|-------|
| Railway (app + DB) | ~$5 USD/mes |
| Brevo (correos) | Gratis hasta 300/día |
| Gemini API | Gratis hasta el límite diario |
| Dominio propio (opcional) | ~$10 USD/año |

**Total: ~$5 USD al mes.**

Si creciera a nivel nacional con miles de usuarios: ~$20-50 USD/mes máximo.

---

## 9. ¿Qué métricas usarían para medir si la app es exitosa?

**De uso:**
- Número de reportes enviados por semana
- Tiempo promedio entre que se reporta y un admin responde
- Cuántos usuarios activos hay por mes

**De seguridad:**
- Porcentaje de alertas verificadas vs falsas
- Tiempo de respuesta ante un SOS

**De satisfacción:**
- Cuántos usuarios usan el chat con el asistente
- Tasa de retención — ¿los usuarios siguen usando la app después del primer mes?

> En una línea: *"El éxito se mediría por qué tan rápido responde la comunidad ante una alerta real."*

---

## 10. ¿Cuánto venderían el software?

**Opción 1 — Licencia por universidad:**
- $15,000 - $30,000 MXN de instalación + $1,500 MXN/mes de mantenimiento

**Opción 2 — SaaS (recomendada):**
- $2 MXN por alumno/mes
- Universidad con 3,000 alumnos = $6,000 MXN/mes
- Costo real ~$5 USD → ganancia casi pura

**Mercado potencial:**
- Más de 3,000 universidades en México
- Con solo 10 clientes = $60,000 MXN/mes

> En una línea: *"Con modelo SaaS a $2 por alumno al mes, con solo 10 universidades cliente estarías generando $60,000 pesos mensuales con costos operativos mínimos."*

---

## Preguntas adicionales que podrían hacer

- ¿Qué pasa si alguien hackea la app?
- ¿Los datos de los usuarios están protegidos?
- ¿Por qué usan 2FA?
- ¿Qué pasa si se cae la base de datos?
- ¿Por qué MySQL y no otra base de datos?
- ¿Qué pasa si el ESP32 no tiene WiFi?
- ¿Por qué usaron ESP32 y no otro dispositivo?
- ¿Qué diferencia tiene HERMES con simplemente llamar al 911?
- ¿Cómo escala la app si la usan todas las universidades del país?
- ¿Qué harían diferente si lo volvieran a hacer desde cero?
