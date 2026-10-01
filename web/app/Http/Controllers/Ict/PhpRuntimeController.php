<?php

namespace App\Http\Controllers\Ict;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PhpRuntimeController extends Controller
{
    public function __invoke(): View
    {
        $checks = [
            [
                'label' => 'PHP zip extension',
                'ok' => extension_loaded('zip'),
                'detail' => extension_loaded('zip') ? 'Loaded' : 'Not loaded — required to read .xlsx',
            ],
            [
                'label' => 'ZipArchive class',
                'ok' => class_exists(\ZipArchive::class),
                'detail' => class_exists(\ZipArchive::class) ? 'Available' : 'Missing — Chart of Accounts Excel import will fail',
            ],
            [
                'label' => 'PHP xml extension',
                'ok' => extension_loaded('xml'),
                'detail' => extension_loaded('xml') ? 'Loaded' : 'Not loaded — required for spreadsheet XML',
            ],
            [
                'label' => 'DOMDocument class',
                'ok' => class_exists(\DOMDocument::class),
                'detail' => class_exists(\DOMDocument::class) ? 'Available' : 'Missing — spreadsheet readers need DOM',
            ],
            [
                'label' => 'PhpSpreadsheet (for .xls)',
                'ok' => class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class),
                'detail' => class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)
                    ? 'Available'
                    : 'Missing — .xlsx still works via zip; .xls needs composer install',
            ],
        ];

        // .xlsx only needs zip + xml (native reader). .xls also needs PhpSpreadsheet.
        $xlsxReady = collect(array_slice($checks, 0, 4))->every(fn (array $check) => $check['ok']);

        $major = explode('.', PHP_VERSION)[0] ?? '8';
        $minor = explode('.', PHP_VERSION)[1] ?? '2';
        $phpPkg = "php{$major}.{$minor}";

        return view('ict.php-runtime', [
            'checks' => $checks,
            'xlsxReady' => $xlsxReady,
            'phpVersion' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'iniFile' => php_ini_loaded_file() ?: '(unknown)',
            'phpPkg' => $phpPkg,
        ]);
    }
}
