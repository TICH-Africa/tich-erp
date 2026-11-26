<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('objective_submissions')
            ->whereNull('student_submitted_at')
            ->where(function ($query) {
                $query->whereNotNull('auto_graded_at')
                    ->orWhereNotNull('score_obtained');
            })
            ->update([
                'student_submitted_at' => DB::raw('COALESCE(auto_graded_at, updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        // Backfilled timestamps cannot be distinguished safely from newly captured submissions.
    }
};
