<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/icons.php';

$masuk = is_logged_in();
$cta   = $masuk ? 'pages/dashboard.php' : 'login.php';

// Status perangkat (kartu "System Online"); aman bila database belum siap
$online = false; $terakhir = null;
try {
    $row = db()->query('SELECT created_at FROM sensor_logs ORDER BY id DESC LIMIT 1')->fetch();
    if ($row) {
        $terakhir = $row['created_at'];
        $online = (time() - strtotime($terakhir)) <= DEVICE_OFFLINE_SECONDS;
    }
} catch (Throwable $e) { /* abaikan */ }

$alat = [
    ['hardware', 'chip',  'ESP32 DevKit V1', 'Mikrokontroler WiFi yang membaca semua sensor dan mengirim data ke server CEKAPI.'],
    ['hardware', 'cloud', 'Sensor MQ-2', 'Mendeteksi gas LPG dan asap di udara sebagai tanda awal terjadinya potensi kebakaran.'],
    ['hardware', 'thermo','Sensor DHT11', 'Mengukur suhu dan kelembaban ruangan dapur secara berkala.'],
    ['hardware', 'flame', 'Sensor Inframerah', 'Mendeteksi cahaya api secara langsung sebelum api membesar.'],
    ['komponen', 'bellring', 'Buzzer Alarm', 'Berbunyi otomatis saat kondisi bahaya agar penghuni segera waspada.'],
    ['komponen', 'flame', 'LED Indikator', 'Menyala atau berkedip menandakan status sistem dan tingkat bahaya.'],
    ['komponen', 'fan', 'Kipas + Relay', 'Menyalakan sirkulasi udara saat asap atau gas meningkat.'],
    ['arsitektur', 'chip', 'Sensor → ESP32', 'Sensor membaca kondisi dapur, ESP32 menilai tingkat bahaya secara lokal.'],
    ['arsitektur', 'cloud', 'WiFi → API PHP', 'Data dikirim lewat HTTP POST ke endpoint CEKAPI dan disimpan di MySQL.'],
    ['arsitektur', 'dashboard', 'Dashboard Web', 'Admin memantau data real-time, riwayat, dan mengontrol aktuator.'],
    ['online', $online ? 'bellring' : 'x', 'Status Perangkat: ' . ($online ? 'ONLINE' : 'OFFLINE'),
        $terakhir ? 'Data terakhir diterima ' . fmt_waktu($terakhir) . '.' : 'Belum ada data yang diterima dari perangkat.'],
];
$tim = [
    ['Safina Rahmatus Sa’diyah', 'E43252017', '@starrvyaa_'],
    ['Rifda Aprilia', 'E43252017', '@starrvyaa_'],
    ['Gery Riski Danindra', 'E43251922', '@starrvyaa_'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CEKAPI.id | Deteksi Dini Kebocoran Gas dan Kebakaran Dapur</title>
  <meta name="description" content="Integrasi web dan IoT untuk deteksi dini kebocoran gas dan kebakaran dapur.">
  <link rel="icon" type="image/svg+xml" href="<?= h(base_url('assets/img/maskotLogo.png')) ?>">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,600;0,700;1,400&amp;family=Poppins:wght@400;500;600;700;800&amp;display=swap">
  <link rel="stylesheet" href="<?= h(base_url('assets/css/global.css')) ?>">
  <link rel="stylesheet" href="<?= h(base_url('assets/css/landing.css')) ?>">
</head>
<body class="landing">

<header class="nav">
  <a class="logo" href="#beranda"><img src="<?= h(base_url('assets/img/maskotLogo.png')) ?>" alt=""><span>CEKAPI.id</span></a>
  <button class="nav-burger" id="burger" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="menu"><?= icon('menu', 28) ?></button>
  <nav id="menu" class="menu" aria-label="Navigasi utama">
    <a href="#beranda" class="on">Beranda</a>
    <a href="#alat-iot">Alat</a>
    <a href="<?= h(base_url('pages/history.php')) ?>">Riwayat</a>
    <a href="<?= h(base_url($cta)) ?>"><?= $masuk ? 'Dashboard' : 'Akun' ?></a>
  </nav>
  <form class="search" action="<?= h(base_url('pages/history.php')) ?>" method="get" role="search">
    <input type="search" name="q" placeholder="PENCARIAN" aria-label="Cari riwayat">
    <button type="submit" aria-label="Cari"><?= icon('search', 22) ?></button>
  </form>
</header>

<main>
  <section class="hero" id="beranda">
    <div class="hero-copy">
      <p class="eyebrow">Solusi Pintar Cegah Kebakaran Dapur</p>
      <h1>CEKAPI.id</h1>
      <p class="lead">Integrasi web dan IoT untuk <em>deteksi dini</em> kebocoran gas dan kebakaran dapur.</p>
      <a class="btn" href="<?= h(base_url($cta)) ?>">Mulai Pantau <?= icon('arrow', 18) ?></a>
    </div>
    <div class="hero-pic">
      
      <img src="<?= h(base_url('assets/img/kitchen-index.png')) ?>" alt="Api menyala di dapur" onerror="this.remove()">
    </div>
  </section>

  <section class="about" id="tentang">
    <div class="about-pic">
      <!-- Ganti dengan foto Anda: assets/img/about-kitchen.jpg -->
      <img src="<?= h(base_url('assets/img/imgAbout.jpg')) ?>" alt="Api kompor yang menyambar di dapur" onerror="this.remove()">
    </div>
    <div class="about-copy">
      <p class="eyebrow">TENTANG &amp; MASALAH</p>
      <h2>Mengapa Deteksi Dini Sangat Krusial?</h2>
      <p>Sebagian besar kebakaran rumah bermula dari dapur yang terlambat ditangani—baik akibat kebocoran gas LPG maupun makanan yang gosong. CEKAPI hadir dengan teknologi IoT untuk mendeteksi gas, suhu panas, dan asap, sebelum bahaya membesar.</p>
      <div class="stats">
        <div class="stat"><b>90%</b><span>Kebakaran rumah bermula dari kelalaian di area dapur dan kebocoran gas LPG.</span></div>
        <div class="stat"><b>24/7 <small>AKTIF</small></b><span>Pemantauan real-time tanpa henti langsung dari genggaman Anda.</span></div>
      </div>
    </div>
  </section>

  <section class="tools" id="alat-iot" aria-labelledby="ttl-alat">
    <div class="tools-filter">
      <h2 id="ttl-alat"><span>ALAT IOT</span></h2>
      <div role="tablist" aria-label="Kategori alat">
        <button type="button" role="tab" data-cat="hardware" aria-selected="true">Spesifikasi Hardware</button>
        <button type="button" role="tab" data-cat="online" aria-selected="false">System Online</button>
        <button type="button" role="tab" data-cat="komponen" aria-selected="false">Komponen Utama</button>
        <button type="button" role="tab" data-cat="arsitektur" aria-selected="false">Arsitektur IoT</button>
      </div>
    </div>
    <button class="arrow" id="prev" type="button" aria-label="Sebelumnya">‹</button>
    <div class="track" id="track" tabindex="0" aria-live="polite">
<?php foreach ($alat as [$cat, $ico, $judul, $isi]): ?>
      <article class="tcard" data-cat="<?= h($cat) ?>"<?= $cat !== 'hardware' ? ' hidden' : '' ?>>
        <div class="tcard-ico"><?= icon($ico, 84) ?></div>
        <h3><?= h($judul) ?></h3>
        <p><?= h($isi) ?></p>
      </article>
<?php endforeach; ?>
    </div>
    <button class="arrow" id="next" type="button" aria-label="Berikutnya">›</button>
  </section>

  <section class="flow" id="alur" aria-labelledby="ttl-alur">
    <p class="eyebrow">ALUR INTEGRASI IOT</p>
    <h2 id="ttl-alur">Bagaimana CEKAPI Bekerja?</h2>
    <ol class="steps">
      <li><div class="step-ico"><?= icon('chip', 96) ?></div><h3>1. Deteksi Sensor (IoT)</h3><p>Sensor api, suhu, dan asap pada miniatur dapur mendeteksi bahaya di area secara langsung (real time).</p></li>
      <li><div class="step-ico"><?= icon('cloud', 96) ?></div><h3>2. Transmisi Data</h3><p>Data yang terekam dari ruangan langsung dikirim ke database server CEKAPI melalui jaringan internet (WiFi).</p></li>
      <li><div class="step-ico"><?= icon('alert', 96) ?></div><h3>3. Pantau &amp; Alarm</h3><p>Sistem langsung menampilkan status di dashboard web dan mengirimkan peringatan/notifikasi bahaya untuk tindakan cepat.</p></li>
    </ol>
  </section>

  <section class="team-gallery">
    <div id="tim">
      <h2 class="eyebrow">TIM PENGEMBANG</h2>
<?php foreach ($tim as [$nama, $nim, $ig]): ?>
      <article class="member">
        <div class="photo" aria-hidden="true"></div>
        <div><h3><?= h($nama) ?></h3><p><?= h($nim) ?></p><p><?= h($ig) ?></p></div>
      </article>
<?php endforeach; ?>
    </div>
    <div id="galeri">
      <h2 class="eyebrow">GALERI</h2>
      <div class="gallery" role="list">
        <div class="g1" role="listitem"></div><div class="g2" role="listitem"></div><div class="g3" role="listitem"></div>
        <div class="g4" role="listitem"></div><div class="g5" role="listitem"></div>
      </div>
    </div>
  </section>
</main>

<footer class="foot" id="kontak">
  <div class="foot-brand">
    <a class="logo" href="#beranda"><img src="<?= h(base_url('assets/img/maskotLogo.png')) ?>" alt=""><span>CEKAPI</span></a>
    <p>Integrasi web dan IoT untuk <em>deteksi dini</em> kebocoran gas dan kebakaran dapur.</p>
  </div>
  <div>
    <h3>Kontak Darurat</h3>
    <ul>
      <li><a href="mailto:support@cekapi.id">support@cekapi.id</a></li>
      <li><a href="tel:113">Hotline: 113 / 112</a></li>
      <li><a href="#tim">Lab IoT / Kampus</a></li>
    </ul>
  </div>
  <div>
    <h3>Tautan Cepat</h3>
    <ul>
      <li><a href="#beranda">Beranda</a></li>
      <li><a href="#alur">Cara Kerja</a></li>
      <li><a href="#tim">Tim Pengembang</a></li>
      <li><a href="#galeri">Galery</a></li>
    </ul>
  </div>
  <div class="foot-bottom">
    <span>© <?= date('Y') ?> CEKAPI Team. All rights reserved.</span>
    <span><a href="#">Kebijakan Privasi</a> &nbsp;|&nbsp; <a href="#">Syarat</a></span>
  </div>
</footer>

<script src="<?= h(base_url('assets/js/landing.js')) ?>"></script>
</body>
</html>
