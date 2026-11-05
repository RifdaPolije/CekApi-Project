<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/icons.php';
require_login();

$pageTitle = 'Dashboard';
$active    = 'dashboard';
$pageCss   = ['dashboard'];
$pageLibs  = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js'];
$pageJs    = ['dashboard'];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
include __DIR__ . '/partials/sidebar.php';
?>
  <main class="panel">
    <h1 class="page-title">Pusat Pemantauan <em>Real-Time</em></h1>
    <span class="conn" id="conn">Menunggu data…</span>

    <h2 class="sub">Kartu Indikator Real-Time</h2>
    <section class="cards" id="cards" aria-label="Indikator sensor">
      <article class="card card--suhu" id="card-suhu" data-state="aman">
        <h3>Suhu</h3>
        <div class="row">
          <div class="val"><span data-val>--</span><small>°C</small></div>
          <div class="pic"><div class="thermo-bar" aria-hidden="true"><i data-bar style="height:0"></i></div></div>
        </div>
        <div class="state" data-state-text>-</div>
      </article>

      <article class="card card--api" id="card-api" data-state="aman">
        <h3>Api</h3>
        <div class="row"><div class="pic" style="margin:auto;color:#d62d20"><?= icon('flame', 76) ?></div></div>
        <div class="state" data-state-text>-</div>
      </article>

      <article class="card card--asap" id="card-asap" data-state="aman">
        <h3>Asap</h3>
        <div class="row">
          <div class="val"><span data-val>--</span> <small>ppm</small></div>
          <div class="pic" style="color:#111"><?= icon('cloud', 54) ?></div>
        </div>
        <div class="state" data-state-text>-</div>
      </article>

      <article class="card card--gas" id="card-gas" data-state="aman">
        <h3>Gas</h3>
        <div class="row">
          <div class="val"><span data-val>--</span><small>%</small></div>
          <div class="pic" style="color:#d8a6ff"><?= icon('gauge', 54) ?></div>
        </div>
        <div class="state" data-state-text>-</div>
      </article>
    </section>

    <div class="dash-grid">
      <section aria-labelledby="ttl-grafik">
        <div class="chart-head">
          <div>
            <h2 class="sub" id="ttl-grafik" style="margin-top:0">Grafik Pemantauan</h2>
            <p style="margin:-6px 0 12px;font:400 1.05rem var(--font-head)">Grafik sensor suhu, asap, dan gas</p>
          </div>
          <div class="seg" role="group" aria-label="Rentang grafik">
            <button type="button" data-range="live" aria-pressed="true">Live</button>
            <button type="button" data-range="week" aria-pressed="false">7 Hari</button>
          </div>
        </div>
        <div class="chart-box"><canvas id="chart" aria-label="Grafik suhu, asap, dan gas" role="img"></canvas></div>
      </section>

      <section aria-labelledby="ttl-log">
        <h2 class="sub" id="ttl-log" style="margin-top:0">Log Insiden Aktif</h2>
        <div class="tbl-wrap">
          <table class="tbl">
            <thead><tr><th>Alat</th><th>Waktu Kejadian</th><th>Tingkat Bahaya</th><th>Pesan</th></tr></thead>
            <tbody id="incidents"><tr><td class="empty" colspan="4">Memuat…</td></tr></tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
<?php include __DIR__ . '/partials/footer.php'; ?>
