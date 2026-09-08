<?php

namespace App\Models\Me;

use App\Models\Department;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MePolicy extends Model
{
    protected $table = 'me_policies';

    protected $fillable = [
        'fiscal_year',
        'title',
        'version',
        'file_path',
        'description',
        'effective_date',
        'status',
        'uploaded_by',
        'uploaded_at',
        'published_at',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'uploaded_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by');
    }

    public function signoffs(): HasMany
    {
        return $this->hasMany(MePolicySignoff::class, 'policy_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
