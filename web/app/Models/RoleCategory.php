<?php

namespace App\Models;

use App\Services\RbacCatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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
        $fromCode = collect(app(RbacCatalogService::class)->categoryOptions());

        if (! Schema::hasTable((new static)->getTable())) {
            return $fromCode;
        }

        $fromDb = static::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('category_name')
            ->pluck('category_name', 'category_code');

        // Code-owned system categories win; DB may add custom codes.
        return $fromCode->union($fromDb);
    }

    public static function labelMap(): Collection
    {
        return static::activeOptions();
    }

    /**
     * @return list<string>
     */
    public static function systemCodes(): array
    {
        return array_keys(app(RbacCatalogService::class)->categoryOptions());
    }
}
