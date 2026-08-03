<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'primary_email')) {
                $table->string('primary_email', 255)->nullable()->after('home_county');
            }
            if (! Schema::hasColumn('staff', 'organisation_email')) {
                $table->string('organisation_email', 255)->nullable()->after('primary_email');
            }
        });

        if (Schema::hasColumn('staff', 'email')) {
            foreach (DB::table('staff')->get() as $staff) {
                $legacyEmail = strtolower(trim((string) $staff->email));

                if ($legacyEmail === '') {
                    continue;
                }

                $isOrganisation = str_ends_with($legacyEmail, '@tich.ac.ke')
                    || str_ends_with($legacyEmail, '@tich.africa');

                if ($isOrganisation) {
                    $organisationEmail = preg_replace('/@tich\.ac\.ke$/', '@tich.africa', $legacyEmail);
                    $primaryEmail = $this->primaryEmailFromStaff($staff);
                } else {
                    $primaryEmail = $legacyEmail;
                    $organisationEmail = $this->organisationEmailFromStaff($staff);
                }

                DB::table('staff')->where('id', $staff->id)->update([
                    'primary_email' => $primaryEmail,
                    'organisation_email' => $organisationEmail,
                ]);

                if ($staff->user_id) {
                    DB::table('users')->where('id', $staff->user_id)->update([
                        'email' => $organisationEmail,
                    ]);
                }
            }

            Schema::table('staff', function (Blueprint $table) {
                $table->dropUnique(['email']);
                $table->dropColumn('email');
            });
        }

        if (Schema::hasColumn('staff', 'primary_email')) {
            DB::statement('ALTER TABLE staff MODIFY primary_email VARCHAR(255) NOT NULL');
        }

        if (Schema::hasColumn('staff', 'organisation_email')) {
            Schema::table('staff', function (Blueprint $table) {
                $table->unique('organisation_email');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('staff')) {
            return;
        }

        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'email')) {
                $table->string('email', 255)->nullable()->after('home_county');
            }
        });

        foreach (DB::table('staff')->get() as $staff) {
            DB::table('staff')->where('id', $staff->id)->update([
                'email' => $staff->organisation_email ?? $staff->primary_email,
            ]);
        }

        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'organisation_email')) {
                $table->dropUnique(['organisation_email']);
                $table->dropColumn('organisation_email');
            }
            if (Schema::hasColumn('staff', 'primary_email')) {
                $table->dropColumn('primary_email');
            }
            if (Schema::hasColumn('staff', 'email')) {
                $table->unique('email');
            }
        });
    }

    private function primaryEmailFromStaff(object $staff): string
    {
        $local = Str::slug(strtolower(trim($staff->first_name).'.'.trim($staff->surname)), '.');

        return ($local ?: 'employee').'@gmail.com';
    }

    private function organisationEmailFromStaff(object $staff): string
    {
        $base = Str::slug(strtolower(trim($staff->first_name).'.'.trim($staff->surname)), '.');
        $base = preg_replace('/[^a-z0-9.]/', '', $base) ?: 'employee';
        $email = $base.'@tich.africa';
        $counter = 1;

        while (
            DB::table('staff')
                ->where('organisation_email', $email)
                ->where('id', '!=', $staff->id)
                ->exists()
        ) {
            $email = $base.$counter.'@tich.africa';
            $counter++;
        }

        return $email;
    }
};
