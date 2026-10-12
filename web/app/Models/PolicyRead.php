<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyRead extends Model
{
    protected $table = 'policy_reads';

    public $timestamps = false;

    protected $fillable = [
        'policy_id',
        'staff_id',
        'read_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(HrPolicy::class, 'policy_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}