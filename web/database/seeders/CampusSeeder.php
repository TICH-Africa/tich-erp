<?php

namespace Database\Seeders;

use App\Models\Campus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampusSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::table('campuses')->exists()) {
            return;
        }

        $this->ensureCampus('MAIN', 'Main Campus', 'main', null);
        $this->ensureCampus('MLIMANI', 'Mlimani Campus', 'campus', null);
    }

    private function ensureCampus(string $code, string $name, string $type, ?int $parentId): int
    {
        $id = DB::table('campuses')->where('campus_code', $code)->value('id');

        if (! $id) {
            $id = DB::table('campuses')->insertGetId([
                'campus_code' => $code,
                'campus_name' => $name,
                'campus_type' => $type,
                'parent_campus_id' => $parentId,
                'county' => 'Kisumu',
                'physical_address' => $name . ', Kisumu, Kenya',
                'is_active' => 1,
                'created_at' => now(),
            ]);
        }

        return (int) $id;
    }
}