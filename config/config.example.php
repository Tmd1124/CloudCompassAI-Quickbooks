<?php
// Copy to config.php (gitignored). Any key can be overridden by an env var, e.g. CCQB_QBO_CLIENT_ID.
return [
    'demo' => false,
    'admin_user' => 'REPLACE_ME',
    'admin_email' => 'REPLACE_ME',           // reset links are sent only to this address
    'admin_password_hash' => 'REPLACE_ME',   // php scripts/make-hash.php "<password>"; seeds the DB on first login
    'encryption_key' => 'REPLACE_ME',        // php scripts/make-key.php
    'base_url' => 'https://books.example.com',
    'sync_from' => '2015-01-01',             // earliest transaction date to sync
    'db' => [
        'driver' => 'mysql',                 // 'sqlite' for local testing only
        'host' => 'localhost',
        'name' => 'REPLACE_ME',
        'user' => 'REPLACE_ME',
        'password' => 'REPLACE_ME',
    ],
    'mail' => ['mode' => 'mail', 'from' => 'no-reply@books.example.com'],  // mode 'log' writes to storage/outbox.log
    'qbo' => [
        'environment' => 'production',       // or 'sandbox'
        'client_id' => 'REPLACE_ME',
        'client_secret' => 'REPLACE_ME',
        'redirect_uri' => 'https://books.example.com/callback.php',
    ],
];
