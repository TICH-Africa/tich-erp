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
        return route('register.invite', ['token' => $this->token]);
    }
}
