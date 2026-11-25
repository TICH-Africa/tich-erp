<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OffboardingQuestionnaireTemplate extends Model
{
    protected $fillable = [
        'title',
        'description',
        'employment_categories',
        'is_active',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'employment_categories' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(OffboardingQuestionnaireQuestion::class, 'template_id')
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OffboardingQuestionnaireInvitation::class, 'template_id');
    }

    /**
     * @return list<string>
     */
    public static function assignableEmploymentCategories(): array
    {
        return array_keys(config('tich-payroll.employment_categories', []));
    }
}