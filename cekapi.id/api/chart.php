<?php
/**
 * GET /api/chart.php?range=live|week  - data grafik (login admin)
 *   live : 30 pembacaan terakhir
 *   week : rata-rata harian 7 hari terakhir
 */
require_once __DIR__ . '/../includes/auth.php';
require_login_api();

$range = ($_GET['range'] ?? 'live') === 'week' ? 'week' : 'live';
$hari  = ['', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

try {
    $labels = $suhu = $asap = $gas = [];

    if ($range === 'live') {
        $rows = db()->query('SELECT created_at, suhu, asap, gas FROM sensor_logs ORDER BY id DESC LIMIT 30')->fetchAll();
        foreach (array_reverse($rows) as $r) {
            $labels[] = date('H:i:s', strtotime($r['created_at']));
            $suhu[]   = $r['suhu'] === null ? null : (float) $r['suhu'];
            $asap[]   = $r['asap'] === null ? null : (int) $r['asap'];
            $gas[]    = $r['gas'] === null ? null : (int) $r['gas'];
        }
    } else {
        $rows = db()->query(
            'SELECT DATE(created_at) AS tgl, AVG(suhu) AS suhu, AVG(asap) AS asap, AVG(gas) AS gas
               FROM sensor_logs
              WHERE created_at >= (CURDATE() - INTERVAL 6 DAY)
              GROUP BY DATE(created_at) ORDER BY tgl'
        )->fetchAll();
        foreach ($rows as $r) {
            $labels[] = $hari[(int) date('N', strtotime($r['tgl']))] . ' ' . date('d/m', strtotime($r['tgl']));
            $suhu[]   = $r['suhu'] === null ? null : round((float) $r['suhu'], 1);
            $asap[]   = $r['asap'] === null ? null : (int) round((float) $r['asap']);
            $gas[]    = $r['gas'] === null ? null : (int) round((float) $r['gas']);
        }
    }

    json_response(['ok' => true, 'range' => $range, 'labels' => $labels, 'suhu' => $suhu, 'asap' => $asap, 'gas' => $gas]);
} catch (Throwable $e) {
    error_log('[cekapi] chart.php: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Kesalahan server'], 500);
}
