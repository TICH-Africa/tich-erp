<?php

namespace App\Models;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrPolicy extends Model
{
    use PrunesStoredFiles;

    protected $table = 'hr_policies';

    /** @var array<string, string> */
    protected array $storedFiles = [
        'file_path' => 'public',
    ];

    protected $fillable = [
        'title',
        'slug',
        'version',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'category',
        'effective_date',
        'expiry_date',
        'is_active',
        'tags',
        'uploaded_by',
        'target_type',
        'target_departments',
        'target_staff_ids',
        'target_roles',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'is_active' => 'boolean',
        'target_departments' => 'array',
        'target_staff_ids' => 'array',
        'target_roles' => 'array',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(PolicyRead::class, 'policy_id');
    }
}
