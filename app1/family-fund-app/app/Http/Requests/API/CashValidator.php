<?php

namespace App\Http\Requests\API;

use App\Models\AssetExt;

trait CashValidator
{
    public function validateCash($validator, $asset_id) {
        $asset = AssetExt::find($asset_id)->first();
        if ($asset) {
            $validator->errors()->add('asset_id', 'Asset not found: ' . $asset_id);
        } else if ($asset->isCash()) {
            $validator->errors()->add('asset_id', 'Cannot modify price of Cash');
        }
    }

    public function withValidator($validator)
    {
        $request = $this;
        $validator->after(function ($validator) use ($request) {
            $request->validateCash($validator, $request->asset_id);
        });
    }
}
