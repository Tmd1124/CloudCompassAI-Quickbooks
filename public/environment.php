<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $to = (string) ($_POST['env'] ?? '');
    if ($to === 'sandbox') {
        Env::setPref('sandbox');
        $_SESSION['flash'] = ['ok', 'Switched to Sandbox (test data).'];
    } elseif ($to === 'production' && !Env::productionReady()) {
        $_SESSION['flash'] = ['err', 'Production keys are not ready yet.'];
    } elseif ($to === 'production' && empty($_POST['confirm'])) {
        $_SESSION['flash'] = ['err', 'Tick the box to confirm you want to view your real books.'];
    } elseif ($to === 'production') {
        Env::setPref('production');
        $_SESSION['flash'] = ['ok', 'Switched to Production (your real books).'];
    }
    header('Location: /environment.php');
    exit;
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$active = Env::active();
$prodReady = Env::productionReady();

page_start('Environment');
?>
<h1>Environment</h1>
<?php if ($flash): ?><div class="notice <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= h($flash[1]) ?></div><?php endif; ?>
<?php if (is_demo()): ?><div class="notice">Demo mode is on, so the environment switch is disabled.</div><?php else: ?>
<div class="envgrid">
  <section class="envcard<?= $active === 'sandbox' ? ' current' : '' ?>">
    <h2>Sandbox <?= $active === 'sandbox' ? '<span class="tag">Active</span>' : '' ?></h2>
    <p class="muted">Intuit's fake test company. Safe for trying things out. It contains none of your Chase transactions.</p>
    <p>Keys: <?= Env::keysReady('sandbox') ? 'configured' : 'not configured' ?> &middot; QuickBooks: <?= Qbo::connected('sandbox') ? 'connected' : 'not connected' ?></p>
    <?php if ($active !== 'sandbox'): ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="env" value="sandbox"><button class="btn secondary">Switch to Sandbox</button></form>
    <?php endif; ?>
  </section>
  <section class="envcard<?= $active === 'production' ? ' current' : '' ?>">
    <h2>Production <?= $active === 'production' ? '<span class="tag">Active</span>' : '' ?></h2>
    <p class="muted">Your real QuickBooks company, including the Chase transactions.</p>
    <p>Keys: <?= $prodReady ? 'configured' : '<strong>not ready yet</strong>' ?> &middot; QuickBooks: <?= $prodReady && Qbo::connected('production') ? 'connected' : 'not connected' ?></p>
    <?php if (!$prodReady): ?>
      <p class="muted">Production keys come from Intuit once the app is approved. Until then the app stays on Sandbox.</p>
    <?php elseif ($active !== 'production'): ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="env" value="production">
      <label class="check"><input type="checkbox" name="confirm" value="1"> I understand this shows my real books</label>
      <button class="btn">Switch to Production</button></form>
    <?php endif; ?>
  </section>
</div>
<p class="muted">Each environment keeps its own QuickBooks connection and its own stored transactions, so they never mix. Connect each one from the <a href="/connect.php">connection page</a> while it is active.</p>
<?php endif; ?>
<?php page_end();
