<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;

class InspectionCheck extends Model
{
    protected $table = 'admin_inspection_checks';

    protected $fillable = [
        'check_code', 'area', 'requirement', 'regulator',
        'status', 'evidence_path', 'notes', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];
}
