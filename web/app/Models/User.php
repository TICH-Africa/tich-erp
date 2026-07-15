<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username', 'email', 'password_hash', 'user_type',
        'mfa_enabled', 'mfa_method', 'mfa_secret', 'mfa_secret_temp', 'mfa_verified',
        'login_attempts', 'locked_until', 'last_login_at', 'staff_id', 'student_id',
        'is_active', 'failed_login_attempts', 'created_by',
    ];

    protected $hidden = [
        'password_hash', 'remember_token', 'mfa_secret', 'mfa_secret_temp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mfa_enabled' => 'boolean',
        'mfa_verified' => 'boolean',
    ];

    // ─── RBAC Relationships ─────────────────────────────────────────────────

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot(['department_id', 'campus_id', 'granted_at', 'granted_by', 'expires_at']);
    }

    public function sessionTokens(): HasMany
    {
        return $this->hasMany(SessionToken::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['department_id', 'campus_id', 'assigned_at', 'assigned_by', 'expires_at']);
    }

    // ─── Staff relationship (optional) ──────────────────────────────────────

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function hasPermission(string $permission): bool
    {
        return app(\App\Services\RBACService::class)->hasPermission($this, $permission);
    }

    public function hasRole(string $roleName): bool
    {
        return app(\App\Services\RBACService::class)->hasRole($this, $roleName);
    }
}