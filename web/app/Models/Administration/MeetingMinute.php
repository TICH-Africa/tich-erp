<?php

namespace App\Models\Administration;

use App\Models\Concerns\PrunesStoredFiles;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinute extends Model
{
    use PrunesStoredFiles;

    protected $table = 'admin_meeting_minutes';

    protected $fillable = [
        'minute_code',
        'title',
        'meeting_date',
        'meeting_time',
        'venue',
        'document_path',
        'original_filename',
        'mime_type',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'file_size' => 'integer',
    ];

    /** @var array<string, string> */
    protected array $storedFiles = [
        'document_path' => 'public',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function meetingTimeLabel(): ?string
    {
        if (! filled($this->meeting_time)) {
            return null;
        }

        $raw = (string) $this->meeting_time;
        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', $raw)->format('g:i A');
        } catch (\Throwable) {
            try {
                return \Carbon\Carbon::createFromFormat('H:i', $raw)->format('g:i A');
            } catch (\Throwable) {
                return $raw;
            }
        }
    }
}
