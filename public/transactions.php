<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';
require_login();

$from = valid_date($_GET['from'] ?? null, date('Y-01-01'));
$to = valid_date($_GET['to'] ?? null, date('Y-m-d'));
$acct = (string) ($_GET['account'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$dupOnly = isset($_GET['dups']);

$rows = [];
$rep = load_or_error('TransactionList', ['start_date' => $from, 'end_date' => $to]);
if ($rep) {
    $rows = transaction_list($rep);
}
$accounts = array_values(array_unique(array_filter(array_column($rows, 'account'))));
sort($accounts);
$dupCount = count(array_filter($rows, fn($r) => $r['dup']));

$view = array_filter($rows, function ($r) use ($acct, $q, $dupOnly) {
    if ($acct !== '' && $r['account'] !== $acct) {
        return false;
    }
    if ($dupOnly && !$r['dup']) {
        return false;
    }
    return $q === '' || stripos($r['name'] . ' ' . $r['memo'] . ' ' . $r['split'], $q) !== false;
});

page_start('Transactions', 'transactions');
?>
<h1>Transactions</h1>
<form class="filters" method="get">
  <label>From<input type="date" name="from" value="<?= h($from) ?>"></label>
  <label>To<input type="date" name="to" value="<?= h($to) ?>"></label>
  <label>Account<select name="account"><option value="">All accounts</option>
    <?php foreach ($accounts as $a): ?><option<?= $a === $acct ? ' selected' : '' ?>><?= h($a) ?></option><?php endforeach; ?>
  </select></label>
  <label>Search<input type="search" name="q" value="<?= h($q) ?>" placeholder="Name or memo"></label>
  <label class="check"><input type="checkbox" name="dups" <?= $dupOnly ? 'checked' : '' ?>> Possible duplicates only</label>
  <button class="btn" type="submit">Apply</button>
</form>
<?php show_qbo_error(); ?>
<?php if ($rep): ?>
<p class="muted"><?= count($view) ?> of <?= count($rows) ?> transactions<?php if ($dupCount): ?> · <strong class="dupnote"><?= $dupCount ?> flagged as possible duplicates</strong> (same date, amount, name, account and type)<?php endif; ?></p>
<div class="tablewrap"><table>
  <thead><tr><th>Date</th><th>Type</th><th>Name</th><th>Memo</th><th>Account</th><th>Category / Split</th><th class="num">Amount</th></tr></thead>
  <tbody>
  <?php foreach ($view as $r): ?>
    <tr<?= $r['dup'] ? ' class="dup"' : '' ?>>
      <td><?= h($r['date']) ?></td><td><?= h($r['type']) ?></td>
      <td><?= h($r['name']) ?><?= $r['dup'] ? ' <span class="tag">Possible duplicate</span>' : '' ?></td>
      <td><?= h($r['memo']) ?></td><td><?= h($r['account']) ?></td><td><?= h($r['split']) ?></td>
      <td class="num <?= (float) $r['amount'] < 0 ? 'neg' : '' ?>"><?= h(money($r['amount'])) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$view): ?><tr><td colspan="7" class="muted">No transactions match.</td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php page_end();
