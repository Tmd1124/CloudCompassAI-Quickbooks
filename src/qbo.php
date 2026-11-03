<?php
declare(strict_types=1);

final class QboException extends RuntimeException
{
    public function __construct(string $m, public bool $needsConnect = false)
    {
        parent::__construct($m);
    }
}

final class Qbo
{
    private const AUTH_URL = 'https://appcenter.intuit.com/connect/oauth2';
    private const TOKEN_URL = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    private const MINOR = '75';

    private static function apiBase(): string
    {
        return cfg('qbo.environment') === 'sandbox'
            ? 'https://sandbox-quickbooks.api.intuit.com'
            : 'https://quickbooks.api.intuit.com';
    }

    private static function key(): string
    {
        $k = base64_decode(need('encryption_key'), true);
        if ($k === false || strlen($k) !== 32) {
            fail_closed('encryption_key must be base64 of 32 bytes');
        }
        return $k;
    }

    private static function tokenFile(): string
    {
        return ROOT . '/storage/qbo_tokens.enc';
    }

    private static function save(array $t): void
    {
        $iv = random_bytes(12);
        $ct = openssl_encrypt(json_encode($t), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        file_put_contents(self::tokenFile(), base64_encode($iv . $tag . $ct), LOCK_EX);
    }

    private static function load(): ?array
    {
        $f = self::tokenFile();
        if (!is_file($f)) {
            return null;
        }
        $raw = base64_decode((string) file_get_contents($f), true);
        if ($raw === false || strlen($raw) < 29) {
            return null;
        }
        $pt = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        $d = $pt === false ? null : json_decode($pt, true);
        return is_array($d) ? $d : null;
    }

    public static function connected(): bool
    {
        return is_demo() || self::load() !== null;
    }

    public static function realm(): ?string
    {
        return is_demo() ? 'demo' : (self::load()['realm_id'] ?? null);
    }

    public static function disconnect(): void
    {
        @unlink(self::tokenFile());
    }

    public static function authUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => need('qbo.client_id'),
            'scope' => 'com.intuit.quickbooks.accounting',
            'redirect_uri' => need('qbo.redirect_uri'),
            'response_type' => 'code',
            'state' => $state,
        ]);
    }

    public static function exchange(string $code, string $realm): void
    {
        $r = self::tokenRequest(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => need('qbo.redirect_uri')]);
        self::save(self::fromTokenResponse($r, $realm));
    }

    private static function fromTokenResponse(array $r, string $realm): array
    {
        return [
            'realm_id' => $realm,
            'access_token' => $r['access_token'],
            'access_exp' => time() + (int) $r['expires_in'],
            'refresh_token' => $r['refresh_token'],
        ];
    }

    private static function tokenRequest(array $form): array
    {
        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($form),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERPWD => need('qbo.client_id') . ':' . need('qbo.client_secret'),
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new QboException("Could not reach Intuit: $err");
        }
        $j = json_decode($body, true);
        if ($code !== 200 || !isset($j['access_token'])) {
            throw new QboException('Intuit rejected the token request. Reconnect QuickBooks.', true);
        }
        return $j;
    }

    private static function accessToken(bool $force = false): array
    {
        $t = self::load();
        if (!$t) {
            throw new QboException('QuickBooks is not connected.', true);
        }
        if ($force || $t['access_exp'] - time() < 60) {
            $r = self::tokenRequest(['grant_type' => 'refresh_token', 'refresh_token' => $t['refresh_token']]);
            $t = self::fromTokenResponse($r, $t['realm_id']);
            self::save($t);
        }
        return $t;
    }

    public static function report(string $name, array $params): array
    {
        if (is_demo()) {
            require_once __DIR__ . '/demo.php';
            return demo_report($name, $params);
        }
        $params['minorversion'] = self::MINOR;
        $retry = false;
        while (true) {
            $t = self::accessToken($retry);
            $url = self::apiBase() . '/v3/company/' . rawurlencode($t['realm_id']) . '/reports/' . $name . '?' . http_build_query($params);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $t['access_token']],
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($body === false) {
                throw new QboException("Could not reach QuickBooks: $err");
            }
            if ($code === 401 && !$retry) {
                $retry = true;
                continue;
            }
            if ($code === 401) {
                throw new QboException('QuickBooks rejected the saved login. Reconnect QuickBooks.', true);
            }
            $j = json_decode($body, true);
            if ($code !== 200 || !is_array($j)) {
                throw new QboException("QuickBooks returned an error (HTTP $code).");
            }
            return $j;
        }
    }
}
