<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();
            $table->text('reason');
            $table->string('movement_type', 50)->default('transfer');
            $table->foreignId('requested_by')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('approval_status', 50)->default('pending');
            $table->string('status', 50)->default('pending');
            $table->date('movement_date')->nullable();
            $table->date('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};
