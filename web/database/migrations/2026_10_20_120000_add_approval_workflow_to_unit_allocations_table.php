<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unit_allocations')) {
            return;
        }

        Schema::table('unit_allocations', function (Blueprint $table) {
            if (! Schema::hasColumn('unit_allocations', 'program_id')) {
                $table->unsignedBigInteger('program_id')->nullable()->after('unit_id');
            }
            if (! Schema::hasColumn('unit_allocations', 'status')) {
                $table->string('status', 30)->default('approved')->after('campus_id');
            }
            if (! Schema::hasColumn('unit_allocations', 'submitted_by')) {
                $table->unsignedBigInteger('submitted_by')->nullable()->after('status');
            }
            if (! Schema::hasColumn('unit_allocations', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            }
            if (! Schema::hasColumn('unit_allocations', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('unit_allocations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('unit_allocations', 'approval_comments')) {
                $table->string('approval_comments', 500)->nullable()->after('approved_at');
            }
        });

        Schema::table('unit_allocations', function (Blueprint $table) {
            if (! $this->hasIndex('unit_allocations', 'unit_allocations_program_id_index')) {
                $table->index('program_id', 'unit_allocations_program_id_index');
            }
            if (! $this->hasIndex('unit_allocations', 'unit_allocations_status_index')) {
                $table->index('status', 'unit_allocations_status_index');
            }
        });

        // A row that is switched off and carries no approval trail was retired, not approved.
        DB::table('unit_allocations')
            ->where('is_active', 0)
            ->whereNull('approved_at')
            ->whereNull('submitted_at')
            ->update(['status' => 'removed']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('unit_allocations')) {
            return;
        }

        Schema::table('unit_allocations', function (Blueprint $table) {
            foreach (['unit_allocations_program_id_index', 'unit_allocations_status_index'] as $index) {
                if ($this->hasIndex('unit_allocations', $index)) {
                    $table->dropIndex($index);
                }
            }

            foreach (['program_id', 'status', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'approval_comments'] as $column) {
                if (Schema::hasColumn('unit_allocations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn (stdClass $row) => $row->Key_name === $index);
    }
};
