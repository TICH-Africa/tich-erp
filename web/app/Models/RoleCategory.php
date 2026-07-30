<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RoleCategory extends Model
{
    protected $fillable = [
        'category_code',
        'category_name',
        'description',
        'display_order',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function rolesCount(): int
    {
        return Role::query()->where('role_category', $this->category_code)->count();
    }

    public static function activeOptions(): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('category_name')
            ->pluck('category_name', 'category_code');
    }

    public static function labelMap(): Collection
    {
        return static::query()
            ->orderBy('display_order')
            ->orderBy('category_name')
            ->pluck('category_name', 'category_code');
    }
}
