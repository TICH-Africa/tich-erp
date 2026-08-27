<?php

namespace App\Console\Commands;

use App\Services\RbacCatalogService;
use Illuminate\Console\Command;

class EnsureRbacRolesCommand extends Command
{
    protected $signature = 'tich:ensure-rbac-roles';

    protected $description = 'Upsert thin role rows from code catalog so user_roles FKs work (no permission/nav seeding)';

    public function handle(RbacCatalogService $catalog): int
    {
        $catalog->ensureRolesExist();
        $this->info('RBAC roles ensured from config/tich-module-roles.php');

        return self::SUCCESS;
    }
}
