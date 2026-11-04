<?php
declare(strict_types=1);

function sync_default_range(): array
{
    return [(string) cfg('sync_from', date('Y-01-01', strtotime('-1 year'))), date('Y-m-d')];
}

function sync_transactions(string $from, string $to, bool $dry = false, ?string $env = null): array
{
    $env ??= Env::active();
    $rows = transaction_list(Qbo::report('TransactionList', ['start_date' => $from, 'end_date' => $to], $env));
    $stats = ['seen' => count($rows), 'inserted' => 0, 'updated' => 0, 'removed' => 0];
    if ($dry) {
        return $stats;
    }
    $db = Db::pdo();
    $db->prepare('INSERT INTO ccqb_syncs (started_at, range_from, range_to, env) VALUES (?, ?, ?, ?)')->execute([now_utc(), $from, $to, $env]);
    $run = (int) $db->lastInsertId();
    $prefix = $env === 'sandbox' ? '' : $env . '|';
    try {
        $db->beginTransaction();
        $occ = [];
        $now = now_utc();
        $find = $db->prepare('SELECT id FROM ccqb_transactions WHERE row_key = ?');
        $upd = $db->prepare('UPDATE ccqb_transactions SET seen_run = ?, last_seen_at = ?, removed_at = NULL WHERE id = ?');
        $ins = $db->prepare('INSERT INTO ccqb_transactions (row_key, qbo_id, txn_date, txn_type, doc_num, name, memo, account, split, amount, seen_run, first_seen_at, last_seen_at, env) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($rows as $r) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $r['date'])) {
                continue;
            }
            $base = $prefix . implode('|', [$r['qbo_id'], $r['date'], $r['type'], $r['num'], $r['name'], $r['memo'], $r['account'], $r['split'], $r['amount']]);
            $occ[$base] = ($occ[$base] ?? 0) + 1;
            $key = hash('sha256', $base . '|' . $occ[$base]);
            $find->execute([$key]);
            $id = $find->fetchColumn();
            if ($id) {
                $upd->execute([$run, $now, $id]);
                $stats['updated']++;
            } else {
                $ins->execute([$key, $r['qbo_id'], $r['date'], $r['type'], $r['num'], $r['name'], $r['memo'], $r['account'], $r['split'], (float) $r['amount'], $run, $now, $now, $env]);
                $stats['inserted']++;
            }
        }
        if ($stats['seen'] > 0) {
            $rm = $db->prepare('UPDATE ccqb_transactions SET removed_at = ? WHERE env = ? AND txn_date BETWEEN ? AND ? AND seen_run <> ? AND removed_at IS NULL');
            $rm->execute([$now, $env, $from, $to, $run]);
            $stats['removed'] = $rm->rowCount();
        }
        $db->commit();
        $db->prepare("UPDATE ccqb_syncs SET finished_at = ?, seen = ?, inserted = ?, updated = ?, removed = ?, status = 'ok' WHERE id = ?")
            ->execute([now_utc(), $stats['seen'], $stats['inserted'], $stats['updated'], $stats['removed'], $run]);
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $db->prepare("UPDATE ccqb_syncs SET finished_at = ?, status = 'error', error = ? WHERE id = ?")
            ->execute([now_utc(), substr($e->getMessage(), 0, 250), $run]);
        throw $e;
    }
    return $stats;
}

function stored_transactions(string $from, string $to): array
{
    $st = Db::pdo()->prepare('SELECT qbo_id, env, txn_date AS date, txn_type AS type, doc_num AS num, name, memo, account, split, amount FROM ccqb_transactions WHERE env = ? AND removed_at IS NULL AND txn_date BETWEEN ? AND ? ORDER BY txn_date DESC, id DESC');
    $st->execute([Env::active(), $from, $to]);
    return mark_dups($st->fetchAll());
}

function last_sync(): ?array
{
    $st = Db::pdo()->prepare("SELECT * FROM ccqb_syncs WHERE env = ? AND status = 'ok' ORDER BY id DESC LIMIT 1");
    $st->execute([Env::active()]);
    return $st->fetch() ?: null;
}

function purge_transactions(?string $env = null): void
{
    $env ??= Env::active();
    $db = Db::pdo();
    $db->prepare('DELETE FROM ccqb_transactions WHERE env = ?')->execute([$env]);
    $db->prepare('DELETE FROM ccqb_syncs WHERE env = ?')->execute([$env]);
}
