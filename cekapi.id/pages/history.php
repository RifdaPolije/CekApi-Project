<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/icons.php';
require_login();

$f    = riwayat_filter($_GET);
$per  = 15;
$pdo  = db();

// $st = $pdo->prepare('SELECT COUNT(*) FROM riwayat ' . $f['where']);
// $st->execute($f['params']);
// $total = (int) $st->fetchColumn();
// $pages = max(1, (int) ceil($total / $per));
// $page  = min($pages, max(1, (int) ($_GET['page'] ?? 1)));

// $st = $pdo->prepare('SELECT * FROM riwayat ' . $f['where'] . ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
// // $i = 1;
// foreach ($f['params'] as $v) $st->bindValue($i++, $v);
// $st->bindValue($i++, $per, PDO::PARAM_INT);
// $st->bindValue($i++, ($page - 1) * $per, PDO::PARAM_INT);
// $st->execute();
// $rows = $st->fetchAll();

$qs = static function (array $extra = []) use ($f) {
    $q = array_filter(['q' => $f['q'], 'from' => $f['from'], 'to' => $f['to']] + $extra, fn($v) => $v !== '' && $v !== null);
    return http_build_query($q);
};

$pageTitle = 'Riwayat';
$active    = 'history';
$pageCss   = ['dashboard', 'history'];
$pageJs    = ['history'];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>
  <main class="panel">
    <h1 class="page-title">Riwayat Data Sensor &amp; Aktivitas</h1>

    <div class="toolbar">
      <form method="get" id="filterForm" role="search">
        <label class="field-pill">
          <?= icon('search', 22) ?>
          <span class="sr-only">Cari riwayat</span>
          <input type="search" name="q" id="searchBox" value="<?= h($f['q']) ?>" placeholder="Cari Riwayat">
        </label>
        <span class="lbl">Pilih Periode:</span>
        <span class="field-pill">
          <?= icon('calendar', 22) ?>
          <input type="date" name="from" value="<?= h($f['from']) ?>" aria-label="Dari tanggal">
          <span>–</span>
          <input type="date" name="to" value="<?= h($f['to']) ?>" aria-label="Sampai tanggal">
        </span>
        <button type="submit" class="sr-only">Terapkan</button>
      </form>
      <button type="button" class="btn" id="btnAdd"><?= icon('plus', 20) ?>Tambah Data</button>
      <a class="btn btn-blue" href="history-export.php<?= $qs() ? '?' . h($qs()) : '' ?>" target="_blank" rel="noopener"><?= icon('download', 20) ?>Download PDF</a>
    </div>

    <div class="tbl-wrap big">
      <table class="tbl hist">
        <thead><tr><th>ID</th><th>Waktu Kejadian</th><th>Jenis Sensor</th><th>Nilai</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
<!-- <?php if (!$rows): ?>
          <tr><td class="empty" colspan="6">Belum ada data untuk filter ini.</td></tr>
<?php endif; ?>
<?php foreach ($rows as $r):
    $json = json_encode([
        'id' => (int) $r['id'], 'jenis_sensor' => $r['jenis_sensor'], 'nilai' => $r['nilai'],
        'status' => $r['status'], 'pesan' => $r['pesan'], 'waktu' => date('Y-m-d\TH:i:s', strtotime($r['created_at'])),
    ], JSON_UNESCAPED_UNICODE);
?>
          <tr data-row="<?= h($json) ?>">
            <td><?= str_pad((string) $r['id'], 3, '0', STR_PAD_LEFT) ?></td>
            <td><?= h(fmt_waktu($r['created_at'])) ?></td>
            <td><?= h($r['jenis_sensor']) ?></td>
            <td><?= h($r['nilai']) ?></td>
            <td><span class="badge <?= h($r['status']) ?>"><?= h(ucfirst($r['status'])) ?></span></td>
            <td>
              <button type="button" class="act" data-edit><?= icon('edit', 20) ?>Edit</button>
              <button type="button" class="act del" data-delete><?= icon('trash', 20) ?>Delete</button>
            </td>
          </tr>
<?php endforeach; ?>
        </tbody> -->
      </table>
    </div>

    <!-- <nav class="pager" aria-label="Halaman">
      <p class="count"><?= $total ?> data · halaman <?= $page ?> dari <?= $pages ?></p>
      <?php if ($page > 1): ?><a href="?<?= h($qs(['page' => $page - 1])) ?>">‹ Sebelumnya</a><?php else: ?><span class="off">‹ Sebelumnya</span><?php endif; ?>
      <?php if ($page < $pages): ?><a href="?<?= h($qs(['page' => $page + 1])) ?>">Berikutnya ›</a><?php else: ?><span class="off">Berikutnya ›</span><?php endif; ?>
    </nav> -->
  </main>

  <dialog class="modal" id="modal" aria-labelledby="modalTitle">
    <form method="dialog" id="modalForm">
      <h2 id="modalTitle">Tambah Data</h2>
      <input type="hidden" id="fId">
      <div class="row2">
        <label>Jenis sensor
          <select id="fJenis" required>
<?php foreach (SENSOR_JENIS as $j): ?><option><?= h($j) ?></option><?php endforeach; ?>
          </select>
        </label>
        <label>Status
          <select id="fStatus" required>
<?php foreach (STATUS_LIST as $s): ?><option value="<?= h($s) ?>"><?= h(ucfirst($s)) ?></option><?php endforeach; ?>
          </select>
        </label>
      </div>
      <label>Nilai <input id="fNilai" maxlength="40" placeholder="mis. 32,5°C atau 1800 ppm" required></label>
      <label>Waktu kejadian <input id="fWaktu" type="datetime-local" step="1" required></label>
      <label>Pesan (opsional) <input id="fPesan" maxlength="160" placeholder="Dikosongkan = pesan otomatis"></label>
      <p class="err" id="fErr" role="alert"></p>
      <div class="actions">
        <button type="button" class="btn btn-ghost" id="btnCancel">Batal</button>
        <button type="submit" class="btn" id="btnSubmit">Simpan</button>
      </div>
    </form>
  </dialog>
<?php include __DIR__ . '/partials/footer.php'; ?>
