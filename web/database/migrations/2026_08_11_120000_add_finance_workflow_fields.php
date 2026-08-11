<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_structures')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                if (! Schema::hasColumn('fee_structures', 'status')) {
                    $table->string('status', 50)->default('draft')->after('is_active');
                }
                if (! Schema::hasColumn('fee_structures', 'version')) {
                    $table->string('version', 20)->nullable()->after('status');
                }
                if (! Schema::hasColumn('fee_structures', 'notes')) {
                    $table->text('notes')->nullable()->after('version');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'status')) {
                    $table->string('status', 50)->default('INITIATED')->after('payment_reference');
                }
                if (! Schema::hasColumn('payments', 'mpesa_receipt_number')) {
                    $table->string('mpesa_receipt_number', 50)->nullable()->unique()->after('status');
                }
            });
        }

        if (Schema::hasTable('receipts') && ! Schema::hasColumn('receipts', 'receipt_number')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->string('receipt_number', 50)->unique()->after('id');
            });
        }

        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                if (! Schema::hasColumn('refunds', 'status')) {
                    $table->string('status', 50)->default('pending')->after('reason');
                }
                if (! Schema::hasColumn('refunds', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('requested_by');
                }
                if (! Schema::hasColumn('refunds', 'approved_at')) {
                    $table->dateTime('approved_at')->nullable()->after('approved_by');
                }
                if (! Schema::hasColumn('refunds', 'processed_by')) {
                    $table->unsignedBigInteger('processed_by')->nullable()->after('approved_at');
                }
                if (! Schema::hasColumn('refunds', 'processed_at')) {
                    $table->dateTime('processed_at')->nullable()->after('processed_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fee_structures')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $columns = array_filter(
                    ['notes', 'version', 'status'],
                    fn (string $column) => Schema::hasColumn('fee_structures', $column),
                );

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $columns = array_filter(
                    ['mpesa_receipt_number', 'status'],
                    fn (string $column) => Schema::hasColumn('payments', $column),
                );

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('receipts') && Schema::hasColumn('receipts', 'receipt_number')) {
            Schema::table('receipts', function (Blueprint $table) {
                $table->dropColumn(['receipt_number']);
            });
        }

        if (Schema::hasTable('refunds')) {
            Schema::table('refunds', function (Blueprint $table) {
                $columns = array_filter(
                    ['processed_at', 'processed_by', 'approved_at', 'approved_by', 'status'],
                    fn (string $column) => Schema::hasColumn('refunds', $column),
                );

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
