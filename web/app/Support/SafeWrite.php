<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Helpers so multi-step writes either fully succeed or fully roll back,
 * with safe handling of unique-constraint races (double-submit).
 */
class SafeWrite
{
    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function transaction(callable $callback, int $attempts = 3): mixed
    {
        return DB::transaction($callback, $attempts);
    }

    /**
     * Run a write; if a unique constraint race fires, rethrow a clear 422.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function transactionOrConflict(callable $callback, string $conflictMessage, int $attempts = 3): mixed
    {
        try {
            return DB::transaction($callback, $attempts);
        } catch (QueryException $e) {
            if (self::isUniqueViolation($e)) {
                abort(422, $conflictMessage);
            }

            throw $e;
        }
    }

    public static function isUniqueViolation(Throwable $e): bool
    {
        if (! $e instanceof QueryException) {
            return false;
        }

        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = strtolower($e->getMessage());

        return $sqlState === '23000'
            || $driverCode === 1062
            || str_contains($message, 'unique')
            || str_contains($message, 'duplicate');
    }
}
