<?php

namespace App\Models\Portal;

use App\Models\Concerns\PrunesStoredFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnershipRequestDocument extends Model
{
    use PrunesStoredFiles;

    protected $table = 'partnership_request_documents';

    public $timestamps = false;

    /** @var array<string, string> */
    protected array $storedFiles = [
        'file_path' => 'local',
    ];

    protected $fillable = [
        'partnership_request_id', 'title', 'file_path', 'original_filename',
        'mime_type', 'file_size', 'created_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PartnershipRequest::class, 'partnership_request_id');
    }
}
