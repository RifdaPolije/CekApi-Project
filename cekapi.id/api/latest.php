<?php
/**
 * GET /api/latest.php  - data sensor terbaru untuk kartu real-time (login admin)
 */
require_once __DIR__ . '/../includes/auth.php';
require_login_api();

try {
    $row = db()->query('SELECT * FROM sensor_logs ORDER BY id DESC LIMIT 1')->fetch();
    if (!$row) {
        json_response(['ok' => true, 'online' => false, 'data' => null, 'warnings_today' => hitung_peringatan_hari_ini()]);
    }
    $age = time() - strtotime($row['created_at']);
    $data = [
        'suhu'       => $row['suhu'] === null ? null : (float) $row['suhu'],
        'kelembaban' => $row['kelembaban'] === null ? null : (float) $row['kelembaban'],
        'asap'       => $row['asap'] === null ? null : (int) $row['asap'],
        'gas'        => $row['gas'] === null ? null : (int) $row['gas'],
        'api'        => (int) $row['api'],
    ];
    $cls = klasifikasi($data['suhu'], $data['asap'], $data['gas'], (bool) $data['api']);

    json_response([
        'ok'             => true,
        'online'         => $age <= DEVICE_OFFLINE_SECONDS,
        'updated_at'     => fmt_waktu($row['created_at']),
        'age'            => $age,
        'data'           => $data,
        'status'         => ['overall' => $cls['overall']] + $cls['per'],
        'warnings_today' => hitung_peringatan_hari_ini(),
    ]);
} catch (Throwable $e) {
    error_log('[cekapi] latest.php: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Kesalahan server'], 500);
}
