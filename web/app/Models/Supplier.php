<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'suppliers';

    protected $fillable = [
        'supplier_code',
        'supplier_name',
        'registration_number',
        'contact_person',
        'email',
        'phone',
        'postal_address',
        'physical_address',
        'kra_pin',
        'tax_compliance_status',
        'compliance_doc_path',
        'pin_certificate_path',
        'cr12_path',
        'audited_financial_statements_path',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_branch',
        'bank_code',
        'is_active',
        'supplier_category',
        'sub_category',
        'risk_rating',
        'compliance_status',
        'performance_score',
        'past_contracts',
        'client_references',
        'staff_count',
        'certifications',
        'blacklist_status',
        'blacklist_reason',
        'blacklisted_at',
        'blacklisted_by',
        'performance_last_updated_at',
        'performance_updated_by',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'performance_score' => 'decimal:2',
        'past_contracts' => 'array',
        'client_references' => 'array',
        'certifications' => 'array',
        'blacklisted_at' => 'datetime',
        'performance_last_updated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function payables(): HasMany
    {
        return $this->hasMany(AccountsPayable::class);
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class, 'awarded_supplier_id');
    }

    public function rfqInvitations(): HasMany
    {
        return $this->hasMany(RfqSupplier::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(RfqQuotation::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(RfqEvaluation::class);
    }

    public function blacklister(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'blacklisted_by');
    }

    public function performanceUpdater(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'performance_updated_by');
    }

    public function isBlacklisted(): bool
    {
        return $this->blacklist_status === 'blacklisted';
    }

    public function isActive(): bool
    {
        return $this->blacklist_status === 'active' && $this->is_active;
    }

    public function isCompliant(): bool
    {
        return $this->compliance_status === 'approved';
    }

    public function scopeActive($query)
    {
        return $query->where('blacklist_status', 'active')->where('is_active', 1);
    }

    public function scopeBlacklisted($query)
    {
        return $query->where('blacklist_status', 'blacklisted');
    }

    public function scopeCompliant($query)
    {
        return $query->where('compliance_status', 'approved');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('supplier_category', $category);
    }
}
