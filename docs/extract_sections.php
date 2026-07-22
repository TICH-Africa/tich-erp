<?php

$t = file_get_contents(__DIR__ . '/proposal_text.txt');

$keys = [
    'Course Version',
    'curriculum engine',
    'Unit & Department',
    'versioning',
    'modular',
    'trimester',
    'learning hour',
    'Academic Dashboard',
    'Phase 2',
    'Phase II',
    'Academics Department',
    'Programme Builder',
    'program_units',
    'CEO approval',
    'Registry',
    'Curriculum',
];

foreach ($keys as $k) {
    $offset = 0;
    $found = 0;
    while (($p = stripos($t, $k, $offset)) !== false && $found < 3) {
        echo "=== {$k} @ {$p} ===\n";
        echo substr($t, max(0, $p - 300), 1200) . "\n\n";
        $offset = $p + strlen($k);
        $found++;
    }
}
