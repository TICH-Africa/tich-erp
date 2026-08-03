<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffNextOfKin extends Model
{
    protected $table = 'staff_next_of_kin';

    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'full_name',
        'relationship',
        'phone_number',
        'alt_phone',
        'email',
        'physical_address',
        'occupation',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
