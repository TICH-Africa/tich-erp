<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('disposal_type', 50)->default('write_off');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposed_value', 12, 2)->nullable()->default(0.00);
            $table->text('reason');
            $table->text('disposal_details')->nullable();
            $table->foreignId('requested_by')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('approval_status', 50)->default('pending');
            $table->string('status', 50)->default('pending');
            $table->date('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
