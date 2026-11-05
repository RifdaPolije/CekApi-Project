/*
 * CEKAPI.id - Firmware ESP32 DevKit V1
 * --------------------------------------------------------------
 * Sensor  : Inframerah/flame (api), MQ-2 (asap & gas), DHT11 (suhu & kelembaban)
 * Aktuator: LED, Buzzer, Kipas (via relay)
 *
 * Library (Library Manager Arduino IDE):
 *   - "DHT sensor library" oleh Adafruit  (+ "Adafruit Unified Sensor")
 * Board  : "ESP32 Dev Module"  (paket esp32 by Espressif, versi 2.x atau 3.x)
 *
 * Alur:
 *   - loop() (core 1)  : baca sensor tiap 1 dtk, tentukan level bahaya, atur aktuator.
 *                        Aktuator TETAP bekerja walau WiFi / server mati.
 *   - netTask (core 0) : kirim data ke server tiap SEND_INTERVAL_MS, lalu terima
 *                        status LED/Buzzer/Kipas yang diatur dari dashboard.
 *
 * Wiring (lihat README.md untuk detail):
 *   DHT11 DATA -> GPIO4        MQ-2 AO -> GPIO34 (WAJIB lewat pembagi tegangan)
 *   Flame DO   -> GPIO27       LED     -> GPIO18 (+ resistor 220 ohm)
 *   Buzzer     -> GPIO19       Relay IN-> GPIO23
 */
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <DHT.h>
#include <math.h>

// ===================== KONFIGURASI (WAJIB DIUBAH) =====================
const char* WIFI_SSID  = "NAMA_WIFI";
const char* WIFI_PASS  = "PASSWORD_WIFI";
// XAMPP lokal : http://<IP-komputer>/cekapi.id/api/receive.php   (bukan localhost!)
// Hosting     : https://domainanda.com/api/receive.php
const char* SERVER_URL = "http://192.168.1.10/cekapi.id/api/receive.php";
const char* API_KEY    = "GANTI_DENGAN_KUNCI_RAHASIA_PANJANG";   // samakan dgn config/app.php
const char* DEVICE_ID  = "ESP32-01";

const unsigned long SEND_INTERVAL_MS = 5000;

// ===================== PIN & POLARITAS =====================
const uint8_t PIN_DHT    = 4;
const uint8_t PIN_MQ2    = 34;   // ADC1 (aman dipakai bersama WiFi)
const uint8_t PIN_FLAME  = 27;
const uint8_t PIN_LED    = 18;
const uint8_t PIN_BUZZER = 19;
const uint8_t PIN_RELAY  = 23;
#define DHTTYPE DHT11

const bool FLAME_ACTIVE_LOW  = true;   // kebanyakan modul flame: DO = LOW saat api
const bool RELAY_ACTIVE_LOW  = true;   // kebanyakan modul relay: IN = LOW -> aktif
const bool BUZZER_ACTIVE_LOW = false;  // buzzer aktif (active buzzer) umumnya HIGH = bunyi

// ===================== MQ-2 =====================
const float MQ2_VCC        = 5.0;      // MQ-2 diberi daya 5V (pin VIN)
const float MQ2_RL_KOHM    = 5.0;      // resistor beban di modul (cek modul Anda: 5k atau 10k)
const float DIVIDER_RATIO  = 1.667;    // pembagi AO->ADC: R1=10k (atas), R2=15k (bawah) => (10+15)/15
const float MQ2_CLEAN_AIR  = 9.83;     // Rs/R0 di udara bersih (datasheet MQ-2)
const unsigned long MQ2_WARMUP_MS = 30000;  // pemanasan awal (idealnya lebih lama)

// ===================== AMBANG BATAS (samakan dgn config/app.php) =====================
const float TH_SUHU_WASPADA = 35.0, TH_SUHU_BAHAYA = 45.0;   // derajat C
const int   TH_ASAP_WASPADA = 1000, TH_ASAP_BAHAYA = 2000;   // ppm
const int   TH_GAS_WASPADA  = 20,   TH_GAS_BAHAYA  = 50;     // persen

// ===================== STATE =====================
DHT dht(PIN_DHT, DHTTYPE);
SemaphoreHandle_t dataMutex;

float suhu = NAN, kelembaban = NAN;
int   asapPpm = 0, gasPct = 0;
bool  api = false;
int   lvlSuhu = 0, lvlAsap = 0, lvlGas = 0, lvlApi = 0, lvlAll = 0;   // 0 aman, 1 waspada, 2 bahaya

volatile bool webBuzzer = false, webLed = false, webKipas = false;   // perintah dari dashboard
float mq2R0 = 10.0;
bool  mq2Ready = false;
int   dhtFail = 0;
unsigned long lastSense = 0, lastDht = 0, lastWifiTry = 0;

// ===================== UTIL =====================
int levelOf(float v, float waspada, float bahaya) {
  if (isnan(v)) return 0;
  if (v >= bahaya)  return 2;
  if (v >= waspada) return 1;
  return 0;
}

