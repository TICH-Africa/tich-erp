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
        Schema::table('research_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('research_projects', 'slug')) {
                $table->string('slug', 320)->nullable()->after('title');
            }
            if (! Schema::hasColumn('research_projects', 'body')) {
                $table->longText('body')->nullable()->after('abstract');
            }
            if (! Schema::hasColumn('research_projects', 'duration_value')) {
                $table->unsignedInteger('duration_value')->nullable()->after('start_date');
            }
            if (! Schema::hasColumn('research_projects', 'duration_unit')) {
                $table->string('duration_unit', 20)->nullable()->after('duration_value');
            }
            if (! Schema::hasColumn('research_projects', 'status_locked')) {
                $table->boolean('status_locked')->default(false)->after('status');
            }
            if (! Schema::hasColumn('research_projects', 'visibility')) {
                $table->string('visibility', 30)->default('draft')->after('is_featured');
            }
            if (! Schema::hasColumn('research_projects', 'published_at')) {
                $table->dateTime('published_at')->nullable()->after('visibility');
            }
        });

        if (Schema::hasColumn('research_projects', 'slug')) {
            $rows = DB::table('research_projects')->whereNull('slug')->orWhere('slug', '')->get(['id', 'title']);
            foreach ($rows as $row) {
                $base = Str::slug((string) $row->title) ?: 'research-'.$row->id;
                $slug = $base;
                $i = 2;
                while (DB::table('research_projects')->where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                    $slug = $base.'-'.$i;
                    $i++;
                }
                DB::table('research_projects')->where('id', $row->id)->update(['slug' => $slug]);
            }
        }

        try {
            Schema::table('research_projects', function (Blueprint $table) {
                $table->unique('slug', 'research_projects_slug_unique');
            });
        } catch (\Throwable) {
            // Index may already exist
        }

        if (! Schema::hasTable('research_project_documents')) {
            Schema::create('research_project_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('research_project_id');
                $table->string('title', 300);
                $table->string('file_path', 500);
                $table->string('original_filename', 300)->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->dateTime('created_at')->useCurrent();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('research_project_id', 'rpd_project_fk')
                    ->references('id')->on('research_projects')->cascadeOnDelete();
                $table->foreign('created_by', 'rpd_created_by_fk')
                    ->references('id')->on('staff')->nullOnDelete();
                $table->index(['research_project_id', 'sort_order'], 'rpd_project_sort_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('research_project_documents');

        Schema::table('research_projects', function (Blueprint $table) {
            foreach (['slug', 'body', 'duration_value', 'duration_unit', 'status_locked', 'visibility', 'published_at'] as $col) {
                if (Schema::hasColumn('research_projects', $col)) {
                    if ($col === 'slug') {
                        try {
                            $table->dropUnique('research_projects_slug_unique');
                        } catch (\Throwable) {
                        }
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
