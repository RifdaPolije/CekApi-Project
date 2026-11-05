<?php
/**
 * Kontrol aktuator
 *
 * GET  /api/control.php   -> status buzzer/led/kipas (login admin ATAU header X-API-KEY dari ESP32)
 * POST /api/control.php   -> simpan perubahan dari dashboard (login admin + header X-CSRF-Token)
 *        body: {"controls":{"buzzer":0,"led":1,"kipas":0}}
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$label = ['buzzer' => 'Buzzer', 'led' => 'LED', 'kipas' => 'Kipas'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $isDevice = hash_equals(API_KEY, (string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
        if (!$isDevice) require_login_api();
        json_response(['ok' => true, 'control' => ambil_controls()]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['ok' => false, 'error' => 'Metode tidak didukung'], 405);
    }

    require_login_api();
    csrf_require_api();

    $in  = read_json_body()['controls'] ?? null;
    if (!is_array($in)) json_response(['ok' => false, 'error' => 'Data tidak valid'], 422);

    $pdo = db();
    $pdo->beginTransaction();
    $now = ambil_controls();
    $upd = $pdo->prepare('UPDATE device_controls SET state = ? WHERE kode = ?');

    foreach ($label as $kode => $nama) {
        if (!array_key_exists($kode, $in)) continue;
        $baru = filter_var($in[$kode], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        if (($now[$kode] ?? 0) !== $baru) {
            $upd->execute([$baru, $kode]);
            catat_aktivitas($nama . ($baru ? ' dinyalakan' : ' dimatikan'), 'admin');
        }
    }
    $pdo->commit();

    json_response([
        'ok'             => true,
        'control'        => ambil_controls(),
        'activities'     => ambil_aktivitas(8),
        'warnings_today' => hitung_peringatan_hari_ini(),
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('[cekapi] control.php: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Kesalahan server'], 500);
}
