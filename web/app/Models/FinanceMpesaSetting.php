<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceMpesaSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'environment',
        'shortcode',
        'passkey',
        'consumer_key',
        'consumer_secret',
        'transaction_type',
        'account_reference_prefix',
        'callback_url_override',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'passkey' => 'encrypted',
        'consumer_secret' => 'encrypted',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'updated_by');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled' => false,
            'environment' => 'sandbox',
            'transaction_type' => 'CustomerPayBillOnline',
            'account_reference_prefix' => 'TICH',
        ]);
    }
}
