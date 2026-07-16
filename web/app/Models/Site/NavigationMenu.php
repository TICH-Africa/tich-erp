<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    protected $table = 'navigation_menus';

    public $timestamps = false;

    protected $fillable = [
        'menu_name', 'display_label', 'location', 'is_active', 'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(NavigationMenuItem::class, 'menu_id')
            ->where('is_active', 1)
            ->whereNull('parent_item_id')
            ->orderBy('display_order');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(NavigationMenuItem::class, 'menu_id')
            ->where('is_active', 1)
            ->orderBy('display_order');
    }
}
