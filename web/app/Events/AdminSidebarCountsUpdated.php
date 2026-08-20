<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminSidebarCountsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, int>  $counts
     * @param  array<string, string|null>  $labels
     */
    public function __construct(
        public array $counts,
        public array $labels,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.sidebar')];
    }

    public function broadcastAs(): string
    {
        return 'sidebar.counts.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'counts' => $this->counts,
            'labels' => $this->labels,
        ];
    }
}
