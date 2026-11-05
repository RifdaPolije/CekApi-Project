<?php
$adm  = current_admin();
$warn = hitung_peringatan_hari_ini();
$ini  = mb_strtoupper(mb_substr($adm['nama'], 0, 1));
?>
<header class="topbar">
  <button class="icon-btn nav-toggle" id="navToggle" type="button" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false"><?= icon('menu') ?></button>
  <a class="logo" href="<?= h(base_url('index.php')) ?>">
    <img src="<?= h(base_url('assets/img/maskotLogo.png')) ?>" alt="">
    <span>CEKAPI.id</span>
  </a>
  <div class="spacer"></div>
  <div class="top-actions">
    <a class="icon-btn" href="history.php?focus=1" aria-label="Cari riwayat"><?= icon('search', 32) ?></a>
    <a class="icon-btn" href="alat-iot.php" aria-label="Peringatan hari ini: <?= $warn ?>">
      <?= icon('bell', 32) ?><?php if ($warn > 0): ?><span class="dot" id="bellDot"></span><?php endif; ?>
    </a>
    <div class="profile">
      <span class="avatar" aria-hidden="true"><?= h($ini) ?></span>
      <div><b><?= h($adm['nama']) ?></b><small><?= h($adm['email']) ?></small></div>
    </div>
  </div>
</header>
