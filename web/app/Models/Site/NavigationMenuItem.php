<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenuItem extends Model
{
    protected $table = 'navigation_menu_items';

    public $timestamps = false;

    protected $fillable = [
        'menu_id', 'parent_item_id', 'label', 'label_sw', 'url_or_route',
        'icon_name', 'target', 'requires_auth', 'allowed_user_types',
        'display_order', 'is_active',
    ];

    protected $casts = [
        'requires_auth' => 'boolean',
        'is_active' => 'boolean',
        'allowed_user_types' => 'array',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_item_id')
            ->where('is_active', 1)
            ->orderBy('display_order');
    }
}
