<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';

$dry = in_array('--dry-run', $argv, true);
$env = null;
foreach ($argv as $a) {
    if (preg_match('/^--env=(sandbox|production)$/', $a, $m)) {
        $env = $m[1];
    }
}
$env ??= (Env::productionReady() && Qbo::connected('production')) ? 'production' : null;
if ($env === null) {
    echo "skipped: production is not ready or not connected (use --env=sandbox to sync the sandbox)\n";
    exit(0);
}
[$from, $to] = sync_default_range();
try {
    $s = sync_transactions($from, $to, $dry, $env);
} catch (Throwable $e) {
    fwrite(STDERR, 'sync failed (' . $env . '): ' . $e->getMessage() . "\n");
    exit(1);
}
printf("%s %s %s..%s seen=%d new=%d unchanged=%d removed=%d\n", $dry ? 'dry-run' : 'synced', $env, $from, $to, $s['seen'], $s['inserted'], $s['updated'], $s['removed']);
