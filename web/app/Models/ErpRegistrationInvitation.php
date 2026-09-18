<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpRegistrationInvitation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'email',
        'token',
        'sent_via_module',
        'invited_by',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isActive(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    public function registerUrl(): string
    {
        $base = rtrim((string) (config('app.invite_base_url') ?: config('app.url')), '/');

        return $base.'/register/invite/'.$this->token;
    }

    public static function appUrlLooksPrivate(): bool
    {
        $host = strtolower((string) parse_url((string) (config('app.invite_base_url') ?: config('app.url')), PHP_URL_HOST));

        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        if (str_starts_with($host, '192.168.') || str_starts_with($host, '10.')) {
            return true;
        }

        if (preg_match('/^172\.(1[6-9]|2\d|3[0-1])\./', $host) === 1) {
            return true;
        }

        return false;
    }
}
