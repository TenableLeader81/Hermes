#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <ArduinoJson.h>      // Instalar desde el Gestor de Librerías

// --- 1. CREDENCIALES DE RED ---
const char* ssid = "alex";
const char* password = "12345678";

// --- 2. API DE GEOLOCALIZACIÓN Y SERVIDOR ---
const char* apiKeyGeolocalizacion = "AIzaSyDC0F0pvy2dZfV9y2AusiASTm7SbRJLWOY";
const char* urlSOS = "https://hermes-production-9a8e.up.railway.app/api/sos.php";

// --- 3. CONFIGURACIÓN DEL BOTÓN ---
const int botonPin = 4;
bool estadoAnterior = HIGH;
unsigned long ultimoRebote = 0;
unsigned long retardoRebote = 50;

void setup() {
  Serial.begin(115200);
  pinMode(botonPin, INPUT_PULLUP);

  WiFi.begin(ssid, password);
  Serial.print("Conectando a WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\n¡Conectado a la red!");
}

void loop() {
  bool estadoActual = digitalRead(botonPin);

  if (estadoActual != estadoAnterior) {
    ultimoRebote = millis();
  }

  if ((millis() - ultimoRebote) > retardoRebote) {
    if (estadoActual == LOW) {
      Serial.println("\n¡Botón de pánico presionado!");

      float lat = 0, lon = 0;
      bool ok = obtenerUbicacion(lat, lon);

      if (ok) {
        enviarSOS(lat, lon);
      } else {
        Serial.println("No se pudo obtener ubicación. Alerta cancelada.");
      }

      delay(10000); // Pausa para no enviar múltiples alertas seguidas
    }
  }
  estadoAnterior = estadoActual;
}

// --- FUNCIÓN: OBTENER UBICACIÓN POR WI-FI ---
// Retorna true y rellena lat/lon si tiene éxito.
bool obtenerUbicacion(float &lat, float &lon) {
  Serial.println("Escaneando redes Wi-Fi...");

  int numRedes = WiFi.scanNetworks();
  Serial.print("Redes encontradas: ");
  Serial.println(numRedes);

  if (numRedes == 0) {
    Serial.println("Sin redes cercanas para triangular.");
    return false;
  }

  String jsonBody = "{\n\"wifiAccessPoints\": [\n";
  int limiteRedes = (numRedes > 10) ? 10 : numRedes;

  for (int i = 0; i < limiteRedes; i++) {
    jsonBody += "  {\n";
    jsonBody += "    \"macAddress\": \"" + WiFi.BSSIDstr(i) + "\",\n";
    jsonBody += "    \"signalStrength\": " + String(WiFi.RSSI(i)) + "\n";
    jsonBody += "  }";
    if (i < limiteRedes - 1) jsonBody += ",\n";
    else jsonBody += "\n";
  }
  jsonBody += "]\n}";

  WiFiClientSecure cliente;
  cliente.setInsecure(); // Necesario para HTTPS en ESP32

  HTTPClient http;
  String urlAPI = "https://www.googleapis.com/geolocation/v1/geolocate?key=" + String(apiKeyGeolocalizacion);

  http.begin(cliente, urlAPI);
  http.addHeader("Content-Type", "application/json");

  int httpCode = http.POST(jsonBody);
  bool exito = false;

  if (httpCode == 200) {
    String respuesta = http.getString();
    DynamicJsonDocument doc(1024);
    deserializeJson(doc, respuesta);

    lat = doc["location"]["lat"].as<float>();
    lon = doc["location"]["lng"].as<float>();

    Serial.print("Ubicación: ");
    Serial.print(lat, 6);
    Serial.print(", ");
    Serial.println(lon, 6);

    exito = true;
  } else {
    Serial.print("Error API geolocalización. HTTP: ");
    Serial.println(httpCode);
    Serial.println(http.getString());
  }

  http.end();
  WiFi.scanDelete();
  return exito;
}

// --- FUNCIÓN: ENVIAR ALERTA SOS AL SERVIDOR ---
// Llama a sos.php?lat=X&lon=Y — el servidor guarda en BD,
// crea la alerta y notifica a todos los usuarios por correo.
void enviarSOS(float lat, float lon) {
  Serial.println("Enviando alerta SOS al servidor...");

  String url = String(urlSOS) + "?lat=" + String(lat, 6) + "&lon=" + String(lon, 6);

  HTTPClient http;
  http.begin(url);

  int httpCode = http.GET();

  if (httpCode == 200) {
    String respuesta = http.getString();
    if (respuesta == "OK") {
      Serial.println("¡Alerta SOS enviada con éxito!");
    } else {
      Serial.println("Respuesta del servidor: " + respuesta);
    }
  } else {
    Serial.print("Error al contactar el servidor. HTTP: ");
    Serial.println(httpCode);
  }

  http.end();
}
