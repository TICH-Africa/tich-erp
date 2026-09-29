<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $table = 'donations';

    protected $fillable = [
        'donation_number',
        'donation_type',
        'amount',
        'designation',
        'donor_name',
        'donor_email',
        'donor_phone',
        'message',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($donation) {
            if (empty($donation->donation_number)) {
                $prefix = 'DON';
                $year = now()->format('Y');
                $lastDonation = static::whereYear('created_at', now()->year)
                    ->where('donation_number', 'like', "{$prefix}-{$year}-%")
                    ->latest('donation_number')
                    ->first();

                if ($lastDonation) {
                    $lastNumber = (int) substr($lastDonation->donation_number, -6);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }

                $donation->donation_number = sprintf('%s-%s-%06d', $prefix, $year, $newNumber);
            }
        });
    }
}