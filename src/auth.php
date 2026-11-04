<?php
declare(strict_types=1);

const REMEMBER_COOKIE = 'ccqb_rm';
const REMEMBER_DAYS = 30;

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

function ensure_admin(): void
{
    $db = Db::pdo();
    if ((int) $db->query('SELECT COUNT(*) FROM ccqb_users')->fetchColumn() > 0) {
        return;
    }
    $db->prepare('INSERT INTO ccqb_users (username, email, password_hash, created_at) VALUES (?, ?, ?, ?)')
        ->execute([need('admin_user'), need('admin_email'), need('admin_password_hash'), now_utc()]);
}

function attempt_login(string $user, string $pass, bool $remember): bool
{
    ensure_admin();
    $st = Db::pdo()->prepare('SELECT id, password_hash FROM ccqb_users WHERE username = ?');
    $st->execute([$user]);
    $row = $st->fetch();
    $hash = $row['password_hash'] ?? password_hash('no-such-user', PASSWORD_BCRYPT);
    if (password_verify($pass, $hash) && $row) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $row['id'];
        if ($remember) {
            remember_issue((int) $row['id']);
        }
        return true;
    }
    return false;
}

function cookie_set(string $name, string $value, int $expires): void
{
    setcookie($name, $value, ['expires' => $expires, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
}

function remember_issue(int $uid): void
{
    $sel = bin2hex(random_bytes(12));
    $val = bin2hex(random_bytes(32));
    $exp = time() + REMEMBER_DAYS * 86400;
    Db::pdo()->prepare('INSERT INTO ccqb_remember (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)')
        ->execute([$uid, $sel, hash('sha256', $val), gmdate('Y-m-d H:i:s', $exp)]);
    cookie_set(REMEMBER_COOKIE, "$sel:$val", $exp);
}

function remember_try(): void
{
    if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', (string) ($_COOKIE[REMEMBER_COOKIE] ?? ''), $m)) {
        cookie_set(REMEMBER_COOKIE, '', time() - 3600);
        return;
    }
    $db = Db::pdo();
    $st = $db->prepare('SELECT user_id, token_hash, expires_at FROM ccqb_remember WHERE selector = ?');
    $st->execute([$m[1]]);
    $row = $st->fetch();
    if (!$row || $row['expires_at'] < now_utc()) {
        $db->prepare('DELETE FROM ccqb_remember WHERE selector = ?')->execute([$m[1]]);
        cookie_set(REMEMBER_COOKIE, '', time() - 3600);
        return;
    }
    if (!hash_equals($row['token_hash'], hash('sha256', $m[2]))) {
        remember_purge_user((int) $row['user_id']);
        cookie_set(REMEMBER_COOKIE, '', time() - 3600);
        return;
    }
    $db->prepare('DELETE FROM ccqb_remember WHERE selector = ?')->execute([$m[1]]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $row['user_id'];
    remember_issue((int) $row['user_id']);
}

function remember_forget(): void
{
    if (preg_match('/^([a-f0-9]{24}):/', (string) ($_COOKIE[REMEMBER_COOKIE] ?? ''), $m)) {
        Db::pdo()->prepare('DELETE FROM ccqb_remember WHERE selector = ?')->execute([$m[1]]);
    }
    cookie_set(REMEMBER_COOKIE, '', time() - 3600);
}

function remember_purge_user(int $uid): void
{
    Db::pdo()->prepare('DELETE FROM ccqb_remember WHERE user_id = ?')->execute([$uid]);
}

function reset_request(string $ident): void
{
    ensure_admin();
    $db = Db::pdo();
    $st = $db->prepare('SELECT id, email FROM ccqb_users WHERE username = ? OR LOWER(email) = LOWER(?)');
    $st->execute([$ident, $ident]);
    $u = $st->fetch();
    if (!$u) {
        return;
    }
    $cnt = $db->prepare('SELECT COUNT(*) FROM ccqb_resets WHERE user_id = ? AND created_at > ?');
    $cnt->execute([$u['id'], gmdate('Y-m-d H:i:s', time() - 3600)]);
    if ((int) $cnt->fetchColumn() >= 3) {
        return;
    }
    $tok = bin2hex(random_bytes(32));
    $db->prepare('INSERT INTO ccqb_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$u['id'], hash('sha256', $tok), gmdate('Y-m-d H:i:s', time() + 3600), now_utc()]);
    $link = rtrim(need('base_url'), '/') . '/reset.php?token=' . $tok;
    $body = "Someone asked to reset the password for CloudCompass Books.\n\nOpen this link within 1 hour to choose a new password:\n$link\n\nIf you did not ask for this, ignore this message; your password has not changed.\n";
    if (!send_mail($u['email'], 'Reset your CloudCompass Books password', $body)) {
        error_log('ccqb: reset mail failed');
    }
}

function reset_find(string $tok): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $tok)) {
        return null;
    }
    $st = Db::pdo()->prepare('SELECT id, user_id FROM ccqb_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?');
    $st->execute([hash('sha256', $tok), now_utc()]);
    return $st->fetch() ?: null;
}

function reset_apply(string $tok, string $newPassword): bool
{
    $r = reset_find($tok);
    if (!$r) {
        return false;
    }
    $db = Db::pdo();
    $db->prepare('UPDATE ccqb_users SET password_hash = ? WHERE id = ?')->execute([password_hash($newPassword, PASSWORD_BCRYPT), $r['user_id']]);
    $db->prepare('UPDATE ccqb_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL')->execute([now_utc(), $r['user_id']]);
    remember_purge_user((int) $r['user_id']);
    return true;
}
