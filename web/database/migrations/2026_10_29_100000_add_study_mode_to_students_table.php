<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admissions has always asked for a campus type and accepts Online, but choosing Online only cleared
 * the campus, so nothing recorded that a person studies at a distance. An online student then looked
 * identical to a campus student who had not been allocated a campus yet, and Academics could not split
 * the two when registering units or planning classes. The mode now lives on the student and can be
 * flipped either way from the Admissions edit screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'study_mode')) {
                $table->string('study_mode', 20)->default('physical')->after('enrollment_campus_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'study_mode')) {
                $table->dropColumn('study_mode');
            }
        });
    }
};
