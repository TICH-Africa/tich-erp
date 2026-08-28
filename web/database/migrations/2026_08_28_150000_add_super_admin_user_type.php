<?php

use App\Support\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM('
            ."'student', 'staff', 'admin', 'external', 'super_admin'"
            .') NOT NULL DEFAULT \'student\''
        );

        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('ur.user_id')
                    ->from('user_roles as ur')
                    ->join('roles as r', 'r.id', '=', 'ur.role_id')
                    ->where('r.role_name', 'Super Admin');
            })
            ->where('user_type', UserType::ADMIN)
            ->update(['user_type' => UserType::SUPER_ADMIN]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('user_type', UserType::SUPER_ADMIN)
            ->update(['user_type' => UserType::ADMIN]);

        DB::statement(
            'ALTER TABLE `users` MODIFY COLUMN `user_type` ENUM('
            ."'student', 'staff', 'admin', 'external'"
            .') NOT NULL DEFAULT \'student\''
        );
    }
};
