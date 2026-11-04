<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        [$sf, $st] = sync_default_range();
        $s = sync_transactions($sf, $st);
        $_SESSION['flash'] = ['ok', "Synced {$s['seen']} transactions: {$s['inserted']} new, {$s['updated']} unchanged, {$s['removed']} removed in QuickBooks."];
    } catch (QboException $e) {
        $_SESSION['flash'] = ['err', 'Sync failed: ' . $e->getMessage()];
        $_SESSION['flash_connect'] = $e->needsConnect;
    } catch (Throwable $e) {
        error_log('ccqb sync: ' . $e->getMessage());
        $_SESSION['flash'] = ['err', 'Sync failed. See the server error log.'];
    }
    header('Location: /transactions.php');
    exit;
}
$flash = $_SESSION['flash'] ?? null;
$flashConnect = !empty($_SESSION['flash_connect']);
unset($_SESSION['flash'], $_SESSION['flash_connect']);

$from = valid_date($_GET['from'] ?? null, date('Y-01-01'));
$to = valid_date($_GET['to'] ?? null, date('Y-m-d'));
$acct = (string) ($_GET['account'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$dupOnly = isset($_GET['dups']);

$rows = stored_transactions($from, $to);
$last = last_sync();
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
<?php if ($flash): ?><div class="notice <?= $flash[0] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= h($flash[1]) ?><?= $flashConnect ? ' <a href="/connect.php">Connect QuickBooks</a>' : '' ?></div><?php endif; ?>
<div class="syncbar">
  <span class="muted"><?= $last ? 'Last synced ' . h($last['finished_at']) . ' UTC' : 'Not synced yet' ?></span>
  <form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="btn secondary" type="submit">Sync now</button></form>
</div>
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
<p class="muted"><?= count($view) ?> of <?= count($rows) ?> stored transactions<?php if ($dupCount): ?> · <strong class="dupnote"><?= $dupCount ?> flagged as possible duplicates</strong> (same date, amount, name, account and type)<?php endif; ?></p>
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
  <?php if (!$view): ?><tr><td colspan="7" class="muted"><?= $last ? 'No transactions match.' : 'Nothing stored yet. Use Sync now to load transactions from QuickBooks.' ?></td></tr><?php endif; ?>
  </tbody>
</table></div>
<?php page_end();
