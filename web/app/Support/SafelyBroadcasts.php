<?php

namespace App\Support;

trait SafelyBroadcasts
{
    protected function broadcastingEnabled(): bool
    {
        $driver = (string) config('broadcasting.default', 'null');

        return $driver !== '' && $driver !== 'null' && $driver !== 'log';
    }

    protected function safelyBroadcast(callable $broadcast): void
    {
        if (! $this->broadcastingEnabled()) {
            return;
        }

        try {
            $broadcast();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
