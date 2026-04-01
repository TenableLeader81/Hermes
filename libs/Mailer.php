<?php
require_once __DIR__ . "/../config/mail.php";

/**
 * Mailer — usa Brevo HTTP API (Railway bloquea SMTP)
 */
class Mailer
{
    public static function enviar(string $para, string $nombre, string $asunto, string $cuerpoHtml): bool
    {
        $payload = json_encode([
            'sender'     => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM],
            'to'         => [['email' => $para, 'name' => $nombre]],
            'subject'    => $asunto,
            'htmlContent'=> $cuerpoHtml,
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'api-key: ' . BREVO_API_KEY,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }

    // ── Plantillas de correo ─────────────────────────

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
        <tr><td style='background:#dc2626;padding:28px 32px;text-align:center;'>
          <p style='margin:0;font-size:48px;'>🆘</p>
          <h1 style='margin:10px 0 4px;color:#fff;font-size:26px;letter-spacing:1px;'>EMERGENCIA EN CAMPUS</h1>
          <p style='margin:0;color:rgba(255,255,255,.9);font-size:15px;font-weight:bold;'>
            Alguien presionó el botón de pánico y necesita ayuda
          </p>
        </td></tr>
        <tr><td style='padding:28px 32px;'>
          <p style='font-size:16px;color:#111827;font-weight:bold;margin:0 0 16px;'>
            ⚠️ Ayuda comunitaria requerida — Por favor acude o avisa a las autoridades del campus.
          </p>
          <table width='100%' cellpadding='0' cellspacing='0'
                 style='background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:16px;margin-bottom:20px;'>
            <tr><td style='font-size:13px;color:#6b7280;padding-bottom:6px;'>📍 Última ubicación GPS registrada</td></tr>
            <tr><td style='font-size:15px;color:#111827;font-weight:600;'>
              Latitud: {$lat}<br>Longitud: {$lon}
            </td></tr>
          </table>
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

        $emoji   = $emojis[$categoria]  ?? '⚠️';
        $color   = $colores[$categoria] ?? '#6b7280';
        $descHtml = $descripcion ? "<p style='margin:8px 0;color:#374151;'>{$descripcion}</p>" : '';

        return "
<!DOCTYPE html>
<html lang='es'>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f3f4f6;font-family:Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0'>
    <tr><td align='center' style='padding:32px 16px;'>
      <table width='560' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);'>
        <tr><td style='background:{$color};padding:24px 32px;'>
          <p style='margin:0;font-size:28px;'>{$emoji}</p>
          <h1 style='margin:8px 0 0;color:#fff;font-size:22px;'>Nueva alerta en el campus</h1>
          <p style='margin:4px 0 0;color:rgba(255,255,255,.85);font-size:14px;'>HERMES – Seguridad UTEQ</p>
        </td></tr>
        <tr><td style='padding:28px 32px;'>
          <table width='100%' cellpadding='0' cellspacing='0'
                 style='background:#f9fafb;border-radius:8px;padding:16px;margin-bottom:16px;'>
            <tr><td style='font-size:13px;color:#6b7280;padding-bottom:4px;'>Tipo de incidente</td></tr>
            <tr><td style='font-size:18px;font-weight:700;color:#111827;'>{$categoria}</td></tr>
            <tr><td style='font-size:14px;color:#374151;padding-top:4px;'>{$subcategoria}</td></tr>
          </table>
          {$descHtml}
          <p style='font-size:13px;color:#6b7280;margin:16px 0 0;'>
            📅 Reportado el {$fecha} · 📍 Campus UTEQ
          </p>
        </td></tr>
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
