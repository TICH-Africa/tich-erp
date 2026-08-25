<?php

namespace App\Models\Administration;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Variance extends Model
{
    protected $table = 'admin_variances';

    protected $guarded = ['id'];

    protected $casts = [
        'planned_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
