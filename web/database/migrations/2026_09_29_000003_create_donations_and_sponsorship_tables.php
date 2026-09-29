<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->enum('donation_type', ['one_time', 'monthly', 'annual']);
            $table->decimal('amount', 12, 2);
            $table->string('designation', 50)->nullable(); // scholarships, emergency_aid, program_grants, student_welfare
            $table->string('donor_name', 300);
            $table->string('donor_email', 255);
            $table->string('donor_phone', 30)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('donor_email');
        });

        Schema::create('sponsorship_inquiries', function (Blueprint $table) {
            $table->id();
            $table->enum('sponsor_type', ['full', 'partial', 'co_sponsor', 'named_scholarship']);
            $table->enum('duration', ['1', '2', '3', 'ongoing']);
            $table->string('preferred_field', 50)->nullable();
            $table->string('sponsor_name', 300);
            $table->string('sponsor_email', 255);
            $table->string('sponsor_phone', 30);
            $table->text('sponsor_message')->nullable();
            $table->enum('status', ['pending', 'contacted', 'in_progress', 'completed', 'declined'])->default('pending');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('sponsor_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsorship_inquiries');
        Schema::dropIfExists('donations');
    }
};