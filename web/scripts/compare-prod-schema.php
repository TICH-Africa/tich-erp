<?php

/**
 * Compare production dump schema vs local MySQL tich_erp.
 */
$root = dirname(__DIR__, 2); // c:\xampp\htdocs\tich-erp
$dumpPath = $root . '/deploy/tichafri_dbmain.sql';
$outDir = $root . '/deploy';

$pdo = new PDO('mysql:host=127.0.0.1;dbname=tich_erp', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function parseDumpTables(string $sql): array
{
    $tables = [];
    // Split on CREATE TABLE
    if (! preg_match_all('/CREATE TABLE `([^`]+)`\s*\((.*?)\)\s*ENGINE=/is', $sql, $matches, PREG_SET_ORDER)) {
        return $tables;
    }

    foreach ($matches as $m) {
        $name = $m[1];
        $body = $m[2];
        $columns = [];
        foreach (preg_split('/\r?\n/', $body) as $line) {
            $line = trim($line, " \t\n\r\0\x0B,");
            if ($line === '' || str_starts_with($line, 'PRIMARY KEY') || str_starts_with($line, 'UNIQUE KEY')
                || str_starts_with($line, 'KEY ') || str_starts_with($line, 'CONSTRAINT')
                || str_starts_with($line, 'FULLTEXT') || str_starts_with($line, 'SPATIAL')) {
                continue;
            }
            if (preg_match('/^`([^`]+)`\s+(.+)$/', $line, $cm)) {
                $columns[$cm[1]] = rtrim($cm[2], ',');
            }
        }
        $tables[$name] = $columns;
    }

    return $tables;
}

function localTables(PDO $pdo): array
{
    $tables = [];
    $names = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($names as $name) {
        $cols = [];
        $rows = $pdo->query('SHOW FULL COLUMNS FROM `'.$name.'`')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $cols[$row['Field']] = $row['Type']
                .($row['Null'] === 'NO' ? ' NOT NULL' : '')
                .(isset($row['Default']) && $row['Default'] !== null ? ' DEFAULT '.$row['Default'] : '')
                .($row['Extra'] ? ' '.$row['Extra'] : '');
        }
        $tables[$name] = $cols;
    }

    return $tables;
}

$prodSql = file_get_contents($dumpPath);
$prod = parseDumpTables($prodSql);
$local = localTables($pdo);

$prodNames = array_keys($prod);
$localNames = array_keys($local);
sort($prodNames);
sort($localNames);

$missingInProd = array_values(array_diff($localNames, $prodNames));
$extraInProd = array_values(array_diff($prodNames, $localNames));

$columnDiffs = [];
$studentFocus = [];

$studentHints = ['student', 'applicant', 'admission', 'enrol', 'registration', 'application', 'fee', 'invoice', 'semester', 'unit', 'program', 'intake', 'transcript', 'clearance'];

foreach (array_intersect($localNames, $prodNames) as $table) {
    $localCols = array_keys($local[$table]);
    $prodCols = array_keys($prod[$table]);
    $missingCols = array_values(array_diff($localCols, $prodCols));
    $extraCols = array_values(array_diff($prodCols, $localCols));
    if ($missingCols || $extraCols) {
        $entry = [
            'table' => $table,
            'missing_in_prod' => $missingCols,
            'extra_in_prod' => $extraCols,
            'local_defs' => [],
            'prod_defs' => [],
        ];
        foreach ($missingCols as $c) {
            $entry['local_defs'][$c] = $local[$table][$c];
        }
        foreach ($extraCols as $c) {
            $entry['prod_defs'][$c] = $prod[$table][$c];
        }
        $columnDiffs[] = $entry;

        $hay = strtolower($table);
        foreach ($studentHints as $hint) {
            if (str_contains($hay, $hint)) {
                $studentFocus[] = $entry;
                break;
            }
        }
    }
}

$report = [
    'summary' => [
        'local_tables' => count($localNames),
        'prod_tables' => count($prodNames),
        'tables_missing_in_prod' => count($missingInProd),
        'tables_only_in_prod' => count($extraInProd),
        'tables_with_column_diffs' => count($columnDiffs),
        'student_related_column_diffs' => count($studentFocus),
    ],
    'tables_missing_in_prod' => $missingInProd,
    'tables_only_in_prod' => $extraInProd,
    'student_related_diffs' => $studentFocus,
    'all_column_diffs' => $columnDiffs,
];

file_put_contents($outDir.'/_schema_diff_report.json', json_encode($report, JSON_PRETTY_PRINT));

echo "Local tables: {$report['summary']['local_tables']}\n";
echo "Prod tables: {$report['summary']['prod_tables']}\n";
echo "Missing in prod: {$report['summary']['tables_missing_in_prod']}\n";
echo "Only in prod: {$report['summary']['tables_only_in_prod']}\n";
echo "Column diffs: {$report['summary']['tables_with_column_diffs']}\n";
echo "Student-related column diffs: {$report['summary']['student_related_column_diffs']}\n";
echo "Report: deploy/_schema_diff_report.json\n";
