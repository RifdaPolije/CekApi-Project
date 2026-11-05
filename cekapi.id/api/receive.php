<?php
/**
 * POST /api/receive.php  - dipanggil oleh ESP32
 *
 * Header : X-API-KEY: <API_KEY>, Content-Type: application/json
 * Body   : {"device_id":"ESP32-01","suhu":31.5,"kelembaban":60,"asap":300,"gas":20,"api":0}
 * Balasan: {"ok":true,"status":"aman","control":{"buzzer":0,"led":1,"kipas":0}}
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Gunakan metode POST'], 405);
}
if (!hash_equals(API_KEY, (string) ($_SERVER['HTTP_X_API_KEY'] ?? ''))) {
    json_response(['ok' => false, 'error' => 'API key tidak valid'], 401);
}

// Terima JSON, atau form-urlencoded sebagai cadangan
$d = read_json_body();
if (!$d) $d = $_POST;

$num = static function ($v, float $min, float $max) {
    if ($v === null || $v === '' || !is_numeric($v)) return null;
    $v = (float) $v;
    return ($v < $min || $v > $max) ? false : $v;
};

$suhu = $num($d['suhu'] ?? null, -40, 125);
$lembap = $num($d['kelembaban'] ?? null, 0, 100);
$asap = $num($d['asap'] ?? null, 0, 100000);
$gas = $num($d['gas'] ?? null, 0, 100);
if (in_array(false, [$suhu, $lembap, $asap, $gas], true)) {
    json_response(['ok' => false, 'error' => 'Nilai sensor di luar rentang'], 422);
}
$asap = $asap === null ? null : (int) round($asap);
$gas  = $gas === null ? null : (int) round($gas);
$api  = filter_var($d['api'] ?? 0, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
$dev  = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) ($d['device_id'] ?? 'ESP32-01')) ?: 'ESP32-01';

try {
    $pdo = db();
    $cls = klasifikasi($suhu, $asap, $gas, (bool) $api);
    $now = $cls['overall'];

    $pdo->beginTransaction();

    // Status keseluruhan pembacaan sebelumnya (untuk mendeteksi perubahan)
    $st = $pdo->prepare('SELECT status FROM sensor_logs WHERE device_id = ? ORDER BY id DESC LIMIT 1');
    $st->execute([$dev]);
    $prev = $st->fetchColumn() ?: 'aman';

    // 1) Simpan pembacaan mentah
    $pdo->prepare('INSERT INTO sensor_logs (device_id, suhu, kelembaban, asap, gas, api, status) VALUES (?,?,?,?,?,?,?)')
        ->execute([$dev, $suhu, $lembap, $asap, $gas, $api, $now]);

    // 2) Catat riwayat hanya saat status satu sensor BERUBAH (hemat ruang)
    $peta = ['suhu' => 'Sensor Suhu', 'asap' => 'Sensor Asap', 'gas' => 'Sensor Gas', 'api' => 'Sensor Api'];
    $cekLast = $pdo->prepare("SELECT status FROM riwayat WHERE jenis_sensor = ? AND sumber = 'sensor' ORDER BY id DESC LIMIT 1");
    $ins = $pdo->prepare('INSERT INTO riwayat (jenis_sensor, nilai, status, pesan, sumber) VALUES (?,?,?,?,\'sensor\')');
    foreach ($peta as $kunci => $jenis) {
        if ($kunci === 'suhu' && $suhu === null) continue;
        if ($kunci === 'asap' && $asap === null) continue;
        if ($kunci === 'gas' && $gas === null) continue;
        $cekLast->execute([$jenis]);
        $last = $cekLast->fetchColumn() ?: 'aman';
        $cur  = $cls['per'][$kunci];
        if ($cur !== $last) {
            $ins->execute([$jenis, nilai_text($jenis, $suhu, $asap, $gas, (bool) $api), $cur, pesan_event($jenis, $cur)]);
        }
    }

    // 3) Catatan aktivitas otomatis saat alarm menyala / padam
    if ($now === 'bahaya' && $prev !== 'bahaya') {
        catat_aktivitas('Buzzer berbunyi (alarm otomatis)');
        catat_aktivitas('LED berkedip (alarm otomatis)');
    } elseif ($prev === 'bahaya' && $now !== 'bahaya') {
        catat_aktivitas('Alarm otomatis dihentikan');
    }

    $pdo->commit();

    // Pembersihan data lama (sesekali saja)
    if (random_int(1, 300) === 1) {
        $pdo->prepare('DELETE FROM sensor_logs WHERE created_at < (NOW() - INTERVAL ? DAY)')->execute([SENSOR_LOG_RETENTION_DAYS]);
    }

    json_response(['ok' => true, 'status' => $now, 'control' => ambil_controls()]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('[cekapi] receive.php: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Kesalahan server'], 500);
}
