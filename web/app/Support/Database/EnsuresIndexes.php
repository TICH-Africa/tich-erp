<?php

namespace App\Support\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent index helpers for MySQL/MariaDB.
 *
 * Use in migrations so lookups by id/status/fk/code hit an index
 * instead of scanning every row (e.g. finding row 4395 among 5000).
 */
trait EnsuresIndexes
{
    /**
     * @param  list<string>  $columns
     */
    protected function ensureIndex(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->indexExists($table, $name)) {
            return;
        }

        // Skip if an identical column set is already indexed under another name.
        if ($this->columnsAlreadyIndexed($table, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->index($columns, $name);
        });
    }

    /**
     * @param  list<string>  $columns
     */
    protected function ensureUniqueIndex(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->indexExists($table, $name) || $this->columnsAlreadyIndexed($table, $columns, uniqueOnly: true)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
            $blueprint->unique($columns, $name);
        });
    }

    protected function dropIndexIfExists(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->dropIndex($name);
        });
    }

    protected function indexExists(string $table, string $name): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            // SQLite / others: attempt create and ignore duplicates via column check only.
            return false;
        }

        $rows = DB::select(
            'SELECT 1 AS present FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND index_name = ?
             LIMIT 1',
            [$table, $name]
        );

        return $rows !== [];
    }

    /**
     * @param  list<string>  $columns
     */
    protected function columnsAlreadyIndexed(string $table, array $columns, bool $uniqueOnly = false): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $wanted = array_map('strtolower', $columns);

        $rows = DB::select(
            'SELECT index_name, non_unique, seq_in_index, column_name
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = ?
             ORDER BY index_name, seq_in_index',
            [$table]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $name = (string) $row->index_name;
            $grouped[$name]['unique'] = ((int) $row->non_unique) === 0;
            $grouped[$name]['columns'][] = strtolower((string) $row->column_name);
        }

        foreach ($grouped as $meta) {
            if ($uniqueOnly && ! $meta['unique']) {
                continue;
            }

            // Exact match or left-prefix match (MySQL can use a left prefix of a composite).
            if ($meta['columns'] === $wanted) {
                return true;
            }

            if (count($wanted) <= count($meta['columns'])
                && array_slice($meta['columns'], 0, count($wanted)) === $wanted) {
                return true;
            }
        }

        return false;
    }
}
