<?php

use App\Models\Semester;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('semesters')
            ->select(['id', 'semester_label', 'semester_number'])
            ->orderBy('id')
            ->get()
            ->each(function ($semester) {
                DB::table('semesters')
                    ->where('id', $semester->id)
                    ->update([
                        'semester_label' => Semester::normalizeLabel(
                            $semester->semester_label,
                            $semester->semester_number ? (int) $semester->semester_number : null,
                        ),
                    ]);
            });
    }

    public function down(): void
    {
        // Display terminology only; no rollback required.
    }
};
