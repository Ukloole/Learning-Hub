<?php
/**
 * UKLOOLE — Shared Security Helper
 * Include at the top of any PHP entry point.
 *  - Hardens session cookies (HttpOnly, SameSite, Secure when HTTPS)
 *  - Sends sensible default security headers
 *  - Provides csrf_token() and csrf_check() helpers
 *  - Provides safe_request_ip() helper
 *
 * NOTE: This file does NOT change any visible UI or wording.
 */

if (!defined('UKLOOLE_SECURITY_LOADED')) {
    define('UKLOOLE_SECURITY_LOADED', true);

    // ---- Session hardening (must be set BEFORE session_start) ----
    if (session_status() === PHP_SESSION_NONE) {
        $is_https = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (($_SERVER['SERVER_PORT'] ?? null) == 443) ||
            (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        );
        @session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $is_https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
    }

    // ---- HTTP security headers ----
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        // HSTS — only when actually on HTTPS
        if (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        ) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

/** Generate or fetch the per-session CSRF token. */
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Render a hidden CSRF input. */
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/** Validate the submitted CSRF token (timing-safe). Returns bool. */
function csrf_check(?string $token = null): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $token = $token ?? ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($_SESSION['_csrf']) && is_string($token) &&
           hash_equals($_SESSION['_csrf'], $token);
}

/** Best-effort client IP (handles common proxy headers). */
function safe_request_ip(): string {
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP']   ?? null,
        $_SERVER['HTTP_X_FORWARDED_FOR']    ?? null,
        $_SERVER['REMOTE_ADDR']             ?? null,
    ];
    foreach ($candidates as $c) {
        if (!$c) continue;
        $ip = trim(explode(',', $c)[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
    return '';
}
