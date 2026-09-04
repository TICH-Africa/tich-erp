<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExaminationPaper extends Model
{
    public const STATUSES = [
        'draft' => 'Draft',
        'tabled' => 'Tabled',
        'moderated' => 'Moderated',
        'approved' => 'Approved',
        'printed' => 'Printed',
    ];

    public const EXAM_TYPES = [
        'main' => 'Main',
        'supplementary' => 'Supplementary',
        'special' => 'Special',
    ];

    public const VERSIONS = ['A', 'B'];

    protected $table = 'examination_papers';

    public $timestamps = false;

    protected $fillable = [
        'unit_id',
        'semester_id',
        'exam_type',
        'version',
        'draft_file_path',
        'moderated_file_path',
        'approved_file_path',
        'is_encrypted',
        'encryption_key_hash',
        'status',
        'prepared_by',
        'tabled_at',
        'moderated_at',
        'approved_by',
        'approved_at',
        'created_at',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'tabled_at' => 'datetime',
        'moderated_at' => 'datetime',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function filePathFor(string $kind): ?string
    {
        return match ($kind) {
            'draft' => $this->draft_file_path,
            'moderated' => $this->moderated_file_path,
            'approved' => $this->approved_file_path,
            default => null,
        };
    }
}
