<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (\App\Models\User::all(['email']) as $u) {
    echo $u->email . ' / ' . $u->displayName() . "\n";
}
