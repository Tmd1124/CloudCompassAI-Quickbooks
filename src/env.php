<?php
declare(strict_types=1);

final class Env
{
    private static ?string $active = null;

    public static function cred(string $env, string $k): string
    {
        $v = cfg("qbo.$env.$k");
        if (($v === null || $v === '') && $env === 'sandbox') {
            $v = cfg("qbo.$k");
        }
        return (string) $v;
    }

    public static function credNeed(string $env, string $k): string
    {
        if (!self::keysReady($env)) {
            fail_closed("QuickBooks $env keys are not configured");
        }
        return self::cred($env, $k);
    }

    public static function keysReady(string $env): bool
    {
        foreach (['client_id', 'client_secret'] as $k) {
            $v = self::cred($env, $k);
            if ($v === '' || str_contains($v, 'REPLACE_ME')) {
                return false;
            }
        }
        return true;
    }

    public static function productionReady(): bool
    {
        return self::keysReady('production');
    }

    public static function pref(): ?string
    {
        $st = Db::pdo()->prepare("SELECT v FROM ccqb_settings WHERE k = 'env'");
        $st->execute();
        $v = $st->fetchColumn();
        return in_array($v, ['sandbox', 'production'], true) ? $v : null;
    }

    public static function setPref(string $env): void
    {
        $db = Db::pdo();
        $db->prepare("DELETE FROM ccqb_settings WHERE k = 'env'")->execute();
        $db->prepare("INSERT INTO ccqb_settings (k, v) VALUES ('env', ?)")->execute([$env]);
        self::$active = null;
    }

    public static function active(): string
    {
        if (is_demo()) {
            return 'sandbox';
        }
        if (self::$active !== null) {
            return self::$active;
        }
        $pref = self::pref();
        if ($pref === 'sandbox') {
            return self::$active = 'sandbox';
        }
        return self::$active = self::productionReady() ? 'production' : 'sandbox';
    }
}
