<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';

$dry = in_array('--dry-run', $argv, true);
[$from, $to] = sync_default_range();
try {
    $s = sync_transactions($from, $to, $dry);
} catch (Throwable $e) {
    fwrite(STDERR, 'sync failed: ' . $e->getMessage() . "\n");
    exit(1);
}
printf("%s %s..%s seen=%d new=%d unchanged=%d removed=%d\n", $dry ? 'dry-run' : 'synced', $from, $to, $s['seen'], $s['inserted'], $s['updated'], $s['removed']);
