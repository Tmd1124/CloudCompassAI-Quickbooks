<?php
declare(strict_types=1);

final class Db
{
    private static ?PDO $pdo = null;

    public static function driver(): string
    {
        return (string) cfg('db.driver', 'mysql');
    }

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }
        try {
            if (self::driver() === 'sqlite') {
                $pdo = new PDO('sqlite:' . ROOT . '/storage/local.sqlite');
            } else {
                $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', need('db.host'), need('db.name'));
                $pdo = new PDO($dsn, need('db.user'), need('db.password'));
            }
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            fail_closed('db connect: ' . $e->getMessage());
        }
        self::$pdo = $pdo;
        self::migrate($pdo);
        return $pdo;
    }

    private static function hasColumn(PDO $pdo, string $table, string $col): bool
    {
        if (self::driver() === 'sqlite') {
            foreach ($pdo->query("PRAGMA table_info($table)")->fetchAll() as $c) {
                if ($c['name'] === $col) {
                    return true;
                }
            }
            return false;
        }
        return (bool) $pdo->query("SHOW COLUMNS FROM $table LIKE '$col'")->fetch();
    }

    private static function migrate(PDO $pdo): void
    {
        $sqlite = self::driver() === 'sqlite';
        $flag1 = ROOT . '/storage/schema.v1';
        $flag2 = ROOT . '/storage/schema.v2';
        if (!is_file($flag1)) {
            $ai = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
            $uid = $sqlite ? 'INTEGER' : 'INT UNSIGNED';
            $tail = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            $idx = $sqlite ? '' : ', INDEX ccqb_tx_date (txn_date)';
            $stmts = [
                "CREATE TABLE IF NOT EXISTS ccqb_users (id $ai, username VARCHAR(100) NOT NULL UNIQUE, email VARCHAR(190) NOT NULL, password_hash VARCHAR(255) NOT NULL, created_at VARCHAR(19) NOT NULL)$tail",
                "CREATE TABLE IF NOT EXISTS ccqb_remember (id $ai, user_id $uid NOT NULL, selector VARCHAR(32) NOT NULL UNIQUE, token_hash CHAR(64) NOT NULL, expires_at VARCHAR(19) NOT NULL)$tail",
                "CREATE TABLE IF NOT EXISTS ccqb_resets (id $ai, user_id $uid NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, expires_at VARCHAR(19) NOT NULL, used_at VARCHAR(19) NULL, created_at VARCHAR(19) NOT NULL)$tail",
                "CREATE TABLE IF NOT EXISTS ccqb_syncs (id $ai, started_at VARCHAR(19) NOT NULL, finished_at VARCHAR(19) NULL, range_from VARCHAR(10) NULL, range_to VARCHAR(10) NULL, seen INT NOT NULL DEFAULT 0, inserted INT NOT NULL DEFAULT 0, updated INT NOT NULL DEFAULT 0, removed INT NOT NULL DEFAULT 0, status VARCHAR(20) NOT NULL DEFAULT 'running', error VARCHAR(255) NULL)$tail",
                "CREATE TABLE IF NOT EXISTS ccqb_transactions (id $ai, row_key CHAR(64) NOT NULL UNIQUE, qbo_id VARCHAR(40) NOT NULL DEFAULT '', txn_date VARCHAR(10) NOT NULL, txn_type VARCHAR(60) NOT NULL DEFAULT '', doc_num VARCHAR(60) NOT NULL DEFAULT '', name VARCHAR(255) NOT NULL DEFAULT '', memo TEXT NULL, account VARCHAR(255) NOT NULL DEFAULT '', split VARCHAR(255) NOT NULL DEFAULT '', amount DECIMAL(14,2) NOT NULL DEFAULT 0, seen_run INT NOT NULL DEFAULT 0, removed_at VARCHAR(19) NULL, first_seen_at VARCHAR(19) NOT NULL, last_seen_at VARCHAR(19) NOT NULL$idx)$tail",
            ];
            if ($sqlite) {
                $stmts[] = 'CREATE INDEX IF NOT EXISTS ccqb_tx_date ON ccqb_transactions (txn_date)';
            }
            foreach ($stmts as $s) {
                $pdo->exec($s);
            }
            file_put_contents($flag1, gmdate('c'));
        }
        if (!is_file($flag2)) {
            $tail = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
            $pdo->exec("CREATE TABLE IF NOT EXISTS ccqb_settings (k VARCHAR(50) NOT NULL PRIMARY KEY, v VARCHAR(255) NOT NULL)$tail");
            foreach (['ccqb_transactions', 'ccqb_syncs'] as $t) {
                if (!self::hasColumn($pdo, $t, 'env')) {
                    $pdo->exec("ALTER TABLE $t ADD COLUMN env VARCHAR(12) NOT NULL DEFAULT 'sandbox'");
                }
            }
            file_put_contents($flag2, gmdate('c'));
        }
    }
}

function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}
