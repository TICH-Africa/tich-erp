<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_milestones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_account_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('milestone_type', 50); // registration, mid_semester, final
            $table->tinyInteger('percentage')->default(0); // 50, 75, 100
            $table->decimal('milestone_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->string('status', 50)->default('pending'); // pending, partial, paid, overdue
            $table->date('due_date')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrentOnUpdate();

            $table->foreign('student_account_id')->references('id')->on('student_accounts')->restrictOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
            $table->foreign('recorded_by')->references('id')->on('staff')->nullOnDelete();
            $table->index(['student_account_id', 'milestone_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_milestones');
    }
};
