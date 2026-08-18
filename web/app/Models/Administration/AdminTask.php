<?php

namespace App\Models\Administration;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;

class AdminTask extends Model
{
    protected $table = 'admin_tasks';

    protected $guarded = ['id'];

    protected $casts = ['due_on' => 'date', 'budget_implication' => 'decimal:2'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}