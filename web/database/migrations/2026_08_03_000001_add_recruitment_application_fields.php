<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('recruitment_applications', 'id_number')) {
                $table->string('id_number', 50)->nullable()->after('full_name');
            }
            if (! Schema::hasColumn('recruitment_applications', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('id_number');
            }
            if (! Schema::hasColumn('recruitment_applications', 'gender')) {
                $table->string('gender', 20)->nullable()->after('date_of_birth');
            }
            if (! Schema::hasColumn('recruitment_applications', 'marital_status')) {
                $table->string('marital_status', 50)->nullable()->after('gender');
            }
            if (! Schema::hasColumn('recruitment_applications', 'physical_address')) {
                $table->string('physical_address', 500)->nullable()->after('postal_address');
            }
            if (! Schema::hasColumn('recruitment_applications', 'institution')) {
                $table->string('institution', 300)->nullable()->after('highest_qualification');
            }
            if (! Schema::hasColumn('recruitment_applications', 'year_completed')) {
                $table->integer('year_completed')->nullable()->after('institution');
            }
            if (! Schema::hasColumn('recruitment_applications', 'grade')) {
                $table->string('grade', 50)->nullable()->after('year_completed');
            }
            if (! Schema::hasColumn('recruitment_applications', 'referee1_name')) {
                $table->string('referee1_name', 300)->nullable()->after('years_of_experience');
            }
            if (! Schema::hasColumn('recruitment_applications', 'referee1_title')) {
                $table->string('referee1_title', 200)->nullable()->after('referee1_name');
            }
            if (! Schema::hasColumn('recruitment_applications', 'referee1_organization')) {
                $table->string('referee1_organization', 300)->nullable()->after('referee1_title');
            }
            if (! Schema::hasColumn('recruitment_applications', 'referee1_contact')) {
                $table->string('referee1_contact', 100)->nullable()->after('referee1_organization');
            }
            if (! Schema::hasColumn('recruitment_applications', 'referee2_name')) {
                $table->string('referee2_name', 300)->nullable()->after('referee1_contact');
            }
            if (! Schema::hasColumn('recruitment_applications', 'referee2_title')) {
                $table->string('referee2_title', 200)->nullable()->after('referee2_name');
            }
            if (! Schema::hasColumn('recruitment_applications', 'referee2_organization')) {
                $table->string('referee2_organization', 300)->nullable()->after('referee2_title');
            }
            if (! Schema::hasColumn('recruitment_applications', 'referee2_contact')) {
                $table->string('referee2_contact', 100)->nullable()->after('referee2_organization');
            }
            if (! Schema::hasColumn('recruitment_applications', 'expected_salary')) {
                $table->string('expected_salary', 100)->nullable()->after('referee2_contact');
            }
            if (! Schema::hasColumn('recruitment_applications', 'notice_period')) {
                $table->string('notice_period', 50)->nullable()->after('expected_salary');
            }
            if (! Schema::hasColumn('recruitment_applications', 'how_did_you_hear')) {
                $table->string('how_did_you_hear', 300)->nullable()->after('notice_period');
            }
            if (! Schema::hasColumn('recruitment_applications', 'status')) {
                $table->string('status', 50)->default('submitted')->after('application_source');
            }
            if (! Schema::hasColumn('recruitment_applications', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('recruitment_applications', 'reviewed_at')) {
                $table->dateTime('reviewed_at')->nullable()->after('reviewed_by');
            }
            if (! Schema::hasColumn('recruitment_applications', 'decision')) {
                $table->string('decision', 50)->nullable()->after('reviewed_at'); // approved, rejected, shortlisted, pending
            }
            if (! Schema::hasColumn('recruitment_applications', 'decision_notes')) {
                $table->text('decision_notes')->nullable()->after('decision');
            }
            if (! Schema::hasColumn('recruitment_applications', 'is_viewed')) {
                $table->tinyInteger('is_viewed')->default(0)->after('decision_notes');
            }

            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
            $table->index('status');
            $table->index('decision');
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_applications', function (Blueprint $table) {
            if (Schema::hasColumn('recruitment_applications', 'reviewed_by')) {
                $table->dropForeign(['reviewed_by']);
            }
            $table->dropIfExists('recruitment_applications');
        });
    }
};
