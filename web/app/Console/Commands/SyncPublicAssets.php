<?php

namespace App\Console\Commands;

use App\Services\PublicAssetSyncService;
use Illuminate\Console\Command;

class SyncPublicAssets extends Command
{
    protected $signature = 'tich:sync-public-assets';

    protected $description = 'Link or copy css/js from web/public into the cPanel domain public_html folder';

    public function handle(PublicAssetSyncService $sync): int
    {
        $result = $sync->sync();

        foreach ($result['messages'] as $line) {
            $this->line($line);
        }

        $this->line('Log written to: '.$sync->logPath());

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
