# CloudCompass QuickBooks Reporting App

## Decision (Travis, 2026-11-03)
- Transactions load into QuickBooks Online via QuickBooks' **native Chase bank feed** (business + personal checking). Intuit handles the bank connection and duplicate matching. The app does not push transactions.
- Host: HostGator shared hosting, as a subdomain of the CloudCompassAI site. No Vercel.

## What the app does (read-only)
1. **Transactions**: all QuickBooks transactions for a date range, filter by account and text, "possible duplicate" flag (same date, amount, name, account, type).
2. **Profit & Loss**: date range, accrual/cash.
3. **Balance Sheet**: as-of date.
All data comes live from the QBO Reports API (`TransactionList`, `ProfitAndLoss`, `BalanceSheet`).

## Stack (assumptions, my calls)
- Plain PHP 8.1+, no framework, no Composer. Server-rendered pages, one CSS file.
- Single-user login: username + bcrypt hash in gitignored `config/config.php` (or `CCQB_*` env vars). CSRF, lockout after 5 failures per IP for 15 min.
- QBO OAuth2 tokens stored encrypted (AES-256-GCM) in `storage/` outside the web docroot. No MySQL needed. Refresh tokens rotate; the new one is saved on every refresh.
- Demo mode (`demo => true`) serves labeled sample data so the UI works without Intuit keys. The header shows "Demo data".
- Fails closed: non-demo mode refuses to run while any secret is still `REPLACE_ME`.

## Non-goals
- No pushing transactions into QuickBooks, no Plaid, no editing/categorizing in the app.

## Anti-goal
- Showing sample or stale numbers as if real. Errors from QuickBooks are shown plainly.

## Travis must do
1. In QuickBooks Online: connect both Chase checking accounts via Banking > Link account, and accept the transactions.
2. Create an Intuit Developer app (Accounting scope), add the redirect URI `https://<subdomain>/callback.php`, and copy the Client ID/Secret into config.
3. In cPanel: create the subdomain with its document root set to this project's `public/` folder, enable SSL.
4. Run `php scripts/make-hash.php` for the admin password hash and `php scripts/make-key.php` for the encryption key.
5. Intuit production keys require completing Intuit's app questionnaire. Sandbox keys work until then, but only against a sandbox company.
