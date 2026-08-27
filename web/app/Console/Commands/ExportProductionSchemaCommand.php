<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Builds deploy/production.sql — an idempotent, data-safe schema sync script
 * for HostPinnacle / production MariaDB-MySQL (Africa/Nairobi = GMT+3).
 *
 * Rules encoded in the SQL:
 * - CREATE TABLE IF NOT EXISTS (never drops tables)
 * - ADD COLUMN / INDEX / FK only when missing (never drops columns or data)
 * - No DELETE / TRUNCATE / DROP TABLE / DROP COLUMN
 * - Continues past already-applied objects (existence checks)
 */
class ExportProductionSchemaCommand extends Command
{
    protected $signature = 'tich:export-production-schema
                            {--path= : Absolute or repo-relative output path (default: deploy/production.sql)}';

    protected $description = 'Regenerate deploy/production.sql from the current database structure (idempotent, non-destructive)';

    public function handle(): int
    {
        $outputPath = $this->resolveOutputPath();

        try {
            $sql = $this->buildSql();
        } catch (Throwable $e) {
            $this->error('Failed to read schema: '.$e->getMessage());

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $sql);

        $this->info('Wrote '.$outputPath.' ('.number_format(strlen($sql)).' bytes)');

        return self::SUCCESS;
    }

    private function resolveOutputPath(): string
    {
        $path = $this->option('path');

        if (is_string($path) && $path !== '') {
            if (preg_match('/^[A-Za-z]:\\\\|^\//', $path) === 1) {
                return $path;
            }

            return base_path('../'.ltrim(str_replace('\\', '/', $path), '/'));
        }

        return base_path('../deploy/production.sql');
    }

