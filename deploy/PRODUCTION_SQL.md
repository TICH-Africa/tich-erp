# Production database schema sync

## Two files (important)

| File | Purpose |
|------|---------|
| `deploy/production.sql` | **Add-only** sync: creates missing tables, columns, indexes, FKs. Never drops or alters existing columns. |
| `deploy/production-patches.sql` | **Intentional deltas**: DROP deprecated tables, MODIFY columns (nullable, etc.). Run when migrations did destructive/alter work locally. |

If you only run `production.sql`, production will **not** drop tables that were removed locally, and will **not** change column nullability on columns that already exist. That is why production can show **200 tables** while localhost has **197** (typically the 3 legacy permission tables: `permissions`, `role_permissions`, `user_permissions`).

## How to apply on production (phpMyAdmin)

1. Open phpMyAdmin → select the production database
2. Run **`deploy/production.sql`** (import or SQL tab)
3. Run **`deploy/production-patches.sql`** (same way)
4. Confirm table count matches localhost:
   ```sql
   SELECT COUNT(*) AS tables
   FROM information_schema.tables
   WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE';
   ```

Or paste each file into the SQL tab and execute in order.

## How `production.sql` stays up to date

After you run migrations locally:

```bash
cd web
php artisan migrate
php artisan tich:export-production-schema
```

`production.sql` is also refreshed automatically when migrations finish successfully (non-production environments).

Commit the updated `deploy/production.sql` and deploy/import it on the server.

When a migration **drops** tables or **modifies** columns, add the equivalent statements to `deploy/production-patches.sql` (or extend that file in the same commit).

## Time zone

Both scripts set **GMT+3** (`SET time_zone = '+03:00'`).
