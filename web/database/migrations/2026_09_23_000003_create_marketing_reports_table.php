<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type');
            $table->string('title');
            $table->date('report_date');
            $table->text('summary')->nullable();
            $table->text('commentary')->nullable();
            $table->text('anomalies')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'distributed', 'archived'])->default('draft');
            $table->foreignId('prepared_by')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('distributed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('distribution_list')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_reports');
    }
};
