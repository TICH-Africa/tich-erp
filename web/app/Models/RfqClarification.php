<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqClarification extends Model
{
    protected $table = 'rfq_clarifications';

    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'asked_by',
        'question',
        'answer',
        'is_public',
        'asked_at',
        'answered_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'asked_at' => 'datetime',
        'answered_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function asker(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'asked_by');
    }
}
