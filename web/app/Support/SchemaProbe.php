<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Answers "does this table / column exist" without repeating the lookup.
 *
 * Screens guard optional features with schema checks so a page still opens when a
 * migration is pending. Each check is a round trip to information_schema, and a
 * portal badge list that probes twenty tables spends more time introspecting the
 * database than reading it. The answer cannot change while one request runs, so
 * the first lookup per table is kept and the rest are served from memory.
 */
final class SchemaProbe
{
    /** @var array<string, bool> */
    private static array $tables = [];

    /** @var array<string, array<string, bool>|null> */
    private static array $columnsByTable = [];

    public static function hasTable(string $table): bool
    {
        return self::$tables[$table] ??= Schema::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        if (! isset(self::$columnsByTable[$table])) {
            $present = self::hasTable($table)
                ? array_map('strtolower', Schema::getColumnListing($table))
                : [];

            self::$columnsByTable[$table] = array_fill_keys($present, true);
        }

        return self::$columnsByTable[$table][strtolower($column)] ?? false;
    }

    /**
     * Drop the memo so a fresh request (or a migration that just added a column)
     * reads the live structure again.
     */
    public static function reset(): void
    {
        self::$tables = [];
        self::$columnsByTable = [];
    }
}
