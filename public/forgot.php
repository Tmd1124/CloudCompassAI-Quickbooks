<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ident = trim((string) ($_POST['ident'] ?? ''));
    if ($ident !== '' && strlen($ident) <= 190) {
        reset_request($ident);
    }
    usleep(300000);
    $sent = true;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Forgot password · CloudCompass Books</title><link rel="stylesheet" href="/assets/style.css"></head>
<body class="login"><main class="card">
<h1>Forgot <em>password</em></h1>
<?php if ($sent): ?>
<div class="notice ok" role="status">If that account exists, a reset link has been sent to the email on file. It expires in 1 hour. Check your spam folder if it doesn't arrive.</div>
<p><a href="/login.php">Back to sign in</a></p>
<?php else: ?>
<p class="muted">Enter your username or email. We'll send a reset link to the email on file.</p>
<form method="post">
<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<label>Username or email<input name="ident" autocomplete="username" required autofocus></label>
<button class="btn" type="submit">Send reset link</button>
</form>
<p class="muted small"><a href="/login.php">Back to sign in</a></p>
<?php endif; ?>
</main></body></html>
