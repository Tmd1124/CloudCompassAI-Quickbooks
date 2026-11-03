<?php
// Copy to config.php (gitignored). Any key can be overridden by an env var, e.g. CCQB_QBO_CLIENT_ID.
return [
    'demo' => false,
    'admin_user' => 'REPLACE_ME',
    'admin_password_hash' => 'REPLACE_ME',   // php scripts/make-hash.php
    'encryption_key' => 'REPLACE_ME',        // php scripts/make-key.php
    'qbo' => [
        'environment' => 'production',       // or 'sandbox'
        'client_id' => 'REPLACE_ME',
        'client_secret' => 'REPLACE_ME',
        'redirect_uri' => 'https://books.example.com/callback.php',
    ],
];
