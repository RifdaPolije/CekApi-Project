
<?php
/**
 * Session & autentikasi admin tunggal
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Inisialisasi session
if (session_status() === PHP_SESSION_NONE) {
    session_name('cekapi_sess');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);

    session_start();
}

// Periksa status login admin
function is_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

// Wajib login untuk mengakses halaman
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

// Wajib login untuk mengakses API
function require_login_api(): void
{
    if (!is_logged_in()) {
        json_response([
            'ok'    => false,
            'error' => 'Unauthorized'
        ], 401);
    }
}

// Ambil informasi admin yang sedang login
function current_admin(): array
{
    return [
        'id'    => $_SESSION['admin_id'] ?? null,
        'nama'  => $_SESSION['admin_nama'] ?? 'Admin',
        'email' => $_SESSION['admin_email'] ?? '',
    ];
}
