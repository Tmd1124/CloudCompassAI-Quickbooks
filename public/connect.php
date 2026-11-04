<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';
require_login();

$env = Env::active();
$label = $env === 'production' ? 'Production' : 'Sandbox';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (is_demo()) {
        header('Location: /connect.php');
        exit;
    }
    if (($_POST['action'] ?? '') === 'disconnect') {
        purge_transactions($env);
        Qbo::disconnect($env);
        header('Location: /connect.php');
        exit;
    }
    $_SESSION['oauth_state'] = bin2hex(random_bytes(24));
    $_SESSION['oauth_env'] = $env;
    header('Location: ' . Qbo::authUrl($env, $_SESSION['oauth_state']));
    exit;
}

page_start('QuickBooks connection');
echo '<h1>QuickBooks connection</h1>';
if (is_demo()) {
    echo '<div class="notice">Demo mode is on. The app shows sample data and is not talking to QuickBooks. Set <code>demo</code> to <code>false</code> in config to connect.</div>';
} else {
    echo '<p>Environment: <strong>' . h($label) . '</strong> (<a href="/environment.php">change</a>)</p>';
    if (Qbo::connected($env)) {
        echo '<p>Connected to QuickBooks company <code>' . h(Qbo::realm($env)) . '</code>.</p>';
        echo '<p class="muted">Disconnecting revokes the app\'s access at Intuit and deletes the ' . h(strtolower($label)) . ' transactions stored in the database.</p>';
        echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="action" value="disconnect"><button class="btn secondary">Disconnect ' . h($label) . '</button></form>';
    } else {
        echo '<p>Not connected. You will sign in at Intuit and approve read access to your ' . ($env === 'sandbox' ? 'sandbox test company' : 'company') . '.</p>';
        echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><button class="btn">Connect QuickBooks (' . h($label) . ')</button></form>';
    }
}
page_end();
