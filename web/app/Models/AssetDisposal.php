<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    protected $table = 'asset_disposals';

    protected $fillable = [
        'asset_id',
        'disposal_type',
        'disposal_date',
        'disposed_value',
        'reason',
        'disposal_details',
        'requested_by',
        'approved_by',
        'approval_status',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'disposal_date' => 'date',
        'disposed_value' => 'decimal:2',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }
}
