# RentTrack V2

## Verified architecture

RentTrack V2 is a Laravel 13 application using PHP 8.5, Blade, Tailwind CSS 4,
Vite 7, and a custom `accounts` authentication model. It has separate Admin and
Tenant profiles and uses session authentication with an email OTP after password
verification.

Core modules are registration invitations, room inventory and requests, monthly
billing, manual payments, advance payments/credit allocation, and PayMongo
hosted checkout. PayMongo checkout and webhook processing are deliberately
limited to test mode.

## Access control

Admin routes require `auth` and `role:admin`. The role middleware also requires
an existing linked Admin profile whose status is Active. Tenant routes likewise
require an active linked Tenant profile.

Invitation resend uses the named route `admin.registrations.resend` at
`POST /admin/registration-requests/{registrationRequest}/resend`. It is part
of the admin-only route group.

## Environment variable names

Provide environment values outside source control. Important names include:

- `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_SECURE_COOKIE`
- `CACHE_STORE`, `QUEUE_CONNECTION`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`,
  `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`
- `PAYMONGO_SECRET_KEY`, `PAYMONGO_WEBHOOK_SECRET`, `PAYMONGO_LIVE_MODE`

Never commit `.env`, credentials, live payment secrets, or tenant/payment data.

## Database status and migration requirement

The repository contains Laravel framework migrations and a recovered migration
for the custom RentTrack domain schema. Do not run migrations against an
existing database. The recovered migration was generated only after approved,
read-only local schema metadata inspection.

### Verified local schema recovery

On 2026-11-10, a read-only inspection of the confirmed local MariaDB development
instance captured metadata for 24 base tables. The 15 RentTrack domain tables
are now represented by the unexecuted migration
`2026_11_10_000003_create_rentrack_domain_tables.php`:

- `admins`, `rooms`, `tenants`, `accounts`
- `registration_requests`, `otp_verifications`, `room_requests`
- `billings`, `billing_items`
- `advance_payments`, `advance_payment_allocations`
- `payment_transactions`, `payment_webhook_events`
- `audit_log`, `transaction_log`

The recovered schema verifies unique constraints for account identity, billing
periods, invitation and payment idempotency identifiers, and advance-payment
submission keys. It also verifies database-level foreign-key actions and check
constraints for billing items and credit allocation amounts. Existing Laravel
framework tables remain in their original migrations. Do not run the recovered
migration against the existing database.

### Schema-only export procedure (operator reference)

The local MySQL account configured for the application has broader privileges
than metadata-only access, so it must not be used for an export without separate
approval. A local `mysqldump.exe` is available at
`C:\xampp\mysql\bin\mysqldump.exe`.

When a dedicated export account is available and the database name is known to
the operator, run this from PowerShell outside the repository. The password
prompt is interactive; do not place credentials in the command, script, log,
or output path.

```powershell
$schemaExportRoot = Join-Path $env:USERPROFILE 'Documents\RentTrack-schema-exports'
New-Item -ItemType Directory -Force -Path $schemaExportRoot

& 'C:\xampp\mysql\bin\mysqldump.exe' `
  --host=127.0.0.1 `
  --port=3306 `
  --user=<metadata-export-user> `
  --password `
  --no-data `
  --skip-triggers `
  --routines=false `
  --events=false `
  --single-transaction `
  --set-gtid-purged=OFF `
  <database-name> `
  | Set-Content -NoNewline (Join-Path $schemaExportRoot 'rentrack-schema.sql')
```

Pass explicitly enumerated base-table names after `<database-name>` rather than
dumping the whole database. This excludes views. With `--no-data`,
`--skip-triggers`, `--routines=false`, and `--events=false`, a base-table-only
export does not contain application records, routines, triggers, or events.

Verify the resulting SQL is schema-only before sharing it:

```powershell
Select-String -Path $schemaExportRoot\rentrack-schema.sql `
  -Pattern '^\s*(INSERT|UPDATE|DELETE|REPLACE|LOAD\s+DATA)\b|\bDEFINER\s*=' `
  -CaseSensitive:$false
```

The command must return no matches. Do not commit the export to this repository.

## Password reset

Password reset uses the existing Laravel `password_reset_tokens` table and the
custom `accounts` table. Reset codes are six digits, stored only as hashes,
expire after ten minutes, are rate-limited, and are consumed inside a database
transaction. A successful reset invalidates existing sessions for that account.
Reset requests always return a generic response to avoid account enumeration.

## Local Gmail SMTP diagnostics

The local application mail transport is configured through `.env` and must use
`MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, and
`MAIL_SCHEME=smtp`. Keep the Gmail account, app password, and sender address
only in the untracked `.env` file. Do not place them in source control.

Use `php artisan rentrack:mail-diagnose` to confirm the active mailer, host,
port, configured-credential status, queue driver, and configuration-cache state
without printing sensitive values. Login OTP, password-reset OTP, registration
approval, and invitation resend all use synchronous `Mail::send` calls, so the
database queue driver does not delay these time-sensitive emails.

`php artisan rentrack:mail-smoke` refuses to send by default. After a recipient
explicitly approves one test message, use
`php artisan rentrack:mail-smoke --to=<approved-address> --confirm`. SMTP
acceptance is not evidence of inbox receipt; the recipient must confirm receipt.

## Safe local validation

Safe checks that do not modify application data include:

```powershell
php -l app/Http/Controllers/PasswordResetController.php
php artisan route:list
composer validate --no-check-publish
```

Database tests are intentionally protected by
`tests/Feature/IsolatedMySqlTestCase.php`. They only run when Laravel sees all
of the following: `APP_ENV=testing`, a local MySQL host, database
`rentrack_v2_testing`, and username `rentrack_test`. Any other target fails
before `RefreshDatabase` can migrate it.

`phpunit.xml` selects the test database name but deliberately contains no
credentials. Copy `.env.testing.example` to an untracked `.env.testing` and
put the dedicated test account password only in that local file.

The following commands require explicit approval because they create and then
modify a disposable database:

```sql
CREATE DATABASE rentrack_v2_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rentrack_test'@'localhost' IDENTIFIED BY '<local-secret>';
GRANT ALL PRIVILEGES ON rentrack_v2_testing.* TO 'rentrack_test'@'localhost';
```

After approval, verify `SELECT DATABASE()` returns only `rentrack_v2_testing`,
then run `php artisan test`. Never use `migrate:fresh`, `db:wipe`, or a test
command against the configured development database.

### Planned isolated test coverage

The committed suite currently includes executable invitation-resend and role
boundary tests using the isolated MySQL guard, plus database-free money tests.
Further billing, OTP, password-reset, checkout, and webhook tests require the
same approved disposable database. Use `Mail::fake()` and HTTP fakes for every
test; never point PHPUnit at the configured MySQL database.

## Deployment checklist

- Obtain and version verified custom-schema migrations.
- Verify production session cookies are HTTPS-only.
- Keep PayMongo in test mode unless production activation is explicitly approved.
- Configure mail transport and verify it in a non-production environment.
- Run database-backed tests only against an isolated disposable database.
- Confirm webhook event and payment-session uniqueness constraints in the
  verified schema.
