<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['role_name', 'display_name', 'role_category', 'module_key', 'description', 'is_system_role'];

    protected $casts = [
        'is_system_role' => 'boolean',
    ];

    public function roleCategory()
    {
        return $this->belongsTo(RoleCategory::class, 'role_category', 'category_code');
    }

    /**
     * Get the users for the role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('campus_id', 'department_id', 'assigned_at', 'assigned_by', 'expires_at')
            ->withTimestamps();
    }

    /**
     * Get the permissions for the role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withPivot('granted_at', 'granted_by')
            ->withTimestamps();
    }
}
