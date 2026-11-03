# CloudCompass Books

Read-only QuickBooks Online viewer (Transactions, Profit & Loss, Balance Sheet) for a HostGator subdomain. Transactions arrive via QuickBooks' native Chase bank feed; this app only reads. Spec: `docs/superpowers/specs/2026-11-03-quickbooks-reporting-app.md`.

## Pick up here
- Run locally: `php -S localhost:8000 -t public` (config/config.php has `demo => true`; local login `demo` / `demo-password-123`, throwaway, gitignored).
- State: UI, login, QBO OAuth + token refresh + report calls are written. Verified in demo mode only (desktop curl + 375px browser). **Live QuickBooks calls are untested** until Intuit keys exist.
- Decisions (Travis, 2026-11-03): QuickBooks-native Chase feed (no Plaid); HostGator, not Vercel.
- Open blockers / Travis to do:
  1. Link both Chase checking accounts in QuickBooks (Banking > Link account).
  2. Create an Intuit Developer app, redirect URI `https://quickbooks.cloudcompassai.com/callback.php`.
  3. cPanel: create subdomain, document root = `public/`, enable SSL, upload project, create `config/config.php` from the example (`php scripts/make-hash.php "<password>"`, `php scripts/make-key.php`).
  4. Sign in, open Connect QuickBooks, approve. Then smoke-test all three pages.
- Known gap: `TransactionList` default columns are parsed by title; confirm against real data on first connect.
