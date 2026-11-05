<?php
/**
 * Halaman cetak riwayat. Dialog cetak terbuka otomatis -> pilih "Simpan sebagai PDF".
 * (Tanpa library tambahan. Untuk PDF langsung di server, gunakan Dompdf/FPDF.)
 */
require __DIR__ . '/../includes/auth.php';
require_login();

$f  = riwayat_filter($_GET);
$st = db()->prepare('SELECT * FROM riwayat ' . $f['where'] . ' ORDER BY created_at DESC, id DESC LIMIT 2000');
$st->execute($f['params']);
$rows = $st->fetchAll();

$periode = ($f['from'] || $f['to'])
    ? ($f['from'] ? date('d/m/Y', strtotime($f['from'])) : '…') . ' – ' . ($f['to'] ? date('d/m/Y', strtotime($f['to'])) : '…')
    : 'Semua periode';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Riwayat CEKAPI.id</title>
  <style>
    @page{size:A4;margin:16mm}
    body{font:12px/1.4 Arial,Helvetica,sans-serif;color:#111}
    h1{font-size:18px;margin:0 0 2px}
    p{margin:0 0 12px;color:#444}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #999;padding:5px 7px;text-align:left}
    th{background:#cfe6f7}
    tr{page-break-inside:avoid}
    .b{font-weight:700}.aman{color:#1d6b22}.waspada{color:#9a6b00}.bahaya{color:#c20012}
    .noprint{margin-bottom:12px}
    @media print{.noprint{display:none}}
  </style>
</head>
<body>
  <div class="noprint"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
  <h1>Riwayat Data Sensor &amp; Aktivitas — CEKAPI.id</h1>
  <p>Periode: <?= h($periode) ?><?= $f['q'] !== '' ? ' · Pencarian: “' . h($f['q']) . '”' : '' ?> · Dicetak: <?= date('d-m-Y H:i') ?> · <?= count($rows) ?> data</p>
  <table>
    <thead><tr><th>ID</th><th>Waktu Kejadian</th><th>Jenis Sensor</th><th>Nilai</th><th>Status</th><th>Pesan</th></tr></thead>
    <tbody>
<?php foreach ($rows as $r): ?>
      <tr>
        <td><?= str_pad((string) $r['id'], 3, '0', STR_PAD_LEFT) ?></td>
        <td><?= h(fmt_waktu($r['created_at'])) ?></td>
        <td><?= h($r['jenis_sensor']) ?></td>
        <td><?= h($r['nilai']) ?></td>
        <td class="b <?= h($r['status']) ?>"><?= h(ucfirst($r['status'])) ?></td>
        <td><?= h($r['pesan']) ?></td>
      </tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6">Tidak ada data.</td></tr><?php endif; ?>
    </tbody>
  </table>
  <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
