<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('marketing_leads')->cascadeOnDelete();
            $table->string('activity_type');
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('completed')->default(false);
            $table->date('completed_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_lead_activities');
    }
};
