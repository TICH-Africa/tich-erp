<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    /** Charges billed on their own, which are not fee structure columns. */
    private const EXTRA_LABELS = [
        'application_fee' => 'Application fee',
        'qa_annual_fee' => 'Quality assurance (annual)',
        'indexing_nck_fee' => 'Indexing (NCK)',
        'graduation_fee' => 'Graduation fees',
        'supplementary_fee' => 'Supplementary examination',
        'other_fee' => 'Other fees',
    ];

    protected $table = 'invoice_items';
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'fee_item',
        'description',
        'amount',
        'scholarship_adjustment',
        'bursary_adjustment',
        'waiver_adjustment',
        'net_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'scholarship_adjustment' => 'decimal:2',
        'bursary_adjustment' => 'decimal:2',
        'waiver_adjustment' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** Plain-language name of the charge, e.g. "Indexing (NCK)" rather than "indexing_nck_fee". */
    public function label(): string
    {
        $key = (string) $this->fee_item;

        return self::EXTRA_LABELS[$key]
            ?? \App\Models\FeeStructure::SEMESTER_CHARGES[$key]
            ?? \App\Models\FeeStructure::OPTIONAL_SEMESTER_CHARGES[$key]
            ?? ucfirst(str_replace('_', ' ', $key));
    }
}
