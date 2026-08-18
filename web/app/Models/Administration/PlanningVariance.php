<?php

namespace App\Models\Administration;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;

class PlanningVariance extends Model
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
}