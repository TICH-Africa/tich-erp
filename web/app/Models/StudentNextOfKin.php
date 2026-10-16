<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentNextOfKin extends Model
{
    protected $table = 'student_next_of_kin';

    public $timestamps = false;

    protected $fillable = [
        'student_id',
        'full_name',
        'relationship',
        'phone_number',
        'alt_phone',
        'email',
        'physical_address',
        'occupation',
        'created_at',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
