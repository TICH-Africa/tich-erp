<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=tich_erp', 'root', '');
foreach (['students', 'applicants', 'applications', 'academic_records', 'student_financial_records', 'student_semester_registrations'] as $t) {
    echo "LOCAL $t\n";
    try {
        foreach ($pdo->query("SHOW COLUMNS FROM `$t`") as $r) {
            echo '  '.$r['Field'].' | '.$r['Type'].' | null='.$r['Null']."\n";
        }
    } catch (Throwable $e) {
        echo "  MISSING\n";
    }
    echo "\n";
}
