<?php

namespace App\Services\Procurement;

use App\Models\Asset;
use Illuminate\Support\Facades\Auth;

class AssetService
{
    public function registerAsset(array $data): Asset
    {
        $data['asset_number'] = (new Asset)->generateAssetNumber();
        $data['asset_status'] = $data['asset_status'] ?? 'new';
        $data['condition'] = $data['condition'] ?? 'new';
        $data['depreciation_method'] = $data['depreciation_method'] ?? 'straight_line';

        if (isset($data['acquisition_cost']) && isset($data['useful_life_years']) && $data['useful_life_years'] > 0) {
            $data['depreciation_per_year'] = round(($data['acquisition_cost'] - ($data['salvage_value'] ?? 0)) / $data['useful_life_years'], 2);
        } else {
            $data['depreciation_per_year'] = 0.0;
        }

        $data['current_value'] = $data['acquisition_cost'] ?? 0.0;

        $asset = Asset::query()->create($data);

        if (! empty($data['tag_number'])) {
            $asset->qr_code_path = 'tags/' . $asset->asset_number . '.png';
            $asset->save();
        }

        return $asset->fresh();
    }

    public function applyAnnualDepreciation(Asset $asset): Asset
    {
        return $asset->applyAnnualDepreciation();
    }
}
