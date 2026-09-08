<?php

namespace App\Models\Me;

use App\Models\Department;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MePolicySignoff extends Model
{
    protected $table = 'me_policy_signoffs';

    public $timestamps = false;

    protected $fillable = [
        'policy_id',
        'department_id',
        'staff_id',
        'user_id',
        'signed_name',
        'employee_number',
        'signature',
        'ip_address',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(MePolicy::class, 'policy_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
