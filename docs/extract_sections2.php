<?php

$t = file_get_contents(__DIR__ . '/proposal_text.txt');

$keys = [
    'Course Versioning',
    'Versioning Builder',
    'Unit & Department Mapping',
    'Unit and Department',
    'priority',
    'total learning hours',
    'Academic Registrar Terminal',
    'Syllabus Parameterization',
    'Course Definitions Curation',
    'Departmental Initialization',
    'Academic Calendar Configuration',
    'Three-Core Framework',
    'Implementation Roadmap',
    'Phase 1',
    'Milestone',
    'program_units',
    'credit matrix',
    'nursing block',
    'Pending Sign-Off',
    'Pending Registry',
];

foreach ($keys as $k) {
    $offset = 0;
    $found = 0;
    while (($p = stripos($t, $k, $offset)) !== false && $found < 2) {
        echo "=== {$k} @ {$p} ===\n";
        echo substr($t, max(0, $p - 400), 1600) . "\n\n";
        $offset = $p + strlen($k);
        $found++;
    }
}
