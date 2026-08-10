<?php

namespace App\Models;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryDocument extends Model
{
    use PrunesStoredFiles;

    /** @var array<string, string> */
    protected array $storedFiles = [
        'document_path' => 'public',
    ];
    protected $table = 'disciplinary_documents';

    public $timestamps = true;

    protected $fillable = [
        'disciplinary_case_id',
        'document_name',
        'document_path',
        'mime_type',
        'description',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }
}
