<?php

namespace App\Services;

use App\Models\Portal\AboutContentBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AboutContentService
{
    /**
     * Suggested section keys for ICT when creating About content manually.
     * Not auto-inserted — the public About page stays empty until published.
     *
     * @return array<string, array{title: string, body: string, display_order: int}>
     */
    public function defaultBlocks(): array
    {
        return [
            'about' => [
                'title' => 'About Us',
                'body' => '',
                'display_order' => 1,
            ],
            'vision' => [
                'title' => 'Vision',
                'body' => '',
                'display_order' => 2,
            ],
            'mission' => [
                'title' => 'Mission',
                'body' => '',
                'display_order' => 3,
            ],
            'history' => [
                'title' => 'History',
                'body' => '',
                'display_order' => 4,
            ],
        ];
    }

    /**
     * @return Collection<int, AboutContentBlock>
     */
    public function activeBlocks(): Collection
    {
        if (! Schema::hasTable('about_content_blocks')) {
            return collect();
        }

        return AboutContentBlock::query()
            ->where('is_active', 1)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, AboutContentBlock>
     */
    public function allBlocksForAdmin(): Collection
    {
        if (! Schema::hasTable('about_content_blocks')) {
            return collect();
        }

        return AboutContentBlock::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }
}
