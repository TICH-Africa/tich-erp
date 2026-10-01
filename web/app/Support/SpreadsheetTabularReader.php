<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Reads the first sheet of .xlsx / .xls / .csv into a list of row arrays.
 *
 * .xlsx uses ZipArchive + XML first (works on LiteSpeed when zip/xml are on),
 * then falls back to PhpSpreadsheet. .xls / complex workbooks use PhpSpreadsheet.
 */
class SpreadsheetTabularReader
{
    /**
     * @return list<array<int, mixed>>
     */
    public function rows(string $path, ?string $extensionHint = null): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new \RuntimeException('Uploaded spreadsheet path is not readable.');
        }

        if (filesize($path) < 1) {
            throw new \RuntimeException('Uploaded spreadsheet is empty.');
        }

        $extension = strtolower((string) ($extensionHint ?: pathinfo($path, PATHINFO_EXTENSION)));
        $format = $this->detectFormat($path, $extension);

        if ($format === 'csv') {
            return $this->readCsv($path);
        }

        if ($format === 'xlsx') {
            try {
                return $this->readXlsxWithZip($path);
            } catch (\Throwable $zipException) {
                try {
                    return $this->readWithPhpSpreadsheet($path, 'Xlsx');
                } catch (\Throwable $spreadsheetException) {
                    throw new \RuntimeException(
                        $this->combineFailures($zipException, $spreadsheetException),
                        0,
                        $spreadsheetException
                    );
                }
            }
        }

        if ($format === 'xls') {
            return $this->readWithPhpSpreadsheet($path, 'Xls');
        }

        // Unknown extension: try content sniff, then PhpSpreadsheet auto-detect.
        try {
            return $this->readXlsxWithZip($path);
        } catch (\Throwable) {
            return $this->readWithPhpSpreadsheet($path, null);
        }
    }

    private function detectFormat(string $path, string $extension): string
    {
        $handle = fopen($path, 'rb');
        $header = $handle ? (string) fread($handle, 8) : '';
        if ($handle) {
            fclose($handle);
        }

        // ZIP / OOXML (.xlsx)
        if (str_starts_with($header, "PK\x03\x04") || str_starts_with($header, "PK\x05\x06") || str_starts_with($header, "PK\x07\x08")) {
            return 'xlsx';
        }

        // OLE Compound Document (.xls)
        if (str_starts_with($header, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
            return 'xls';
        }

        return match ($extension) {
            'xlsx', 'xlsm' => 'xlsx',
            'xls' => 'xls',
            'csv', 'txt' => 'csv',
            default => $extension !== '' ? $extension : 'xlsx',
        };
    }

    /**
     * @return list<array<int, mixed>>
     */
    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Could not open CSV for reading.');
        }

        // Skip UTF-8 BOM when present.
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Minimal OOXML reader — enough for Chart of Accounts tabular imports.
     *
     * @return list<array<int, mixed>>
     */
    private function readXlsxWithZip(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive is required to read .xlsx files.');
        }

        if (! class_exists(\DOMDocument::class)) {
            throw new \RuntimeException('DOMDocument (php-xml) is required to read .xlsx files.');
        }

        $zip = new \ZipArchive();
        $opened = $zip->open($path);
        if ($opened !== true) {
            throw new \RuntimeException('Could not open .xlsx archive (ZipArchive code '.$opened.').');
        }

        try {
            $sharedStrings = $this->parseSharedStrings($zip);
            $sheetPath = $this->resolveFirstSheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false || $sheetXml === '') {
                throw new \RuntimeException('Could not read worksheet XML from .xlsx.');
            }

            return $this->parseSheetRows($sheetXml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    /**
     * @return list<string>
     */
    private function parseSharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false || $xml === '') {
            return [];
        }

        $dom = $this->loadXml($xml);
        $strings = [];
        foreach ($dom->getElementsByTagName('si') as $si) {
            $text = '';
            foreach ($si->getElementsByTagName('t') as $t) {
                $text .= $t->textContent;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function resolveFirstSheetPath(\ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbook !== false && $rels !== false) {
            $workbookDom = $this->loadXml($workbook);
            $sheets = $workbookDom->getElementsByTagName('sheet');
            if ($sheets->length > 0) {
                $sheet = $sheets->item(0);
                $rId = $sheet?->attributes?->getNamedItemNS(
                    'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
                    'id'
                )?->nodeValue
                    ?: $sheet?->getAttribute('r:id');

                if (is_string($rId) && $rId !== '') {
                    $relsDom = $this->loadXml($rels);
                    foreach ($relsDom->getElementsByTagName('Relationship') as $rel) {
                        if ($rel->getAttribute('Id') === $rId) {
                            $target = ltrim(str_replace('\\', '/', $rel->getAttribute('Target')), '/');
                            if (! str_starts_with($target, 'xl/')) {
                                $target = 'xl/'.$target;
                            }

                            return $target;
                        }
                    }
                }
            }
        }

        foreach (['xl/worksheets/sheet1.xml', 'xl/worksheets/sheet.xml'] as $candidate) {
            if ($zip->locateName($candidate) !== false) {
                return $candidate;
            }
        }

        throw new \RuntimeException('No worksheet found inside the .xlsx file.');
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return list<array<int, mixed>>
     */
    private function parseSheetRows(string $sheetXml, array $sharedStrings): array
    {
        $dom = $this->loadXml($sheetXml);
        $rows = [];
        $maxColumn = 0;

        foreach ($dom->getElementsByTagName('row') as $rowNode) {
            $rowIndex = max(0, ((int) $rowNode->getAttribute('r')) - 1);
            while (count($rows) <= $rowIndex) {
                $rows[] = [];
            }

            foreach ($rowNode->getElementsByTagName('c') as $cell) {
                $ref = $cell->getAttribute('r');
                $colIndex = $this->columnIndexFromReference($ref);
                $maxColumn = max($maxColumn, $colIndex);
                $type = $cell->getAttribute('t');
                $valueNodes = $cell->getElementsByTagName('v');
                $raw = $valueNodes->length > 0 ? $valueNodes->item(0)?->textContent : '';

                if ($type === 'inlineStr') {
                    $text = '';
                    foreach ($cell->getElementsByTagName('t') as $t) {
                        $text .= $t->textContent;
                    }
                    $rows[$rowIndex][$colIndex] = $text;
                    continue;
                }

                if ($type === 's') {
                    $idx = (int) $raw;
                    $rows[$rowIndex][$colIndex] = $sharedStrings[$idx] ?? '';
                    continue;
                }

                $rows[$rowIndex][$colIndex] = $raw;
            }
        }

        // Normalise sparse rows to contiguous zero-based lists.
        foreach ($rows as $i => $row) {
            $normalised = array_fill(0, $maxColumn + 1, null);
            foreach ($row as $col => $value) {
                $normalised[$col] = $value;
            }
            $rows[$i] = $normalised;
        }

        return $rows;
    }

    private function columnIndexFromReference(string $reference): int
    {
        if (! preg_match('/^([A-Za-z]+)/', $reference, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    private function loadXml(string $xml): \DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new \RuntimeException('Invalid XML inside the spreadsheet package.');
        }

        return $dom;
    }

    /**
     * @return list<array<int, mixed>>
     */
    private function readWithPhpSpreadsheet(string $path, ?string $readerType): array
    {
        if (! class_exists(IOFactory::class)) {
            throw new \RuntimeException(
                'PhpSpreadsheet is not installed on this server (run composer install in /web). .xlsx can still use the zip reader; .xls requires PhpSpreadsheet.'
            );
        }

        $previousLimit = ini_get('memory_limit');
        if (is_string($previousLimit) && $this->memoryLimitBytes($previousLimit) < 256 * 1024 * 1024) {
            @ini_set('memory_limit', '256M');
        }

        try {
            if ($readerType !== null) {
                $reader = IOFactory::createReader($readerType);
            } else {
                $reader = IOFactory::createReaderForFile($path);
            }
            $reader->setReadDataOnly(true);

            try {
                $spreadsheet = $reader->load($path);
            } catch (\Throwable $exception) {
                $spreadsheet = IOFactory::load($path);
            }

            $rows = $spreadsheet->getSheet(0)->toArray(null, true, false, false);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return array_values(array_filter($rows, static fn ($row) => is_array($row)));
        } finally {
            if (is_string($previousLimit) && $previousLimit !== '') {
                @ini_set('memory_limit', $previousLimit);
            }
        }
    }

    private function memoryLimitBytes(string $limit): int
    {
        $limit = trim($limit);
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $limit,
        };
    }

    private function combineFailures(\Throwable $primary, \Throwable $fallback): string
    {
        $a = trim($primary->getMessage());
        $b = trim($fallback->getMessage());

        if ($a === $b || $b === '') {
            return $a !== '' ? $a : 'Unable to read spreadsheet.';
        }

        return $a.' | PhpSpreadsheet fallback: '.$b;
    }
}
