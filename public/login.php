<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';

if (logged_in()) {
    header('Location: /transactions.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (login_locked()) {
        $error = 'Too many attempts. Try again in 15 minutes.';
    } else {
        $ok = attempt_login((string) ($_POST['user'] ?? ''), (string) ($_POST['pass'] ?? ''));
        login_record($ok);
        if ($ok) {
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
<form method="post" autocomplete="on">
<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<label>Username<input name="user" autocomplete="username" required autofocus></label>
<label>Password<input name="pass" type="password" autocomplete="current-password" required></label>
<button class="btn" type="submit">Sign in</button>
</form><p class="muted"><a href="/privacy.html">Privacy</a> &middot; <a href="/eula.html">Terms</a></p></main></body></html>
