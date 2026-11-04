<?php
declare(strict_types=1);

function valid_date(?string $d, string $fallback): string
{
    return ($d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false) ? $d : $fallback;
}

function money(float|string|null $v): string
{
    if ($v === null || $v === '') {
        return '';
    }
    $n = (float) $v;
    $s = number_format(abs($n), 2);
    return $n < 0 ? "($s)" : $s;
}

function flatten_rows(array $rows, int $depth = 0): array
{
    $out = [];
    foreach ($rows as $row) {
        $hdr = $row['Header']['ColData'][0]['value'] ?? null;
        if (isset($row['ColData'])) {
            $out[] = ['label' => $row['ColData'][0]['value'] ?? '', 'amount' => $row['ColData'][1]['value'] ?? '', 'depth' => $depth, 'kind' => 'data'];
        }
        if ($hdr !== null && $hdr !== '') {
            $out[] = ['label' => $hdr, 'amount' => '', 'depth' => $depth, 'kind' => 'header'];
        }
        if (isset($row['Rows']['Row'])) {
            $out = array_merge($out, flatten_rows($row['Rows']['Row'], $hdr !== null && $hdr !== '' ? $depth + 1 : $depth));
        }
        if (isset($row['Summary']['ColData'])) {
            $out[] = ['label' => $row['Summary']['ColData'][0]['value'] ?? '', 'amount' => $row['Summary']['ColData'][1]['value'] ?? '', 'depth' => $depth, 'kind' => 'total'];
        }
    }
    return $out;
}

function statement_lines(array $report): array
{
    return flatten_rows($report['Rows']['Row'] ?? []);
}

function transaction_list(array $report): array
{
    $cols = [];
    $map = ['date' => 'date', 'transaction type' => 'type', 'num' => 'num', 'name' => 'name', 'memo/description' => 'memo', 'account' => 'account', 'split' => 'split', 'amount' => 'amount'];
    foreach (($report['Columns']['Column'] ?? []) as $i => $c) {
        $t = strtolower($c['ColTitle'] ?? '');
        if (isset($map[$t])) {
            $cols[$map[$t]] = $i;
        }
    }
    $rows = [];
    foreach (($report['Rows']['Row'] ?? []) as $r) {
        if (!isset($r['ColData'])) {
            continue;
        }
        $row = [];
        foreach (['date', 'type', 'num', 'name', 'memo', 'account', 'split', 'amount'] as $k) {
            $row[$k] = isset($cols[$k]) ? (string) ($r['ColData'][$cols[$k]]['value'] ?? '') : '';
        }
        $row['qbo_id'] = isset($cols['date']) ? (string) ($r['ColData'][$cols['date']]['id'] ?? '') : '';
        $rows[] = $row;
    }
    return $rows;
}

function mark_dups(array $rows): array
{
    $seen = [];
    foreach ($rows as $row) {
        $k = dup_key($row);
        $seen[$k] = ($seen[$k] ?? 0) + 1;
    }
    foreach ($rows as &$row) {
        $row['dup'] = $seen[dup_key($row)] > 1;
    }
    unset($row);
    return $rows;
}

function dup_key(array $r): string
{
    return implode('|', [$r['date'], abs((float) $r['amount']), strtolower($r['name']), $r['account'], $r['type']]);
}

function load_or_error(string $name, array $params): ?array
{
    try {
        return Qbo::report($name, $params);
    } catch (QboException $e) {
        $GLOBALS['qbo_error'] = $e;
        return null;
    }
}
