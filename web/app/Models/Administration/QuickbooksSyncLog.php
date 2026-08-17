<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;

class QuickbooksSyncLog extends Model
{
    protected $table = 'admin_quickbooks_sync_logs';

    protected $fillable = [
        'sync_batch', 'source_type', 'source_id', 'external_ref',
        'status', 'payload', 'error_message', 'triggered_by', 'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];
}
