<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfqSupplier extends Model
{
    protected $table = 'rfq_suppliers';

    public $timestamps = false;

    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'invitation_status',
        'decline_reason',
        'invited_at',
        'responded_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function scopeInvited($query)
    {
        return $query->where('invitation_status', 'invited');
    }

    public function scopeAccepted($query)
    {
        return $query->where('invitation_status', 'accepted');
    }

    public function scopeNoResponse($query)
    {
        return $query->where('invitation_status', 'no_response');
    }

    public function hasResponded(): bool
    {
        return in_array($this->invitation_status, ['accepted', 'declined'], true);
    }
}
