<?php

$t = file_get_contents(__DIR__ . '/proposal_text.txt');

echo "=== Modular Builder @ 77202 ===\n";
echo substr($t, 76800, 4000) . "\n\n";

echo "=== Faculty @ 37385 ===\n";
echo substr($t, 37000, 4000) . "\n\n";

echo "=== HOD Dashboard continuation ===\n";
$p = stripos($t, 'HOD Dashboard provides');
echo substr($t, $p, 5000) . "\n";
