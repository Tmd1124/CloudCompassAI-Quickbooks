<?php
declare(strict_types=1);
require (is_file(__DIR__ . '/src/bootstrap.php') ? __DIR__ : dirname(__DIR__)) . '/src/bootstrap.php';

header('Location: ' . (logged_in() ? '/transactions.php' : '/login.php'));
