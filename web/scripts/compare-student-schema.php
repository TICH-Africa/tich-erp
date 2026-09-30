<?php

$root = dirname(__DIR__, 2);
$dumpPath = $root.'/deploy/tichafri_dbmain.sql';
$outPath = $root.'/deploy/_student_schema_deep_diff.json';

$pdo = new PDO('mysql:host=127.0.0.1;dbname=tich_erp', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function parseDumpTables(string $sql): array
{
    $tables = [];
    if (! preg_match_all('/CREATE TABLE `([^`]+)`\s*\((.*?)\)\s*ENGINE=/is', $sql, $matches, PREG_SET_ORDER)) {
        return $tables;
    }

    foreach ($matches as $m) {
        $name = $m[1];
        $columns = [];
        foreach (preg_split('/\r?\n/', $m[2]) as $line) {
            $line = trim($line, " \t\n\r\0\x0B,");
            if ($line === '' || preg_match('/^(PRIMARY KEY|UNIQUE KEY|KEY |CONSTRAINT|FULLTEXT|SPATIAL)/', $line)) {
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

function normalizeType(string $def): string
{
    $def = strtolower($def);
    $def = preg_replace('/\s+/', ' ', $def);
    $def = str_replace(['`', '"'], '', $def);
    // Drop display widths that MySQL 8 often ignores in SHOW
    $def = preg_replace('/\bint\(\d+\)/', 'int', $def);
    $def = preg_replace('/\btinyint\(\d+\)/', 'tinyint', $def);
    $def = preg_replace('/\bsmallint\(\d+\)/', 'smallint', $def);
    $def = preg_replace('/\bbigint\(\d+\)/', 'bigint', $def);
    $def = preg_replace('/\bmediumint\(\d+\)/', 'mediumint', $def);
    $def = preg_replace('/ character set [a-z0-9_]+/', '', $def);
    $def = preg_replace('/ collate [a-z0-9_]+/', '', $def);
    $def = preg_replace('/ default current_timestamp\(\)/', ' default current_timestamp', $def);
    $def = preg_replace('/ on update current_timestamp\(\)/', ' on update current_timestamp', $def);
    $def = str_replace('default null', '', $def);
    $def = preg_replace('/\s+/', ' ', trim($def));

    return $def;
}

$focus = [
    'students', 'applicants', 'applications', 'application_documents', 'application_statuses',
    'admission_letters', 'student_accounts', 'student_addresses', 'student_next_of_kin',
    'student_semester_registrations', 'student_notifications', 'student_profile_change_requests',
    'student_document_requests', 'student_lifecycle_requests', 'student_clearance_items',
    'student_transcript_requests', 'student_suggestions', 'student_financial_records',
    'academic_records', 'users', 'fee_invoices', 'fee_payments', 'fee_structures',
    'program_intakes', 'academic_programs', 'semesters', 'units', 'unit_registrations',
    'enrollment', 'enrollments', 'registrations', 'student_unit_registrations',
];

$prod = parseDumpTables(file_get_contents($dumpPath));
$localNames = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

// Expand focus: any local/prod table matching student/applicant/admission/enrol
foreach (array_unique(array_merge($localNames, array_keys($prod))) as $t) {
    if (preg_match('/student|applicant|admission|enrol|application|transcript|clearance|intake/i', $t)) {
        $focus[] = $t;
    }
}
$focus = array_values(array_unique($focus));

$result = [
    'missing_tables_in_prod' => [],
    'missing_tables_in_local' => [],
    'type_or_null_mismatches' => [],
    'missing_columns_in_prod' => [],
    'missing_columns_in_local' => [],
];

foreach ($focus as $table) {
    $inLocal = in_array($table, $localNames, true);
    $inProd = isset($prod[$table]);

    if ($inLocal && ! $inProd) {
        $result['missing_tables_in_prod'][] = $table;
        continue;
    }
    if ($inProd && ! $inLocal) {
        $result['missing_tables_in_local'][] = $table;
        continue;
    }
    if (! $inLocal && ! $inProd) {
        continue;
    }

    $localCols = [];
    foreach ($pdo->query("SHOW FULL COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $parts = $row['Type'];
        if ($row['Null'] === 'NO') {
            $parts .= ' not null';
        }
        if ($row['Default'] !== null) {
            $parts .= ' default '.$row['Default'];
        } elseif ($row['Null'] === 'YES' && $row['Default'] === null && ! str_contains(strtolower($row['Extra']), 'auto_increment')) {
            // leave
        }
        if ($row['Extra']) {
            $parts .= ' '.$row['Extra'];
        }
        $localCols[$row['Field']] = normalizeType($parts);
    }

    $prodCols = [];
    foreach ($prod[$table] as $name => $def) {
        $prodCols[$name] = normalizeType($def);
    }

    $missingProd = array_diff(array_keys($localCols), array_keys($prodCols));
    $missingLocal = array_diff(array_keys($prodCols), array_keys($localCols));
    foreach ($missingProd as $c) {
        $result['missing_columns_in_prod'][] = [
            'table' => $table,
            'column' => $c,
            'local' => $localCols[$c],
        ];
    }
    foreach ($missingLocal as $c) {
        $result['missing_columns_in_local'][] = [
            'table' => $table,
            'column' => $c,
            'prod' => $prodCols[$c],
        ];
    }

    foreach (array_intersect(array_keys($localCols), array_keys($prodCols)) as $c) {
        $l = $localCols[$c];
        $p = $prodCols[$c];
        // Compare core type + nullability roughly
        $lCore = preg_replace('/ default .*$/', '', $l);
        $pCore = preg_replace('/ default .*$/', '', $p);
        $lNull = str_contains($l, 'not null');
        $pNull = str_contains($p, 'not null');
        $lType = preg_replace('/\s+not null.*/', '', $lCore);
        $pType = preg_replace('/\s+not null.*/', '', $pCore);

        if ($lType !== $pType || $lNull !== $pNull) {
            $result['type_or_null_mismatches'][] = [
                'table' => $table,
                'column' => $c,
                'local' => $localCols[$c],
                'prod' => $prodCols[$c],
            ];
        }
    }
}

file_put_contents($outPath, json_encode($result, JSON_PRETTY_PRINT));
echo 'missing tables in prod: '.count($result['missing_tables_in_prod']).PHP_EOL;
echo 'missing cols in prod: '.count($result['missing_columns_in_prod']).PHP_EOL;
echo 'missing cols in local: '.count($result['missing_columns_in_local']).PHP_EOL;
echo 'type/null mismatches: '.count($result['type_or_null_mismatches']).PHP_EOL;
echo implode(', ', $result['missing_tables_in_prod']).PHP_EOL;
