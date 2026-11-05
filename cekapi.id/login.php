
<?php

/**
 * CEKAPI.id - Login Admin
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/icons.php';

// Jika admin sudah login, langsung ke dashboard
if (is_logged_in()) {
    header('Location: ' . base_url('pages/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Pemeriksaan login lock
    $lock = (int) ($_SESSION['login_lock_until'] ?? 0);

    if (time() < $lock) {

        $error = 'Terlalu banyak percobaan. Coba lagi dalam '
               . ($lock - time()) . ' detik.';

    } elseif (!csrf_valid($_POST['csrf'] ?? '')) {

        $error = 'Sesi formulir kedaluwarsa. Muat ulang halaman.';

    } else {

        $email = trim((string) ($_POST['username'] ?? ''));
        $pass  = (string) ($_POST['password'] ?? '');

        try {

            // Ambil akun khusus admin dari tabel user
            $st = db()->prepare(
                "SELECT
                    id_user,
                    name,
                    email,
                    password,
                    role
                 FROM `user`
                 WHERE email = ?
                 AND role = 'Admin'
                 LIMIT 1"
            );

            $st->execute([$email]);

            $admin = $st->fetch();

            // Verifikasi password
            $valid = $admin
                && password_verify($pass, $admin['password']);

            if ($valid) {

                // Regenerasi session untuk keamanan
                session_regenerate_id(true);

                // Simpan identitas admin
                $_SESSION['admin_id'] = (int) $admin['id_user'];
                $_SESSION['admin_nama'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];

                // Reset percobaan login
                unset($_SESSION['login_fail']);
                unset($_SESSION['login_lock_until']);

                // Masuk dashboard
                header('Location: ' . base_url('pages/dashboard.php'));
                exit;

            } else {

                // Jika email atau password salah
                $_SESSION['login_fail'] =
                    (int) ($_SESSION['login_fail'] ?? 0) + 1;

                // Maksimal 5 percobaan
                if ($_SESSION['login_fail'] >= 5) {

                    $_SESSION['login_lock_until'] = time() + 60;
                    $_SESSION['login_fail'] = 0;

                    $error = 'Terlalu banyak percobaan. Tunggu 60 detik.';

                } else {

                    $error = 'Email atau password salah.';

                }
            }

        } catch (Throwable $e) {

            // Catat error sebenarnya ke PHP error log
            error_log('[CEKAPI LOGIN] ' . $e->getMessage());

            $error = 'Terjadi kesalahan saat memproses login. '
                   . 'Periksa konfigurasi database.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Masuk | CEKAPI.id</title>

    <link rel="icon"
          type="image/png"
          href="<?= h(base_url('assets/img/maskotLogo.png')) ?>">

    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Poppins:wght@400;500;600;700&display=swap">

    <link rel="stylesheet"
          href="<?= h(base_url('assets/css/global.css')) ?>">

    <link rel="stylesheet"
          href="<?= h(base_url('assets/css/login.css')) ?>">

</head>

<body class="login">

    <div class="login-wrap">

        <form class="login-form"
      method="POST"
      action=""
      autocomplete="on"
      novalidate
      onsubmit="console.log('FORM BERHASIL DIKIRIM');">

            <h1>Welcome Back !!</h1>

            <p class="tagline">
                Masuk untuk memantau dapur Anda.
            </p>

            <?php if ($error): ?>

                <p class="alert" role="alert">
                    <?= h($error) ?>
                </p>

            <?php endif; ?>

            <?= csrf_field() ?>

            <div class="field">

                <label for="username">Email</label>

                <div class="control">

                    <input
                        id="username"
                        name="username"
                        type="email"
                        inputmode="email"
                        autocomplete="username"
                        placeholder="Example@gmail.com"
                        value="<?= h($_POST['username'] ?? '') ?>"
                        required
                        autofocus
                    >

                </div>

            </div>

            <div class="field">

                <label for="password">Password</label>

                <div class="control">

                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-eye"
                        id="toggleEye"
                        aria-label="Tampilkan password"
                    >

                        <span class="ico-eye">
                            <?= icon('eye', 26) ?>
                        </span>

                        <span class="ico-eye-off" style="display:none">
                            <?= icon('eye-off', 26) ?>
                        </span>

                    </button>

                </div>

                <a href="#" class="forgot" id="forgot">
                    Forget password?
                </a>

                <p class="hint" id="hint">
                    Akun khusus administrator CEKAPI.id.
                    Hubungi administrator jika lupa password.
                </p>

            </div>

            <button type="submit" class="btn-signin">
                Sign In
            </button>

        </form>

        <div class="login-art" aria-hidden="true">

            <div class="circle">

                <img
                    src="<?= h(base_url('assets/img/maskotLogo.png')) ?>"
                    alt=""
                >

            </div>

        </div>

    </div>

    <script src="<?= h(base_url('assets/js/login.js')) ?>"></script>

</body>
</html>
