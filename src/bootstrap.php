<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

function cfg(string $key, mixed $default = null): mixed
{
    static $c = null;
    if ($c === null) {
        $f = ROOT . '/config/config.php';
        $c = is_file($f) ? (require $f) : [];
    }
    $env = getenv('CCQB_' . strtoupper(str_replace('.', '_', $key)));
    if ($env !== false && $env !== '') {
        return $env;
    }
    $v = $c;
    foreach (explode('.', $key) as $p) {
        if (!is_array($v) || !array_key_exists($p, $v)) {
            return $default;
        }
        $v = $v[$p];
    }
    return $v;
}

function is_demo(): bool
{
    return filter_var(cfg('demo', false), FILTER_VALIDATE_BOOL);
}

function fail_closed(string $msg): never
{
    http_response_code(500);
    error_log('ccqb: ' . $msg);
    exit('Server is not configured. See the setup steps in docs/superpowers/specs.');
}

function need(string $key): string
{
    $v = (string) cfg($key, '');
    if ($v === '' || str_contains($v, 'REPLACE_ME')) {
        fail_closed("missing config: $key");
    }
    return $v;
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

require __DIR__ . '/db.php';
require __DIR__ . '/mail.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/env.php';
require __DIR__ . '/qbo.php';
require __DIR__ . '/report.php';
require __DIR__ . '/sync.php';
require __DIR__ . '/layout.php';

if (PHP_SAPI !== 'cli') {
    session_name('ccqb');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => is_https(),
        'path' => '/',
    ]);
    session_start();

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; frame-ancestors 'none'");
    header('Cache-Control: no-store');

    if (!logged_in() && isset($_COOKIE[REMEMBER_COOKIE])) {
        remember_try();
    }
}
