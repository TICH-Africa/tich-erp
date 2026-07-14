<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'mfa_secret',
        'mfa_backup_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mfa_backup_codes' => 'array',
            'mfa_enabled_at' => 'datetime',
            'mfa_last_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    /**
     * Get the staff associated with the user.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the student associated with the user.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the roles for the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('campus_id', 'department_id', 'assigned_at', 'assigned_by', 'expires_at')
            ->withTimestamps();
    }

    /**
     * Get the permissions for the user.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('campus_id', 'department_id', 'granted_at', 'granted_by', 'expires_at')
            ->withTimestamps();
    }

    /**
     * Get the session tokens for the user.
     */
    public function sessionTokens()
    {
        return $this->hasMany(SessionToken::class);
    }

    /**
     * Check if user has specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return app(\App\Services\RBACService::class)->hasPermission($this, $permission);
    }

    /**
     * Check if user has specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return app(\App\Services\RBACService::class)->hasRole($this, $roleName);
    }
}
