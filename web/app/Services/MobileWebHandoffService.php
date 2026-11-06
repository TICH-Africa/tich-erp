<?php

namespace App\Services;

use App\Models\MobileWebHandoff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MobileWebHandoffService
{
    /**
     * @return array{token: string, url: string}
     */
    public function issue(User $user, string $intendedPath): array
    {
        $this->ensureTable();

        if (! $user->is_active) {
            throw new InvalidArgumentException('This account is not active.');
        }

        $path = $this->normalizePath($intendedPath);
        $this->assertPathAllowedForUser($user, $path);

        $plain = Str::random(64);
        $hash = hash('sha256', $plain);

        MobileWebHandoff::query()->create([
            'user_id' => $user->id,
            'token_hash' => $hash,
            'intended_path' => $path,
            'expires_at' => now()->addMinutes(5),
        ]);

        return [
            'token' => $plain,
            'url' => url('/mobile/handoff/'.$plain),
        ];
    }

    public function consume(string $token): ?MobileWebHandoff
    {
        $this->ensureTable();

        $token = trim($token);
        if ($token === '' || strlen($token) > 128) {
            return null;
        }

        $hash = hash('sha256', $token);

        return DB::transaction(function () use ($hash) {
            /** @var MobileWebHandoff|null $record */
            $record = MobileWebHandoff::query()
                ->where('token_hash', $hash)
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $record) {
                return null;
            }

            $record->forceFill(['consumed_at' => now()])->save();

            return $record->load('user');
        });
    }

    public function isSafeRelativePath(string $path): bool
    {
        if ($path === '' || strlen($path) > 500) {
            return false;
        }

        if (! str_starts_with($path, '/')) {
            return false;
        }

        if (str_contains($path, '..') || str_contains($path, '\\')) {
            return false;
        }

        if (preg_match('#^https?://#i', $path)) {
            return false;
        }

        return true;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if (! $this->isSafeRelativePath($path)) {
            throw new InvalidArgumentException('Invalid destination path.');
        }

        return $path;
    }

    private function assertPathAllowedForUser(User $user, string $path): void
    {
        $pathOnly = parse_url($path, PHP_URL_PATH) ?: $path;

        if ($user->user_type === 'student' || $user->student_id) {
            if (! str_starts_with($pathOnly, '/portal')) {
                throw new InvalidArgumentException('Students may only open student portal pages on the web.');
            }

            return;
        }

        $allowedPrefixes = ['/portal', '/procurement', '/employee', '/staff', '/dashboard'];
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($pathOnly, $prefix)) {
                return;
            }
        }

        throw new InvalidArgumentException('This web destination is not allowed from mobile.');
    }

    private function ensureTable(): void
    {
        if (! Schema::hasTable('mobile_web_handoffs')) {
            throw new InvalidArgumentException('Mobile web handoff is not available yet. Contact ICT.');
        }
    }
}
