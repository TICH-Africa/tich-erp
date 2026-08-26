<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('events')) {
            return;
        }

        if (! Schema::hasColumn('events', 'slug')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('slug', 300)->nullable()->after('title');
            });
        }

        $used = [];

        foreach (DB::table('events')->orderBy('id')->get(['id', 'title', 'slug']) as $event) {
            if (! empty($event->slug) && ! isset($used[$event->slug])) {
                $used[$event->slug] = true;

                continue;
            }

            $base = Str::slug((string) $event->title) ?: 'event';
            $slug = $base;
            $i = 2;

            while (isset($used[$slug])) {
                $slug = $base.'-'.$i;
                $i++;
            }

            $used[$slug] = true;
            DB::table('events')->where('id', $event->id)->update(['slug' => $slug]);
        }

        try {
            Schema::table('events', function (Blueprint $table) {
                $table->unique('slug');
            });
        } catch (\Throwable) {
            // Index may already exist.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('events') || ! Schema::hasColumn('events', 'slug')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            try {
                $table->dropUnique(['slug']);
            } catch (\Throwable) {
            }
            $table->dropColumn('slug');
        });
    }
};
