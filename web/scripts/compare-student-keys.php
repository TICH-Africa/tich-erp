<?php

$root = dirname(__DIR__, 2);
$sql = file_get_contents($root.'/deploy/tichafri_dbmain.sql');
$pdo = new PDO('mysql:host=127.0.0.1;dbname=tich_erp', 'root', '');

function dumpKeys(string $sql, string $table): array
{
    $keys = [];
    // From CREATE TABLE body
    if (preg_match('/CREATE TABLE `'.$table.'`\s*\((.*?)\)\s*ENGINE=/is', $sql, $m)) {
        foreach (preg_split('/\r?\n/', $m[1]) as $line) {
            $line = trim($line, " \t\n\r\0\x0B,");
            if (preg_match('/^(PRIMARY KEY|UNIQUE KEY|KEY|CONSTRAINT)\s*(.*)$/i', $line, $km)) {
                $keys[] = $km[0];
            }
        }
    }
    // From ALTER TABLE ADD ...
    if (preg_match_all('/ALTER TABLE `'.$table.'`\s+(.*?);/is', $sql, $matches)) {
        foreach ($matches[1] as $chunk) {
            foreach (preg_split('/,\s*(?=ADD |MODIFY |CHANGE |DROP )/i', $chunk) as $part) {
                $part = trim($part);
                if (preg_match('/^ADD\s+(PRIMARY KEY|UNIQUE KEY|KEY|CONSTRAINT|FOREIGN KEY)\b/i', $part)) {
                    $keys[] = preg_replace('/\s+/', ' ', $part);
                }
            }
        }
    }

    return $keys;
}

function localKeys(PDO $pdo, string $table): array
{
    $rows = $pdo->query("SHOW INDEX FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $r) {
        $out[] = $r['Key_name'].' ('.$r['Column_name'].')'.($r['Non_unique'] == 0 ? ' UNIQUE' : '');
    }

    return $out;
}

$tables = ['students', 'applicants', 'applications', 'student_semester_registrations', 'admission_letters', 'users'];
$report = [];
foreach ($tables as $t) {
    $report[$t] = [
        'prod_keys' => dumpKeys($sql, $t),
        'local_keys' => localKeys($pdo, $t),
    ];
}

// Check AUTO_INCREMENT presence in prod ALTER
$auto = [];
foreach ($tables as $t) {
    $auto[$t] = (bool) preg_match('/ALTER TABLE `'.$t.'`\s+MODIFY\s+`id`.*?AUTO_INCREMENT/is', $sql)
        || (bool) preg_match('/ALTER TABLE `'.$t.'`\s+CHANGE\s+`id`.*?AUTO_INCREMENT/is', $sql)
        || (bool) preg_match('/-- AUTO_INCREMENT for table `'.$t.'`.*?ALTER TABLE `'.$t.'`\s+MODIFY\s+`id`.*?AUTO_INCREMENT/is', $sql);
}

$report['auto_increment_in_prod_dump'] = $auto;
file_put_contents($root.'/deploy/_student_keys_diff.json', json_encode($report, JSON_PRETTY_PRINT));
echo "Wrote deploy/_student_keys_diff.json\n";
foreach ($auto as $t => $ok) {
    echo "$t AUTO_INCREMENT in dump: ".($ok ? 'yes' : 'NO')."\n";
}
