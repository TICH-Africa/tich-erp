<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username', 'email', 'password_hash', 'user_type',
        'mfa_enabled', 'mfa_method', 'mfa_secret', 'mfa_secret_temp', 'mfa_verified',
        'mfa_backup_codes', 'mfa_enabled_at', 'mfa_last_verified_at',
        'login_attempts', 'locked_until', 'last_login_at', 'staff_id', 'student_id',
        'is_active', 'failed_login_attempts', 'created_by',
    ];

    protected $hidden = [
        'password_hash', 'remember_token', 'mfa_secret', 'mfa_secret_temp',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'mfa_enabled_at' => 'datetime',
        'mfa_last_verified_at' => 'datetime',
        'mfa_backup_codes' => 'array',
        'mfa_enabled' => 'boolean',
        'mfa_verified' => 'boolean',
        'is_active' => 'boolean',
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

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isEnrolledStudent(): bool
    {
        if ($this->student_id) {
            return true;
        }

        return Student::query()->where('user_id', $this->id)->exists();
    }

    public function isTeachingStaff(): bool
    {
        return app(\App\Services\StaffPortalService::class)->isTeachingStaff($this);
    }

    public function hasEmployeeProfile(): bool
    {
        return app(\App\Services\EmployeePortalService::class)->hasEmployeeProfile($this);
    }

    public function hasPermission(string $permission): bool
    {
        return app(\App\Services\RBACService::class)->hasPermission($this, $permission);
    }

    public function hasRole(string $roleName): bool
    {
        return app(\App\Services\RBACService::class)->hasRole($this, $roleName);
    }

    public function hasAnyRole(array $roleNames): bool
    {
        return app(\App\Services\RBACService::class)->hasAnyRole($this, $roleNames);
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}