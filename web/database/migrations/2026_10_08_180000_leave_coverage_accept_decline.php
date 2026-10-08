<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_request_coverages')) {
            return;
        }

        Schema::table('leave_request_coverages', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_request_coverages', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('notified_at');
            }
            if (! Schema::hasColumn('leave_request_coverages', 'response_notes')) {
                $table->string('response_notes', 1000)->nullable()->after('responded_at');
            }
        });

        // Re-open auto-accepted stand-ins for leave still awaiting HR (no access granted yet).
        if (Schema::hasTable('leave_requests')) {
            $ids = DB::table('leave_request_coverages as c')
                ->join('leave_requests as lr', 'lr.id', '=', 'c.leave_request_id')
                ->where('c.status', 'accepted')
                ->whereNull('c.access_granted_at')
                ->whereIn('lr.overall_status', ['pending_hr', 'returned'])
                ->pluck('c.id');

            if ($ids->isNotEmpty()) {
                DB::table('leave_request_coverages')
                    ->whereIn('id', $ids->all())
                    ->update([
                        'status' => 'pending',
                        'responded_at' => null,
                        'response_notes' => null,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('leave_request_coverages')) {
            return;
        }

        Schema::table('leave_request_coverages', function (Blueprint $table) {
            if (Schema::hasColumn('leave_request_coverages', 'response_notes')) {
                $table->dropColumn('response_notes');
            }
            if (Schema::hasColumn('leave_request_coverages', 'responded_at')) {
                $table->dropColumn('responded_at');
            }
        });
    }
};
