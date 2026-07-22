<?php

namespace App\Models\Site;

use Illuminate\Database\Eloquent\Model;

class ContactChannel extends Model
{
    protected $table = 'contact_channels';

    public $timestamps = false;

    protected $fillable = [
        'channel_type', 'label', 'label_sw', 'value', 'display_value',
        'department_scope', 'is_primary', 'display_order', 'is_active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];
}
