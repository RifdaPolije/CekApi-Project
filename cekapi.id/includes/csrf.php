<?php
/**
 * Token CSRF (form biasa + header untuk fetch)
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid($token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/** Untuk endpoint API yang dipanggil JS: token dikirim lewat header X-CSRF-Token */
function csrf_require_api(): void
{
    if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        json_response(['ok' => false, 'error' => 'Token CSRF tidak valid. Muat ulang halaman.'], 403);
    }
}
