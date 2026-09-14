<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqQuotation extends Model
{
    protected $table = 'rfq_quotations';

    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'submitted_by',
        'unit_price',
        'total_price',
        'delivery_period',
        'payment_terms',
        'warranty_terms',
        'validity_period',
        'technical_notes',
        'attachments',
        'status',
        'submitted_at',
        'revised_at',
        'locked_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'attachments' => 'array',
        'submitted_at' => 'datetime',
        'revised_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'submitted_by');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeLocked($query)
    {
        return $query->where('status', 'locked');
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }
}
