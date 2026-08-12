<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountsPayable extends Model
{
    protected $table = 'accounts_payable';

    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'requisition_id',
        'purchase_order_id',
        'invoice_date',
        'due_date',
        'invoice_amount',
        'tax_amount',
        'total_amount',
        'amount_paid',
        'balance',
        'invoice_file_path',
        'three_way_match_status',
        'three_way_match_by',
        'three_way_match_at',
        'finance_approval_status',
        'finance_approved_by',
        'finance_approved_at',
        'payment_status',
        'payment_date',
        'payment_reference',
        'payment_method',
        'is_quickbooks_synced',
        'quickbooks_sync_ref',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'invoice_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'three_way_match_at' => 'datetime',
        'finance_approved_at' => 'datetime',
        'payment_date' => 'date',
        'is_quickbooks_synced' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
