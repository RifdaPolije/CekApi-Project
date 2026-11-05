<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf'] ?? '')) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: ' . base_url('login.php'));
    exit;
}
header('Location: ' . base_url(is_logged_in() ? 'pages/dashboard.php' : 'login.php'));
exit;