void outPin(uint8_t pin, bool on, bool activeLow) {
  digitalWrite(pin, (on ^ activeLow) ? HIGH : LOW);
}

bool blinkEvery(unsigned long ms) { return (millis() / ms) % 2 == 0; }

// ===================== MQ-2 =====================
float mq2Volt() {                       // tegangan di pin AO modul (sebelum pembagi)
  uint32_t sum = 0;
  for (int i = 0; i < 16; i++) { sum += analogReadMilliVolts(PIN_MQ2); delay(2); }
  return (sum / 16.0f / 1000.0f) * DIVIDER_RATIO;
}

float mq2Rs(float vout) {
  if (vout < 0.05f) vout = 0.05f;
  if (vout > MQ2_VCC - 0.05f) vout = MQ2_VCC - 0.05f;
  return (MQ2_VCC - vout) / vout * MQ2_RL_KOHM;
}

// Dipanggil SEKALI setelah masa pemanasan selesai (tidak memblokir sensor lain)
void mq2Calibrate() {
  float rs = 0;
  for (int i = 0; i < 50; i++) { rs += mq2Rs(mq2Volt()); delay(20); }
  mq2R0 = (rs / 50.0f) / MQ2_CLEAN_AIR;
  mq2Ready = true;
  Serial.printf("[MQ2] Kalibrasi selesai, R0 = %.2f kOhm (pastikan saat itu udara bersih)\n", mq2R0);
}

// ===================== BACA SENSOR =====================
void readSensors() {
  // --- MQ-2: estimasi kasar, kalibrasi ulang sesuai sensor Anda ---
  int asap = 0, gas = 0;
  if (mq2Ready) {
    float ratio = mq2Rs(mq2Volt()) / mq2R0;
    if (ratio < 0.05f) ratio = 0.05f;
    float smoke = 3616.1f * powf(ratio, -2.675f);          // ppm asap (perkiraan)
    float lpg   = 574.25f * powf(ratio, -2.222f);          // ppm LPG  (perkiraan)
    asap = (int) constrain(smoke, 0.0f, 99999.0f);
    gas  = (int) constrain(lpg / 10000.0f * 100.0f, 0.0f, 100.0f);   // persen skala 0-10.000 ppm
  }

  // --- Sensor api: pilih nilai mayoritas dari 5 pembacaan ---
  int hit = 0;
  for (int i = 0; i < 5; i++) { if (digitalRead(PIN_FLAME) == (FLAME_ACTIVE_LOW ? LOW : HIGH)) hit++; delay(2); }
  bool apiNow = hit >= 3;

  // --- DHT11: maksimal tiap 2 detik ---
  float t = suhu, h = kelembaban;
  if (millis() - lastDht >= 2000) {
    lastDht = millis();
    float nt = dht.readTemperature(), nh = dht.readHumidity();
    if (!isnan(nt) && !isnan(nh)) { t = nt; h = nh; dhtFail = 0; }
    else if (++dhtFail >= 5) { t = NAN; h = NAN; }
  }

  // --- simpan + nilai level ---
  xSemaphoreTake(dataMutex, portMAX_DELAY);
  suhu = t; kelembaban = h; asapPpm = asap; gasPct = gas; api = apiNow;
  xSemaphoreGive(dataMutex);

  lvlSuhu = levelOf(t, TH_SUHU_WASPADA, TH_SUHU_BAHAYA);
  lvlAsap = levelOf(asap, TH_ASAP_WASPADA, TH_ASAP_BAHAYA);
  lvlGas  = levelOf(gas, TH_GAS_WASPADA, TH_GAS_BAHAYA);
  lvlApi  = apiNow ? 2 : 0;
  lvlAll  = max(max(lvlSuhu, lvlAsap), max(lvlGas, lvlApi));

  Serial.printf("[SENSOR] suhu=%.1fC hum=%.0f%% asap=%dppm gas=%d%% api=%d level=%d\n",
                isnan(t) ? -1.0f : t, isnan(h) ? -1.0f : h, asap, gas, apiNow, lvlAll);
}

// ===================== AKTUATOR =====================
void applyActuators() {
  bool led, buzzer;
  if (lvlAll == 2) {            // BAHAYA: LED kedip cepat + buzzer putus-putus
    led = blinkEvery(250);
    buzzer = blinkEvery(300);
  } else if (lvlAll == 1) {     // WASPADA: LED kedip pelan, buzzer ikut pengaturan dashboard
    led = blinkEvery(800);
    buzzer = webBuzzer;
  } else {                      // AMAN: ikut pengaturan dashboard
    led = webLed;
    buzzer = webBuzzer;
  }
  // Kipas menyala otomatis untuk membuang asap/gas/panas, KECUALI ada api
  // (kipas bisa membesarkan api). Selain itu ikut pengaturan dashboard.
  bool kipasAuto = (!api) && (lvlAsap >= 1 || lvlGas >= 1 || lvlSuhu >= 1);
  bool kipas = webKipas || kipasAuto;

  outPin(PIN_LED, led, false);
  outPin(PIN_BUZZER, buzzer, BUZZER_ACTIVE_LOW);
  outPin(PIN_RELAY, kipas, RELAY_ACTIVE_LOW);
}

