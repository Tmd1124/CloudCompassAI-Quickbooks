# CloudCompass Books

> **Retired 2026-11-03.** This standalone app moved into the CloudCompassAI website admin (repo `CloudCompassAI-WebSite`, tab Admin > QuickBooks). quickbooks.cloudcompassai.com now redirects there (`scripts/retired.htaccess`); the legacy files and cron still sit on that host and can be deleted. Its `ccqb_*` tables are shared with the new tab: the unused `ccqb_users`, `ccqb_remember` and `ccqb_resets` can be dropped. Do not use Disconnect or Environment here any more.

QuickBooks Online viewer for quickbooks.cloudcompassai.com (HostGator, PHP 8 + MySQL). Transactions arrive in QuickBooks via its native Chase bank feed; this app syncs them into MySQL (`ccqb_*` tables in the website DB), flags possible duplicates, and shows live Profit & Loss and Balance Sheet. Read-only toward QuickBooks. Spec: `docs/superpowers/specs/2026-11-03-quickbooks-reporting-app.md`.

## Pick up here
- Run locally: `php -d extension=pdo_sqlite -d extension=sqlite3 -S localhost:8000 -t public` (local config uses demo data + SQLite; reset emails are written to `storage/outbox.log`).
- Deploy: `scripts/deploy.sh [--dry-run]` (FTP creds in gitignored `.env.deploy`; server config is `config/config.server.php`, uploaded as `config/config.php`). Rollback = redeploy the previous git commit.
- State (2026-11-03): login (DB-backed, remember me, forgot/reset by email, show-password), transaction sync + dedupe, sandbox connection, privacy/EULA pages are live. Verified live with the Intuit sandbox (141 transactions, re-sync adds none). Real Chase data needs Intuit production keys.
- Decisions (Travis): QuickBooks-native feed, no Plaid; HostGator not Vercel; use website MySQL DB with `ccqb_` prefix; reset mail via PHP mail() to the account email only.
- Open: Intuit production keys/questionnaire; optional cPanel cron `php /home2/aycjztte/public_html/quickbooks/cron/sync.php`; confirm reset email actually arrives; rotate pasted secrets (Intuit client secret, FTP/DB password; the DB and FTP password are the same); take a DB backup.
- Disconnect revokes at Intuit and deletes stored transactions.

## Environments (sandbox / production)
- Keys live in `qbo.sandbox` and `qbo.production` in config. Production stays unused while its keys are `REPLACE_ME`; the app then defaults to Sandbox with a banner "Production keys are not ready yet".
- Once production keys exist the app defaults to Production. Switch from the header chip or `/environment.php` (production needs a confirm tick).
- Each environment has its own QuickBooks connection (`storage/qbo_tokens.<env>.enc`) and its own rows (`env` column). Disconnect and purge only touch the active environment.
- Cron (`cron/sync.php`) syncs production only, when ready and connected; otherwise it logs "skipped". Run `--env=sandbox` by hand if needed.
- To go live: add the redirect URI under Intuit's Production settings, put the production Client ID/Secret in `config/config.server.php`, redeploy, switch to Production, click Connect QuickBooks.
