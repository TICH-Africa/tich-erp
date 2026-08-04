<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearance_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offboarding_request_id');
            $table->string('department', 100); // HR, Finance, ICT, Library, Supervisor, CEO
            $table->string('item', 300);
            $table->tinyInteger('is_completed')->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();

            $table->foreign('offboarding_request_id')->references('id')->on('offboarding_requests')->restrictOnDelete();
            $table->foreign('completed_by')->references('id')->on('staff')->nullOnDelete();
            $table->index('offboarding_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_checklist_items');
    }
};
