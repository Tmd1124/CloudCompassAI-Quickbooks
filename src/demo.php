<?php
declare(strict_types=1);

function d_data(string $l, string $a): array
{
    return ['type' => 'Data', 'ColData' => [['value' => $l], ['value' => $a]]];
}

function d_sec(string $name, array $rows, string $totalLabel, string $total): array
{
    return ['type' => 'Section', 'Header' => ['ColData' => [['value' => $name], ['value' => '']]], 'Rows' => ['Row' => $rows], 'Summary' => ['ColData' => [['value' => $totalLabel], ['value' => $total]]]];
}

function d_total(string $l, string $a): array
{
    return ['type' => 'Section', 'Summary' => ['ColData' => [['value' => $l], ['value' => $a]]]];
}

function demo_report(string $name, array $p): array
{
    if ($name === 'ProfitAndLoss') {
        return ['Rows' => ['Row' => [
            d_sec('Income', [d_data('Consulting', '48200.00'), d_data('Retainers', '12000.00')], 'Total Income', '60200.00'),
            d_sec('Expenses', [d_data('Software & Subscriptions', '3150.40'), d_data('Insurance', '1800.00'), d_data('Meals', '920.15'), d_data('Contractors', '14500.00')], 'Total Expenses', '20370.55'),
            d_total('Net Income', '39829.45'),
        ]]];
    }
    if ($name === 'BalanceSheet') {
        return ['Rows' => ['Row' => [
            d_sec('ASSETS', [d_sec('Bank Accounts', [d_data('Chase Business Checking', '28450.12'), d_data('Chase Personal Checking', '9120.55')], 'Total Bank Accounts', '37570.67')], 'TOTAL ASSETS', '37570.67'),
            d_sec('LIABILITIES AND EQUITY', [
                d_sec('Liabilities', [d_data('Chase Ink Card', '2210.30')], 'Total Liabilities', '2210.30'),
                d_sec('Equity', [d_data("Owner's Equity", '20000.00'), d_data('Net Income', '15360.37')], 'Total Equity', '35360.37'),
            ], 'TOTAL LIABILITIES AND EQUITY', '37570.67'),
        ]]];
    }
    $cols = ['Date', 'Transaction Type', 'Num', 'Posting', 'Name', 'Memo/Description', 'Account', 'Split', 'Amount'];
    $t = [
        ['2026-10-30', 'Deposit', '', 'Yes', 'Northwind Retainer', 'ACH credit', 'Chase Business Checking', 'Retainers', '4000.00'],
        ['2026-10-28', 'Expense', '', 'Yes', 'Microsoft Azure', 'Azure usage', 'Chase Business Checking', 'Software & Subscriptions', '-412.18'],
        ['2026-10-28', 'Expense', '', 'Yes', 'Microsoft Azure', 'Azure usage', 'Chase Business Checking', 'Software & Subscriptions', '-412.18'],
        ['2026-10-25', 'Expense', '', 'Yes', 'State Farm', 'Insurance premium', 'Chase Business Checking', 'Insurance', '-300.00'],
        ['2026-10-22', 'Deposit', '', 'Yes', 'Contoso Ltd', 'Invoice 1042', 'Chase Business Checking', 'Consulting', '9600.00'],
        ['2026-10-20', 'Transfer', '', 'Yes', '', 'To personal', 'Chase Business Checking', 'Chase Personal Checking', '-3000.00'],
        ['2026-10-20', 'Transfer', '', 'Yes', '', 'From business', 'Chase Personal Checking', 'Chase Business Checking', '3000.00'],
        ['2026-10-15', 'Expense', '', 'Yes', 'Contractor - R. Diaz', 'October work', 'Chase Business Checking', 'Contractors', '-2900.00'],
        ['2026-10-12', 'Expense', '', 'Yes', 'Duke Energy', 'Utilities', 'Chase Personal Checking', 'Utilities', '-186.40'],
    ];
    $rows = array_map(fn($r) => ['type' => 'Data', 'ColData' => array_map(fn($v) => ['value' => $v], $r)], $t);
    return ['Columns' => ['Column' => array_map(fn($c) => ['ColTitle' => $c], $cols)], 'Rows' => ['Row' => $rows]];
}
