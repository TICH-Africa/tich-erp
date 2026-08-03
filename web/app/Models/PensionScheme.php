<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PensionScheme extends Model
{
    protected $table = 'pension_schemes';

    protected $fillable = [
        'scheme_name',
        'scheme_number',
        'provider_name',
        'contribution_rate_employee',
        'contribution_rate_employer',
        'is_active',
    ];

    protected $casts = [
        'contribution_rate_employee' => 'decimal:2',
        'contribution_rate_employer' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
