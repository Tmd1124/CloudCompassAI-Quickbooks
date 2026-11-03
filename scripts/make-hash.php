<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$p = $argv[1] ?? '';
if (strlen($p) < 12) {
    fwrite(STDERR, "Usage: php scripts/make-hash.php \"password-of-12+-chars\"\n");
    exit(1);
}
echo password_hash($p, PASSWORD_BCRYPT), "\n";