    private function buildSql(): string
    {
        $database = (string) DB::getDatabaseName();
        $generatedAt = now('Africa/Nairobi')->format('Y-m-d H:i:s T');

        $tables = DB::select(
            'SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_COMMENT
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?
             ORDER BY TABLE_NAME',
            [$database, 'BASE TABLE']
        );

        $lines = [];
        $lines[] = '-- =============================================================================';
        $lines[] = '-- TICH ERP — production schema sync (idempotent, non-destructive)';
        $lines[] = '-- =============================================================================';
        $lines[] = '-- Generated: '.$generatedAt;
        $lines[] = '-- Source DB: '.$database;
        $lines[] = '-- Time zone: Africa/Nairobi (GMT+3)';
        $lines[] = '--';
        $lines[] = '-- SAFE FOR PRODUCTION DATA:';
        $lines[] = '--   * Never DROP TABLE / DROP COLUMN / TRUNCATE / DELETE';
        $lines[] = '--   * CREATE TABLE IF NOT EXISTS';
        $lines[] = '--   * ADD COLUMN / INDEX / FOREIGN KEY only when missing';
        $lines[] = '--   * Re-runnable: already-applied objects are skipped';
        $lines[] = '--';
        $lines[] = '-- Regenerate locally after migrations:';
        $lines[] = '--   php artisan migrate';
        $lines[] = '--   php artisan tich:export-production-schema';
        $lines[] = '-- (Also auto-refreshed when migrations finish successfully.)';
        $lines[] = '-- =============================================================================';
        $lines[] = '';
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = "SET time_zone = '+03:00';";
        $lines[] = 'SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $lines[] = 'SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS;';
        $lines[] = 'SET UNIQUE_CHECKS = 0;';
        $lines[] = '';
        $lines[] = $this->helperProcedures();
        $lines[] = '';

        foreach ($tables as $table) {
            $name = $table->TABLE_NAME;
            $lines[] = '-- -----------------------------------------------------------------------------';
            $lines[] = '-- Table: `'.$name.'`';
            $lines[] = '-- -----------------------------------------------------------------------------';
            $lines[] = $this->createTableIfNotExists($name);
            $lines[] = '';
            $lines = array_merge($lines, $this->ensureColumns($database, $name));
            $lines[] = '';
            $lines = array_merge($lines, $this->ensureIndexes($database, $name));
            $lines[] = '';
        }

        $lines[] = '-- =============================================================================';
        $lines[] = '-- Foreign keys (added only when missing; never drops existing FKs)';
        $lines[] = '-- =============================================================================';
        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $lines[] = '';

        foreach ($tables as $table) {
            $lines = array_merge($lines, $this->ensureForeignKeys($database, $table->TABLE_NAME));
        }

        $lines[] = '';
        $lines[] = 'SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;';
        $lines[] = "SET time_zone = '+03:00';";
        $lines[] = '';
        $lines[] = '-- Done. Existing row data was not modified.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function helperProcedures(): string
    {
        return <<<'SQL'
-- Helper routines (recreated each run; no application data involved)
DROP PROCEDURE IF EXISTS `tich_ensure_column`;
DROP PROCEDURE IF EXISTS `tich_ensure_index`;
DROP PROCEDURE IF EXISTS `tich_ensure_unique`;
DROP PROCEDURE IF EXISTS `tich_ensure_fk`;

DELIMITER $$

CREATE PROCEDURE `tich_ensure_column`(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @tich_sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE tich_stmt FROM @tich_sql;
        EXECUTE tich_stmt;
        DEALLOCATE PREPARE tich_stmt;
    END IF;
END$$

CREATE PROCEDURE `tich_ensure_index`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @tich_sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
        PREPARE tich_stmt FROM @tich_sql;
        EXECUTE tich_stmt;
        DEALLOCATE PREPARE tich_stmt;
    END IF;
END$$

CREATE PROCEDURE `tich_ensure_unique`(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @tich_sql = CONCAT('ALTER TABLE `', p_table, '` ADD UNIQUE INDEX `', p_index, '` (', p_columns, ')');
        PREPARE tich_stmt FROM @tich_sql;
        EXECUTE tich_stmt;
        DEALLOCATE PREPARE tich_stmt;
    END IF;
END$$

CREATE PROCEDURE `tich_ensure_fk`(
    IN p_table VARCHAR(64),
    IN p_constraint VARCHAR(64),
    IN p_columns TEXT,
    IN p_ref_table VARCHAR(64),
    IN p_ref_columns TEXT,
    IN p_on_update VARCHAR(16),
    IN p_on_delete VARCHAR(16)
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND CONSTRAINT_NAME = p_constraint
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        SET @tich_sql = CONCAT(
            'ALTER TABLE `', p_table, '` ADD CONSTRAINT `', p_constraint,
            '` FOREIGN KEY (', p_columns, ') REFERENCES `', p_ref_table, '` (', p_ref_columns, ')',
            ' ON UPDATE ', p_on_update, ' ON DELETE ', p_on_delete
        );
        PREPARE tich_stmt FROM @tich_sql;
        EXECUTE tich_stmt;
        DEALLOCATE PREPARE tich_stmt;
    END IF;
END$$

DELIMITER ;
SQL;
    }

    private function createTableIfNotExists(string $table): string
    {
        $row = DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
        $create = $row->{'Create Table'} ?? null;

        if (! is_string($create) || $create === '') {
            return '-- SKIP: could not read CREATE TABLE for `'.$table.'`';
        }

        // Make idempotent and strip AUTO_INCREMENT current value (data-safe; structure only).
        $create = preg_replace('/^CREATE TABLE /i', 'CREATE TABLE IF NOT EXISTS ', $create, 1) ?? $create;
        $create = preg_replace('/\s*AUTO_INCREMENT=\d+/i', '', $create) ?? $create;

        return rtrim($create, "; \t\r\n").';';
    }

    /**
     * @return list<string>
     */
    private function ensureColumns(string $database, string $table): array
    {
        $columns = DB::select(
            'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT, GENERATION_EXPRESSION
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$database, $table]
        );

        $lines = ['-- Columns for `'.$table.'` (add only if missing)'];

        foreach ($columns as $col) {
            // Primary key / identity columns are created with the table; still ensure for altered schemas.
            $definition = $this->columnDefinition($col);
            $lines[] = "CALL `tich_ensure_column`('{$table}', '{$col->COLUMN_NAME}', ".$this->sqlString($definition).');';
        }

        return $lines;
    }

    private function columnDefinition(object $col): string
    {
        $parts = [$col->COLUMN_TYPE];

        if (($col->IS_NULLABLE ?? 'YES') === 'NO') {
            $parts[] = 'NOT NULL';
        } else {
            $parts[] = 'NULL';
        }

        $extra = strtolower((string) ($col->EXTRA ?? ''));
        $hasGenerated = str_contains($extra, 'generated') || ! empty($col->GENERATION_EXPRESSION);

        if ($hasGenerated && ! empty($col->GENERATION_EXPRESSION)) {
            $parts = [$col->COLUMN_TYPE];
            $stored = str_contains($extra, 'stored') ? 'STORED' : 'VIRTUAL';
            $parts[] = 'GENERATED ALWAYS AS ('.$col->GENERATION_EXPRESSION.') '.$stored;
            if (($col->IS_NULLABLE ?? 'YES') === 'NO') {
                $parts[] = 'NOT NULL';
            }

            return implode(' ', $parts);
        }

        if (array_key_exists('COLUMN_DEFAULT', (array) $col) && $col->COLUMN_DEFAULT !== null) {
            $default = $col->COLUMN_DEFAULT;
            if ($this->isRawDefault($default)) {
                $parts[] = 'DEFAULT '.$default;
            } else {
                $parts[] = 'DEFAULT '.$this->quoteDefault($default);
            }
        } elseif (($col->IS_NULLABLE ?? 'YES') === 'YES' && ! str_contains($extra, 'auto_increment')) {
            // Explicit NULL default omitted — engine default is fine.
        }

        if (str_contains($extra, 'auto_increment')) {
            $parts[] = 'AUTO_INCREMENT';
        }

        if (! empty($col->COLUMN_COMMENT)) {
            $parts[] = 'COMMENT '.$this->quoteDefault($col->COLUMN_COMMENT);
        }

        return implode(' ', $parts);
    }

    private function isRawDefault(string $default): bool
    {
        $upper = strtoupper(trim($default));

        return in_array($upper, ['CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP()', 'NULL'], true)
            || str_starts_with($upper, 'CURRENT_TIMESTAMP');
    }

    /**
     * @return list<string>
     */
    private function ensureIndexes(string $database, string $table): array
    {
        $stats = DB::select(
            'SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART, INDEX_TYPE
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
             ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [$database, $table]
        );

        $grouped = [];
        foreach ($stats as $row) {
            $grouped[$row->INDEX_NAME][] = $row;
        }

        $lines = ['-- Indexes for `'.$table.'` (add only if missing)'];

        foreach ($grouped as $indexName => $rows) {
            if ($indexName === 'PRIMARY') {
                continue; // created with table / PK columns
            }

            $cols = [];
            foreach ($rows as $row) {
                $col = '`'.$row->COLUMN_NAME.'`';
                if (! empty($row->SUB_PART)) {
                    $col .= '('.(int) $row->SUB_PART.')';
                }
                $cols[] = $col;
            }
            $colList = implode(', ', $cols);
            $unique = ((int) $rows[0]->NON_UNIQUE) === 0;

            if ($unique) {
                $lines[] = "CALL `tich_ensure_unique`('{$table}', '{$indexName}', ".$this->sqlString($colList).');';
            } else {
                $lines[] = "CALL `tich_ensure_index`('{$table}', '{$indexName}', ".$this->sqlString($colList).');';
            }
        }

        if (count($lines) === 1) {
            $lines[] = '-- (no secondary indexes)';
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function ensureForeignKeys(string $database, string $table): array
    {
        $fks = DB::select(
            'SELECT
                tc.CONSTRAINT_NAME,
                GROUP_CONCAT(kcu.COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION SEPARATOR ",") AS COLUMNS_CSV,
                kcu.REFERENCED_TABLE_NAME,
                GROUP_CONCAT(kcu.REFERENCED_COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION SEPARATOR ",") AS REF_COLUMNS_CSV,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
             FROM information_schema.TABLE_CONSTRAINTS tc
             JOIN information_schema.KEY_COLUMN_USAGE kcu
               ON tc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
              AND tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
              AND tc.TABLE_NAME = kcu.TABLE_NAME
             JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
               ON tc.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
              AND tc.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
             WHERE tc.CONSTRAINT_SCHEMA = ?
               AND tc.TABLE_NAME = ?
               AND tc.CONSTRAINT_TYPE = ?
             GROUP BY tc.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME, rc.UPDATE_RULE, rc.DELETE_RULE
             ORDER BY tc.CONSTRAINT_NAME',
            [$database, $table, 'FOREIGN KEY']
        );

        if ($fks === []) {
            return [];
        }

        $lines = ['-- Foreign keys for `'.$table.'`'];

        foreach ($fks as $fk) {
            $cols = implode(', ', array_map(fn ($c) => '`'.trim($c).'`', explode(',', (string) $fk->COLUMNS_CSV)));
            $refCols = implode(', ', array_map(fn ($c) => '`'.trim($c).'`', explode(',', (string) $fk->REF_COLUMNS_CSV)));
            $onUpdate = $fk->UPDATE_RULE ?: 'RESTRICT';
            $onDelete = $fk->DELETE_RULE ?: 'RESTRICT';

            $lines[] = 'CALL `tich_ensure_fk`('
                .$this->sqlString($table).', '
                .$this->sqlString($fk->CONSTRAINT_NAME).', '
                .$this->sqlString($cols).', '
                .$this->sqlString($fk->REFERENCED_TABLE_NAME).', '
                .$this->sqlString($refCols).', '
                .$this->sqlString($onUpdate).', '
                .$this->sqlString($onDelete)
                .');';
        }

        $lines[] = '';

        return $lines;
    }

    private function sqlString(string $value): string
    {
        return "'".str_replace(["\\", "'"], ["\\\\", "\\'"], $value)."'";
    }

    private function quoteDefault(string $value): string
    {
        return "'".str_replace(["\\", "'"], ["\\\\", "\\'"], $value)."'";
    }
}
