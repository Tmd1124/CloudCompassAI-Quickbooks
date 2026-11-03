<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';
require_login();

$to = valid_date($_GET['to'] ?? null, date('Y-m-d'));

$rep = load_or_error('BalanceSheet', ['end_date' => $to]);
$lines = $rep ? statement_lines($rep) : [];

page_start('Balance Sheet', 'balance');
?>
<h1>Balance Sheet</h1>
<form class="filters" method="get">
  <label>As of<input type="date" name="to" value="<?= h($to) ?>"></label>
  <button class="btn" type="submit">Apply</button>
</form>
<?php show_qbo_error(); ?>
<?php if ($rep): ?>
<p class="muted">As of <?= h($to) ?></p>
<?php include ROOT . '/src/statement.php'; ?>
<?php endif; ?>
<?php page_end();
