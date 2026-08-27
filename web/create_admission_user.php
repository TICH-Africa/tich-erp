<?php
$webRoot = 'C:\Users\ADMIN\Documents\tich-erp\web';
require $webRoot . '/vendor/autoload.php';
$app = require_once $webRoot . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$email = 'admission@tich.ac.ke';
$password = 'Password123!';
$roleName = 'Admissions Officer';

$user = User::query()->where('email', $email)->first();

if (! $user) {
    $user = User::create([
        'email' => $email,
        'user_type' => 'staff',
        'password_hash' => Hash::make($password),
        'is_active' => true,
        'mfa_enabled' => true,
        'mfa_method' => 'email',
        'mfa_verified' => false,
    ]);
    echo "Created user: $email\n";
} else {
    echo "User already exists: $email\n";
}

$roleId = Role::query()->where('role_name', $roleName)->value('id');

if ($roleId) {
    $hasRole = DB::table('user_roles')
        ->where('user_id', $user->id)
        ->where('role_id', $roleId)
        ->exists();

    if (! $hasRole) {
        $rbac = app(\App\Services\RBACService::class);
        $rbac->assignRoleToUser($user, $roleId);
        echo "Assigned role '$roleName' to $email\n";
    } else {
        echo "Role '$roleName' already assigned to $email\n";
    }
} else {
    echo "Role '$roleName' not found\n";
}

echo "Done.\n";
