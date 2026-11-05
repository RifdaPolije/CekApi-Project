<?php
$active = $active ?? '';
$menu = [
    ['dashboard', 'dashboard.php', 'Dashboard', 'dashboard'],
    ['alat',      'alat-iot.php',  'Alat IoT',  'tool'],
    ['history',   'history.php',   'History',   'history'],
];
?>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <nav class="nav" aria-label="Menu utama">
<?php foreach ($menu as [$key, $href, $label, $ico]): ?>
      <a href="<?= h($href) ?>" class="<?= $active === $key ? 'is-active' : '' ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= icon($ico, 34) ?><span><?= h($label) ?></span></a>
<?php endforeach; ?>
    </nav>
    <div class="nav-bottom">
      <a href="<?= h(base_url('index.php')) ?>#kontak"><?= icon('help', 28) ?><span>Help</span></a>
      <form method="post" action="<?= h(base_url('logout.php')) ?>">
        <?= csrf_field() ?>
        <button type="submit"><?= icon('logout', 28) ?><span>Logout</span></button>
      </form>
    </div>
  </aside>
  <div class="backdrop" id="backdrop"></div>
