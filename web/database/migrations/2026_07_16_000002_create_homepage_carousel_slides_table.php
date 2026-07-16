<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_carousel_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title', 300);
            $table->string('subtitle', 500)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('cta_label', 100)->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->integer('display_order')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();
        });

        Schema::table('academic_programs', function (Blueprint $table) {
            if (! Schema::hasColumn('academic_programs', 'is_featured_on_homepage')) {
                $table->tinyInteger('is_featured_on_homepage')->default(0)->after('status');
            }
            if (! Schema::hasColumn('academic_programs', 'homepage_display_order')) {
                $table->integer('homepage_display_order')->default(0)->after('is_featured_on_homepage');
            }
            if (! Schema::hasColumn('academic_programs', 'homepage_tagline')) {
                $table->string('homepage_tagline', 500)->nullable()->after('homepage_display_order');
            }
            if (! Schema::hasColumn('academic_programs', 'entry_requirements')) {
                $table->text('entry_requirements')->nullable()->after('homepage_tagline');
            }
        });
    }

    public function down(): void
    {
        Schema::table('academic_programs', function (Blueprint $table) {
            $columns = ['is_featured_on_homepage', 'homepage_display_order', 'homepage_tagline', 'entry_requirements'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('academic_programs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('homepage_carousel_slides');
    }
};
