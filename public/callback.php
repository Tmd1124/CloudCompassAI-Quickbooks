<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';
require_login();

$state = (string) ($_GET['state'] ?? '');
$expected = (string) ($_SESSION['oauth_state'] ?? '');
unset($_SESSION['oauth_state']);
$code = (string) ($_GET['code'] ?? '');
$realm = (string) ($_GET['realmId'] ?? '');

if ($expected === '' || !hash_equals($expected, $state) || $code === '' || !preg_match('/^\d{5,25}$/', $realm)) {
    http_response_code(400);
    exit('Connection was not completed. Go back and try Connect QuickBooks again.');
}
try {
    Qbo::exchange($code, $realm);
} catch (QboException $e) {
    http_response_code(502);
    exit(h($e->getMessage()));
}
header('Location: /transactions.php');
