<?php
/** @var array $lines */
?>
<div class="tablewrap"><table class="statement">
  <tbody>
  <?php foreach ($lines as $l): ?>
    <tr class="<?= h($l['kind']) ?> d<?= min((int) $l['depth'], 3) ?>">
      <td><?= h($l['label']) ?></td>
      <td class="num <?= (float) $l['amount'] < 0 ? 'neg' : '' ?>"><?= h(money($l['amount'])) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$lines): ?><tr><td class="muted">No data for this period.</td><td></td></tr><?php endif; ?>
  </tbody>
</table></div>
