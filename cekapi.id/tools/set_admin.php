<?php
/**
 * Buat / ganti akun admin tunggal (hanya lewat terminal).
 *
 *   php tools/set_admin.php email@anda.com PasswordBaru "Nama Anda"
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Hanya bisa dijalankan dari terminal.');
}
require __DIR__ . '/../config/database.php';

$email    = $argv[1] ?? '';
$password = $argv[2] ?? '';
$nama     = $argv[3] ?? 'Admin CEKAPI';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    fwrite(STDERR, "Pemakaian: php tools/set_admin.php email@anda.com PasswordBaru(min 8 karakter) \"Nama Anda\"\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo  = db();
$id   = $pdo->query('SELECT id FROM admin ORDER BY id LIMIT 1')->fetchColumn();

if ($id) {
    $pdo->prepare('UPDATE admin SET nama = ?, email = ?, password_hash = ? WHERE id = ?')->execute([$nama, $email, $hash, $id]);
    echo "Admin diperbarui: $email\n";
} else {
    $pdo->prepare('INSERT INTO admin (nama, email, password_hash) VALUES (?, ?, ?)')->execute([$nama, $email, $hash]);
    echo "Admin dibuat: $email\n";
}
