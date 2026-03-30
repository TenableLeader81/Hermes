<?php
require_once __DIR__ . "/../config/mail.php";

/**
 * Mailer — cliente SMTP ligero para Gmail (STARTTLS, puerto 587).
 * No requiere librerías externas.
 */
class Mailer
{
    /**
     * Envía un correo HTML a un destinatario.
     *
     * @param string $para      Dirección destino
     * @param string $nombre    Nombre del destinatario
     * @param string $asunto    Asunto del correo
     * @param string $cuerpoHtml Cuerpo en HTML
     * @return bool  true si fue aceptado por el servidor
     */
    public static function enviar(string $para, string $nombre, string $asunto, string $cuerpoHtml): bool
    {
        $host = MAIL_HOST;
        $port = MAIL_PORT;
        $user = MAIL_USER;
        $pass = MAIL_PASS;
        $from = MAIL_FROM;
        $fromName = MAIL_FROM_NAME;

        // 1. Conectar al servidor SMTP
        $sock = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10);
        if (!$sock) return false;

        stream_set_timeout($sock, 10);

        // 2. Leer saludo del servidor
        self::leer($sock);

        // 3. EHLO
        self::escribir($sock, "EHLO localhost");
        self::leerMultilinea($sock);

        // 4. Iniciar TLS
        self::escribir($sock, "STARTTLS");
        self::leer($sock);

        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            fclose($sock);
            return false;
        }

        // 5. EHLO de nuevo tras TLS
        self::escribir($sock, "EHLO localhost");
        self::leerMultilinea($sock);

        // 6. Autenticación LOGIN
        self::escribir($sock, "AUTH LOGIN");
        self::leer($sock);
        self::escribir($sock, base64_encode($user));
        self::leer($sock);
        self::escribir($sock, base64_encode($pass));
        $authResp = self::leer($sock);

        if (substr(trim($authResp), 0, 3) !== '235') {
            fclose($sock);
            return false; // Credenciales incorrectas
        }

        // 7. Sobre del mensaje
        self::escribir($sock, "MAIL FROM:<{$from}>");
        self::leer($sock);

        self::escribir($sock, "RCPT TO:<{$para}>");
        self::leer($sock);

        // 8. Cuerpo del mensaje
        self::escribir($sock, "DATA");
        self::leer($sock);

        $nombreEnc  = "=?UTF-8?B?" . base64_encode($nombre)   . "?=";
        $fromNameEnc = "=?UTF-8?B?" . base64_encode($fromName) . "?=";
        $asuntoEnc  = "=?UTF-8?B?" . base64_encode($asunto)   . "?=";

        $mensaje  = "From: {$fromNameEnc} <{$from}>\r\n";
        $mensaje .= "To: {$nombreEnc} <{$para}>\r\n";
        $mensaje .= "Subject: {$asuntoEnc}\r\n";
        $mensaje .= "MIME-Version: 1.0\r\n";
        $mensaje .= "Content-Type: text/html; charset=UTF-8\r\n";
        $mensaje .= "Content-Transfer-Encoding: base64\r\n";
        $mensaje .= "\r\n";
        $mensaje .= chunk_split(base64_encode($cuerpoHtml));
        $mensaje .= "\r\n.";

        self::escribir($sock, $mensaje);
        self::leer($sock);

        // 9. Cerrar sesión
        self::escribir($sock, "QUIT");
        fclose($sock);

        return true;
    }

    // ── Helpers ─────────────────────────────────────

    private static function escribir($sock, string $cmd): void
    {
        fwrite($sock, $cmd . "\r\n");
    }

    private static function leer($sock): string
    {
        return (string) fgets($sock, 515);
    }

    /** Lee respuestas multi-línea del servidor (ej. EHLO devuelve varias líneas). */
    private static function leerMultilinea($sock): void
    {
        while ($line = fgets($sock, 515)) {
            // Las líneas intermedias tienen guion en posición 3: "250-..."
            // La última tiene espacio: "250 ..."
            if (isset($line[3]) && $line[3] === ' ') break;
        }
    }

    // ── Plantillas de correo ─────────────────────────

    /**
     * Genera el HTML del correo de emergencia SOS (botón de pánico).
     */
    public static function plantillaSOS(
        float  $lat,
        float  $lon,
        string $mapsUrl,
        string $fecha
    ): string {
        return "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#111827;font-family:Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0'>
    <tr><td align='center' style='padding:32px 16px;'>
      <table width='560' cellpadding='0' cellspacing='0'
             style='background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(239,68,68,.4);'>

        <!-- Cabecera roja pulsante -->
        <tr><td style='background:#dc2626;padding:28px 32px;text-align:center;'>
          <p style='margin:0;font-size:48px;'>🆘</p>
          <h1 style='margin:10px 0 4px;color:#fff;font-size:26px;letter-spacing:1px;'>EMERGENCIA EN CAMPUS</h1>
          <p style='margin:0;color:rgba(255,255,255,.9);font-size:15px;font-weight:bold;'>
            Alguien presionó el botón de pánico y necesita ayuda
          </p>
        </td></tr>

        <!-- Cuerpo -->
        <tr><td style='padding:28px 32px;'>
          <p style='font-size:16px;color:#111827;font-weight:bold;margin:0 0 16px;'>
            ⚠️ Ayuda comunitaria requerida — Por favor acude o avisa a las autoridades del campus.
          </p>

          <!-- Coordenadas -->
          <table width='100%' cellpadding='0' cellspacing='0'
                 style='background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:16px;margin-bottom:20px;'>
            <tr>
              <td style='font-size:13px;color:#6b7280;padding-bottom:6px;'>📍 Última ubicación GPS registrada</td>
            </tr>
            <tr>
              <td style='font-size:15px;color:#111827;font-weight:600;'>
                Latitud: {$lat}<br>Longitud: {$lon}
              </td>
            </tr>
          </table>

          <!-- Botón Google Maps -->
          <table width='100%' cellpadding='0' cellspacing='0'>
            <tr><td align='center'>
              <a href='{$mapsUrl}'
                 style='display:inline-block;background:#dc2626;color:#fff;text-decoration:none;
                        padding:14px 32px;border-radius:8px;font-size:15px;font-weight:700;'>
                📍 Ver ubicación en Google Maps
              </a>
            </td></tr>
          </table>

          <p style='font-size:13px;color:#6b7280;margin:20px 0 0;'>
            📅 Alerta generada el {$fecha} · 🏫 Campus UTEQ
          </p>
        </td></tr>

        <!-- Pie -->
        <tr><td style='background:#fef2f2;padding:16px 32px;border-top:1px solid #fca5a5;'>
          <p style='margin:0;font-size:12px;color:#9ca3af;'>
            Este correo fue enviado automáticamente por el sistema HERMES.<br>
            Universidad Tecnológica de Querétaro — Seguridad Campus.
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>";
    }

    /**
     * Genera el HTML del correo de alerta.
     */
    public static function plantillaAlerta(
        string $categoria,
        string $subcategoria,
        string $descripcion,
        string $fecha
    ): string {
        $emojis = [
            'Robo'            => '🚨',
            'Accidente'       => '🚑',
            'Falla electrica' => '⚡',
        ];
        $colores = [
            'Robo'            => '#ef4444',
            'Accidente'       => '#f59e0b',
            'Falla electrica' => '#3b82f6',
        ];

        $emoji  = $emojis[$categoria]  ?? '⚠️';
        $color  = $colores[$categoria] ?? '#6b7280';
        $descHtml = $descripcion ? "<p style='margin:8px 0;color:#374151;'>{$descripcion}</p>" : '';

        return "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0'>
    <tr><td align='center' style='padding:32px 16px;'>
      <table width='560' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);'>

        <!-- Cabecera -->
        <tr><td style='background:{$color};padding:24px 32px;'>
          <p style='margin:0;font-size:28px;'>{$emoji}</p>
          <h1 style='margin:8px 0 0;color:#fff;font-size:22px;'>Nueva alerta en el campus</h1>
          <p style='margin:4px 0 0;color:rgba(255,255,255,.85);font-size:14px;'>HERMES – Seguridad UTEQ</p>
        </td></tr>

        <!-- Cuerpo -->
        <tr><td style='padding:28px 32px;'>
          <table width='100%' cellpadding='0' cellspacing='0'
                 style='background:#f9fafb;border-radius:8px;padding:16px;margin-bottom:16px;'>
            <tr>
              <td style='font-size:13px;color:#6b7280;padding-bottom:4px;'>Tipo de incidente</td>
            </tr>
            <tr>
              <td style='font-size:18px;font-weight:700;color:#111827;'>{$categoria}</td>
            </tr>
            <tr>
              <td style='font-size:14px;color:#374151;padding-top:4px;'>{$subcategoria}</td>
            </tr>
          </table>

          {$descHtml}

          <p style='font-size:13px;color:#6b7280;margin:16px 0 0;'>
            📅 Reportado el {$fecha} · 📍 Campus UTEQ
          </p>
        </td></tr>

        <!-- Pie -->
        <tr><td style='background:#f9fafb;padding:16px 32px;border-top:1px solid #e5e7eb;'>
          <p style='margin:0;font-size:12px;color:#9ca3af;'>
            Este correo fue enviado automáticamente por el sistema HERMES.<br>
            Universidad Tecnológica de Querétaro — Seguridad Campus.
          </p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>";
    }
}
