<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ref = fn ($rel, array $fields) => $this->whenLoaded($rel, fn () => $this->$rel ? $this->$rel->only($fields) : null);

        return [
            'id' => $this->id,
            'asset_number' => $this->asset_number,
            'old_asset_number' => $this->old_asset_number,
            'serial_number' => $this->serial_number,
            'status' => $this->status,
            'current_location_detail' => $this->current_location_detail,
            'type' => $ref('assetType', ['id', 'name']),
            'brand' => $ref('assetBrand', ['id', 'name']),
            'series' => $ref('assetSeries', ['id', 'name']),
            'jobsite' => $ref('jobsite', ['id', 'code', 'name']),
            'entity' => $ref('entity', ['id', 'code', 'name']),
            'department' => $ref('departement', ['id', 'code', 'name']),
            'holder' => $this->whenLoaded('holder', fn () => $this->holder ? ['id' => $this->holder->id, 'name' => $this->holder->name, 'nik' => $this->holder->nik] : null),
            'label_url' => route('assets.show', ['asset' => $this->asset_number]),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
