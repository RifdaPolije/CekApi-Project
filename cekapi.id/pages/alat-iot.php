<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/icons.php';
require_login();

// $ctrl  = ambil_controls();
// $acts  = ambil_aktivitas(8);
$warn  = hitung_peringatan_hari_ini();
$devices = ['buzzer' => 'Buzzer', 'led' => 'LED', 'kipas' => 'Kipas'];

$pageTitle = 'Alat IoT';
$active    = 'alat';
$pageCss   = ['alat-iot'];
$pageJs    = ['alat-iot'];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>
  <main class="panel">
    <div class="iot-grid">
      <section aria-labelledby="ttl-panel">
        <h1 id="ttl-panel">Panel Control Perangkat</h1>
        <div class="ctrl-panel">
<?php foreach ($devices as $kode => $nama): $on = !empty($ctrl[$kode]); ?>
          <div class="ctrl">
            <p class="ctrl-label" id="lbl-<?= h($kode) ?>" data-nama="<?= h($nama) ?>"><?= h($nama) ?> : Status <?= $on ? 'Aktif' : 'Non Aktif' ?></p>
            <button type="button" class="toggle" role="switch" data-kode="<?= h($kode) ?>"
                    aria-checked="<?= $on ? 'true' : 'false' ?>" aria-labelledby="lbl-<?= h($kode) ?>">
              <span class="t-text"><?= $on ? 'ON' : 'OFF' ?></span><span class="knob"></span>
            </button>
          </div>
<?php endforeach; ?>
          <div class="ctrl-foot">
            <p class="ctrl-note">Saat kondisi bahaya, perangkat tetap menyalakan alarm otomatis walaupun diatur OFF.</p>
            <button type="button" class="btn-save" id="btnSave">Simpan Perubahan</button>
          </div>
        </div>
      </section>

      <section aria-labelledby="ttl-akt">
        <h2 class="col-title" id="ttl-akt">Aktivitas</h2>
        <h3 class="sub" style="margin-top:0">Peringatan Hari Ini</h3>
        <div class="warn-card">
          <?= icon('alert', 76) ?>
          <span class="num" id="warnCount"><?= (int) $warn ?></span>
          <span class="txt">Peringatan</span>
        </div>

        <h3 class="sub">Catatan Aktivitas</h3>
        <div class="act-log">
          <!-- <ul id="actList">
<?php foreach ($acts as $a): ?>
            <li><time><?= h($a['waktu']) ?></time><?= h($a['pesan']) ?></li>
<?php endforeach; ?>
          </ul>
          <p class="empty" id="actEmpty"<?= $acts ? ' hidden' : '' ?>>Belum ada aktivitas.</p> -->
        </div>
      </section>
    </div>
  </main>
<?php include __DIR__ . '/partials/footer.php'; ?>
