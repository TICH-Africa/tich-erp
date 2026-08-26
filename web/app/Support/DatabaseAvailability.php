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
     */
    public static function isUnavailable(Throwable $e): bool
    {
        if ($e instanceof PDOException && ! $e instanceof QueryException) {
            return true;
        }

        if ($e instanceof QueryException) {
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            $message = strtolower($e->getMessage());

            // 1130 = host not allowed; 2002/2003 = can't connect; 1045 = access denied; etc.
            if (in_array($driverCode, [1040, 1042, 1044, 1045, 1049, 1129, 1130, 2002, 2003, 2006], true)) {
                return true;
            }

            if (str_contains($message, 'is not allowed to connect')
                || str_contains($message, 'connection refused')
                || str_contains($message, 'no connection could be made')
                || str_contains($message, 'server has gone away')
                || str_contains($message, 'could not find driver')
                || str_contains($message, 'getaddrinfo')
                || ($sqlState === 'HY000' && str_contains($message, 'connect'))) {
                return true;
            }
        }

        $previous = $e->getPrevious();

        return $previous instanceof Throwable && self::isUnavailable($previous);
    }
}
