<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$valid = reset_find($token) !== null;
$error = '';
if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $p1 = (string) ($_POST['pass1'] ?? '');
    $p2 = (string) ($_POST['pass2'] ?? '');
    if (strlen($p1) < 12) {
        $error = 'Use at least 12 characters.';
    } elseif ($p1 !== $p2) {
        $error = 'The two passwords do not match.';
    } elseif (reset_apply($token, $p1)) {
        $_SESSION = [];
        header('Location: /login.php?reset=1');
        exit;
    } else {
        $valid = false;
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Reset password · CloudCompass Books</title><link rel="stylesheet" href="/assets/style.css"></head>
<body class="login"><main class="card">
<h1>Choose a new <em>password</em></h1>
<?php if (!$valid): ?>
<div class="notice err" role="alert">This reset link is invalid or has expired.</div>
<p><a href="/forgot.php">Request a new link</a></p>
<?php else: ?>
<?php if ($error): ?><div class="notice err" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="token" value="<?= h($token) ?>">
<label>New password (12+ characters)<span class="pwfield"><input id="pass1" name="pass1" type="password" autocomplete="new-password" minlength="12" required><button type="button" class="eye" data-toggle-password="pass1" aria-label="Show password" aria-pressed="false">&#128065;&#65039;</button></span></label>
<label>Confirm new password<span class="pwfield"><input id="pass2" name="pass2" type="password" autocomplete="new-password" minlength="12" required><button type="button" class="eye" data-toggle-password="pass2" aria-label="Show password" aria-pressed="false">&#128065;&#65039;</button></span></label>
<button class="btn" type="submit">Update password</button>
</form>
<?php endif; ?>
</main><script src="/assets/login.js"></script></body></html>
