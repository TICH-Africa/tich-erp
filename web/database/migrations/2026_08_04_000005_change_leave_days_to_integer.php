<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_requests') || ! Schema::hasTable('leave_balances')) {
            return;
        }

        DB::statement('UPDATE leave_requests SET days_requested = ROUND(days_requested)');
        DB::statement('UPDATE leave_balances SET balance_days = ROUND(balance_days), days_pending = ROUND(days_pending), days_taken = ROUND(days_taken)');

        DB::statement('ALTER TABLE leave_requests MODIFY days_requested INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE leave_balances MODIFY balance_days INT NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        if (! Schema::hasTable('leave_requests') || ! Schema::hasTable('leave_balances')) {
            return;
        }

        DB::statement('ALTER TABLE leave_requests MODIFY days_requested DECIMAL(5, 2) NOT NULL');
        DB::statement('ALTER TABLE leave_balances MODIFY balance_days DECIMAL(5, 2) NOT NULL DEFAULT 0.00');
    }
};
