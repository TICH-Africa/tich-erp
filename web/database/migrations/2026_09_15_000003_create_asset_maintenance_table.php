<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('maintenance_type', 50)->default('repair');
            $table->text('fault_description');
            $table->string('priority', 50)->default('medium');
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->text('work_done')->nullable();
            $table->json('parts_used')->nullable();
            $table->decimal('parts_cost', 12, 2)->default(0.00);
            $table->decimal('labour_cost', 12, 2)->default(0.00);
            $table->string('technician_name', 200)->nullable();
            $table->string('technician_phone', 30)->nullable();
            $table->json('attachment_paths')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status', 50)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance');
    }
};
