<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('qa_audit_checklists') && Schema::hasColumn('qa_audit_checklists', 'max_score')) {
            DB::statement('UPDATE `qa_audit_checklists` SET `max_score` = ROUND(`max_score`)');
            DB::statement('ALTER TABLE `qa_audit_checklists` MODIFY `max_score` INT UNSIGNED NOT NULL DEFAULT 100');
        }

        if (Schema::hasTable('qa_department_submissions') && Schema::hasColumn('qa_department_submissions', 'score')) {
            DB::statement('UPDATE `qa_department_submissions` SET `score` = ROUND(`score`) WHERE `score` IS NOT NULL');
            DB::statement('ALTER TABLE `qa_department_submissions` MODIFY `score` INT UNSIGNED NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('qa_audit_checklists') && Schema::hasColumn('qa_audit_checklists', 'max_score')) {
            DB::statement('ALTER TABLE `qa_audit_checklists` MODIFY `max_score` DECIMAL(5,2) NOT NULL DEFAULT 100.00');
        }

        if (Schema::hasTable('qa_department_submissions') && Schema::hasColumn('qa_department_submissions', 'score')) {
            DB::statement('ALTER TABLE `qa_department_submissions` MODIFY `score` DECIMAL(5,2) NULL DEFAULT NULL');
        }
    }
};
