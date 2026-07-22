<?php

$t = file_get_contents(__DIR__ . '/proposal_text.txt');

$keys = [
    'Builder',
    'Mapping',
    'version',
    'Implementation Plan',
    'Roadmap',
    'Deliverable',
    'Sprint',
    'Academic Board',
    'Modular Builder',
    'Curriculum &',
    'contact hours',
    'learning contact',
    'program version',
    'course catalog',
    'unit allocation',
    'HOD',
    'Faculty & Leadership Dashboard',
];

foreach ($keys as $k) {
    $offset = 0;
    $found = 0;
    while (($p = stripos($t, $k, $offset)) !== false && $found < 1) {
        echo "=== {$k} @ {$p} ===\n";
        echo substr($t, max(0, $p - 500), 2000) . "\n\n";
        $offset = $p + strlen($k);
        $found++;
    }
}
