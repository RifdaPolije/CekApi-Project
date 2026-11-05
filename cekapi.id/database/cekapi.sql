-- =====================================================
--  CEKAPI.id - Skema Database (MySQL / MariaDB)
--  Import: phpMyAdmin > Import, atau  mysql -u root -p < database/cekapi.sql
-- =====================================================
CREATE DATABASE IF NOT EXISTS cekapi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cekapi;

-- 1) Admin tunggal ------------------------------------
CREATE TABLE IF NOT EXISTS admin (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama          VARCHAR(100) NOT NULL,
  email         VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB;

-- 2) Log sensor (satu baris = satu pembacaan ESP32) ----
CREATE TABLE IF NOT EXISTS sensor_logs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  device_id   VARCHAR(50)  NOT NULL DEFAULT 'ESP32-01',
  suhu        DECIMAL(5,2) NULL COMMENT 'DHT11, derajat Celsius',
  kelembaban  DECIMAL(5,2) NULL COMMENT 'DHT11, persen RH',
  asap        INT          NULL COMMENT 'MQ-2, estimasi ppm',
  gas         INT          NULL COMMENT 'MQ-2, level gas dalam persen',
  api         TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Sensor inframerah: 1 = api terdeteksi',
  status      ENUM('aman','waspada','bahaya') NOT NULL DEFAULT 'aman',
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_created (created_at),
  KEY idx_device (device_id, id)
) ENGINE=InnoDB;

-- 3) Riwayat / log insiden (satu baris = satu kejadian per sensor)
CREATE TABLE IF NOT EXISTS riwayat (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  jenis_sensor VARCHAR(30) NOT NULL,
  nilai        VARCHAR(40) NOT NULL,
  status       ENUM('aman','waspada','bahaya') NOT NULL,
  pesan        VARCHAR(160) NOT NULL DEFAULT '',
  sumber       ENUM('sensor','manual') NOT NULL DEFAULT 'sensor',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_created (created_at),
  KEY idx_sensor (jenis_sensor, id)
) ENGINE=InnoDB;

-- 4) Status aktuator yang diatur dari dashboard --------
CREATE TABLE IF NOT EXISTS device_controls (
  id         TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode       VARCHAR(20) NOT NULL,
  nama       VARCHAR(50) NOT NULL,
  state      TINYINT(1)  NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_kode (kode)
) ENGINE=InnoDB;

-- 5) Catatan aktivitas (halaman Alat IoT) --------------
CREATE TABLE IF NOT EXISTS activity_logs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  pesan      VARCHAR(160) NOT NULL,
  sumber     ENUM('admin','sistem') NOT NULL DEFAULT 'sistem',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_created (created_at)
) ENGINE=InnoDB;

-- ===== DATA AWAL ======================================
INSERT INTO device_controls (kode, nama, state) VALUES
  ('buzzer', 'Buzzer', 0),
  ('led',    'LED',    1),
  ('kipas',  'Kipas',  0)
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

-- Admin awal:  admin@cekapi.id  /  admin123   (WAJIB diganti!)
-- Ganti dengan:  php tools/set_admin.php email@anda.com PasswordBaru "Nama Anda"
INSERT INTO admin (nama, email, password_hash) VALUES
  ('Admin CEKAPI', 'admin@cekapi.id', '$2y$12$yB5SEB92cIWVlvgc7xLQIOAzhi34H79fyBThYbI8RUx7DK9rv8hOi')
ON DUPLICATE KEY UPDATE email = email;
