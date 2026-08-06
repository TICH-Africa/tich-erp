<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'setting_key',
        'setting_value',
        'value_type',
        'group_name',
        'label',
        'description',
        'is_public',
        'is_active',
        'updated_by',
        'updated_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'updated_at' => 'datetime',
    ];
}
