<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_carry_forward_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->integer('from_year');
            $table->integer('to_year');
            $table->decimal('days_requested', 5, 2);
            $table->decimal('days_approved', 5, 2)->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->cascadeOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('staff')->nullOnDelete();
            $table->unique(['staff_id', 'leave_type_id', 'from_year'], 'carry_forward_unique_per_year');
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->decimal('carried_forward_days', 5, 2)->default(0)->after('entitled_days');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_carry_forward_requests');

        if (Schema::hasColumn('leave_balances', 'carried_forward_days')) {
            Schema::table('leave_balances', function (Blueprint $table) {
                $table->dropColumn('carried_forward_days');
            });
        }
    }
};
