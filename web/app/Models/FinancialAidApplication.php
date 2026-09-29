<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAidApplication extends Model
{
    use SoftDeletes;

    protected $table = 'financial_aid_applications';

    protected $fillable = [
        'financial_aid_opportunity_id',
        'student_id',
        'student_name',
        'student_email',
        'student_phone',
        'student_number',
        'program_applied',
        'personal_statement',
        'financial_need_statement',
        'supporting_documents',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'approved_amount',
        'allocation_status',
        'allocated_by',
        'allocated_at',
    ];

    protected $casts = [
        'supporting_documents' => 'array',
        'approved_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'allocated_at' => 'datetime',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(FinancialAidOpportunity::class, 'financial_aid_opportunity_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function allocator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}