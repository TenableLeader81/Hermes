#include <WiFi.h>
#include <HTTPClient.h>
#include <TinyGPSPlus.h>
#include <HardwareSerial.h>

TinyGPSPlus gps;
HardwareSerial gpsSerial(1);

const int boton = 4;

const char* ssid = "UTEQ-Alumnos";
const char* pass = "";

String servidor = "http://10.13.53.70/HERMES/public/api/sos.php";

void setup() {
  Serial.begin(115200);

  pinMode(boton, INPUT_PULLUP);

  // GPS en pines 16 (RX) y 17 (TX)
  gpsSerial.begin(9600, SERIAL_8N1, 16, 17);

  WiFi.begin(ssid, pass);
  Serial.print("Conectando WiFi");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nWiFi conectado");
}

void loop() {

  while (gpsSerial.available() > 0) {
    gps.encode(gpsSerial.read());
  }

  if (digitalRead(boton) == LOW) {

    if (gps.location.isValid()) {

      float lat = gps.location.lat();
      float lon = gps.location.lng();

      Serial.println("Ubicación GPS obtenida:");
      Serial.println(lat, 6);
      Serial.println(lon, 6);

      HTTPClient http;

      String url = servidor + "?lat=" + String(lat,6) + "&lon=" + String(lon,6);

      http.begin(url);
      http.setTimeout(10000);
      int code = http.GET();

      Serial.print("Código HTTP: ");
      Serial.println(code);

      String respuesta = http.getString();
      Serial.println(respuesta);

      http.end();

      delay(5000);
    }
    else {
      Serial.println("Esperando señal GPS...");
    }
   Serial.print("IP ESP32: ");
    Serial.println(WiFi.localIP());
  }
}
