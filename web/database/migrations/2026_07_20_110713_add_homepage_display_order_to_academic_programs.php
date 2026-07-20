<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academic_programs', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_programs', 'is_featured_on_homepage')) {
                $table->tinyInteger('is_featured_on_homepage')->default(0);
            }
            if (! Schema::hasColumn('academic_programs', 'homepage_display_order')) {
                $table->integer('homepage_display_order')->default(0);
            }
            if (! Schema::hasColumn('academic_programs', 'entry_requirements')) {
                $table->string('entry_requirements', 500)->nullable();
            }
            if (! Schema::hasColumn('academic_programs', 'homepage_tagline')) {
                $table->string('homepage_tagline', 300)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_programs', function (Blueprint $table) {
            foreach (['homepage_display_order', 'is_featured_on_homepage', 'entry_requirements', 'homepage_tagline'] as $column) {
                if (Schema::hasColumn('academic_programs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