// ===================== KIRIM DATA (task core 0) =====================
int jsonFlag(const String& s, const char* key) {
  String k = String("\"") + key + "\":";
  int i = s.indexOf(k);
  if (i < 0) return -1;
  return s.charAt(i + k.length()) == '1' ? 1 : 0;
}

void sendData() {
  if (WiFi.status() != WL_CONNECTED) return;

  float t, h; int a, g; bool f;
  xSemaphoreTake(dataMutex, portMAX_DELAY);
  t = suhu; h = kelembaban; a = asapPpm; g = gasPct; f = api;
  xSemaphoreGive(dataMutex);

  String body = "{\"device_id\":\"" + String(DEVICE_ID) + "\"";
  body += ",\"suhu\":" + (isnan(t) ? String("null") : String(t, 1));
  body += ",\"kelembaban\":" + (isnan(h) ? String("null") : String(h, 1));
  body += ",\"asap\":" + String(a);
  body += ",\"gas\":" + String(g);
  body += ",\"api\":" + String(f ? 1 : 0) + "}";

  HTTPClient http;
  WiFiClient plain;
  WiFiClientSecure secure;
  if (String(SERVER_URL).startsWith("https")) {
    secure.setInsecure();               // TIDAK memverifikasi sertifikat. Produksi: pakai setCACert()
    http.begin(secure, SERVER_URL);
  } else {
    http.begin(plain, SERVER_URL);
  }
  http.setTimeout(5000);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", API_KEY);

  int code = http.POST(body);
  if (code == 200) {
    String res = http.getString();
    int b = jsonFlag(res, "buzzer"), l = jsonFlag(res, "led"), k = jsonFlag(res, "kipas");
    if (b >= 0) webBuzzer = b;
    if (l >= 0) webLed = l;
    if (k >= 0) webKipas = k;
    Serial.printf("[HTTP] OK  buzzer=%d led=%d kipas=%d\n", webBuzzer, webLed, webKipas);
  } else {
    Serial.printf("[HTTP] Gagal, kode=%d (%s)\n", code, code < 0 ? HTTPClient::errorToString(code).c_str() : "");
  }
  http.end();
}

void netTask(void*) {
  for (;;) {
    sendData();
    vTaskDelay(SEND_INTERVAL_MS / portTICK_PERIOD_MS);
  }
}

// ===================== WIFI =====================
void wifiConnect() {
  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print(F("[WiFi] Menghubungkan"));
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 15000) { delay(300); Serial.print('.'); }
  if (WiFi.status() == WL_CONNECTED) Serial.printf("\n[WiFi] OK, IP %s\n", WiFi.localIP().toString().c_str());
  else Serial.println(F("\n[WiFi] Belum terhubung, sistem tetap berjalan lokal."));
}

void wifiKeepAlive() {
  if (WiFi.status() == WL_CONNECTED || millis() - lastWifiTry < 10000) return;
  lastWifiTry = millis();
  WiFi.disconnect();
  WiFi.begin(WIFI_SSID, WIFI_PASS);
}

// ===================== SETUP / LOOP =====================
void setup() {
  Serial.begin(115200);
  delay(300);

  pinMode(PIN_LED, OUTPUT);
  pinMode(PIN_BUZZER, OUTPUT);
  pinMode(PIN_RELAY, OUTPUT);
  pinMode(PIN_FLAME, INPUT);
  outPin(PIN_LED, false, false);
  outPin(PIN_BUZZER, false, BUZZER_ACTIVE_LOW);
  outPin(PIN_RELAY, false, RELAY_ACTIVE_LOW);

  analogReadResolution(12);
  analogSetPinAttenuation(PIN_MQ2, ADC_11db);
  dht.begin();
  dataMutex = xSemaphoreCreateMutex();

  // Uji aktuator singkat (relay & buzzer akan berbunyi sebentar)
  outPin(PIN_LED, true, false); outPin(PIN_BUZZER, true, BUZZER_ACTIVE_LOW); delay(150);
  outPin(PIN_LED, false, false); outPin(PIN_BUZZER, false, BUZZER_ACTIVE_LOW);

  wifiConnect();
  xTaskCreatePinnedToCore(netTask, "netTask", 12288, NULL, 1, NULL, 0);

  Serial.println(F("[MQ2] Pemanasan 30 dtk, pastikan udara bersih. Sensor api & suhu tetap aktif."));
}

void loop() {
  unsigned long now = millis();
  wifiKeepAlive();

  // Kalibrasi MQ-2 otomatis setelah pemanasan (sensor lain sudah berjalan sejak awal)
  if (!mq2Ready && now >= MQ2_WARMUP_MS) mq2Calibrate();

  if (now - lastSense >= 1000) {
    lastSense = now;
    readSensors();
  }
  applyActuators();   // dipanggil tiap putaran agar kedip LED/buzzer mulus
  delay(10);
}
