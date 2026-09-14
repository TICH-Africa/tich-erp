<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rfq extends Model
{
    protected $table = 'rfqs';

    protected $fillable = [
        'rfq_number',
        'requisition_id',
        'created_by',
        'item_description',
        'quantity',
        'specifications',
        'delivery_timeline',
        'delivery_location',
        'submission_deadline',
        'minimum_categories',
        'preferred_categories',
        'minimum_suppliers',
        'status',
        'award_decision',
        'awarded_supplier_id',
        'awarded_amount',
        'approval_level',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'award_letter_path',
        'published_at',
        'closed_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'awarded_amount' => 'decimal:2',
        'minimum_categories' => 'array',
        'preferred_categories' => 'array',
        'submission_deadline' => 'date',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequisition::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function awardedSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'awarded_supplier_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(RfqSupplier::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(RfqQuotation::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RfqEvaluation::class);
    }

    public function clarifications(): HasMany
    {
        return $this->hasMany(RfqClarification::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeAwarded($query)
    {
        return $query->where('status', 'awarded');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isAwarded(): bool
    {
        return $this->status === 'awarded';
    }
}
