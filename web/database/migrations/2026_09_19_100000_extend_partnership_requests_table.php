<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partnership_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('partnership_requests', 'applicant_type')) {
                $table->string('applicant_type', 30)->default('organisation')->after('request_number');
            }
            if (! Schema::hasColumn('partnership_requests', 'first_name')) {
                $table->string('first_name', 120)->nullable()->after('applicant_type');
            }
            if (! Schema::hasColumn('partnership_requests', 'last_name')) {
                $table->string('last_name', 120)->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('partnership_requests', 'alternative_email')) {
                $table->string('alternative_email', 255)->nullable()->after('email');
            }
            if (! Schema::hasColumn('partnership_requests', 'alternative_phone')) {
                $table->string('alternative_phone', 30)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('partnership_requests', 'research_area')) {
                $table->string('research_area', 200)->nullable()->after('alternative_phone');
            }
            if (! Schema::hasColumn('partnership_requests', 'what_they_do')) {
                $table->text('what_they_do')->nullable()->after('research_area');
            }
            if (! Schema::hasColumn('partnership_requests', 'why_partnership')) {
                $table->text('why_partnership')->nullable()->after('what_they_do');
            }
            if (! Schema::hasColumn('partnership_requests', 'organisation_details')) {
                $table->text('organisation_details')->nullable()->after('organization_name');
            }
            if (! Schema::hasColumn('partnership_requests', 'individual_details')) {
                $table->text('individual_details')->nullable()->after('organisation_details');
            }
        });

        // Soften NOT NULL columns for individual applicants (MySQL)
        try {
            DB::statement('ALTER TABLE `partnership_requests` MODIFY `organization_name` varchar(300) NULL');
            DB::statement('ALTER TABLE `partnership_requests` MODIFY `organization_type` varchar(50) NULL');
            DB::statement('ALTER TABLE `partnership_requests` MODIFY `contact_person` varchar(200) NULL');
            DB::statement('ALTER TABLE `partnership_requests` MODIFY `proposed_scope` text NULL');
        } catch (\Throwable) {
            // Already nullable or unsupported engine
        }

        if (! Schema::hasTable('partnership_request_documents')) {
            Schema::create('partnership_request_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partnership_request_id');
                $table->string('title', 300)->nullable();
                $table->string('file_path', 500);
                $table->string('original_filename', 300)->nullable();
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->foreign('partnership_request_id', 'prd_request_fk')
                    ->references('id')->on('partnership_requests')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partnership_request_documents');
    }
};
