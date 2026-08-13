<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_carousel_slides')) {
            return;
        }

        Schema::table('homepage_carousel_slides', function (Blueprint $table) {
            if (! Schema::hasColumn('homepage_carousel_slides', 'program_id')) {
                $table->unsignedBigInteger('program_id')->nullable()->after('id');
                $table->unique('program_id');
                $table->foreign('program_id')->references('id')->on('academic_programs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_carousel_slides')) {
            return;
        }

        Schema::table('homepage_carousel_slides', function (Blueprint $table) {
            if (Schema::hasColumn('homepage_carousel_slides', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropUnique(['program_id']);
                $table->dropColumn('program_id');
            }
        });
    }
};
