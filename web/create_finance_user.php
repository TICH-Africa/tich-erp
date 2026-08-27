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

$email = 'finance@tich.ac.ke';
$password = 'Password123!';
$roleName = 'Finance Manager';

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

$role = Role::query()->where('role_name', $roleName)->first();

if (! $role) {
    $role = Role::create(['role_name' => $roleName]);
    echo "Created role: $roleName\n";
}

$hasRole = DB::table('user_roles')
    ->where('user_id', $user->id)
    ->where('role_id', $role->id)
    ->exists();

if (! $hasRole) {
    $rbac = app(\App\Services\RBACService::class);
    $rbac->assignRoleToUser($user, $role->id);
    echo "Assigned role '$roleName' to $email\n";
} else {
    echo "Role '$roleName' already assigned to $email\n";
}

echo "Done.\n";
