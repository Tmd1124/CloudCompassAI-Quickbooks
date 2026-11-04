<?php
declare(strict_types=1);

function page_start(string $title, string $active = ''): void
{
    $nav = ['transactions' => ['Transactions', '/transactions.php'], 'pnl' => ['Profit & Loss', '/pnl.php'], 'balance' => ['Balance Sheet', '/balance.php']];
    $demo = is_demo();
    $env = Env::active();
    if ($demo) {
        $status = '<span class="pill demo">Demo data</span>';
    } else {
        $conn = Qbo::connected($env) ? '<span class="pill ok">QuickBooks connected</span>' : '<span class="pill warn">Not connected</span>';
        $status = '<a class="pill env ' . $env . '" href="/environment.php">' . ($env === 'production' ? 'Production' : 'Sandbox') . '</a>' . $conn;
    }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">';
    echo '<title>' . h($title) . ' · CloudCompass Books</title><link rel="stylesheet" href="/assets/style.css"></head><body>';
    if (!$demo && $env === 'sandbox') {
        echo '<div class="envbanner" role="status"><strong>SANDBOX</strong> &mdash; test data, not your real books. '
            . (Env::productionReady() ? '<a href="/environment.php">Switch to production</a>' : 'Production keys are not ready yet.') . '</div>';
    }
    echo '<header class="top"><div class="wrap bar"><a class="brand" href="/">CloudCompass <em>Books</em></a><nav aria-label="Main">';
    foreach ($nav as $k => [$label, $href]) {
        echo '<a href="' . $href . '"' . ($k === $active ? ' aria-current="page"' : '') . '>' . h($label) . '</a>';
    }
    echo '</nav><div class="right">' . $status;
    echo '<form method="post" action="/logout.php"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><button class="link">Sign out</button></form></div></div></header><main class="wrap">';
}

function page_end(): void
{
    echo '</main><footer class="wrap foot">Read-only view of your QuickBooks Online data. Transactions arrive through the QuickBooks Chase bank feed. &middot; <a href="/privacy.html">Privacy</a> &middot; <a href="/eula.html">Terms</a></footer></body></html>';
}

function show_qbo_error(): void
{
    $e = $GLOBALS['qbo_error'] ?? null;
    if (!$e) {
        return;
    }
    echo '<div class="notice err" role="alert"><strong>Could not load from QuickBooks.</strong> ' . h($e->getMessage());
    if ($e->needsConnect) {
        echo ' <a href="/connect.php">Connect QuickBooks</a>';
    }
    echo '</div>';
}

function env_badge(?string $env = null): string
{
    if (is_demo()) {
        return '<span class="envtag demo">Demo</span>';
    }
    $env ??= Env::active();
    return '<span class="envtag ' . h($env) . '">' . ($env === 'production' ? 'Production' : 'Sandbox') . '</span>';
}
