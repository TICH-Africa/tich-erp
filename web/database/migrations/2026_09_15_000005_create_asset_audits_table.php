<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('auditor_id')->constrained('staff')->cascadeOnDelete();
            $table->string('verification_status', 50)->default('pending');
            $table->boolean('location_verified')->default(false);
            $table->boolean('custodian_verified')->default(false);
            $table->string('condition', 50)->default('unknown');
            $table->text('notes')->nullable();
            $table->json('photo_paths')->nullable();
            $table->date('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_audits');
    }
};
