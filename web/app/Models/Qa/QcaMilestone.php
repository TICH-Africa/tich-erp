<?php

namespace App\Models\Qa;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcaMilestone extends Model
{
    protected $table = 'qca_milestones';

    public $timestamps = true;

    protected $fillable = [
        'qca_flag_id', 'milestone_type', 'description', 'recorded_by', 'evidence',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function flag(): BelongsTo
    {
        return $this->belongsTo(QcaFlag::class, 'qca_flag_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'recorded_by');
    }
}
