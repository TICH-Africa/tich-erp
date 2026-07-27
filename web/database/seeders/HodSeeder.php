<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Services\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HodSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'hod@tich.ac.ke'],
            [
                'username' => 'hod.demo',
                'user_type' => 'staff',
                'password_hash' => Hash::make('Password123!'),
                'is_active' => 1,
                'mfa_enabled' => false,
                'mfa_verified' => true,
            ]
        );

        $staff = Staff::query()->firstOrCreate(
            ['employee_number' => 'EMP-HOD-001'],
            [
                'first_name' => 'Mary',
                'surname' => 'Wanjiru',
                'date_of_birth' => '1980-03-15',
                'gender' => 'female',
                'email' => 'hod@tich.ac.ke',
                'phone_number' => '0722334455',
                'department_id' => 9,
                'job_title' => 'Head of Department',
                'employment_category' => 'permanent',
                'employment_start_date' => '2018-07-01',
                'employment_status' => 'active',
                'user_id' => $user->id,
                'is_teaching_staff' => 1,
            ]
        );

        $user->update(['staff_id' => $staff->id]);

        $roleId = Role::query()->where('role_name', 'HOD')->value('id');
        if ($roleId) {
            app(RBACService::class)->assignRoleToUser($user, $roleId);
        }

        $this->command->info('HOD user created: hod@tich.ac.ke / Password123!');
    }
}