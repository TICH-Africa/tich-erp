<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PensionScheme extends Model
{
    protected $table = 'pension_schemes';

    public $timestamps = false;

    protected $fillable = [
        'scheme_code',
        'scheme_name',
        'scheme_type',
        'employer_contribution_pct',
        'employee_contribution_pct',
        'is_active',
        'created_at',
    ];

    protected $casts = [
        'employer_contribution_pct' => 'decimal:2',
        'employee_contribution_pct' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
