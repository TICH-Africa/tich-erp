<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;

class StatutoryCertification extends Model
{
    protected $table = 'admin_statutory_certifications';

    protected $fillable = [
        'certificate_code', 'title', 'authority', 'certificate_number',
        'issued_on', 'expires_on', 'status', 'document_path',
        'alignment_notes', 'updated_by',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'expires_on' => 'date',
    ];

    public function refreshStatus(): void
    {
        if (! $this->expires_on) {
            return;
        }

        if ($this->expires_on->isPast()) {
            $this->status = 'expired';
        } elseif ($this->expires_on->lte(now()->addDays(60))) {
            $this->status = 'expiring';
        } elseif ($this->status === 'expired' || $this->status === 'expiring') {
            $this->status = 'active';
        }
    }
}
