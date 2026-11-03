<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';
require_login();

$from = valid_date($_GET['from'] ?? null, date('Y-01-01'));
$to = valid_date($_GET['to'] ?? null, date('Y-m-d'));
$method = ($_GET['method'] ?? 'Accrual') === 'Cash' ? 'Cash' : 'Accrual';

$rep = load_or_error('ProfitAndLoss', ['start_date' => $from, 'end_date' => $to, 'accounting_method' => $method]);
$lines = $rep ? statement_lines($rep) : [];

page_start('Profit & Loss', 'pnl');
?>
<h1>Profit &amp; Loss</h1>
<form class="filters" method="get">
  <label>From<input type="date" name="from" value="<?= h($from) ?>"></label>
  <label>To<input type="date" name="to" value="<?= h($to) ?>"></label>
  <label>Basis<select name="method"><option<?= $method === 'Accrual' ? ' selected' : '' ?>>Accrual</option><option<?= $method === 'Cash' ? ' selected' : '' ?>>Cash</option></select></label>
  <button class="btn" type="submit">Apply</button>
</form>
<?php show_qbo_error(); ?>
<?php if ($rep): ?>
<p class="muted"><?= h($from) ?> to <?= h($to) ?> · <?= h($method) ?> basis</p>
<?php include ROOT . '/src/statement.php'; ?>
<?php endif; ?>
<?php page_end();
