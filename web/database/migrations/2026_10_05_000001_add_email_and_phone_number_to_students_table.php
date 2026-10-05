<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'email')) {
                $table->string('email', 255)->nullable()->after('emergency_contact_relationship')
                    ->comment('Contact email held on the student record (existing-student flow does not always link a User).');
            }

            if (! Schema::hasColumn('students', 'phone_number')) {
                $table->string('phone_number', 30)->nullable()->after('email')
                    ->comment('Contact phone number held on the student record.');
            }
        });

        // Keep the unique user-email index from being violated by duplicate student emails:
        // the existing-student flow validates uniqueness against users.email, so students.email
        // is intentionally not unique to allow a student to exist before/instead of a User row.
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'phone_number')) {
                $table->dropColumn('phone_number');
            }

            if (Schema::hasColumn('students', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};
