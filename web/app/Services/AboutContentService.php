<?php

namespace App\Services;

use App\Models\Portal\AboutContentBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AboutContentService
{
    /**
     * Canonical About page sections managed by ICT.
     *
     * @return array<string, array{title: string, body: string, display_order: int}>
     */
    public function defaultBlocks(): array
    {
        return [
            'about' => [
                'title' => 'About Us',
                'body' => 'The Tropical Institute of Community Health and Development (TICH) is a premier institution committed to empowering individuals, families, and communities through transformative education, research, and skills development.',
                'display_order' => 1,
            ],
            'vision' => [
                'title' => 'Vision',
                'body' => 'A healthy, just, prosperous and sustainable society in which individuals, families and communities are enabled, empowered and equipped with the necessary practical capacities to enjoy essential elements of dignified livelihoods.',
                'display_order' => 2,
            ],
            'mission' => [
                'title' => 'Mission',
                'body' => 'Building on and strengthening the potentials, actions, strengths and capacities of individuals, families, communities and institutions in order to develop concerned and effective leadership and programs in community health and skills development.',
                'display_order' => 3,
            ],
            'history' => [
                'title' => 'History',
                'body' => 'Established in 1998, TICH has pioneered training in community health and development, fostering leaders equipped to address health and livelihood challenges across Western Kenya and beyond.',
                'display_order' => 4,
            ],
        ];
    }

    public function ensureDefaults(?int $staffId = null): void
    {
        if (! Schema::hasTable('about_content_blocks')) {
            return;
        }

        // Seed once when empty so ICT can fully manage (add / delete / reorder) afterward.
        if (AboutContentBlock::query()->exists()) {
            return;
        }

        foreach ($this->defaultBlocks() as $key => $defaults) {
            AboutContentBlock::query()->create([
                'block_key' => $key,
                'title' => $defaults['title'],
                'body' => $defaults['body'],
                'display_order' => $defaults['display_order'],
                'is_active' => 1,
                'created_by' => $staffId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return Collection<int, AboutContentBlock>
     */
    public function activeBlocks(): Collection
    {
        if (! Schema::hasTable('about_content_blocks')) {
            return collect();
        }

        $this->ensureDefaults();

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
        $this->ensureDefaults();

        return AboutContentBlock::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }
}
