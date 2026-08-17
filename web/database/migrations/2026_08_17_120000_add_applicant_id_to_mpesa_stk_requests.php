<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mpesa_stk_requests', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['student_id']);
        });

        Schema::table('mpesa_stk_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable()->change();
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->unsignedBigInteger('applicant_id')->nullable()->after('student_id');

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('applicant_id')->references('id')->on('applicants')->nullOnDelete();
            $table->index(['applicant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_stk_requests', function (Blueprint $table) {
            $table->dropForeign(['applicant_id']);
            $table->dropIndex(['applicant_id', 'status']);
            $table->dropColumn('applicant_id');
            $table->dropForeign(['invoice_id']);
            $table->dropForeign(['student_id']);
        });

        Schema::table('mpesa_stk_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')->nullable(false)->change();
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }
};
