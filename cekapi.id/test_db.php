
<?php

require_once __DIR__ . '/config/database.php';

try {
    $pdo = db();

    echo "KONEKSI DATABASE BERHASIL!";
    echo "<br>Database: " . DB_NAME;

} catch (Throwable $e) {

    echo "KONEKSI DATABASE GAGAL!";
    echo "<br>Pesan error: " . $e->getMessage();

}
