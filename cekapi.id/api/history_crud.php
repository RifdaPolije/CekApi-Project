<?php
/**
 * POST /api/history_crud.php  - tambah / ubah / hapus baris riwayat (login admin + CSRF)
 * body: {"action":"create|update|delete","id":1,"jenis_sensor":"Sensor Suhu","nilai":"32,5°C",
 *        "status":"aman|waspada|bahaya","pesan":"...","waktu":"2026-10-18T03:25:50"}
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Gunakan metode POST'], 405);
}
require_login_api();
csrf_require_api();

$in     = read_json_body();
$action = $in['action'] ?? '';
$id     = (int) ($in['id'] ?? 0);

try {
    if ($action === 'delete') {
        if ($id < 1) json_response(['ok' => false, 'error' => 'ID tidak valid'], 422);
        db()->prepare('DELETE FROM riwayat WHERE id = ?')->execute([$id]);
        json_response(['ok' => true]);
    }

    if ($action !== 'create' && $action !== 'update') {
        json_response(['ok' => false, 'error' => 'Aksi tidak dikenal'], 422);
    }

    $jenis  = (string) ($in['jenis_sensor'] ?? '');
    $nilai  = trim((string) ($in['nilai'] ?? ''));
    $status = (string) ($in['status'] ?? '');
    $pesan  = trim((string) ($in['pesan'] ?? ''));
    $waktu  = (string) ($in['waktu'] ?? '');

    if (!in_array($jenis, SENSOR_JENIS, true))  json_response(['ok' => false, 'error' => 'Jenis sensor tidak valid'], 422);
    if (!in_array($status, STATUS_LIST, true))  json_response(['ok' => false, 'error' => 'Status tidak valid'], 422);
    if ($nilai === '' || mb_strlen($nilai) > 40) json_response(['ok' => false, 'error' => 'Nilai wajib diisi (maks. 40 karakter)'], 422);
    if (mb_strlen($pesan) > 160)                json_response(['ok' => false, 'error' => 'Pesan maks. 160 karakter'], 422);

    $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $waktu) ?: DateTime::createFromFormat('Y-m-d\TH:i', $waktu);
    if (!$dt) json_response(['ok' => false, 'error' => 'Format waktu tidak valid'], 422);
    $waktuSql = $dt->format('Y-m-d H:i:s');
    if ($pesan === '') $pesan = pesan_event($jenis, $status);

    if ($action === 'create') {
        db()->prepare("INSERT INTO riwayat (jenis_sensor, nilai, status, pesan, sumber, created_at) VALUES (?,?,?,?,'manual',?)")
            ->execute([$jenis, $nilai, $status, $pesan, $waktuSql]);
    } else {
        if ($id < 1) json_response(['ok' => false, 'error' => 'ID tidak valid'], 422);
        db()->prepare('UPDATE riwayat SET jenis_sensor=?, nilai=?, status=?, pesan=?, created_at=? WHERE id=?')
            ->execute([$jenis, $nilai, $status, $pesan, $waktuSql, $id]);
    }
    json_response(['ok' => true]);
} catch (Throwable $e) {
    error_log('[cekapi] history_crud.php: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Kesalahan server'], 500);
}
