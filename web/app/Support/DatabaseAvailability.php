<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

final class DatabaseAvailability
{
    /**
     * True when the failure is a connection / host-privilege / server-down problem
     * (safe to show a generic 503; never expose SQL details).
     *
     * Schema/query bugs (unknown column/table, syntax) are NOT treated as unavailable.
     */
    public static function isUnavailable(Throwable $e): bool
    {
        if ($e instanceof QueryException) {
            return self::isConnectionFailure(
                (string) ($e->errorInfo[0] ?? ''),
                (int) ($e->errorInfo[1] ?? 0),
                $e->getMessage()
            );
        }

        if ($e instanceof PDOException) {
            $sqlState = is_array($e->errorInfo ?? null) ? (string) ($e->errorInfo[0] ?? '') : '';
            $driverCode = is_array($e->errorInfo ?? null) ? (int) ($e->errorInfo[1] ?? 0) : (int) $e->getCode();

            return self::isConnectionFailure($sqlState, $driverCode, $e->getMessage());
        }

        $previous = $e->getPrevious();

        return $previous instanceof Throwable && self::isUnavailable($previous);
    }

    private static function isConnectionFailure(string $sqlState, int $driverCode, string $message): bool
    {
        $message = strtolower($message);

        // 1130 = host not allowed; 2002/2003 = can't connect; 1045 = access denied; etc.
        if (in_array($driverCode, [1040, 1042, 1044, 1045, 1049, 1129, 1130, 2002, 2003, 2006], true)) {
            return true;
        }

        return str_contains($message, 'is not allowed to connect')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'no connection could be made')
            || str_contains($message, 'server has gone away')
            || str_contains($message, 'could not find driver')
            || str_contains($message, 'getaddrinfo')
            || ($sqlState === 'HY000' && str_contains($message, 'connect'));
    }
}
