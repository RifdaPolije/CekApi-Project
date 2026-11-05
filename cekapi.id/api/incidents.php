<?php
/**
 * GET /api/incidents.php  - 5 kejadian terakhir untuk "Log Insiden Aktif" (login admin)
 */
require_once __DIR__ . '/../includes/auth.php';
require_login_api();

try {
    $rows = db()->query('SELECT jenis_sensor, nilai, status, pesan, created_at FROM riwayat ORDER BY id DESC LIMIT 5')->fetchAll();
    $out = array_map(static fn($r) => [
        'alat'   => $r['jenis_sensor'],
        'waktu'  => fmt_waktu($r['created_at']),
        'status' => $r['status'],
        'pesan'  => $r['pesan'] !== '' ? $r['pesan'] : $r['nilai'],
    ], $rows);
    json_response(['ok' => true, 'items' => $out]);
} catch (Throwable $e) {
    error_log('[cekapi] incidents.php: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Kesalahan server'], 500);
}
