<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';

if (logged_in()) {
    header('Location: /transactions.php');
    exit;
}
$error = '';
$ok = isset($_GET['reset']) ? 'Password updated. Sign in with your new password.' : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (login_locked()) {
        $error = 'Too many attempts. Try again in 15 minutes.';
    } else {
        $good = attempt_login((string) ($_POST['user'] ?? ''), (string) ($_POST['pass'] ?? ''), isset($_POST['remember']));
        login_record($good);
        if ($good) {
            header('Location: /transactions.php');
            exit;
        }
        $error = 'Incorrect username or password.';
        usleep(400000);
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Sign in · CloudCompass Books</title><link rel="stylesheet" href="/assets/style.css"></head>
<body class="login"><main class="card">
<h1>CloudCompass <em>Books</em></h1>
<p class="muted">Sign in to view your QuickBooks reports.</p>
<?php if ($error): ?><div class="notice err" role="alert"><?= h($error) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="notice ok" role="status"><?= h($ok) ?></div><?php endif; ?>
<form method="post" autocomplete="on">
<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<label>Username<input name="user" autocomplete="username" required autofocus></label>
<label>Password<span class="pwfield"><input id="pass" name="pass" type="password" autocomplete="current-password" required><button type="button" class="eye" data-toggle-password="pass" aria-label="Show password" aria-pressed="false">&#128065;&#65039;</button></span></label>
<div class="row"><label class="check"><input type="checkbox" name="remember"> Remember me for 30 days</label><a href="/forgot.php">Forgot password?</a></div>
<button class="btn" type="submit">Sign in</button>
</form>
<p class="muted small"><a href="/privacy.html">Privacy</a> &middot; <a href="/eula.html">Terms</a></p>
</main><script src="/assets/login.js"></script></body></html>
