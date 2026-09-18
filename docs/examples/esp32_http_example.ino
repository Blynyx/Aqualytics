#include <WiFi.h>
#include <HTTPClient.h>

const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";

// IP LAN del PC que ejecuta Laravel, no 127.0.0.1.
const char* API_URL = "http://192.168.1.100:8000/api/readings";
const char* DEVICE_UID = "ESP32-001";
const char* DEVICE_TOKEN = "YOUR_DEVICE_TOKEN";

const uint32_t WIFI_TIMEOUT_MS = 15000;
const uint32_t HTTP_TIMEOUT_MS = 8000;

void connectWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  const uint32_t startedAt = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - startedAt) < WIFI_TIMEOUT_MS) {
    delay(250);
  }
}

bool sendReading(float temperature, float ph, float turbidity, float waterLevel) {
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Wi-Fi no disponible");
    return false;
  }

  HTTPClient http;
  http.setTimeout(HTTP_TIMEOUT_MS);

  if (!http.begin(API_URL)) {
    Serial.println("No se pudo iniciar HTTP");
    return false;
  }

  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  http.addHeader("X-Device-Token", DEVICE_TOKEN);

  char body[192];
  snprintf(
    body,
    sizeof(body),
    "{\"device_uid\":\"%s\",\"temperature\":%.2f,\"ph\":%.2f,\"turbidity\":%.2f,\"water_level\":%.2f}",
    DEVICE_UID,
    temperature,
    ph,
    turbidity,
    waterLevel
  );

  const int status = http.POST(body);
  Serial.print("HTTP status: ");
  Serial.println(status);
  http.end();

  return status == 201;
}

void setup() {
  Serial.begin(115200);
  connectWiFi();
}

void loop() {
  // Sustituir por lecturas reales de sensores en una tarea posterior.
  sendReading(25.6, 7.2, 34.5, 82.0);

  delay(30000);
}
