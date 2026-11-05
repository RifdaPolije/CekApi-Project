# CEKAPI.id — Sistem Pendeteksi Kebakaran & Kebocoran Gas Dapur

ESP32 + sensor (Inframerah, MQ-2, DHT11) → API PHP → MySQL → Dashboard web.
Aktuator: LED, Buzzer, Kipas (relay). Admin tunggal.

## 1. Pasang di komputer (XAMPP)
1. Salin folder `cekapi.id/` ke `C:\xampp\htdocs\` (Windows) atau `/opt/lampp/htdocs/`.
2. Jalankan **Apache** dan **MySQL** di XAMPP.
3. Buka phpMyAdmin → **Import** → pilih `database/cekapi.sql`.
4. Edit `config/database.php` bila user/password MySQL Anda berbeda.
5. Edit `config/app.php`:
   - `API_KEY` → ganti dengan teks acak panjang (samakan dengan firmware ESP32).
   - `BASE_URL` → `/cekapi.id` bila ada di `htdocs/cekapi.id`, `''` bila di root domain.
6. Buka `http://localhost/cekapi.id/` → **Mulai Pantau** → login:
   - Username: `admin@cekapi.id`  Password: `admin123`
7. **Ganti akun admin** lewat terminal:
   `php tools/set_admin.php emailanda@gmail.com PasswordBaru123 "Nama Anda"`

Kebutuhan: PHP 7.4+ (disarankan 8.x) dengan ekstensi `pdo_mysql`. Dashboard memuat Chart.js dan
Google Fonts dari internet.

## 2. Uji API tanpa ESP32
```bash
curl -X POST http://localhost/cekapi.id/api/receive.php \
  -H "Content-Type: application/json" -H "X-API-KEY: GANTI_DENGAN_KUNCI_RAHASIA_PANJANG" \
  -d '{"device_id":"ESP32-01","suhu":31.5,"kelembaban":60,"asap":300,"gas":5,"api":0}'
```
Balasan: `{"ok":true,"status":"aman","control":{"buzzer":0,"led":1,"kipas":0}}`.
Coba kirim `"asap":2500` atau `"api":1` untuk melihat status BAHAYA, log insiden, dan kartu berkedip.

## 3. Firmware ESP32
1. Arduino IDE → Boards Manager → pasang **esp32 by Espressif**. Board: *ESP32 Dev Module*.
2. Library Manager → pasang **DHT sensor library** (Adafruit) dan **Adafruit Unified Sensor**.
3. Buka `firmware/cekapi_esp32/cekapi_esp32.ino`, ubah `WIFI_SSID`, `WIFI_PASS`, `SERVER_URL`, `API_KEY`.
   `SERVER_URL` harus memakai **IP komputer** (cek `ipconfig`), bukan `localhost`; ESP32 dan komputer
   harus satu jaringan WiFi. Izinkan Apache di firewall Windows (port 80).
4. Upload, buka Serial Monitor 115200 baud.

### Wiring
| Komponen | Pin ESP32 | Catatan |
|---|---|---|
| DHT11 DATA | GPIO 4 | VCC 3V3, GND |
| MQ-2 AO | GPIO 34 | **Wajib pembagi tegangan**: AO → R1 10 kΩ → GPIO34 → R2 15 kΩ → GND. MQ-2 diberi 5V |
| Flame (inframerah) DO | GPIO 27 | VCC 3V3, GND. Atur potensiometer modul untuk sensitivitas |
| LED | GPIO 18 | Seri resistor 220 Ω ke GND |
| Buzzer (aktif) | GPIO 19 | Bila buzzer aktif-LOW, ubah `BUZZER_ACTIVE_LOW` |
| Relay IN | GPIO 23 | Kipas dihubungkan ke terminal COM/NO relay dengan catu daya kipas sendiri |

### Perilaku aktuator
| Kondisi | LED | Buzzer | Kipas |
|---|---|---|---|
| Aman | ikut dashboard | ikut dashboard | ikut dashboard |
| Waspada | kedip pelan | ikut dashboard | ON bila asap/gas/suhu naik dan tidak ada api |
| Bahaya | kedip cepat | berbunyi putus-putus | sama seperti waspada (OFF bila ada api) |

Alarm bahaya dijalankan **langsung oleh ESP32**, jadi tetap bekerja walau WiFi atau server mati.

## 4. Status & ambang batas
| Sensor | Waspada | Bahaya |
|---|---|---|
| Suhu | ≥ 35 °C | ≥ 45 °C |
| Asap (ppm, estimasi) | ≥ 1000 | ≥ 2000 |
| Gas (% skala 0–10.000 ppm LPG) | ≥ 20 | ≥ 50 |
| Api | — | terdeteksi |

Ubah di `config/app.php` **dan** di bagian atas `.ino` (keduanya harus sama).

## 5. Catatan penting
- **Nilai MQ-2 adalah estimasi kasar.** Kalibrasi (R0) dilakukan otomatis 30 detik setelah ESP32
  menyala; lakukan di udara bersih. Untuk akurasi, bandingkan dengan alat ukur dan sesuaikan
  konstanta kurva di `readSensors()`.
- **Keselamatan:** relay mekanis bisa memercikkan api kecil. Jika dipakai di dekat LPG, letakkan
  relay/kipas di luar area kebocoran atau gunakan relay solid-state dan kipas anti-percik.
  Proyek ini alat bantu, bukan pengganti detektor gas/asap bersertifikat.
- **HTTPS:** bila di hosting dengan HTTPS, isi `SERVER_URL` dengan `https://...`. Firmware memakai
  `setInsecure()` (tanpa verifikasi sertifikat); untuk produksi gunakan `setCACert()`.
- **Riwayat** dicatat saat status satu sensor *berubah* (aman ↔ waspada ↔ bahaya), bukan setiap
  pembacaan, agar tabel tidak membengkak. `sensor_logs` menyimpan semua pembacaan dan dibersihkan
  otomatis setelah 30 hari (`SENSOR_LOG_RETENTION_DAYS`).
- **Download PDF** memakai dialog cetak browser (pilih *Save as PDF*), tanpa library tambahan.
- **OLED** ada di desain halaman Alat IoT tetapi tidak ada di spesifikasi hardware, jadi tidak
  dimasukkan. Menambahkannya: tambah baris `('oled','OLED',0)` di `device_controls`, tambah item
  di `$devices` pada `pages/alat-iot.php`, dan tangani di firmware.
- Tautan **Sign up** pada desain login dihapus karena admin hanya satu.
- Foto hero/tentang/galeri/tim masih placeholder: taruh `hero-kitchen.jpg` dan `about-kitchen.jpg`
  di `assets/img/`. Maskot dan logo adalah SVG sementara (`assets/img/maskot.svg`, `logo.svg`).

## 6. Struktur
```
index.php  login.php  logout.php
api/        receive.php latest.php chart.php incidents.php control.php history_crud.php
config/     app.php database.php
includes/   auth.php csrf.php functions.php icons.php
pages/      dashboard.php alat-iot.php history.php history-export.php  partials/
assets/     css/ js/ img/
database/   cekapi.sql
firmware/   cekapi_esp32/cekapi_esp32.ino
tools/      set_admin.php
```
