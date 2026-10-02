<?php

namespace App\Support;

/**
 * Detect whether PhpSpreadsheet is actually loadable on this host.
 * Autoload entries can exist while the package files are missing after a partial deploy.
 */
class PhpSpreadsheetAvailability
{
    public static function isAvailable(): bool
    {
        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class, false)) {
            return true;
        }

        $path = base_path('vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php');

        if (! is_file($path)) {
            return false;
        }

        try {
            return class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function missingMessage(string $action = 'Excel export/import'): string
    {
        return "{$action} is unavailable because PhpSpreadsheet is missing on this server "
            .'(expected at vendor/phpoffice/phpspreadsheet). Redeploy so composer install completes, '
            .'or ask ICT to restore that package under web/vendor. CSV import still works when available.';
    }
}
