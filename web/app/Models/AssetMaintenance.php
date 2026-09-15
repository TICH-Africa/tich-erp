<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    protected $table = 'asset_maintenance';

    protected $fillable = [
        'asset_id',
        'maintenance_type',
        'fault_description',
        'priority',
        'scheduled_date',
        'completed_date',
        'work_done',
        'parts_used',
        'parts_cost',
        'labour_cost',
        'technician_name',
        'technician_phone',
        'attachment_paths',
        'completed_by',
        'status',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'parts_used' => 'array',
        'parts_cost' => 'decimal:2',
        'labour_cost' => 'decimal:2',
        'attachment_paths' => 'array',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'completed_by');
    }
}
