<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyAcknowledgement extends Model
{
    protected $table = 'policy_acknowledgements';

    public $timestamps = false;

    protected $fillable = [
        'policy_id',
        'policy_name',
        'policy_version',
        'policy_file_path',
        'effective_date',
        'staff_id',
        'is_acknowledged',
        'acknowledged_at',
        'acknowledgement_method',
        'acknowledged_by',
        'employee_number',
        'signature',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_acknowledged' => 'boolean',
        'acknowledged_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(HrPolicy::class, 'policy_id');
    }
}
