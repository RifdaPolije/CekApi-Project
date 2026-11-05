
<?php

/**
 * Konfigurasi aplikasi CEKAPI.id
 */

date_default_timezone_set('Asia/Jakarta');

// URL dasar project
define('BASE_URL', '/cekapi.id');

// API Key ESP32
// Samakan dengan API_KEY pada firmware ESP32
define('API_KEY', 'CEKAPI_ESP32_2026_Rahasia_9xK7pQ2m');

// Zona waktu MySQL (WIB)
define('DB_TZ_OFFSET', '+07:00');

// Ambang batas suhu (Celsius)
const TH_SUHU_WASPADA = 35.0;
const TH_SUHU_BAHAYA  = 45.0;

// Ambang batas asap (ppm estimasi MQ-2)
const TH_ASAP_WASPADA = 1000;
const TH_ASAP_BAHAYA  = 2000;

// Ambang batas gas (%)
const TH_GAS_WASPADA = 20;
const TH_GAS_BAHAYA = 50;

// Perangkat dianggap offline jika tidak mengirim
// data selama 20 detik
const DEVICE_OFFLINE_SECONDS = 20;

// Retensi data sensor (hari)
const SENSOR_LOG_RETENTION_DAYS = 30;
