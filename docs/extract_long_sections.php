<?php

$t = file_get_contents(__DIR__ . '/proposal_text.txt');

$markers = [
    'Curriculum & Modular Builder',
    'multi-tiered curriculum customization',
    'Faculty & Leadership Dashboard',
    'Academic Registrar Management Terminal',
    'Curriculum Design & Unit Customization',
    'Entity Creation & Modification Workflow',
    'Academics & Registrar Console',
];

foreach ($markers as $k) {
    $p = stripos($t, $k);
    if ($p === false) {
        echo "NOT FOUND: {$k}\n\n";
        continue;
    }
    echo str_repeat('=', 80) . "\n{$k}\n" . str_repeat('=', 80) . "\n";
    echo substr($t, $p, 4500) . "\n\n";
}
