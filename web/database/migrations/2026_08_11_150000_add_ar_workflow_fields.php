<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'last_reminder_sent_at')) {
                    $table->dateTime('last_reminder_sent_at')->nullable()->after('sent_at');
                }
                if (! Schema::hasColumn('invoices', 'reminder_count')) {
                    $table->unsignedInteger('reminder_count')->default(0)->after('last_reminder_sent_at');
                }
            });
        }

        if (! Schema::hasTable('credit_memos')) {
            Schema::create('credit_memos', function (Blueprint $table) {
                $table->id();
                $table->string('credit_memo_number', 50)->unique();
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('student_account_id');
                $table->unsignedBigInteger('student_id');
                $table->decimal('amount', 12, 2);
                $table->string('reason', 500);
                $table->string('status', 50)->default('issued');
                $table->unsignedBigInteger('issued_by');
                $table->dateTime('issued_at')->useCurrent();
                $table->dateTime('created_at')->useCurrent();
                $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
                $table->foreign('student_account_id')->references('id')->on('student_accounts')->restrictOnDelete();
                $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
                $table->foreign('issued_by')->references('id')->on('staff')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_memos');

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $columns = array_filter(
                    ['reminder_count', 'last_reminder_sent_at'],
                    fn (string $column) => Schema::hasColumn('invoices', $column),
                );

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
