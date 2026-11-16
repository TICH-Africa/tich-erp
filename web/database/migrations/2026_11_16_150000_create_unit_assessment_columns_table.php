<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('unit_assessment_columns')) {
            return;
        }

        Schema::create('unit_assessment_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('assessment_name', 200);
            $table->string('assessment_type', 50);
            $table->decimal('max_score', 5, 2);
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();

            $table->unique(['unit_id', 'semester_id', 'assessment_name'], 'unit_assessment_columns_unit_semester_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_assessment_columns');
    }
};
