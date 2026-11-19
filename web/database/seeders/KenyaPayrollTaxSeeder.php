<?php

namespace Database\Seeders;

use App\Services\KenyaPayrollTaxProvisioner;
use Illuminate\Database\Seeder;

class KenyaPayrollTaxSeeder extends Seeder
{
    public function run(): void
    {
        app(KenyaPayrollTaxProvisioner::class)->ensureConfigured();
    }
}