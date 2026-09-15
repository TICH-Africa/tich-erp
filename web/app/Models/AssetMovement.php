<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMovement extends Model
{
    protected $table = 'asset_movements';

    protected $fillable = [
        'asset_id',
        'from_location',
        'to_location',
        'reason',
        'movement_type',
        'requested_by',
        'approved_by',
        'approval_status',
        'status',
        'movement_date',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'movement_date' => 'date',
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
