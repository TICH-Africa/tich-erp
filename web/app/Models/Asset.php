<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $table = 'assets';

    protected $fillable = [
        'asset_number',
        'asset_name',
        'asset_category',
        'serial_number',
        'description',
        'acquisition_date',
        'acquisition_cost',
        'supplier_id',
        'purchase_order_id',
        'useful_life_years',
        'depreciation_method',
        'salvage_value',
        'current_value',
        'depreciation_per_year',
        'accumulated_depreciation',
        'condition',
        'disposed_date',
        'disposed_value',
        'disposed_reason',
        'warranty_expiry_date',
        'tag_number',
        'qr_code_path',
        'custodian_id',
        'location_name',
        'building',
        'room',
        'asset_status',
        'handover_date',
        'grn_id',
        'procurement_requisition_id',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'depreciation_per_year' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'disposed_date' => 'date',
        'disposed_value' => 'decimal:2',
        'warranty_expiry_date' => 'date',
        'handover_date' => 'date',
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

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'custodian_id');
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function procurementRequisition(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequisition::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    public function disposals(): HasMany
    {
        return $this->hasMany(AssetDisposal::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AssetAudit::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('asset_status', ['active', 'new']);
    }

    public function scopeAuditable($query)
    {
        return $query->where('asset_status', '!=', 'disposed');
    }

    public function scopeUnderMaintenance($query)
    {
        return $query->where('asset_status', 'maintenance');
    }

    public function isFixedAsset(): bool
    {
        return ($this->acquisition_cost >= 50000) && ($this->useful_life_years >= 1);
    }

    public function calculateDepreciation(): float
    {
        if ($this->depreciation_method === 'straight_line' && $this->useful_life_years > 0) {
            return round(($this->acquisition_cost - $this->salvage_value) / $this->useful_life_years, 2);
        }

        return 0.0;
    }

    public function applyAnnualDepreciation(): self
    {
        $depreciation = $this->calculateDepreciation();
        $this->accumulated_depreciation = round($this->accumulated_depreciation + $depreciation, 2);
        $this->current_value = round(max($this->acquisition_cost - $this->accumulated_depreciation, $this->salvage_value), 2);
        $this->depreciation_per_year = $depreciation;
        $this->save();

        return $this;
    }

    public function generateAssetNumber(): string
    {
        $year = now()->year;
        $prefix = "AST-{$year}-";
        $last = self::query()->where('asset_number', 'like', $prefix . '%')->orderByDesc('asset_number')->value('asset_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) : 0;

        return $prefix . str_pad((string) ($seq + 1), 4, '0', STR_PAD_LEFT);
    }

    public function generateTag(): string
    {
        return 'QR-' . $this->asset_number;
    }
}
