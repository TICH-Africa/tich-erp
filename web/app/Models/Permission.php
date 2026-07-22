<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['permission_name', 'slug', 'module', 'category', 'description', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Get the roles for the permission.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withPivot('granted_at', 'granted_by')
            ->withTimestamps();
    }

    /**
     * Get the users for the permission.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot('campus_id', 'department_id', 'granted_at', 'granted_by', 'expires_at')
            ->withTimestamps();
    }
}
