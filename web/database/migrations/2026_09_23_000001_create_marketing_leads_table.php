<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 300);
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('source')->nullable();
            $table->string('stage')->default('new');
            $table->foreignId('program_id')->nullable()->constrained('academic_programs')->nullOnDelete();
            $table->string('intake')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_followup')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_leads');
    }
};
