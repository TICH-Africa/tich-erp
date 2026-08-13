<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'supplier_code',
        'supplier_name',
        'contact_person',
        'email',
        'phone',
        'postal_address',
        'physical_address',
        'kra_pin',
        'tax_compliance_status',
        'compliance_doc_path',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_branch',
        'bank_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function payables(): HasMany
    {
        return $this->hasMany(AccountsPayable::class);
    }
}
