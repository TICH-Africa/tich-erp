# Production database schema sync

## File

`deploy/production.sql` — full, **idempotent**, **non-destructive** schema for HostPinnacle.

- Time zone: **GMT+3** (`SET time_zone = '+03:00'`)
- Creates missing tables / columns / indexes / foreign keys
- **Never** drops tables/columns or deletes/truncates row data
- Safe to re-run: existing objects are skipped

## How to apply on production (phpMyAdmin)

1. Open phpMyAdmin → select the production database
2. Import / run `deploy/production.sql`
3. Ignore “already exists” style skips (the script checks first)

Or paste into the SQL tab and execute.

## How it stays up to date

After you run migrations locally:

```bash
cd web
php artisan migrate
php artisan tich:export-production-schema
```

`production.sql` is also refreshed automatically when migrations finish successfully (non-production environments).

Commit the updated `deploy/production.sql` and deploy/import it on the server when you need the DB structure to match.
