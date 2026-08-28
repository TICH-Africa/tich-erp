<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-time data cleanup (Principal / Lecturer rename). Safe to keep as migration-style seeder.
 */
class SyncDefaultRolesSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('roles')) {
            return;
        }

        $principalId = DB::table('roles')->where('role_name', 'Principal')->value('id');

        if ($principalId) {
            DB::table('user_roles')->where('role_id', $principalId)->delete();
            DB::table('roles')->where('id', $principalId)->delete();
        }

        DB::table('roles')
            ->where('role_name', 'Lecturer')
            ->update([
                'role_name' => 'Lecturer/Tutor',
                'display_name' => 'Lecturer / Tutor',
            ]);

        $deanId = DB::table('roles')->where('role_name', 'Dean')->value('id');
        $deanOfStudentsId = DB::table('roles')->where('role_name', 'Dean of Students')->value('id');

        if ($deanId && $deanOfStudentsId) {
            $assignments = DB::table('user_roles')->where('role_id', $deanId)->get();

            foreach ($assignments as $assignment) {
                $exists = DB::table('user_roles')
                    ->where('user_id', $assignment->user_id)
                    ->where('role_id', $deanOfStudentsId)
                    ->where(function ($query) use ($assignment) {
                        if ($assignment->department_id) {
                            $query->where('department_id', $assignment->department_id);
                        } else {
                            $query->whereNull('department_id');
                        }
                    })
                    ->where(function ($query) use ($assignment) {
                        if ($assignment->campus_id) {
                            $query->where('campus_id', $assignment->campus_id);
                        } else {
                            $query->whereNull('campus_id');
                        }
                    })
                    ->exists();

                if (! $exists) {
                    DB::table('user_roles')->where('id', $assignment->id)->update([
                        'role_id' => $deanOfStudentsId,
                    ]);
                } else {
                    DB::table('user_roles')->where('id', $assignment->id)->delete();
                }
            }

            DB::table('roles')->where('id', $deanId)->delete();
        }
    }
}
