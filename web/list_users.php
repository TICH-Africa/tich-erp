<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Users:\n";
foreach (\App\Models\User::all(['email','username']) as $u) {
    echo $u->email . ' / ' . $u->username . "\n";
}
