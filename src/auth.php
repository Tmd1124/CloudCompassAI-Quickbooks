<?php
declare(strict_types=1);

function logged_in(): bool
{
    return !empty($_SESSION['uid']);
}

function require_login(): void
{
    if (!logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_check(): void
{
    $t = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals(csrf_token(), $t)) {
        http_response_code(400);
        exit('Bad request.');
    }
}

function attempts_file(): string
{
    return ROOT . '/storage/login_attempts.json';
}

function attempts_load(): array
{
    $f = attempts_file();
    $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];
    return is_array($d) ? $d : [];
}

function login_locked(): bool
{
    $k = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
    $a = attempts_load()[$k] ?? null;
    return $a && $a['n'] >= 5 && time() - $a['t'] < 900;
}

function login_record(bool $ok): void
{
    $k = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
    $all = attempts_load();
    foreach ($all as $key => $a) {
        if (time() - $a['t'] > 900) {
            unset($all[$key]);
        }
    }
    if ($ok) {
        unset($all[$k]);
    } else {
        $all[$k] = ['n' => ($all[$k]['n'] ?? 0) + 1, 't' => time()];
    }
    file_put_contents(attempts_file(), json_encode($all), LOCK_EX);
}

function attempt_login(string $user, string $pass): bool
{
    $hash = need('admin_password_hash');
    $u = need('admin_user');
    $okUser = hash_equals($u, $user);
    $okPass = password_verify($pass, $hash);
    if ($okUser && $okPass) {
        session_regenerate_id(true);
        $_SESSION['uid'] = 1;
        return true;
    }
    return false;
}
