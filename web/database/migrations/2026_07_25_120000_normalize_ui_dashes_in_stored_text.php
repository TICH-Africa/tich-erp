<?php

use App\Support\UiText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('program_timetable_sessions')) {
            DB::table('program_timetable_sessions')
                ->select(['id', 'title'])
                ->whereNotNull('title')
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $normalized = UiText::normalizeDash($row->title);
                        if ($normalized !== $row->title) {
                            DB::table('program_timetable_sessions')
                                ->where('id', $row->id)
                                ->update(['title' => $normalized]);
                        }
                    }
                });
        }

        if (Schema::hasTable('program_timetables')) {
            foreach (['title', 'generation_notes'] as $column) {
                DB::table('program_timetables')
                    ->select(['id', $column])
                    ->whereNotNull($column)
                    ->orderBy('id')
                    ->chunkById(200, function ($rows) use ($column) {
                        foreach ($rows as $row) {
                            $normalized = UiText::normalizeDash($row->{$column});
                            if ($normalized !== $row->{$column}) {
                                DB::table('program_timetables')
                                    ->where('id', $row->id)
                                    ->update([$column => $normalized]);
                            }
                        }
                    });
            }
        }
    }

    public function down(): void
    {
        // Irreversible content normalization.
    }
};
