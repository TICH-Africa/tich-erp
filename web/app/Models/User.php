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
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_type',
        'username',
        'email',
        'password_hash',
        'staff_id',
        'student_id',
        'mfa_enabled',
        'mfa_method',
        'mfa_secret',
        'mfa_backup_codes',
        'mfa_enabled_at',
        'mfa_last_verified_at',
        'is_active',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
        'created_by',
    ];

    protected $hidden = [
        'password_hash',
        'mfa_secret',
        'mfa_backup_codes',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mfa_enabled' => 'boolean',
        'mfa_backup_codes' => 'array',
        'mfa_enabled_at' => 'datetime',
        'mfa_last_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
    ];

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('campus_id', 'department_id', 'assigned_at', 'assigned_by', 'expires_at')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('campus_id', 'department_id', 'granted_at', 'granted_by', 'expires_at')
            ->withTimestamps();
    }

    public function sessionTokens(): HasMany
    {
        return $this->hasMany(SessionToken::class);
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