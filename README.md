# CloudCompass Books

QuickBooks Online viewer for quickbooks.cloudcompassai.com (HostGator, PHP 8 + MySQL). Transactions arrive in QuickBooks via its native Chase bank feed; this app syncs them into MySQL (`ccqb_*` tables in the website DB), flags possible duplicates, and shows live Profit & Loss and Balance Sheet. Read-only toward QuickBooks. Spec: `docs/superpowers/specs/2026-11-03-quickbooks-reporting-app.md`.

## Pick up here
- Run locally: `php -d extension=pdo_sqlite -d extension=sqlite3 -S localhost:8000 -t public` (local config uses demo data + SQLite; reset emails are written to `storage/outbox.log`).
- Deploy: `scripts/deploy.sh [--dry-run]` (FTP creds in gitignored `.env.deploy`; server config is `config/config.server.php`, uploaded as `config/config.php`). Rollback = redeploy the previous git commit.
- State (2026-11-03): login (DB-backed, remember me, forgot/reset by email, show-password), transaction sync + dedupe, sandbox connection, privacy/EULA pages are live. Verified live with the Intuit sandbox (141 transactions, re-sync adds none). Real Chase data needs Intuit production keys.
- Decisions (Travis): QuickBooks-native feed, no Plaid; HostGator not Vercel; use website MySQL DB with `ccqb_` prefix; reset mail via PHP mail() to the account email only.
- Open: Intuit production keys/questionnaire; optional cPanel cron `php /home2/aycjztte/public_html/quickbooks/cron/sync.php`; confirm reset email actually arrives; rotate pasted secrets (Intuit client secret, FTP/DB password; the DB and FTP password are the same); take a DB backup.
- Disconnect revokes at Intuit and deletes stored transactions.
